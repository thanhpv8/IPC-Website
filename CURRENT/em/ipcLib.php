<?php

function debugLog($mode, $log) {
    global $debugObj;
    if ($debugObj != null)
        if(is_string($log))
            $debugObj->log($mode, $log);
        else if(is_array($log)) {
            $logString="";
            foreach($log as $key=>$value) {
                if(is_array($value)) $value=convertArray2String($value,"|");
                $logString .="$key:$value\n";
            }
            $debugObj->log($mode, $logString);
        }    
}

function convertArray2String($array, $delimiter=NULL) {
    $stringVal = "";
    foreach($array as $key=>$value){
        if(is_array($value)) $value = "(".convertArray2String($value,",").")";
        end($array);
        if ($key === key($array)) $stringVal .= "$value";
        else $stringVal .= "$value $delimiter "; 
    }
    return $stringVal;
}

function convertRspToKeyValueArray($rsp) {
    $rspArray=[];
    $rsp = substr($rsp, 1, -1);
    $rspExtracted = explode(',', $rsp);

    foreach($rspExtracted as $parameter) {
        $paraExtract = explode("=", $parameter,2);
        if(count($paraExtract) === 1){
            $rspArray[$paraExtract[0]] = NULL;
        }
        else {
            $rspArray[$paraExtract[0]] = $paraExtract[1];
        }
    }
    return $rspArray;
}

function lib_getIpcTime() {
    
    $wcObj = new WC();

    // establish ipc_time
    date_default_timezone_set("UTC");
    $utc_tz = date_default_timezone_get();
    $utc_t = time();
    $ipc_tz_offset = $wcObj->tz * 3600;
    $ipc_t = $utc_t + ($wcObj->tz * 3600);

    $secondSundayInMarch = date("d-M-Y", strtotime("second sunday " . date('Y') . "-03"));
    $firstSundayInNovember = date("d-M-Y", strtotime("first sunday " . date('Y') . "-11"));
    $dst_begin_t = strtotime($secondSundayInMarch) + $ipc_tz_offset;
    $dst_end_t = strtotime($firstSundayInNovember) + $ipc_tz_offset;

    $ipc_tzone = $wcObj->tzone;
    if ($ipc_t > $dst_begin_t && $ipc_t < $dst_end_t) {
        if (date('I', $ipc_t) == 0) {
            $ipc_t = $ipc_t + 3600;
            $ipc_tzone = substr_replace($wcObj->tzone,"DT",-2);
        }
    }
    $ipc_time = date("Y-m-d H:i:s", $ipc_t);
    return $ipc_time;
}

function lib_IsTimedOut($date, $timeLimit) {
    //global $local_timezone;

    $local_timezone = date_default_timezone_get();
    $dt = new DateTime("now", new DateTimeZone($local_timezone));
    $currentTime = $dt->format('U');
    $lt = new DateTime($date, new DateTimeZone($local_timezone));
    $prevTime   = $lt->format('U');
    $timediff     = ($currentTime - $prevTime);
    if ($timediff > $timeLimit) {
        return TRUE;
    }
    return FALSE;
}

//logout user if exceeds idle_to
function lib_ValidateUser($userObj) {
    global $local_timezone;

    if ($userObj->uname != 'ADMIN' && $userObj->uname != 'SYSTEM') {
        $refObj = new REF();
        // if time since last action exceeds user_idle_to then logout user
        // if user_idle_to is 0/empty/null, set value to default, if default is 0/empty/null, set value = 45
        $userIdleInfo = $refObj->ref['user_idle_to'];
        if($userIdleInfo == 0){
            $userIdleInfo = $refObj->default['user_idle_to'];
            if($userIdleInfo == 0)
                $userIdleInfo = 45;
        }
    
        $idle_to = $userIdleInfo * 60;

        if ($userObj->stat == 'ACTIVE') {
            if (lib_IsTimedOut($userObj->login, $idle_to)) {
                $userObj->updateLogout();
                $result['rslt'] = 'fail';
                $result['reason'] = 'SESSION HAS TIMED OUT';
                $result['rows'] = [];
                return $result;
            }
            else {

                $userObj->updateLogin();
                $result['rslt'] = 'success';
                $result['reason'] = 'USER IS ACTIVE';
                $result['rows'] = [];
                return $result;
            }
        }
        else {
            if ($userObj->stat == 'INACTIVE') {
                $result['reason'] = 'SESSION HAS TIMED OUT';
            }
            else {
                $result['reason'] = 'USER IS ' . $userObj->stat;
            }
            $result['rslt'] = 'fail';
            $result['rows'] = [];
            return $result;
        }
    }
    else {
        $result['rslt'] = 'success';
        $result['reason'] = 'USER IS ACTIVE';
        $result['rows'] = [];
        return $result;
    }
}

