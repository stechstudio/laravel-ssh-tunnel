<?php namespace STS\Tunneler\Jobs;

class CreateTunnel
{

    /**
     * The Command for checking if the tunnel is open
     * @var string
     */
    protected $ncCommand;

    /**
     * The command for creating the tunnel
     * @var string
     */
    protected $sshCommand;

    /**
     * Simple place to keep all output.
     * @var array
     */
    protected $output = [];

    public function __construct()
    {
        $this->ncCommand = sprintf('%s -z %s %d  > /dev/null 2>&1',
            config('tunneler.nc_path'),
            config('tunneler.local_address'),
            config('tunneler.local_port')
        );

        $this->sshCommand = sprintf('%s -N -i %s -L %d:%s:%d -p %d %s@%s',
            config('tunneler.ssh_path'),
            config('tunneler.identity_file'),
            config('tunneler.local_port'),
            config('tunneler.bind_address'),
            config('tunneler.bind_port'),
            config('tunneler.port'),
            config('tunneler.user'),
            config('tunneler.hostname')
        );
    }


    public function handle(): int
    {
        if ($this->verifyTunnel()){
            return 1;
        }

        $this->createTunnel();

        if ($this->verifyTunnel()){
            return 2;
        }

        throw new \ErrorException(sprintf("Could Not Create SSH Tunnel with command:\n\t%s\nCheck your configuration.",
            $this->sshCommand));
    }


    /**
     * Creates the SSH Tunnel for us.
     */
    protected function createTunnel()
    {
        $this->runCommand(sprintf('%s %s > /dev/null &', config('tunneler.nohup_path'), $this->sshCommand));
        // Ensure we wait long enough for it to actually connect.
        usleep(config('tunneler.wait'));
    }

    /**
     * Verifies whether the tunnel is active or not.
     * @return bool
     */
    protected function verifyTunnel()
    {
        return $this->runCommand($this->ncCommand);
    }

    /**
     * Runs a command and converts the exit code to a boolean
     * @param $command
     * @return bool
     */
    protected function runCommand($command)
    {
        $return_var = 1;
        exec($command, $this->output, $return_var);
        return (bool)($return_var === 0);
    }


}