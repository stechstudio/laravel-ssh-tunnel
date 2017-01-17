<?php namespace	stechstudio\Tunneler;

use Illuminate\Support\ServiceProvider;
use stechstudio\Tunneler\Console\TunnelerCommand;
use stechstudio\Tunneler\Jobs\CreateTunnel;


class TunnelerServiceProvider extends ServiceProvider{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Default path to configuration
     * @var string
     */
    protected $configPath = __DIR__ . '/config/tunneler.php';


    public function boot()
    {
        // helps deal with Lumen vs Laravel differences
        if (function_exists('config_path')) {
            $publishPath = config_path('tunneler.php');
        } else {
            $publishPath = base_path('config/tunneler.php');
        }

        $this->publishes([$this->configPath => $publishPath], 'config');

        if (config('tunneler.on_boot')){
            dispatch(new CreateTunnel());
        }
    }

    public function register()
    {
        if ( is_a($this->app,'Laravel\Lumen\Application')){
            $this->app->configure('tunneler');
        }
        $this->mergeConfigFrom($this->configPath, 'tunneler');

        $this->app['command.tunneler.activate'] = $this->app->share(
            function ($app) {
                return new TunnelerCommand();
            }
        );

        $this->commands('command.tunneler.activate');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('command.tunneler.activate');
    }

}