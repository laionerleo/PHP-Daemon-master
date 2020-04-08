<?php
/*version 1.02
1.02 onLauncher puede devolver false, entonces no genera hijo. Util para controlar desde ese callback si debo hacer algo nuevo
*/
declare(ticks=1);
require "class.pidfile.php";
class Daemon{

    public $maxProcesses = 10;
    public $childsProcName = NULL;
    public $keepWaiting=TRUE;
    public $childObject=NULL;
    public $pidFileDir="/var/run";
    public $procName="daemon";
    public $multiInstances=FALSE;

    protected $currentJobs = array();
    protected $signalQueue=array();
    protected $parentPID;
    private $listeners = array();

    public function __construct($multi=FALSE){
        $this->multiInstances=$multi;
        $this->parentPID = getmypid();
        pcntl_signal(SIGCHLD, array($this, "childSignalHandler"));
        pcntl_signal(SIGHUP, array($this, "hupSignalHandler"));
        pcntl_signal(SIGTERM, array($this, "termSignalHandler"));
    }

    public function run(){
        $pidfile = new pidfile($this->pidFileDir,$this->procName);
        if($pidfile->is_already_running() && $this->multiInstances==FALSE) {
                $this->fire('onMultiInstance',array(&$this));
                exit;
        }
        if(is_null($this->childsProcName)) $this->childsProcName="{$this->procName}_child.php";
        while(TRUE===$this->keepWaiting) {
            $jobID = rand(0,10000000000000);
            while(count($this->currentJobs) >= $this->maxProcesses) sleep(1);
            $launched = $this->launchJob($jobID);
        }

        while(count($this->currentJobs)) sleep(1);
        $this->fire('onRunTerminated',array(&$this));
    }

    protected function launchJob($jobID){
        if(FALSE===$this->fire('onLauncher',array(&$this))) {
                usleep(20000);
                return false;
        }
        $pid = pcntl_fork();
        if($pid == -1){
            $this->fire('onLaunchJobError',array(&$this));
            return false;
        }
        else if ($pid){
            $this->currentObjects[$pid]=$this->childObject;
            $this->currentJobs[$pid] = $jobID;

            if(isset($this->signalQueue[$pid])){
                $this->childSignalHandler(SIGCHLD, $pid, $this->signalQueue[$pid]);
                unset($this->signalQueue[$pid]);
            }
        }
        else{
            unset($this->currentObjects);
            $exitStatus = 0;
            setproctitle($this->childsProcName);
            $this->fire('onLaunchJob',array(&$this));
            exit($exitStatus);
        }
        return true;
    }

    public function hupSignalHandler() {

        if( $this->parentPID == getmypid()) {
                $this->fire('daemonHupSignalReceived',array(&$this));
                foreach($this->currentJobs as $pid => $id) {
                        posix_kill($pid,SIGUSR1);
                }
                $this->fire('daemonHupSignalProcesed',array(&$this));
        }

    }

    public function termSignalHandler() {

        if( $this->parentPID == getmypid()) {
                $this->fire('daemonTermSignalReceived',array(&$this));
                foreach($this->currentJobs as $pid => $id) {
                        $this->fire('onChildTerminated',array(&$this,$pid));
                        posix_kill($pid,SIGKILL);
                }
                $this->fire('daemonTerminating',array(&$this));
                exit(0);
        }
    }

    public function childSignalHandler($signo, $pid=null, $status=null){

        if(!$pid) $pid = pcntl_waitpid(-1, $status, WNOHANG);
        while($pid > 0){
            if($pid && isset($this->currentJobs[$pid])){
                $exitCode = pcntl_wexitstatus($status);
                $this->fire('onChildTerminated',array(&$this,$pid));
                unset($this->currentObjects[$pid]);
                unset($this->currentJobs[$pid]);
            }
            else if($pid) $this->signalQueue[$pid] = $status;
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
        return true;
    }
    public function bind($event, $callback)
    {
        $this->listeners[$event][] = $callback;
    }

    public function fire($event, array $parameters)
    {
        if ( ! empty($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                call_user_func_array($listener, $parameters);
            }
        }
    }
}
