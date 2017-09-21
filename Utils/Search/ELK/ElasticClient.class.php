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

    public function __construct() {
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
    public function init() {
        if (!$this->is_open) {
            return false;
        }
        $hosts = C('ELK.hosts');
        $this->client = \Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();

        return $this->client;
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
    public function get($index, $type, $id) {
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
     * 根据自定义条件搜索
     *
     * @param $index
     * @param $type
     * @param $query
     * @param int $from
     * @param int $size
     *
     * @return mixed
     */
    public function search($index, $type, $query = [], $from = 0, $size = 0) {
        $index = $this->getIndex($index);
        $params = [
            'index' => $index,
            'type' => $type,
        ];
        $query && $params['body'] = ['query' => $query];
        $from && $params['from'] = $from;
        $size && $params['size'] = $size;

        try {
            $result = $this->client->search($params);
        } catch (\Exception $e) {
            $result = $e->getMessage();
        }

        return $this->getResult($result, __FUNCTION__);
    }

    /**
     * 创建索引
     *
     * @param $index
     * @param $type
     *
     * @return mixed
     */
    public function createIndex($index, $type) {
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
     * 添加记录
     *
     * @param $index
     * @param $type
     * @param $data
     * @param null $id
     *
     * @return mixed
     */
    public function add($index, $type, $data, $id = null) {
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
    public function update($index, $type, $id, $data) {
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
    public function delete($index, $type, $id) {
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
    public function getIndex($index) {
        $index = C('ELK.env') ? C('ELK.env') . '-' . $index : $index;

        return $index;
    }

    /**
     * 转换返回值
     *
     * @param $result
     *
     * @return mixed
     */
    protected function getResult($result, $method = null) {
        if (!is_array($result)) {
            $result = json_decode($result, true);
        }
        if (!empty($result['error'])) {
            return [
                'status' => 0,
                'error_code' => $result['error']['type'],
                'error_msg' => $result['error']['reason']
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
                'data' => $result['hits']
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