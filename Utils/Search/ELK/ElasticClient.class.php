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
        $index = C('ELK.env')."-".$index;
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
        $params = [
            'index' => C('ELK.env').'-'.$index,
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
            $params = [
                'index' => C('ELK.env').'-'.$index,
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
        $index = C('ELK.env').'-'.$index;
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
     * 创建索引
     *
     * @param $index
     * @param $type
     *
     * @return mixed
     */
    public function createIndex($index, $type)
    {
        $index = $this->getIndex($index);
        // 设置索引名称
        $index = ['index' => $index, 'type' => $type];
        // 设置分片数量
        $index['body']['settings'] = ['number_of_shards' => 5, 'number_of_replicas' => 0];
        // 创建索引
        try {
            $result = $this->indices()->create($index);
        } catch (\Exception $e) {
            $result = $e->getMessage();
        }

        return $this->getResult($result, __FUNCTION__);
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