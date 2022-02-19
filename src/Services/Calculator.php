<?php

namespace majorbio\rpc\services;

class Calculator
{
    public int $a = 1;
    public int $b = 2;

    public function setA(int $a = 0)
    {
        $this->a = $a;
        // echo '设置A=' . $a . PHP_EOL;
    }

    public function setB(int $b = 0)
    {
        $this->b = $b;
        // echo '设置B=' . $b . PHP_EOL;
    }

    public function add()
    {
        $rs = $this->a + $this->b;
        // echo $this->a . ' + ' . $this->b . PHP_EOL;
        // return $rs;
        return ['code' => 0, 'message' => 'calculator', 'data' => [$rs]];
    }
}