function lib_scanUsersTimeOut() {
    $userList = new USERS();
    $userList->queryByName('','');
    $refObj = new REF();

    $len = count($userList->rows);
    for ($i=0; $i<$len; $i++) {
        if (lib_IsTimeout($userList->rows[$i]['login'], $refObj->rows[0]['disable_to'])) {
            if ($userList->rows[$i]['ugrp'] != 'ADMIN') {
                $userList->disableUserByUname($userList->rows[$i]['uname']);
            }
        }

    }
}

function lib_inactiveUsers() {
    $usersObj = new USERS();
    $usersObj->queryByUName('','');
    $usersList = $usersObj->rows;
    for ($i=0; $i<count($usersList); $i++) {
        $postRq = new POST_REQUEST();
        $url = 'ipcDispatch.php';
        $params = array("act"=>"logout", "api"=>"ipcLogout", "user"=>$usersList[$i]['uname']);
        $postRq->asyncPostRequest($url,$params);
    }
}

function lib_inactiveNonAdminUsers() {
    $usersObj = new USERS();
    $usersObj->queryByUName('','');
    $usersList = $usersObj->rows;
    for ($i=0; $i<count($usersList); $i++) {
        if($usersList[$i]["ugrp"] !== "ADMIN") {
            $postRq = new POST_REQUEST();
            $url = 'ipcDispatch.php';
            $params = array("act"=>"logout", "api"=>"ipcLogout", "user"=>$usersList[$i]['uname']);
            $postRq->asyncPostRequest($url,$params);
        }
        
    }
}


// function libValidat
function libFilterUsers($userLogginObj, $rows) {
    $filteredRows = [];
    
    for ($i = 0; $i < count($rows); $i++) {
        if($userLogginObj->uname == $rows[$i]['uname']) {
            $filteredRows[] = $rows[$i];
        }
        else {
            if (strtoupper($rows[$i]['uname']) != "ADMIN" && strtoupper($rows[$i]['uname']) != "SYSTEM") {
                // if userLoggin is higher than rows user
                if($userLogginObj->grp <= $rows[$i]['grp']) {
                    if($userLogginObj->ugrp == "ADMIN") {
                        // if ($rows[$i]['ugrp'] == "ADMIN") {
                        //     $rows[$i]['ssn'] = "";
                        // } 
                        $filteredRows[] = $rows[$i];
                    }
                    else {
                        $rows[$i]['ssn'] = "";
                        $filteredRows[] = $rows[$i];
                    }
                }
            }
        }
    }
    return $filteredRows;
}

// function checkWcStatus() {
//     $wcObj = new WC();
//     if ($wcObj->rslt == "fail") {
//         $result["rslt"] = "fail";
//         $result["reason"] = "CHECK_WC_STATUS: ".$wcObj->reason;
//         return $result;
//     }

//     if($wcObj->getIpcStat() == "OOS") {
//         $result["rslt"] = "fail";
//         $result["reason"] = "CHECK_WC_STATUS: IPC SYSTEM IS OOS";
//         return $result;
//     }

//     $result["rslt"] = "success";
//     $result["reason"] = "CHECK_WC_STATUS: IPC SYSTEM IS INS";
//     return $result;
// }


//=========================SW UPDATE==================================//

