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

        $this->ncCommand = sprintf('%s -vz %s %d  > /dev/null 2>&1',
            config('tunneler.nc_path'),
            config('tunneler.local_address'),
            config('tunneler.local_port')
        );

        $this->bashCommand = sprintf('timeout 1 %s -c \'cat < /dev/null > /dev/tcp/%s/%d\' > /dev/null 2>&1',
            config('tunneler.bash_path'),
            config('tunneler.local_address'),
            config('tunneler.local_port')
        );

        $this->sshCommand = sprintf('%s %s %s -N -i %s -L %d:%s:%d -p %d %s@%s',
            config('tunneler.ssh_path'),
            config('tunneler.ssh_options'),
            config('tunneler.ssh_verbosity'),
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
        if ($this->verifyTunnel()) {
            return 1;
        }

        $this->createTunnel();

        $tries = config('tunneler.tries');
        for ($i = 0; $i < $tries; $i++) {
            if ($this->verifyTunnel()) {
                return 2;
            }

            // Wait a bit until next iteration
            usleep(config('tunneler.wait'));
        }

        throw new \ErrorException(sprintf("Could Not Create SSH Tunnel with command:\n\t%s\nCheck your configuration.",
            $this->sshCommand));
    }


    /**
     * Creates the SSH Tunnel for us.
     */
    protected function createTunnel()
    {
        $this->runCommand(sprintf('%s %s >> %s 2>&1 &',
            config('tunneler.nohup_path'),
            $this->sshCommand,
            config('tunneler.nohup_log')
        ));
        // Ensure we wait long enough for it to actually connect.
        usleep(config('tunneler.wait'));
    }

    /**
     * Verifies whether the tunnel is active or not.
     * @return bool
     */
    protected function verifyTunnel()
    {
        if (config('tunneler.verify_process') == 'bash') {
            return $this->runCommand($this->bashCommand);
        }

        return $this->runCommand($this->ncCommand);
    }

    /*
     * Use pkill to kill the SSH tunnel
     */

    public function destroyTunnel(){
        $ssh_command = preg_replace('/[\s]{2}[\s]*/',' ',$this->sshCommand);
        return $this->runCommand('pkill -f "'.$ssh_command.'"');
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
