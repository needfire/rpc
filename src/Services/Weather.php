<?php

namespace majorbio\rpc\services;

class Weather
{
    public string $city = '北京';

    public function setCity(string $city = '上海')
    {
        $this->city = $city;
    }

    public function get()
    {
        return ['code' => 0, 'message' => 'weather', 'data' => [$this->city . '天气晴朗']];
    }
}
