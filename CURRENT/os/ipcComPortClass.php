<?php
class COMPORT {
    public $com_port;
    public $connect;
    public $timeout;
    public $RD_interval = 100000;

    public $rslt;
    public $reason;

    public function __construct($com_port,$baud, $bits, $stop, $parity, $timeoutSec, $timeoutUsec) {
        if($com_port == NULL) {
            $this->rslt = 'fail';
            $this->reason = "MISSING COM_PORT INFO";
            return;
        }

        //Connect to serial port
        $connect = dio_open("/dev/$com_port", O_RDWR | O_NOCTTY | O_NONBLOCK);
        if($connect === false) {
            $this->rslt = 'fail';
            $this->reason = "UNABLE TO CREATE CONNECTION TO PORT ($com_port)";
            return;
        }
        //if successfully connected
        $this->connect = $connect;
        $this->com_port = $com_port;

        //configure the connnection's parameters
        dio_tcsetattr($this->connect, array(
            'baud'=> $baud,
            'bits'=>$bits,
            'stop'=>$stop,
            'parity'=>$parity
        ));

        //set timeout parameter for 1 sec
        $this->timeout = (float)$timeoutSec + ((float)$timeoutUsec/1000000);
    }

    public function sendCmd($cmd) {
        //send cmd to CPS HW
        $write = dio_write($this->connect, $cmd, strlen($cmd));
        //this function returns # bytes written to descriptor
        if ($write == 0) {
            $this->rslt = "fail";
            $this->reason = "SEND CMD FAILS";
            return;
        }
    }

    //waiting for data from HW with timeout
    public function receiveRsp() {
        $rsp = '';
        $startTime = microtime(true);
        while((microtime(true) - $startTime) < $this->timeout) {
            $data = dio_read($this->connect, 1024);
            //if nothing returned, continue the while loop
            if(trim($data) == "")
                continue;
            else {
                //if some data returned from HW, keep reading until nothing returned, then break out of loops
                while(1) {
                    $rsp .= $data;
                    usleep($this->RD_interval);
                    $data = dio_read($this->connect, 1024);
                    if(trim($data) != ""){
                        continue;
                    }
                    else break;
                }
                break;
            }     
        }

        $this->rslt = 'success';
        $this->reason = 'receive successfully';
        return $rsp;
    }

    public function endConnection() {
        dio_close($this->connect);
    }




}


?>