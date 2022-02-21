# Laravel 的 RPC 框架
- 基于 workerman 的多进程 RPC 框架
- 状态可维持，支持多次调用

<br>

# 数据包样本
- 首部固定 10 个字节长度用来保存整个数据包长度，位数不够左补 0
- 数据格式为 json 字符串
```bash
0000000068{"code":0,"message":"ok","data":["hello world, hello u!"]}
```

# 安装
```bash
# 安装
composer require majorbio/rpc

# 发布配置文件
php artisan vendor:publish --provider="majorbio\rpc\Providers\RpcServiceProvider"
```


# Server 服务端
1. 配置 /config/rpc.php
```php
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
```

2. 创建 RPC 服务 /app/Rpc/Calculator.php
```php
<?php

namespace App\Rpc;

class Calculator
{
    public int $a = 1;
    public int $b = 2;

    public function setA(int $a = 0)
    {
        $this->a = $a;
    }

    public function setB(int $b = 0)
    {
        $this->b = $b;
    }

    public function add()
    {
        return ['code' => 0, 'message' => 'ERP-Rpc-Calculator', 'data' => [$this->a + $this->b]];
    }
}

```

3. 命令行程序 /app/Console/Commands/Rpc.php
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use majorbio\rpc\Server;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\ConsoleOutput;

class Rpc extends Command
{
    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '启动 RPC 服务: php artisan rpc start';

    /**
     * RPC 服务
     * 
     * @return void
     */
    protected function configure()
    {
        $this->setName('rpc')
            ->addArgument('action', InputArgument::REQUIRED, 'start|stop|restart|reload|status|connections');
    }

    /**
     * 执行
     * 
     * @param \Symfony\Component\Console\Output\ConsoleOutput $output
     *
     * @return void
     */
    public function handle(ConsoleOutput $output)
    {
        // 配置
        $config = config('rpc');
        if (!is_array($config) || empty($config)) {
            $output->writeln("<error>没有找到配置文件 /config/rpc.php 请参照如下示例:</error>");
            return false;
        }

        // 参数 action
        $action = $this->argument('action');
        if (!in_array($action, ['start', 'stop', 'reload', 'restart', 'status', 'connections'])) {
            $output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|restart|reload|status|connections .</error>");
            return false;
        }

        // 启动
        $server = new Server($config);
        $server->start();
    }
}
```

4. 运行 RPC 服务
```bash
php artisan rpc start
```


# Client 客户端
```php
<?php

use majorbio\rpc\Client as RpcClient;

// 创建 RpcClient
$rpcClient = new RpcClient('127.0.0.1', 30106);

$rpcClient->invoke('Calculator', 'setA', [5]);

$rpcClient->invoke('Calculator', 'setB', [3]);

var_dump('Calculator-add-');
var_dump($rpcClient->invoke('Calculator', 'add'));

$rpcClient->disconnect();

```
