<?php

namespace majorbio\rpc;

use Illuminate\Support\Facades\Log;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Lib\Timer;

include_once __DIR__ . '/Protocols/LenJson.php';

class Server
{
    protected $worker;
    protected $port = '30106';
    protected $count = 4;
    protected $name = 'MajorbioRpc';
    /** @var InstanceKeeper[] $instanceKeepers */
    protected $instanceKeepers = [];
    protected $pidFile = '';
    protected $logFile = '';
    protected $daemonize = false;
    protected $context = [];
    protected $event = ['onWorkerStart', 'onConnect', 'onMessage', 'onClose', 'onError'];
    protected $rpcNameSpace = '';

    /**
     * 构造函数
     *
     * @return void
     */
    public function __construct($config = [])
    {
        // 初始化参数
        if (!empty($config)) {
            foreach ($config as $key => $val) {
                $this->$key = $val;
            }
        }
        // 设置 workerman 的 pid 文件
        if (!empty($this->pidFile)) {
            Worker::$pidFile = $this->pidFile;
        }
        // 设置 workerman 的 log 文件
        if (!empty($this->logFile)) {
            Worker::$logFile = $this->logFile;
        }
        // 省略 -d 参数
        if (!empty($this->daemonize)) {
            Worker::$daemonize = $this->daemonize;
        }

        // 实例化
        $this->worker = new Worker('LenJson://0.0.0.0:' . $this->port, $this->context);

        // 启动数量
        $this->worker->count = $this->count;

        // 设置实例的名称
        $this->worker->name = $this->name;

        // 设置回调
        foreach ($this->event as $event) {
            if (method_exists($this, $event)) {
                $this->worker->$event = [$this, $event];
            }
        }
    }

    /**
     * 回调函数 onWorkerStart
     *
     * @return void
     */
    public function onWorkerStart()
    {
        Log::write('info', "Worker " . $this->worker->id . " starting...\n");

        // 每个 worker 每 n 秒一次检测
        Timer::add(30, function () {
            // 处理过期的
            if (!empty($this->instanceKeepers)) {
                /** @var InstanceKeeper $instanceKeeper */
                foreach ($this->instanceKeepers as $connectionId => $instanceKeeper) {
                    // 如果超过 180 秒无更新的话，则释放资源
                    if (time() - $instanceKeeper->getLastInvokeTime() > 180) {
                        // echo "清理 " . $this->worker->id . " 资源\n";
                        unset($this->instanceKeepers[$connectionId]);
                    }
                }
            }
        });
    }

    /**
     * 回调函数 onConnect
     *
     * @param TcpConnection $connection
     * 
     * @return void
     */
    public function onConnect(TcpConnection $connection)
    {
        // echo "新链接 ip " . $connection->getRemoteIp() . "\n";

        // 创建当前链接的 instanceKeeper
        $this->instanceKeepers[$connection->id] = new InstanceKeeper();
    }

    /**
     * 回调函数 onMessage
     *
     * @param TcpConnection $connection
     * @param mixed $data
     * 
     * @return void
     */
    public function onMessage(TcpConnection $connection, $data)
    {
        // 检查
        if (!isset($this->instanceKeepers[$connection->id])) {
            // 如果不存在，则重新创建 instanceKeeper
            $this->instanceKeepers[$connection->id] = new InstanceKeeper();
        }

        // 更新最后通讯时间
        $this->instanceKeepers[$connection->id]->updateLastInvokeTime();

        // 类、方法、参数
        $class = $this->rpcNameSpace . $data['class'];
        $method = $data['method'];
        $params = $data['params'];

        // 类是否存在
        if (!class_exists($class)) {
            $connection->send($this->rs(404, 'class ' . $class . ' not exist'));
            return;
        }

        // 容器中如果没有此实例
        if (!$this->instanceKeepers[$connection->id]->has($class)) {
            // 则实例化
            $this->instanceKeepers[$connection->id]->set($class, new $class());
        }

        // 方法是否存在
        if (!method_exists($this->instanceKeepers[$connection->id]->get($class), $method)) {
            $connection->send($this->rs(404, 'method ' . $method . ' not exist'));
            return;
        }

        // 执行方法
        $rs = $this->instanceKeepers[$connection->id]->get($class)->$method(...$params);
        // var_dump($rs);
        if (empty($rs)) {
            $rs = [
                'code' => 0,
                'message' => 'invoke ' . $data['class'] . '@' . $method . ' success',
                'data' => null,
            ];
        }

        // 发给此客户端
        $connection->send($rs);
    }

    /**
     * 回调函数 onClose
     *
     * @param TcpConnection $connection
     * 
     * @return void
     */
    function onClose(TcpConnection $connection)
    {
        // 释放资源
        if (isset($this->instanceKeepers[$connection->id])) {
            unset($this->instanceKeepers[$connection->id]);
        }
        // echo "connection closed\n";
    }

    /**
     * 回调函数 onError
     *
     * @return void
     */
    function onError()
    {
        Log::write('info', "Worker " . $this->worker->id . " error...\n");
    }

    /**
     * 启动
     *
     * @return void
     */
    public function start()
    {
        Worker::runAll();
    }

    /**
     * 设置 worker 属性
     *
     * @param mixed $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->worker->$name = $value;
    }

    /**
     * 调用 worker 方法
     *
     * @param string $method
     * @param mixed $args
     * 
     * @return void
     */
    public function __call($method, $args)
    {
        call_user_func_array([$this->worker, $method], $args);
    }

    /**
     * 返回数据结构
     *
     * @param integer $code
     * @param string $message
     * @param mixed $data
     * 
     * @return array
     */
    public function rs(int $code = 0, string $message = '', $data = []): array
    {
        return [
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];
    }
}
