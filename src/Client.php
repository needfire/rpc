<?php

namespace majorbio\rpc;

use Exception;

class Client
{
    private $socket;
    // 服务器
    protected string $host = '';
    // 端口
    protected int $port = 0;
    // 读长
    protected int $readLength = 8790;

    /**
     * 构造函数
     *
     * @param string $host
     * @param integer $port
     * 
     * @throws Exception
     */
    public function __construct(string $host = '127.0.0.1', int $port = 30106, int $readLength = 8790)
    {
        // 参数
        $this->host = $host;
        $this->port = $port;
        $this->readLength = $readLength;

        // 创建 socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) {
            $errorCode = socket_last_error();
            $errorMessage = socket_strerror($errorCode);
            throw new Exception($errorMessage, $errorCode);
        }

        // 连接
        $conn = socket_connect($this->socket, $this->host, $this->port);
        if ($conn === false) {
            $errorCode = socket_last_error();
            $errorMessage = socket_strerror($errorCode);
            throw new Exception($errorMessage, $errorCode);
        }
    }

    /**
     * 设置读长
     *
     * @param integer $readLength
     * 
     * @return void
     */
    public function setReadLength(int $readLength = 0)
    {
        if ($readLength < 1024) {
            $readLength = 8790;
        }
        $this->readLength = $readLength;
    }

    /**
     * 调用 RPC 方法
     *
     * @param string $class
     * @param string $method
     * @param array $params
     * 
     * @return mixed
     */
    public function invoke(string $class = '', string $method = '', array $params = [])
    {
        // 组装
        $command = json_encode([
            'class' => trim($class),
            'method' => trim($method),
            'params' => $params,
            'dateTime' => date('Y-m-d H:i:s'),
        ]) . "\n";

        // 向 socket 写入数据（发送数据）
        socket_write($this->socket, $command, strlen($command));

        // 得到结果
        $msg = socket_read($this->socket, $this->readLength);
        if ($msg === false) {
            $errorCode = socket_last_error();
            $errorMessage = socket_strerror($errorCode);
            throw new Exception($errorMessage, $errorCode);
        }
        return json_decode(trim($msg), true);
    }

    /**
     * 断开链接
     *
     * @return void
     */
    public function disconnect()
    {
        socket_close($this->socket);
    }
}
