#!/usr/bin/php
<?php
/* daemon sample */
require "class.daemon.php";

function childJob(&$daemon) {
        $daemon->childObject->stack();
}

function childStart(&$daemon) {
        $dummy=new dummy();
        $dummy->setuniq();
        echo "Starting new child with: {$dummy->uniqid}\n";
        $daemon->childObject=$dummy;
        return TRUE;
}

function childDying(&$daemon,$pid) {
        echo "Child with: {$daemon->currentObjects[$pid]->uniqid} terminating\n";
}

class dummy {

        public $uniqid=NULL;
        function setuniq() {
                $this->uniqid=uniqid();
        }
        function stack() {
                while(1) sleep(2000);
        }

}

$job=new Daemon();
$job->procName='phpdaemon';
$job->bind('onLauncher','childStart');
$job->bind('onLaunchJob','childJob');
$job->bind('onChildTerminated','childDying');
$job->run();