function removeFiles($directory){
    foreach(glob("{$directory}/*") as $file)
    {
        if(is_dir($file)) { 
            removeFolder($file);
        } else {
            if(!unlink($file)) {
                throw new Exception("UNABLE_DELETE_FILE");
            }
        }
    }
}

function removeFolder($directory) {
    foreach(glob("{$directory}/*") as $file)
    {
        if(is_dir($file)) { 
            removeFolder($file);
        } else {
           if(!unlink($file)) {
               throw new Exception("UNABLE_DELETE_FILE");
           }
        }
    }
    if(!rmdir($directory)) {
        throw new Exception("UNABLE_DELETE_FOLDER");
    }
}

function changePermissionFiles($directory, $permission){
    if(!chmod($directory, $permission)) {
        throw new Exception("UNABLE_CONFIGURE_PERMISSION_FOLDER");
    }
    foreach(glob("{$directory}/*") as $file)
    {
        if(is_dir($file)) { 
            // chmod($file, 0777);
            changePermissionFiles($file, $permission);
        } else {
            if(!chmod($file, $permission)) {
                throw new Exception("UNABLE_CONFIGURE_PERMISSION_FILE");
            }
        }
    }
}

function moveFiles($fromDir,$toDir) { 

    ////Note: need to create folder before transfer files
    $dir = opendir($fromDir); 
    if (!file_exists($toDir)) {
        throw new Exception("FOLDER_NOT_EXIST");
    }
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if (is_dir($fromDir .'/'. $file) ) { 
                moveFiles($fromDir.'/'.$file, $toDir .'/'.$file); 
            } 
            else { 
                if(!copy($fromDir.'/'.$file, $toDir.'/'.$file)) {
                    throw new Exception("UNABLE_COPY_FILE");
                } 
            } 
        } 
    } 
    closedir($dir); 
} 


function updateFolderDir() {
    $running = "";
    // $new = "";
    // $update = "";
    // $previous = "";

    $file = fopen("../../bhd.cfg", "r");
    if ($file) {
        while (($line = fgets($file)) !== false) {
            // process the line read.
            $lineExtract = explode(":", $line);
            if($lineExtract[0] == "RUNNING") 
                $running = str_replace(array("\r\n", "\r", "\n", "\t"),"",$lineExtract[1]);
            // else if($lineExtract[0] == "NEW") 
            //     $new = str_replace(array("\r\n", "\r", "\n", "\t"),"",$lineExtract[1]);
            // else if($lineExtract[0] == "UPDATE") 
            //     $update = str_replace(array("\r\n", "\r", "\n", "\t"),"",$lineExtract[1]);
            // else if($lineExtract[0] == "PREVIOUS") 
            //     $previous = str_replace(array("\r\n", "\r", "\n", "\t"),"",$lineExtract[1]);
        }

        fclose($file);
    }

    $result['RUNNING'] = $running;
    // $result['UPDATE'] = $update;
    // $result['NEW'] = $new;
    // $result['PREVIOUS'] = $previous;
    return $result;
}

function changeDir($dir) {
    $lines = file("../../bhd.cfg");
    if(!$lines) {
        return false;
    }
    $result = '';
    
    foreach($lines as $line) {
        if(explode(':',$line)[0] == 'RUNNING') {
            $result .= 'RUNNING:'.$dir."\n";
        } 
        else {
            $result .= $line;
        }
    }
    
    $write = file_put_contents("../../bhd.cfg", $result);
    if(!$write) {
        return false;
    }
    
    return true;
}


//===================create confirm key (for login, change pw)=========================//
function createKey() {
    $key = "";
    $key .= ord('B');
    $key .= chr('66');
    $key .= ord('H');
    $key .= chr('88');
    $key .= ord('D');
    $key .= chr('99');

    return $key;
}

function encryptData($data) {
    $serverKey = createKey();
    return JWT::encode($data, $serverKey);
}

function decryptData($data) {
    try { 
        if($data == "")
            return "";
        $serverKey = createKey();
        return JWT::decode($data, $serverKey, array('HS256'));
    }
    catch (Exception $e) {
        throw new Exception("UNABLE TO DECODE INFORMATION!", 100);
    }
}


