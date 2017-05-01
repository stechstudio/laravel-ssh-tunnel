<?php namespace	STS\Supervisor;

use Illuminate\Support\ServiceProvider;
use STS\Supervisor\Console\DaemonCommand;
use STS\Supervisor\Daemon\Listener;

class DaemonServiceProvider extends ServiceProvider {
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

        $this->registerListener();
    }

    /**
     * Register the queue listener.
     *
     * @return void
     */
    protected function registerListener()
    {
        $this->registerListenCommand();

        $this->app->singleton('queue.daemon.listener', function ($app) {
            return new Listener($app->basePath());
        });
    }

    /**
     * Register the queue listener console command.
     *
     * @return void
     */
    protected function registerListenCommand()
    {
        $this->app->singleton('command.queue.daemon', function ($app) {
            return new DaemonCommand($app['queue.daemon.listener']);
        });

        $this->commands('command.queue.daemon');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'queue.supervisor.listener', 'command.queue.daemon'
        ];
    }
}
