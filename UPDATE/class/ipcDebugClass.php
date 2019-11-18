<?php

class DEBUG{
    public $logFile = "";
    public $mode = 0;
    public $fd = 0;
    public $rslt;
    public $reason;

    public function __construct(){
        
        $file = __DIR__ . "/../../../ipc-debug.cfg";
        $str = file_get_contents($file);
        $data = trim($str);
        $this->mode = $data;
    
        if ($this->mode > 0) {
            $this->logFile = __DIR__ . "/../../LOG/debug.log";
            $this->fd = fopen($this->logFile, "a");
        }
        
        $this->rslt = 'success';
        $this->reason = "DEBUG CONSTRUCTED";
    }

    public function close() {
        if ($this->fd > 0) {
            fclose($this->fd);
            $this->fd = 0;
        }
    }

    public function log($mode, $string) {
        if ($this->logFile != "") {
            if ($this->fd == 0) {
                $this->fd = fopen($this->logFile, "a");
            }

            if ($this->mode > $mode) {
                $timestamp = date('Y-m-d H:i:s');
                $str = "\n$timestamp mode=$mode - $string";
                fwrite($this->fd, $str);
            }
        }
    }

    

}





?>