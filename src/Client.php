<?php

namespace majorbio\rpc;

use Exception;
use majorbio\helper\RS;

class Client
{
    private $socket = null;
    // 服务器
    protected string $host = '';
    // 端口
    protected int $port = 0;
    // 如果没有得到预期结果，是否要抛出异常
    protected bool $throwException = true;

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

        // 发送超时 10 秒
        // socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 2, "usec" => 0));

        // 接收超时 15 秒
        // socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => 30, "usec" => 0));

        // 连接
        $conn = socket_connect($this->socket, $this->host, $this->port);
        if ($conn === false) {
            $errorCode = socket_last_error();
            $errorMessage = socket_strerror($errorCode);
            throw new Exception($errorMessage, $errorCode);
        }
    }

    /**
     * 获取连接
     *
     * @return void
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * 开启抛出异常
     *
     * @return void
     */
    public function enableThrowException()
    {
        $this->throwException = true;
    }

    /**
     * 关闭抛出异常
     *
     * @return void
     */
    public function disableThrowException()
    {
        $this->throwException = false;
    }

    /**
     * 调用 RPC 方法
     *
     * @param string $class 类
     * @param string $method 方法
     * @param array $params 参数：[参数1, 参数2, ...]
     * 
     * @return mixed
     */
    public function invoke(string $class = '', string $method = '', array $params = [])
    {
        // 打包
        $dataPackageString = $this->encode(new RS(0, 'ok', [
            'class' => trim($class),
            'method' => trim($method),
            'params' => $params,
            'dateTime' => date('Y-m-d H:i:s'),
        ]));
        // dd($dataPackageString, $this->decode($dataPackageString));

        // 向 socket 写入数据（发送数据）
        socket_write($this->socket, $dataPackageString, strlen($dataPackageString));

        // 头部标识
        $readHead = true;
        // 第一次读长（头部）
        $readLength = 10;
        // 包头
        $head = '';
        // 包体，最终是 {"code":0,"message":"","data":null} 数据结构
        $body = '';

        // 循环读取
        while ($data = socket_read($this->socket, $readLength)) {

            // 异常
            if ($data === false) {
                $head = $body = '';
                $errorCode = socket_last_error();
                $errorMessage = socket_strerror($errorCode);
                throw new Exception($errorMessage, $errorCode);
            }

            // 处理读取到的数据
            if ($readHead) {

                // 读取“包头”
                $head .= $data;

                // 计算
                $thisReadLength = strlen($head);
                if ($thisReadLength === $readLength) {
                    // 本次接收到的数据长度 === 期望读长，则说明接收完毕
                    // 下一个循环读取的不再是“包头”了
                    $readHead = false;
                    // 解析包总长（ps 如果包总长太大的话，就要分段去读取了，比如每次读取 1M 数据。）
                    $totalLength = base_convert(substr($head, 0, 10), 10, 10);
                    $totalLength = intval($totalLength);
                    $head = '';
                    // 设置包体的读长
                    $readLength = $totalLength - 10;
                } else {
                    // 本次接收到的数据长度 < 期望读长，则计算出包头还剩多少
                    $readLength -= $thisReadLength;
                }
                //

            } else {

                // 读取“包体”

                // 记录本次读取的数据
                $body .= $data;

                // 计算
                $thisReadLength = strlen($data);
                if ($thisReadLength === $readLength) {
                    // 本次接收到的数据长度 === 期望读取的长度，则说明接收完毕
                    break;
                } else {
                    // 本次接收到的数据长度 < 期望读取的长度，则计算出还剩多少
                    $readLength -= $thisReadLength;
                }
            }
        }

        // 如果连接断开
        if ($data === false) {
            throw new Exception('连接断开了。', 10555);
        }

        // 解压
        $result = $this->decode($body);
        unset($body);

        // 是否要抛异常
        if ($result->code > 0 && $this->throwException) {
            throw new Exception($result->message, $result->code);
        }

        // 返回数据
        return $result->data;
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
     * @param RS $buffer
     * 
     * @return string
     */
    public function encode(RS $buffer): string
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
     * @return RS
     */
    public function decode(string $buffer): RS
    {
        $obj = json_decode($buffer);
        $buffer = new RS($obj->code ?? 10404, $obj->message ?? '没有信息', $obj->data ?? null);
        unset($obj);
        return $buffer;
    }
}