// validateId function for facility, maint, svc

function validateId($IdString) {
    //  create variables for checking first and last character
    $lastCharPosition = strlen($IdString) - 1;
    $beginLetter = $IdString[0];
    $lastLetter = $IdString[$lastCharPosition];

    if ($IdString == ''
    ||!preg_match('/^[-a-zA-Z0-9]+$/', $IdString) 
    || $beginLetter == '-' 
    || $lastLetter == '-' 
    || strpos($IdString, '--') !== false
    || preg_match('/\s/', $IdString) == 1) 
    {
        return false;
    }
    else {
        return true;
    }

}

//extract ackid from a cmd/response
// extracts ackid from cmd
function extractAckid($cmd)
{
    // "\$command,action=close" . $rcs . ",ackid=$ackid*"
    $ackid = "";
    $newCmd = substr($cmd, 1, -1);
    $shortCmdArray = explode(",", $newCmd);
    foreach ($shortCmdArray as $parameter) {
        $paraExtract = explode("=", $parameter);
        if ($paraExtract[0] == "ackid") {
            $ackid = $paraExtract[1];
            break;
        }
    }
    return $ackid;
}


//================Supporting functions for provisioning and maintenance==================//
// create array of row/col of all relays in the path and group them by node
// return array[node=>[[row,col],[row,col],..]]
function  loadRelaysRowColForCmd($pathObj)
{
    debugLog(3, "Function: " . __FUNCTION__ . " | Variable: Path($pathObj->id)");

    $rcArray = [];
    $relayList = ['r1', 'r2', 'r3f', 'rco', 'r4', 'rci', 'r3t', 'r5', 'r6', 'r7'];
    // loop through relayList and extract node, columns and rows
    // store col, row data into rcArray
    for ($i = 0; $i < count($relayList); $i++) {
        $relay = $pathObj->{$relayList[$i]};
        if ($relay->node != -1 && $relay->rcObj->row !=-1 && $relay->rcObj->col !=-1) {
            $node = $relay->node +1; // node here is base 0;
            
            if (!isset($rcArray[$node])) {
                $rcArray[$node] = []; //initialize key/value before append value to it
            }
            
            $rcArray[$node][]= [$relay->rcObj->row,$relay->rcObj->col];
        }
    }
    if(count($rcArray) == 0) {
        $response['rslt']  = 'success';
        $response['reason'] = 'No ROW/COL(s) loaded';
        return $response;
    }

    $response['rslt']  = 'success';
    $response['reason'] = 'Row/Col Array Created';
    $response['rows'] = $rcArray;
    debugLog(3, $response['rslt'].":".$response['reason']);
    debugLog(3, $rcArray);
    return $response;
}

//create array of ackid built from node, action, path-id where node extracted from array of row/col
function createAckidListForPathCmdByNode($rcArray, $pathId, $ackidCode) {
    debugLog(3, "Function: " . __FUNCTION__ . " | Variable: Path($pathId) | AckidCode: $ackidCode");
    debugLog(3, $rcArray);

    $ackidArray = [];
    foreach ($rcArray as $node => $rcs) {
        $ackid = "$node-$ackidCode-$pathId";
        $ackidArray[$node] = $ackid;
    }
    if(count($ackidArray) == 0) {
        $response['rslt']  = 'success';
        $response['reason'] = 'No Ackid Created';
        return $response;
    }
    $response['rslt']  = 'success';
    $response['reason'] = 'Ackid Array Created';
    $response['rows'] = $ackidArray;
    debugLog(3, $response['rslt'].":".$response['reason']);
    debugLog(3, $ackidArray);
    return $response;
}

