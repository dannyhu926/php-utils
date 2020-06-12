<?php

namespace Utils\Search\ELK;

/**
 * ElasticClient.class.php ElasticSearch的全文搜索引擎，基于RESTful web接口
 *
 * @author             hyperblue <i@kushu.net>
 * @version            0.1
 * @copyright      (C) 2015- *
 * @license            http://www.kushu.net
 * @lastmodify         17/9/6 15:22
 */
class ElasticClient
{
    protected $client;
    protected $is_open;

    public function __construct()
    {
        $this->is_open = C('ELK.is_open');
        if (empty($this->client)) {
            $this->init();
        }
    }

    /**
     * 连接elasticsearch
     *
     * @return \Elasticsearch\Client
     */
    public function init()
    {
        if (!$this->is_open) {
            return false;
        }
        $hosts = C('ELK.hosts');
        $this->client = \Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();

        return $this->client;
    }

    /**
     * 强制刷新单个索引
     *
     * @param string $index
     */
    public function refresh($index)
    {
        $index = $this->getIndex($index);
        try {
            $param = ['index' => $index];
            $this->client->indices()->refresh($param);
        } catch (\Exception $e) {
            $this->logger->error("强制refresh失败");
        }
    }

    /**
     * add a mapping to an index
     * @param string $index
     * @param string $type
     * @param array $mappings
     *
     *
     * @return array
     */
    public function putMappings(string $index, string $type, array $mappings)
    {
        $index = $this->getIndex($index);
        $params = [
            'index' => $index,
            'type' => $type,
            'body' => [
                $type => $mappings,
            ],
        ];
        try {
            $response = $this->client->indices()->putMapping($params);

            return ['code' => 200, 'data' => $response, 'message' => ''];
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    '新增【%s:%s】索引mapping失败, 新增设置为【%s】, 失败理由：【%s】',
                    $index,
                    $type,
                    json_encode($mappings),
                    $e->getMessage()
                )
            );

