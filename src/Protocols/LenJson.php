<?php

namespace Protocols;

use majorbio\helper\RS;

/**
 * 数据包样本要求：
 * a. 首部固定 10 个字节长度用来保存整个数据包长度，位数不够补 0
 * b. 数据格式为 json 字符串
 * 
 * 例如：
 * 0000000068{"code":0,"message":"ok","data":["hello world, hello u!"]}
 */
class LenJson
{
    /**
     * 检查包的完整性
     * 如果能够得到包长，则返回包的在 buffer 中的长度，否则返回 0 继续等待数据
     * 如果协议有问题，则可以返回 false，当前客户端连接会因此断开
     * 
     * @param string $buffer
     * 
     * @return int
     */
    public static function input(string $buffer): int
    {
        if (strlen($buffer) < 10) {
            // 不够 10 字节，返回 0 继续等待数据
            return 0;
        }
        // 返回包长
        return base_convert(substr($buffer, 0, 10), 10, 10);
    }

    /**
     * 打包，当向客户端发送数据的时候会自动调用
     * 
     * @param RS $buffer
     * 
     * @return string
     */
    public static function encode(RS $buffer): string
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
    public static function decode(string $buffer): RS
    {
        // 去掉前面 10 个字节的包头，转化为数组
        $tmp = json_decode(substr($buffer, 10), true);
        $buffer = new RS($tmp['code'] ?? 10404, $tmp['message'] ?? '没有信息', $tmp['data'] ?? null);
        unset($tmp);
        return $buffer;
    }
}