//create list of path cmd from list of row/col, list of ackid and cmd-action 
function createCmdForPath($rcArray, $ackidArray, $action)
{
    debugLog(3, "Function:" . __FUNCTION__ . " | Variable: Action: $action");
    debugLog(3, $rcArray);
    debugLog(3, $ackidArray);
    $cmdArray = [];

    // loop through each node and creates cmds in the row column array
    $cmdObj = new CMD();
    foreach ($rcArray as $node => $rowcols) {
        $cmd="";
        if($action=="open") 
            $cmd = $cmdObj->createCmdOpenRowCol($ackidArray[$node],$rowcols);
        if($action=="close")
            $cmd = $cmdObj->createCmdCloseRowCol($ackidArray[$node],$rowcols);
        if($cmd==="") {
            $response['rslt'] = 'fail';
            $response['reason'] = "CMD for action($action) and ackid(".$ackidArray[$node].") not created";
            return $response;
        }
        $cmdArray[$node] = $cmd;
    }

    $response['rslt'] = 'success';
    $response['reason'] = 'Command Array Created';
    $response['rows'] = $cmdArray;
    debugLog(3, $response['rslt'].":".$response['reason']);
    debugLog(3,$cmdArray);
    return $response;
}

// adds cmd to t_Cmdque per node
function addCmdToCmdqueTable($cmdArray, $ackidArray)
{
    debugLog(3, "Function:" . __FUNCTION__ );
    debugLog(3,$cmdArray);
    debugLog(3,$ackidArray);
    // loop through each item in array and add the cmd to the table based on node
    // if any cmd is not added, retract cmds that were previously successful - log &return
    foreach ($cmdArray as $node => $cmd) {
        $ackid = $ackidArray[$node];
        $cmdObj = new CMD($ackid);
        if ($cmdObj->rslt == 'fail') {
            $cmdObj->addCmd($node, $ackid, $cmd);
            if ($cmdObj->rslt == 'fail') {
                foreach ($ackidArray as $ack) {
                    $cmdObj->removeCmd($ack);
                }
                $response['rslt'] = 'fail';
                $response['reason'] = "FAILED TO ADD CMD ($cmd) TO TABLE";
                return $response;
            }
        } else {
            $response['rslt'] = 'fail';
            $response['reason'] = "ACKID ($ackid) EXISTS IN TABLE";
            return $response;
        }
    }
    $response['rslt'] = 'success';
    $response['reason'] = 'CMDS ADDED TO DATABASE';
    return $response;
}

// loops to send all ackids to ipcCps
function sendCmdToCps($ackidArray)
{
    // log function and data
    debugLog(3, "Function:" . __FUNCTION__ );
    debugLog(3,$ackidArray);

    foreach ($ackidArray as $ackid) {
        $cmdObj = new CMD($ackid);
        debugLog(3, "Send Cmd: " . $cmdObj->cmd);
        $cmdObj->sendCmd();
    }
}


function createCmdAndSendToCps($rcArray, $ackidArray, $action) 
{
    debugLog(3, "Function:" . __FUNCTION__ . " | Variable: Action: $action");
    debugLog(3, $rcArray);
    debugLog(3, $ackidArray);
    $cmdqueObj = new CMDQUE();
    foreach ($rcArray as $node => $rc) {
        $ackid = $ackidArray[$node];
        if ($action == "close") {
            $cmdqueObj->sendCmdCloseRelays($node, $ackid, $rc);
        }
        else if ($action == "open") {
            $cmdqueObj->sendCmdOpenRelays($node, $ackid, $rc);
        }
        else {
            $response['rslt'] = 'fail';
            $response['reason'] = "INVALID ACTION - $action";
            return $response;
        }
                
        if ($cmdqueObj->rslt == 'fail') {
            $response['rslt'] = 'fail';
            $response['reason'] = "FAILED TO ADD CMD TO TABLE | $cmdqueObj->reason";
            debugLog(3, $response['rslt'].":".$response['reason']);

            foreach ($ackidArray as $ackid) {
                $cmdqueObj->removeCmd($ackid);
            }
            return $response;
        }
        debugLog(3, "Cmd Created and Sent to CPS: ".$cmdqueObj->cmd);
    }
    $response['rslt'] = $cmdqueObj->rslt;
    $response['reason'] = "CMD(s) created and sent successfully";
    debugLog(3, $response['rslt'].":".$response['reason']);
    return $response;
}
    