<?php

namespace majorbio\rpc;

class InstanceKeeper
{
    /**
     * 最后调用时间
     *
     * @var integer
     */
    private int $lastInvokeTime = 0;

    /**
     * 实例
     *
     * @var array
     */
    private array $instances = [];

    /**
     * 构造
     */
    public function __construct()
    {
        $this->updateLastInvokeTime();
    }

    /**
     * 更新最后调用时间
     *
     * @return void
     */
    public function updateLastInvokeTime()
    {
        $this->lastInvokeTime = time();
    }

    /**
     * 获取最后调用时间
     *
     * @return int
     */
    public function getLastInvokeTime(): int
    {
        return $this->lastInvokeTime;
    }

    /**
     * 设置实例
     *
     * @param string $name
     * @param object $instance
     * 
     * @return void
     */
    public function set(string $name = '', object $instance)
    {
        $this->instances[$name] = $instance;
    }

    /**
     * 实例是否存在
     *
     * @param string $name
     * 
     * @return boolean
     */
    public function has(string $name = ''): bool
    {
        return isset($this->instances[$name]);
    }

    /**
     * 获取实例
     *
     * @param string $name
     * 
     * @return object
     */
    public function get(string $name = ''): object
    {
        return $this->instances[$name];
    }
}
