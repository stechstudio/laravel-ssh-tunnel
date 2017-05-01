<?php namespace STS\Supervisor\Daemon;

use Illuminate\Queue\Listener as QueueListener;
use Symfony\Component\Process\Process;

pcntl_async_signals(true);

class Listener extends QueueListener{

    /**
     * Set to true if we catch a signal and need to
     * stop after we finish what we are doing.
     *
     * @var bool
     */
    protected $stopGracefully = false;


    /**
     * Create a new queue listener.
     *
     * @param  string $commandPath
     */
    public function __construct($commandPath)
    {
        if (!function_exists('pcntl_signal')){
            throw new \RuntimeException('You must have PCNTL functions to use this Listener');
        }
        parent::__construct($commandPath);

        // Install the signal handler
        $this->setSignalHandler(SIGHUP);  /* Hangup (POSIX).             */
        $this->setSignalHandler(SIGINT);  /* Interrupt (ANSI).           */
        $this->setSignalHandler(SIGQUIT); /* Quit (POSIX).               */
        $this->setSignalHandler(SIGABRT); /* Abort (ANSI).               */
        $this->setSignalHandler(SIGTERM); /* Termination (ANSI).         */
        $this->setSignalHandler(SIGTSTP); /* Keyboard stop (POSIX).      */
    }

    /**
     * Sets the signal to be handled by either the closure or the built in
     * signal handler.
     *
     * @param int $signal
     * @param callable|null $closure
     *
     * @return bool
     */
    public function setSignalHandler(int $signal, callable $closure = null){
        if (empty($closure)){
            return pcntl_signal($signal, array($this, 'sigHandler'));
        }
        return pcntl_signal($signal, $closure);
    }

    /**
     * Built in Signal Handler.
     * @param int $signo
     */
    public function sigHandler( int $signo ){
        $this->handleWorkerOutput('warn', sprintf("Signal %d Caught, asking the daemon to stop gracefully.", $signo));
        $this->stopGracefully = true;
    }

    /**
     * Log that we are done and exiting.
     */
    public function gracefulStop(){
        $this->handleWorkerOutput('warn', "Work done, exiting now.");
        $this->stop();
    }
    /**
     * Run the given process.
     *
     * @param  Process  $process
     * @param  int  $memory
     * @return void
     */
    public function runProcess(Process $process, $memory)
    {
        try {
            $process->run(function ($type, $line) {
                $this->handleWorkerOutput($type, $line);
            });
        }catch (\Exception $e){
            dd($e);
        }

        // If we caught a signal and need to stop gracefully, this is the place to
        // do it.
        pcntl_signal_dispatch();
        if ($this->stopGracefully){
            $this->gracefulStop();
        }
        // Once we have run the job we'll go check if the memory limit has been
        // exceeded for the script. If it has, we will kill this script so a
        // process manager will restart this with a clean slate of memory.
        if ($this->memoryExceeded($memory)) {
            $this->stop();
        }
    }

    /**
     * Handle output from the worker process.
     *
     * @param  string  $type
     * @param  string  $line
     * @return void
     */
    protected function handleWorkerOutput($type, $line)
    {
        if (isset($this->outputHandler)) {
            call_user_func($this->outputHandler, $type, $line);
        }
    }
}
