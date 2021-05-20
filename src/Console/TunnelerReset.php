<?php
namespace STS\Tunneler\Console;

use Illuminate\Console\Command;
use STS\Tunneler\Jobs\CreateTunnel;

class TunnelerReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tunneler:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Destroy and reconnect the SSH tunnel';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tunnel = new CreateTunnel();
        $tunnel->destroyTunnel();

        \Artisan::call('tunneler:activate');
    }
}
