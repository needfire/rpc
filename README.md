# Laravel 的 RPC 框架
- 基于 workerman 的多进程 RPC 框架
- 状态可维持，支持多次调用

<br>

## 数据包样本
- 首部固定 10 个字节长度用来保存整个数据包长度，位数不够左补 0
- 数据格式为 json 字符串
```bash
0000000068{"code":0,"message":"ok","data":["hello world, hello u!"]}
```

## 安装
```bash
# 安装
composer require majorbio/rpc

# 发布配置文件
php artisan vendor:publish --provider="majorbio\rpc\Providers\RpcServiceProvider"
```

<br>
<br>

# Server 服务端
一、 配置 /config/rpc.php
```php
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
```

二、创建 RPC 服务 /app/Rpc/Calculator.php
```php
<?php

namespace App\Rpc;

use majorbio\helper\RS;

class Calculator
{
    public int $a = 1;
    public int $b = 2;

    /**
     * 设置 a
     *
     * @param int $a
     *
     * @return void
     */
    public function setA(int $a = 0)
    {
        $this->a = $a;
    }

    /**
     * 设置 b
     *
     * @param int $a
     *
     * @return void
     */
    public function setB(int $b = 0)
    {
        $this->b = $b;
    }

    /**
     * 求和
     *
     * @return RS
     */
    public function sum(): RS
    {
        return new RS(0, 'Calculator-sum', ($this->a + $this->b));
    }

    /**
     * 相乘
     *
     * @return int
     */
    public function multiply(): int
    {
        return $this->a * $this->b;
    }
}

```

三、运行 RPC 服务
```bash
php artisan rpc start
```

<br>
<br>

# Client 客户端
```php
<?php

use majorbio\rpc\Client as RpcClient;

// 创建 RpcClient
$rpcClient = new RpcClient('127.0.0.1', 30106);

// 调用 setA 方法（注意传参是个数组）
$rpcClient->invoke('Calculator', 'setA', [5]);

// 调用 setB 方法（注意传参是个数组）
$rpcClient->invoke('Calculator', 'setB', [3]);

// 调用 sum 方法
var_dump($rpcClient->invoke('Calculator', 'sum'));

// 调用 multiply 方法
var_dump($rpcClient->invoke('Calculator', 'multiply'));

$rpcClient->disconnect();
```
