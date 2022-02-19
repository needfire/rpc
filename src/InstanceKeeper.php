<?php

namespace majorbio\rpc;

class InstanceKeeper
{
    private int $lastMessageTime = 0;

    private array $objects = [];

    public function __construct()
    {
        $this->updateLastMessageTime();
    }

    public function updateLastMessageTime()
    {
        $this->lastMessageTime = time();
    }

    public function getLastMessageTime()
    {
        return $this->lastMessageTime;
    }

    public function add(string $name = '', object $instance)
    {
        $this->objects[$name] = $instance;
    }

    public function has(string $name = '')
    {
        return isset($this->objects[$name]);
    }

    public function get(string $name = '')
    {
        return $this->objects[$name];
    }
}
