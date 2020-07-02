<?php
namespace Faed\Wx;

use Illuminate\Support\ServiceProvider;

class WxServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //配置文件
        $this->publishes([
            $this->configPath() => config_path('wx.php'),
        ]);
    }

    public function register()
    {
        $this->mergeConfigFrom($this->configPath(), 'wx');
    }

    /**
     * Set the config path
     *
     * @return string
     */
    protected function configPath()
    {
        return __DIR__ . '/config/wx.php';
    }
}
