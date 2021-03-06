<?php

namespace majorbio\rpc\Providers;

use Illuminate\Support\ServiceProvider;

class RpcServiceProvider extends ServiceProvider
{
    // 延迟加载服务
    protected $defer = true;

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
                __DIR__ . '/../Publishes/config.php' => config_path('rpc.php'),
                __DIR__ . '/../Publishes/Rpc.php' => app_path('Console/Commands/Rpc.php'),
            ]);
        }
    }
}
