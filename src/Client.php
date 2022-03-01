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

    /**
     * 构造函数
     *
     * @param string $host
     * @param integer $port
     * 
     * @throws Exception
     */
    public function __construct(string $host = '127.0.0.1', int $port = 30106)
    {
        // 参数
        $this->host = $host;
        $this->port = $port;

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
        // 打包
        $dataPackageString = $this->encode([
            'class' => trim($class),
            'method' => trim($method),
            'params' => $params,
            'dateTime' => date('Y-m-d H:i:s'),
        ]);
        // dd($dataPackageString, $this->decode($dataPackageString));

        // 向 socket 写入数据（发送数据）
        socket_write($this->socket, $dataPackageString, strlen($dataPackageString));

        // 头部标识
        $readHead = true;
        // 第一次头部读长
        $readLength = 10;
        // 数据体
        $body = '';

        // 循环读取
        while ($data = socket_read($this->socket, $readLength)) {

            // 异常
            if ($data === false) {
                $errorCode = socket_last_error();
                $errorMessage = socket_strerror($errorCode);
                throw new Exception($errorMessage, $errorCode);
            }

            // 读取包头
            if ($readHead) {
                $readHead = false;
                // 解析包总长（ps 如果包总长太大的话，就要分段去读取了，比如每次读取 1M 数据。）
                $totalLength = base_convert(substr($data, 0, 10), 10, 10);
                $totalLength = intval($totalLength);
                // var_dump($totalLength);
                // 不够
                if ($totalLength < 10) {
                    $data = $this->rs(0, 'ok');
                    break;
                }
                // 重新赋值长度
                $readLength = $totalLength - 10;
                //
                continue;
            }

            // 保存本次读取的数据
            $body .= $data;

            // 本次接收到的数据长度 === 期望读取的长度
            $thisReadLength = strlen($data);
            if ($thisReadLength === $readLength) {
                // 证明接受完毕，停止接受
                break;
            }

            // 本次接收到的数据长度 < 期望读取的长度，则计算出还剩多少
            $readLength -= $thisReadLength;
        }
        // 解压
        return $this->decode($body);
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

    /**
     * 打包，当向客户端发送数据的时候会自动调用
     * 
     * @param string $buffer
     * 
     * @return string
     */
    public function encode($buffer)
    {
        // 包体
        $buffer = json_encode($buffer);
        // 总长度 = 包头 + 包体
        $totalLength = 10 + strlen($buffer);
        // 转化为字符串，不够 10 位则左补 0
        $totalLengthString = str_pad($totalLength, 10, '0', STR_PAD_LEFT);
        // 返回数据包
        return $totalLengthString . $buffer;
    }

    /**
     * 解包，当接收到的数据字节数等于 input 返回的值（大于0的值）自动调用
     * 并传递给 onMessage 回调函数的 $data 参数
     * 
     * @param string $buffer
     * 
     * @return string
     */
    public function decode($buffer)
    {
        return json_decode($buffer, true);
    }

    /**
     * 返回数据结构
     *
     * @param integer $code
     * @param string $message
     * @param mixed $data
     * 
     * @return string
     */
    public function rs(int $code = 0, string $message = '', $data = []): string
    {
        return json_encode([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
