<?php

return [
    // 进程的名称
    'name' => env('RPC_NAME', 'MajorbioRpc'),
    // 监听端口
    'port' => env('RPC_PORT', 39000),
    // 启动进程数量
    'count' => env('RPC_WORKER_COUNT', 4),
    // RPC 服务文件命名空间
    'rpcNameSpace' => '\\App\\Rpc\\',
    // 指定 workerman 的 pid 文件
    'pidFile' => storage_path() . '/workerman/rpc/workerman.pid',
    // 指定 workerman 的 log 文件
    'logFile' => storage_path() . '/workerman/rpc/workerman.log',
    // 省略 -d 参数
    'daemonize' => env('RPC_DAEMONIZE', false),
];
