<?php  

//initialize parameter
//change working dir to the file location
chdir(__DIR__);

///////////////////////////////////////
// define ERROR CODE
const CMD_EXEC_FAIL = 2;
const CPS_LOOP_RUNNING = 1;
const CPS_LOOP_NOT_RUNNING = 0;

//////////////////////////////////////
//change working dir to the os folder
// $pwd = __DIR__;
// chdir("$pwd/CURRENT/os");
// echo getcwd ();

$result = checkRunning();
if($result == CPS_LOOP_NOT_RUNNING) {
    $pid = exec("php ipcCps.php > /dev/null 2>&1 & echo $!", $output, $return);
}
else if($result == CMD_EXEC_FAIL) {
    //log to debug file
}



//============Supporting functions================//

function checkRunning() {
    //check if the process "php ipcCpsloop.php 1 9001" exists
    exec("ps -ef | grep '\<php ipcCps.php\>'", $output, $return);
    if($return !==0) {
        return CMD_EXEC_FAIL;
    }
    for($i=0; $i<count($output); $i++) {
       
        $processArray = preg_split("/[\s]+/", $output[$i], 8);
        $cmd = $processArray[7];
        if($cmd === "php ipcCps.php"){
            return CPS_LOOP_RUNNING;
        }
    }
    return CPS_LOOP_NOT_RUNNING;
}




?>