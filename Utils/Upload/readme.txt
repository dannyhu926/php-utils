<?php
return [
    /* 上传组件配置 */
    'UPLOAD_OPTION' => [
        'type' => 'FastDFS', //FastDFS:资源服务器

        /* FastDFS 资源服务器配置 */
        'FastDFS_OPTION' => [
            'tracker' => [ //调度节点(暂时只能设置一个调度节点，暂不支持调度负载)
                ['host' => '192.168.0.3','port' => '22122']
            ],
            'storage_host_alias' => '192.168.0.3' //设置后，会覆盖存储节点的host(主要用于不同环境调试)
        ],
    ],
];