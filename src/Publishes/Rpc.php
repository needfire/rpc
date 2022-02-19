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
            $output->writeln("
            \$config = [
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
            ");
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
