<?php
///Notice: run this file with sudo permission in order to execute ipcloop by root user

//change working directory to the script location
chdir(__DIR__);
$dir = __DIR__;
//check the current cron job configured
$addCpsCronJob = true;
$cpsCronJob = "'* * * * * php $dir/ipcCheckCpsloop.php'";

exec("crontab -l", $output, $result);
for($i=0; $i<count($output); $i++) {
    if($output[$i] == $cpsCronJob) {
        $addCpsCronJob = false;
        break;
    }
}

//if there is no cron job for cpsloop => create one
if($addCpsCronJob) {
    exec("crontab -l > ipcCron",$output, $result);
    exec("echo $cpsCronJob >> ipcCron",$output, $result);
    exec("crontab ipcCron",$output, $result);
    exec("rm ipcCron",$output, $result);
}

?>