            return ['code' => 400, 'message' => '新增mapping失败'];
        }
    }

    /**
     * get mapping.
     *
     * @param string $index
     * @param string $type
     *
     * @return bool|array
     */
    public function getMapping($index, $type)
    {
        try {
            $index = $this->getIndex($index);
            $params = [
                'index' => $index,
                'type' => $type,
            ];
            $response = $this->client->indices()->getMapping($params);

            return $response;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('获取【%s】【%s】的mapping失败，失败原因:【%s】'));

            return false;
        }
    }

    /**
     * 根据id查询数据
     *
     * @param $index
     * @param $type
     * @param $id
     *
     * @return mixed
     */
    public function get($index, $type, $id)
    {
        $index = $this->getIndex($index);
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id,
        ];
        try {
            $result = $this->client->get($params);
        } catch (\Exception $e) {
            $result = $e->getMessage();
        }

        return $this->getResult($result, __FUNCTION__);
    }

    /**
     * search items.   from+size大于10000的数据请采用scroll方式，会返回对应的scroll
     *
     * 返回数据包含在_source下标中
     *
     * @param string $index
     * @param string $type
     * @param array $query
     * @param int $from
     * @param int $size
     * @param string $scrollTime
     *
     * @return array
     */
    public function search(string $index, string $type, $query, $from = 0, $size = 0, $scrollTime = '')
    {
        $index = $this->getIndex($index);
        $count = $this->count($index, $type, $query);
        if (0 === $count) {
            return ['code' => 200, 'message' => "", 'data' => ['total' => 0, 'rows' => []]];
        }
        $params = [
            'index' => $index,
            'type' => $type,
            'body' => $query,
        ];
        if (0 === $size) {
            $params['size'] = 10000;
            //获取全部结果集
            if ($count >= 10000) {
                //返回前1000条消息和scrollId
                $params['scroll'] = !empty($scrollTime) ? $scrollTime : "30s";
            }
        }
        !empty($from) && $params['from'] = $from;
        !empty($size) && $params['size'] = $size;
        try {
            $response = $this->client->search($params);
            $result = ['code' => 200, 'data' => []];
            $result['data']['total'] = $response['hits']['total'];
            $result['data']['rows'] = $response['hits']['hits'];
            isset($response['_scroll_id']) && $result['data']['scroll_id'] = $response['_scroll_id'];

            return $result;
        } catch (\Exception $e) {
            $this->logger->info(sprintf('【Elasticsearch】数据获取失败，失败理由：【%s】', $e->getMessage()));
        }

        return ['code' => 500, 'message' => "数据获取失败"];
    }

    /**
     * aggregation.
     * 聚合操作
     *
     * @param string $index
     * @param string $type
     * @param array $query
     *
     * @return array
     */
    public function aggregation(string $index, string $type, array $query)
    {
        $index = $this->getIndex($index);
        $params = [
            'index' => $index,
            'type' => $type,
            'body' => $query,
        ];
        try {
            $response = $this->client->search($params);

            return ['code' => 200, 'data' => isset($response['aggregations']) ? $response['aggregations'] : []];
        } catch (\Exception $e) {
            $this->logger->info(sprintf('聚合操作失败，失败理由：【%s】', $e->getMessage()));
        }

        return ['code' => 500, 'message' => "聚合操作失败"];
    }

    /**
     * 创建索引
     *
     * @param string $index  索引名
     * @param $type
     * @param array $mapping 索引mapping设置
     * @param array $options 创建的索引的参数，settings项
     * @return mixed
     */
    public function createIndex(string $index, $type, array $mapping = [], array $options = [])
    {
        $index = $this->getIndex($index);
        // 设置索引名称
        $index = ['index' => $index, 'type' => $type];
        // 设置分片数量
        $index['body'] = [
            'settings' => [
                'number_of_shards' => isset($options['number_of_shards']) ? $options['number_of_shards'] : 6,
                'number_of_replicas' => isset($options['number_of_replicas']) ? $options['number_of_replicas'] : 1,
                'refresh_interval' => isset($options['refresh_interval']) ? $options['refresh_interval'] : '5s',
                'index.mapping.total_fields.limit' => isset($options['limit']) ? $options['limit'] : 10000000,
            ],
            'mappings' => $mapping,
        ];
        // 创建索引
        try {
            $result = $this->indices()->create($index);
        } catch (\Exception $e) {
            $result = $e->getMessage();
        }

        return $this->getResult($result, __FUNCTION__);
    }

    /**
     * create alias for an index
     *
     * @param string $index 索引名
     * @param string $alias 别名
     * @param bool $isForce 是否强制将该别名命名到当前索引
     *
     * @return array
     */
    public function createIndexAlias($index, $alias, $isForce = false)
    {
        //检测别名是否存在
        try {
            $aliasExists = $this->client->indices()->existsAlias(['name' => $alias]);
            //索引已存在
            if ($aliasExists && !$isForce) {
                return ['code' => 400, 'message' => '别名已存在', 'data' => []];
            }
            if ($isForce && true === $aliasExists) {
                //别名已存在，同意强制删除旧的
                $aliasInfo = $this->client->indices()->getAlias(['name' => $alias]);
                $this->client->indices()->deleteAlias(['index' => array_keys($aliasInfo)[0], 'name' => $alias]);
            }
            $data = array('index' => $index, 'name' => $alias);
            $result = $this->client->indices()->putAlias($data);
            if (isset($result['acknowledged']) && $result['acknowledged']) {
                return ['code' => 200, 'data' => [], 'message' => ''];
            }

            return ['code' => 400, 'data' => [], 'message' => '索引名别设置失败'];
        } catch (\Exception $e) {
            $this->logger->warning(sprintf('给【%s】赋予别名【%s】出错，错误原因：【%s】', $index, $alias, $e->getMessage()));

            return ['code' => 400, 'message' => '别名赋值出错', 'data' => ''];
        }
    }

    /**
     * 重建索引
     *
     * @param string $oldIndex
     * @param string $newIndex
     * @param array $query 查询条件
     * @param array $options
     *                     可设置  refresh              boolean 是否刷新
     *                     timeout              time    超时时间
     *                     consistency          enum    冲突解决办法
     *                     wait_for_completion  boolean 是否等待当前操作完成
     *                     requests_per_second  float
     *
     * @return array
     */
    public function reIndex($oldIndex, $newIndex, $query = [], $options = [])
    {
        try {
            $param = [];
            isset($options['refresh']) && $param['refresh'] = (bool)$options['refresh'];
            isset($options['timeout']) && $param['timeout'] = $options['timeout'];
            isset($options['consistency']) && $param['consistency'] = $options['consistency'];
            isset($options['wait_for_completion']) && $param['wait_for_completion'] = $options['wait_for_completion'];
            isset($options['requests_per_second']) && $param['requests_per_second'] = $options['requests_per_second'];
            $param['body'] = ['source' => ['index' => $oldIndex], 'dest' => ['index' => $newIndex]];
            !empty($query) && $param['body']['source']['query'] = $query;
            $result = $this->client->reindex($param);

            return ['code' => 200];
        } catch (\Exception $e) {
            $this->logger->error(sprintf("索引【%s】reindex为【%s】出错，错误原因:【%s】", $oldIndex, $newIndex, $e->getMessage()));
        }

        return ['code' => 400, 'message' => '索引reindex出错'];
    }

    /**
     * 插入数据
     *
     * @param string $index
     * @param string $type
     * @param array $items
     * @param bool $refresh
     *
     * @return array 返回成功插入的数据数
     */
    public function batchAdd(string $index, string $type, array $items, bool $refresh = false)
    {
        $body = [];
        foreach ($items as $key => $item) {
            $body[] = [
                'index' => [
                    '_id' => $item['_id'],
                ],
            ];
            $item['id'] = (int)$item['_id'];
            unset($item['_id']);
            $body[] = $item;
        }
        try {
            $response = $this->bulk($body, $index, $type, $refresh);

            return ['code' => 200, 'data' => $response];
        } catch (\Exception $e) {
            $this->logger->error(sprintf('数据插入失败，插入数据 【%s】，失败理由：【%s】', json_encode($body), $e->getMessage()));
        }

        return ['code' => 400, 'message' => "elasticsearch插入数据失败"];
    }

    /**
     * update documents.
     * if not exists then insert.
     *
     * @param string $index
     * @param string $type
     * @param array $updates 形如['id' => ['doc'/'script' => $update]]
     * @param bool $refresh  刷新ES
     *
     * @return array
     */
    public function batchUpdate(string $index, string $type, array $updates, bool $refresh = false)
    {
        $body = [];
        foreach ($updates as $id => $update) {
            $body[] = [
                'update' => [
                    "_id" => $id,
                    "_retry_on_conflict" => 3,
                ],
            ];
            $body[] = [array_keys($update)[0] => $update[(array_keys($update))[0]]];
        }
        try {
            $response = $this->bulk($body, $index, $type, $refresh);

            return $response;
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('【Elasticsearch】更新失败，更新数据【%s】，失败理由：【%s】', json_encode($body), $e->getMessage())
            );
        }

        return ['code' => 500, 'message' => "数据库更新失败"];
    }

    /**
     * delete documents.
     *
     * @param string $index
     * @param string $type
     * @param array $ids
     * @param bool $refresh
     *
     * @return array
     */
    public function batchDelete(string $index, string $type, array $ids, bool $refresh)
    {
        $body = [];
        foreach ($ids as $id) {
            $body[] = [
                "delete" => [
                    '_id' => $id,
                ],
            ];
        }
        try {
            $response = $this->bulk($body, $index, $type, $refresh);

            return $response;
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('【Elasticsearch】数据删除失败，删除数据 【%s】，失败理由：【%s】', json_encode($body), $e->getMessage())
            );
        }

        return ['code' => 500, 'message' => "elasticsearch数据删除失败"];
    }

    /**
     * 添加记录
     *
     * @param $index
     * @param $type
     * @param $data
     * @param null $id
     *
     * @return mixed
     */
    public function add($index, $type, $data, $id = null)
    {
        $index = $this->getIndex($index);
        $params = [
            'index' => $index,
            'type' => $type,
            'body' => $data,
        ];
        $id && $params['id'] = $id;
        try {
            $result = $this->client->index($params);
        } catch (\Exception $e) {
            $result = $e->getMessage();
        }

        return $this->getResult($result, __FUNCTION__);
    }

    /**
     * 更新记录
     *
     * @param $index
     * @param $type
     * @param $id
     * @param $data
     *
     * @return mixed
     */
    public function update($index, $type, $id, $data)
    {
        $index = $this->getIndex($index);
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id,
            'body' => ['doc' => $data],
        ];
        try {
            $result = $this->client->update($params);
        } catch (\Exception $e) {
            $result = $e->getMessage();
        }

        return $this->getResult($result, __FUNCTION__);
    }

    /**
     * 删除记录
     *
     * @param $index
     * @param $type
     * @param $id
     *
     * @return mixed
     */
    public function delete($index, $type, $id)
    {
        $index = $this->getIndex($index);
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id,
        ];
        try {
            $result = $this->client->delete($params);
        } catch (\Exception $e) {
            $result = $e->getMessage();
        }

        return $this->getResult($result, __FUNCTION__);
    }

    /**
     * 获取各个环境索引完整地址
     *
     * @param $index
     *
     * @return string
     */
    public function getIndex($index)
    {
        $index = C('ELK.env') ? C('ELK.env').'-'.$index : $index;

        return $index;
    }

    /**
     * checkout the index exists
     * @param string $index 索引名
     *
     * @return array delete result
     */
    public function existsIndex(string $index)
    {
        $params = [
            'index' => $index,
        ];
        try {
            $response = $this->client->indices()->exists($params);

            return ['code' => 200, 'message' => '', 'data' => $response];
        } catch (\Exception $e) {
            $this->logger->error(sprintf('验证索引(%s)失败，失败原因：【%s】', $index, $e->getMessage()));

            return ['code' => 400, 'message' => '验证索引是否存在失败'];
        }
    }

    /**
     * checkout type exists
     * @param string $index 索引名
     * @param string $type
     *
     * @return array  result
     */
    public function existsType(string $index, string $type)
    {
        $params = [
            'index' => $index,
            'type' => $type,
        ];
        try {
            $response = $this->client->indices()->existsType($params);

            return ['code' => 200, 'data' => $response];
        } catch (\Exception $e) {
            //var_dump($e->getMessage());
            $this->logger->error(sprintf('验证type(%s/%s)失败，失败原因：【%s】', $index, $type, $e->getMessage()));

            return ['code' => 400, 'message' => '验证type是否存在失败'];
        }
    }

    /**
     * 根据条件查询结果集条数
     *
     * @param string $index
     * @param string $type
     * @param array $query
     *
     * @return int
     */
    public function count(string $index, string $type, $query)
    {
        $param = [
            'index' => $index,
            'type' => $type,
        ];
        (isset($query['query']) && is_array($query['query'])) && $param['body'] = ['query' => $query['query']];
        try {
            $res = $this->client->count($param);

            return $res['count'];
        } catch (\Exception $e) {
            $this->logger->warning(
                sprintf('【Elasticsearch】获取数据总数出错，查询条件【%s】,错误原因:【%s】', json_encode($param), $e->getMessage())
            );
        }

        return 0;
    }

    /**
     * 根据scrollId获取下一批数据
     *
     * @param string $scrollId
     * @param string $scrollTime
     *
     * @return array
     */
    public function scroll($scrollId, $scrollTime)
    {
        $params = [
            'scroll_id' => $scrollId,
            'scroll' => $scrollTime,
        ];
        try {
            $response = $this->client->scroll($params);
            $result = ['code' => 200, 'data' => ['scroll_id' => $scrollId, 'rows' => $response['hits']['hits']]];

            return $result;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('数据获取失败，失败理由：【%s】', $e->getMessage()));
        }

        return ['code' => 500, 'message' => "数据获取失败"];
    }

    /**
     * 批量操作
     *
     * @param array $body
     * @param string $index
     * @param string $type
     * @param bool $refresh
     *
     * @return array|bool 返回失败的id数组
     */
    public function bulk($body, $index = "", $type = "", bool $refresh = false)
    {
        try {
            $params = ['body' => $body, 'refresh' => $refresh];
            !empty($index) && $params['index'] = getenv("APP_ENV")."_".$index;
            !empty($type) && $params['type'] = $type;
            $response = $this->client->bulk($params);
            $total = count($response['items']);  // 总操作数
            $errors = empty($response['errors']) ? 0 : $response['errors'];
            $errorRes = [];
            $successIds = [];
            $items = $response['items'];
            foreach ($items as $item) {
                foreach ($item as $key => $value) {
                    ("index" === $key || "create" === $key) && $key = "add";
                    $successIds[] = $value['_id'];
                }
            }
            if ($errors > 0) {
                $this->logger->warning(
                    sprintf('【Elasticsearch】bulk操作【%s】条发送失败,失败原因为【%s】.', count($errorRes), json_encode($errorRes))
                );
            }

            return [
                'code' => 200,
                'data' => ['success' => count($successIds), 'fail' => $errors, 'ids' => $successIds],
            ];
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('【Elasticsearch】bulk操作失败,操作数据 【%s】，失败理由：【%s】', json_encode($params), $e->getMessage())
            );

            return ['code' => 500, 'message' => 'bulk操作失败'];
        }
    }

    /**
     * 转换自定义运算符为ES联合查询条件.
     *
     * @param array $items    查询条目，格式为FilterModel->parseFilterConditions返回数据格式
     * @param array $mappings 索引的默认设置
     *
     * @return array
     */
    public function generateBoolQuery($items, $mappings)
    {
        $boolQuery = [];
        if (!empty($items)) {
            foreach ($items as $key => $item) {
                if (empty($item['type'])) {
                    continue;
                }
                $matchType = false !== stripos($item['type'], 'term') ? 'term' : 'match';
                switch ($item['type']) {
                    case 'match':
                        // 聚合匹配
                    case 'term':
                        // 精准匹配
                        if (!empty($item['field']) && (!empty($item['value']) || 0 === $item['value'] || '0' === $item['value'])) {
                            //判断当前的key是否是设置了分词效果
                            isset($mappings[$item['field']]) && isset($mappings[$item['field']]['analyzer']) && $item['field'] = $item['field'].".keyword";
                            $boolQuery['must'][] = [$matchType => [$item['field'] => $item['value']]];
                        }
                        break;
                    case 'must_not':
                        // 不匹配
                    case 'must_not_term':
                        // 精确不匹配
                        if (!empty($item['field']) && (!empty($item['value']) || 0 === $item['value'] || '0' === $item['value'])) {
                            isset($mappings[$item['field']]) && isset($mappings[$item['field']]['analyzer']) && $item['field'] = $item['field'].".keyword";
                            $boolQuery['must_not'][] = [$matchType => [$item['field'] => $item['value']]];
                        }
                        break;
                    case 'should':
                        // 多个选项匹配
                    case 'should_term':
                        // 多个选项精确匹配
                        $values = explode(',', $item['value']);
                        $bool = [];
                        if (!empty($values) && is_array($values)) {
                            isset($mappings[$item['field']]) && isset($mappings[$item['field']]['analyzer']) && $item['field'] = $item['field'].".keyword";
                            foreach ($values as $val) {
                                $bool['bool']['should'][] = [$matchType => [$item['field'] => $val]];
                            }
                            $bool['bool']['minimum_should_match'] = 1; //至少匹配一项
                            $boolQuery['must'][] = $bool;
                        }
                        break;
                    case 'should_wildcard':
                        // 多个选项正则匹配
                        $values = explode(',', $item['value']);
                        $bool = [];
                        if (!empty($values) && is_array($values)) {
                            isset($mappings[$item['field']]) && isset($mappings[$item['field']]['analyzer']) && $item['field'] = $item['field'].".keyword";
                            foreach ($values as $val) {
                                $bool['bool']['should'][] = ['wildcard' => [$item['field'] => '*'.$val.'*']];
                            }
                            $bool['bool']['minimum_should_match'] = 1; //至少匹配一项
                            $boolQuery['must'][] = $bool;
                        }
                        break;
                    case 'must_not_should_wildcard':
                        $values = explode(',', $item['value']);
                        $bool = [];
                        if (!empty($values) && is_array($values)) {
                            isset($mappings[$item['field']]) && isset($mappings[$item['field']]['analyzer']) && $item['field'] = $item['field'].".keyword";
                            foreach ($values as $val) {
                                $bool['bool']['should'][] = ['wildcard' => [$item['field'] => '*'.$val.'*']];
                            }
                            $boolQuery['must_not'][] = $bool;
                        }
                        break;
                    case 'should_not_term':
                        // 多个选项精确不匹配
                        $values = explode(',', $item['value']);
                        $bool = [];
                        if (!empty($values) && is_array($values)) {
                            isset($mappings[$item['field']]) && isset($mappings[$item['field']]['analyzer']) && $item['field'] = $item['field'].".keyword";
                            foreach ($values as $val) {
                                $bool['bool']['should'][] = ['bool' => ['must_not' => [['term' => [$item['field'] => $val]]]]];
                            }
                            $bool['bool']['minimum_should_match'] = 1; //至少匹配一项
                            $boolQuery['must'][] = $bool;
                        }
                        break;
                    case 'or':
                        //或者
                        if (!empty($item['field']) && (!empty($item['value'] || 0 === $item['value'] || '0' === $item['value']))) {
                            $boolQuery['should'][] = ['match' => [$item['field'] => $item['value']]];
                        }
                        break;
                    case 'not_exists':
                        // 不存在
                        $query = [];
                        if (!empty($item['field'])) {
                            if (isset($item['addition'])) {
                                $query['bool']['should'][] = ['bool' => ['must_not' => ['exists' => ['field' => $item['field']]]]];
                                $query['bool']['should'][] = [$item['addition']['type'] => [$item['addition']['field'] => $item['addition']['value']]];
                                $boolQuery['must'][] = $query;
                            } else {
                                $boolQuery['must_not'][] = ['exists' => ['field' => $item['field']]];
                            }
                        }
                        break;
                    case 'exists':
                        // 存在
                        if (!empty($item['field'])) {
                            $boolQuery['must'][] = ['exists' => ['field' => $item['field']]];
                            if (isset($item['addition'])) {
                                $boolQuery['must'][] = ['bool' => ['must_not' => [$item['addition']['type'] => [$item['addition']['field'] => $item['addition']['value']]]]];
                            }
                        }
                        break;
                    case 'range_gt':
                        // 范围匹配-大于
                        if (!empty($item['field']) && (!empty($item['value']) || 0 === $item['value'] || '0' === $item['value'])) {
                            $boolQuery['must'][] = [
                                'range' => [$item['field'] => ['gt' => $item['value']]],
                            ];
                        }
                        break;
                    case 'range_lt':
                        //范围匹配-小于
                        if (!empty($item['field']) && (!empty($item['value']) || 0 === $item['value'] || '0' === $item['value'])) {
                            $boolQuery['must'][] = [
                                'range' => [$item['field'] => ['lt' => $item['value']]],
                            ];
                        }
                        break;
                    case 'between':
                        // 范围-介于
                        $start = isset($item['value']['start']) ? $item['value']['start'] : '';
                        $end = isset($item['value']['end']) ? $item['value']['end'] : '';
                        if ((!empty($start) || '0' === $start || 0 === $start) && (!empty($end) || 0 === $end || '0' === $end)) {
                            $boolQuery['must'][] = ['range' => [$item['field'] => ['gte' => $start]]];
                            $boolQuery['must'][] = ['range' => [$item['field'] => ['lte' => $end]]];
                        }
                        break;
                    case 'not_between':
                        // 范围-不介于
                        $start = isset($item['value']['start']) ? $item['value']['start'] : '';
                        $end = isset($item['value']['end']) ? $item['value']['end'] : '';
                        $bool = array();
                        if ((!empty($start) || '0' === $start || 0 === $start) && (!empty($end) || 0 === $end || '0' === $end)) {
                            $bool['bool']['should'][] = ['range' => [$item['field'] => ['gt' => $end]]];
                            $bool['bool']['should'][] = ['range' => [$item['field'] => ['lt' => $start]]];
                            $bool['bool']['should'][] = ['bool' => ['must_not' => ['exists' => ['field' => $item['field']]]]]; //添加不存在字段
                            $bool['bool']['minimum_should_match'] = 1; //至少匹配一项
                            $boolQuery['must'][] = $bool;
                        }
                        break;
                    case 'multi_must_not':
                        // 不匹配多个
                    case 'multi_must_not_term':
                        // 精确不匹配多个--一个都匹配不上
                        $values = explode(',', $item['value']);
                        if (!empty($values) && is_array($values)) {
                            isset($mappings[$item['field']]) && isset($mappings[$item['field']]['analyzer']) && $item['field'] = $item['field'].".keyword";
                            foreach ($values as $val) {
                                $boolQuery['must_not'][] = [$matchType => [$item['field'] => $val]];
                            }
                        }
                        break;
                    case 'multi_must_term':
                        // 精确匹配多个
                        $values = explode(',', $item['value']);
                        if (!empty($values) && is_array($values)) {
                            isset($mappings[$item['field']]) && isset($mappings[$item['field']]['analyzer']) && $item['field'] = $item['field'].".keyword";
                            foreach ($values as $val) {
                                $boolQuery['must'][] = ['term' => [$item['field'] => $val]];
                            }
                        }
                        break;
                    case 'multi_should_field_terms':
                        // 多个选项聚合匹配
                        $fieldTerms = empty($item['field_terms']) ? array() : $item['field_terms'];
                        $bool = [];
                        if (!empty($fieldTerms) && is_array($fieldTerms)) {
                            foreach ($fieldTerms as $k => $fieldTerm) {
                                $oper = empty($fieldTerm['oper']) ? '' : $fieldTerm['oper'];
                                $field = empty($fieldTerm['field']) ? '' : $fieldTerm['field'];
                                $value = empty($fieldTerm['value']) ? '' : $fieldTerm['value'];
                                if (!empty($oper) && !empty($field) && (!empty($value) || 000 === $value)) {
                                    switch ($oper) {
                                        case 'match':
                                            $bool['bool']['should'][] = ['match' => [$field => $value]];
                                            break;
                                        case 'term':
                                            isset($mappings[$field]) && isset($mappings[$item['field']]['analyzer']) && $item['field'] = $item['field'].".keyword";
                                            $bool['bool']['should'][] = ['term' => [$field => $value]];
                                            break;
                                        case 'range':
                                            $expression = empty($fieldTerm['expression']) ? '' : $fieldTerm['expression'];
                                            if (!empty($expression) && in_array(
                                                    $expression,
                                                    ['gt', 'gte', 'lt', 'lte']
                                                )) {
                                                $bool['bool']['should'][] = ['range' => [$field => [$expression => $value]]];
                                            }
                                            break;
                                    }
                                }
                            }
                            $bool['bool']['minimum_should_match'] = 1; //至少聚合匹配一项
                            $boolQuery['must'][] = $bool;
                        }
                        break;
                    case "multi_should_not_term":
                        // 精确不匹配多个--全部匹配不上才能查找出来
                        $values = explode(',', $item['value']);
                        $bool = [];
                        if (!empty($values) && is_array($values)) {
                            isset($mappings[$item['field']]) && isset($mappings[$item['field']]['analyzer']) && $item['field'] = $item['field'].".keyword";
                            foreach ($values as $val) {
                                $bool['bool']['should'][] = [$matchType => [$item['field'] => $val]];
                            }
                        }
                        $bool['bool']['minimum_should_match'] = count($values);
                        $boolQuery['must_not'][] = $bool;
                        break;
                    case 'wildcard':
                        //模糊匹配（正则）
                        if (!empty($item['field']) && (!empty($item['value']) || 0 === $item['value'] || '0' === $item['value'])) {
                            if (isset($mappings[$item['field']]) && isset($mappings[$item['field']]['analyzer'])) {
                                //分词器上的模糊匹配
                                $boolQuery['must'][] = ['match' => [$item['field'] => $item['value']]];
                                break;
                            }
                            if (empty($item['position'])) {
                                $boolQuery['must'][] = ['wildcard' => [$item['field'] => "*{$item['value']}*"]];
                            } else {
                                if ('front' === $item['position']) {
                                    $boolQuery['must'][] = ['wildcard' => [$item['field'] => "{$item['value']}*"]];
                                } elseif ('later' === $item['position']) {
                                    $boolQuery['must'][] = ['wildcard' => [$item['field'] => "*{$item['value']}"]];
                                }
                            }
                        }
                        break;
                    case "script":
                        if (!empty($item['inline']) && (!isset($item['value']) || !empty($item['value']) || 0 === $item['value'] || '0' === $item['value'])) {
                            $boolQuery['must'][] = [
                                'script' => [
                                    'script' => [
                                        'inline' => $item['inline'],
                                        'lang' => "painless",
                                        'params' => isset($item['value']) ? $item['value'] : new \stdClass(),
                                    ],
                                ],
                            ];
                        }
                        break;
                }
            }
        }

        return $boolQuery ? ['bool' => $boolQuery] : [];
    }

    /**
     * 转换返回值
     *
     * @param $result
     *
     * @return mixed
     */
    protected function getResult($result, $method = null)
    {
        if (!is_array($result)) {
            $result = json_decode($result, true);
        }
        if (!empty($result['error'])) {
            return [
                'status' => 0,
                'error_code' => $result['error']['type'],
                'error_msg' => $result['error']['reason'],
            ];
        }
        if ($method == 'get' && $result['found']) {
            return [
                'status' => 1,
                'data' => $result['_source'],
            ];
        }
        if ($method == 'search' && $result['hits']['total']) {
            return [
                'status' => 1,
                'data' => $result['hits'],
            ];
        }
        if ($method == 'add' && $result['created']) {
            return [
                'status' => 1,
                'data' => $result['_id'],
            ];
        }
        if ($method == 'update' && $result['result'] == 'updated') {
            return [
                'status' => 1,
                'data' => $result['_id'],
            ];
        }
        if ($method == 'delete') {
            return [
                'status' => $result['result'] == 'deleted' ? 1 : 0,
                'data' => [],
            ];
        }

        return $result;
    }
}