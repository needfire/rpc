<?php

return [
    // 监听端口
    'port' => 30106,
    // 启动进程数量
    'count' => 4,
    // 进程的名称
    'name' => 'MajorbioRpc',
    // RPC 服务文件命名空间
    'rpcNameSpace' => '\\App\\Rpc\\',
    // 指定 workerman 的 pid 文件
    'pidFile' => storage_path() . '/workerman/workerman.pid',
    // 指定 workerman 的 log 文件
    'logFile' => storage_path() . '/workerman/workerman.log',
    //省略 -d 参数
    'daemonize' => false,
];
