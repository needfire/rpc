<?php

namespace majorbio\rpc\services;

class Month
{
    public string $fx = 'north';
    public function setFx($w = 'north')
    {
        $this->fx = $w;
    }
    public function spring()
    {
        return ['code' => 0, 'message' => $this->fx . ' Month', 'data' => ($this->fx == 'north') ? [1, 2, 3] : [7, 8, 9]];
    }
    public function winter()
    {
        return ['code' => 0, 'message' => $this->fx . 'Month', 'data' => ($this->fx == 'north') ? [10, 11, 12] : [4, 5, 6]];
    }
}
