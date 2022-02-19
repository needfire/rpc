<?php

namespace majorbio\rpc\Providers;

use Illuminate\Support\ServiceProvider;

class RpcServiceProvider extends ServiceProvider
{
    protected $defer = true; // 延迟加载服务

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../Publishes/config.php' => config_path('rpcc.php'),
            ]);
        }
    }
}
