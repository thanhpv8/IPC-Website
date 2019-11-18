<?php

// initialize expected inputs from front end
$act = "";
if (isset($_POST['act']))
    $act = $_POST['act'];
    
$node = "";
if (isset($_POST['node']))
    $node = $_POST['node'];
    
$rack = "";
if (isset($_POST['rack']))
    $rack = $_POST['rack'];
    
$serial_no = "";
if (isset($_POST['serial_no']))
    $serial_no = $_POST['serial_no'];

$device = "";
if (isset($_POST['device'])) {
    $device = $_POST['device'];
}
//@TODO this device will need to be removed when the hardware comes in
$device = "ttyUSB0";

$hwRsp = "";
if (isset($_POST['hwRsp'])) {
    $hwRsp = $_POST['hwRsp'];
}

$cmd = "";
if (isset($_POST['cmd'])) {
    $cmd = $_POST['cmd'];
}

// dispatch to functions
if ($act == "queryAll") {
	$result = queryAll();	
	echo json_encode($result);
	mysqli_close($db);
	return;
}

// if ($act == "DISCOVER") {
//     $result = discover($node, $device, $userObj);
//     echo json_encode($result);
// 	mysqli_close($db);
// 	return;
// }

// not being used on the front end
// if ($act == "START") {
//     $result = start($node, $userObj);
//     echo json_encode($result);
// 	mysqli_close($db);
// 	return;
// }

// if ($act == "STOP") {
//     $result = stop($node, $serial_no, $userObj);
//     echo json_encode($result);
// 	mysqli_close($db);
// 	return;
// }

// if ($act == "DISCOVERED") {
//     $result = discovered($node, $hwRsp);
//     echo json_encode($result);
// 	mysqli_close($db);
// 	return;
// }

if ($act == "CPS_STATUS") {
    $result = updateCpsStatus($hwRsp);
    echo json_encode($result);
	mysqli_close($db);
	return;
}

// if ($act == "CPS_ON") {
//     $result = cps_on($node);
//     echo json_encode($result);
// 	mysqli_close($db);
// 	return;
// }
// if ($act == "CPS_OFF") {
//     $result = cps_off($node);
//     echo json_encode($result);
// 	mysqli_close($db);
// 	return;
// }

// if ($act == "EXEC_RESP") {
//     $result = exec_resp($node, $hwRsp, $userObj);
//     echo json_encode($result);
// 	mysqli_close($db);
// 	return;
// }

// if ($act == "EXEC_CMD") {
//     $result = exec_cmd($node, $cmd, $userObj);
//     echo json_encode($result);
// 	mysqli_close($db);
// 	return;
// }

if ($act == "VIEW CMD") {
    $result = view_cmd($node);
    echo json_encode($result);
    mysqli_close($db);
    return;
}

if ($act == "cps_connected") {
    $result = cps_connected($node);
    echo json_encode($result);
    mysqli_close($db);
    return;
}

if ($act == "cps_disconnected") {
    $result = cps_disconnected($node);
    echo json_encode($result);
    mysqli_close($db);
    return;
}

if ($act == "cps_online") {
    $result = cps_online($node);
    echo json_encode($result);
    mysqli_close($db);
    return;
}

if ($act == "cps_offline") {
    $result = cps_offline($node);
    echo json_encode($result);
    mysqli_close($db);
    return;
}

else {
	$result["rslt"] = 'fail';
	$result["reason"] = "This action is under development!";
	echo json_encode($result);
	mysqli_close($db);
	return;
}


// functions section
function cps_disconnected($node) {

    $cps = new CPS($node);
    // ignore if not discovered yes
    // if ($cps->psta == 'UAS') {
    //     $result["rslt"] = 'fail';
    //     $result["reason"] = 'CPS IS UAS';
    //     return $result;
    // }

    $evt = 'CPS_OUT';
    $sms = new SMS($cps->psta, $cps->ssta, $evt);
    if ($sms->rslt == 'success') {
        $cps->setPsta($sms->npsta, $sms->nssta);
        
        if ($cps->psta == 'OOS') {
            $almid = "$node-CPS-L";
            $almObj = new ALMS($almid);
            // create new alarm if not exists
            if ($almObj->rslt == "fail") {
                $src = 'CPS CARD';
                $almtype = 'EQUIP';
                $cond = 'NO COMMUNICATION';
                $sev = 'MAJ';
                $sa = 'Y';
                $remark = ":NODE $node: CPS IS DISCONNECTED";        
                $almObj->newAlm($almid, $src, $almtype, $cond, $sev, $sa, $remark);
            }
            $result["rslt"] = $almObj->rslt;
            $result["reason"] = $almObj->reason . ": $almid";
            return $result;
        }
        else {
            $result["rslt"] = $sms->rslt;
            $result["reason"] = "CPS PSTA=$cps->psta";
            return $result;
        }
    }
    $result["rslt"] = $sms->rslt;
    $result["reason"] = "INVALID PSTA ($sms->psta) FOR ACTION CPS-DISCONNECTED";
    return $result;
}


function cps_connected($node) {

    $cps = new CPS($node);
    // if first discovered
    // if ($cps->psta == 'UAS') {
    //     //assign serial_no to node
    // }
    // else {
    //     //raise alarm if diff serial_no 
    // }

    $evt = 'CPS_IN';
    $sms = new SMS($cps->psta, $cps->ssta, $evt);
    if ($sms->rslt == 'success') {
        $cps->setPsta($sms->npsta, $sms->nssta);
        if ($cps->psta == 'INS') {
            
            // clear alarm if exists
            $almid = "$node-CPS-L";
            $almObj = new ALMS($almid);
            if ($almObj->rslt == "success") {
                $src = 'CPS CARD';
                $almtype = 'EQUIP';
                $remark = ":NODE $node: CPS IS CONNECTED";  
                $almObj->sysClr($almid, $remark);
            }
            $result["rslt"] = $almObj->rslt;
            $result["reason"] = $almObj->reason;
            return $result;
        }
    }
    $result["rslt"] = $sms->rslt;
    $result["reason"] = "INVALID PSTA ($sms->psta) FOR ACTION CPS-CONNECTED";
    return $result;
}


function cps_online($node) {

    // validates and updates node psta/ssta
    $nodeObj = new NODE($node);
    $nodeEvt = "COMM_ON";
    $smsObj = new SMS($nodeObj->psta, $nodeObj->ssta, $nodeEvt);
    if ($smsObj->rslt == 'success') {
        $nodeObj->updatePsta($smsObj->npsta, $smsObj->nssta);
    }

    $cps = new CPS($node);
    $evt = 'CPS_ON';
    $sms = new SMS($cps->psta, $cps->ssta, $evt);
    if ($sms->rslt == 'success') {
        $cps->setPsta($sms->npsta, $sms->nssta);
        
        if ($cps->psta == 'INS' && $cps->ssta == 'UAS') {
            // clear alarm if exists

            $almid = "$node-CPS-C";
            $almObj = new ALMS($almid);
            if ($almObj->rslt == "success") {
                $src = 'CPS CARD';
                $almtype = 'EQUIP';
                $remark = ":NODE $node: CPS IS CONNECTED";  
                $almObj->sysClr($almid, $remark);
            }
            $result["rslt"] = $almObj->rslt;
            $result["reason"] = $almObj->reason;
            return $result;
        }
    }
    $result["rslt"] = $sms->rslt;
    $result["reason"] = "INVALID PSTA ($sms->psta) FOR ACTION CPS-ONLINE";
    return $result;
}

function cps_offline($node) {

    // validates and updates node psta/ssta
    $nodeObj = new NODE($node);
    $nodeEvt = "COMM_OFF";
    $smsObj = new SMS($nodeObj->psta, $nodeObj->ssta, $nodeEvt);
    if ($smsObj->rslt == 'success') {
        $nodeObj->updatePsta($smsObj->npsta, $smsObj->nssta);
    }

    $cps = new CPS($node);
    $evt = 'CPS_OFF';
    $sms = new SMS($cps->psta, $cps->ssta, $evt);
    if ($sms->rslt == 'success') {
        $cps->setPsta($sms->npsta, $sms->nssta);
        
        if ($cps->psta == 'OOS' && $cps->ssta == 'INS') {
            // create alarm if not exists

            $almid = "$node-CPS-C";
            $almObj = new ALMS($almid);
            if ($almObj->rslt == "fail") {
                $src = 'CPS CARD';
                $almtype = 'EQUIP';
                $cond = 'NO COMMUNICATION';
                $sev = 'MAJ';
                $sa = 'Y';
                $remark = ":NODE $node: CPS IS DISCONNECTED";  
                $almObj->newAlm($almid, $src, $almtype, $cond, $sev, $sa, $remark);
            }
            $result["rslt"] = $almObj->rslt;
            $result["reason"] = $almObj->reason;
            return $result;
        }
    }
    
    $result["rslt"] = $sms->rslt;
    $result["reason"] = "INVALID PSTA ($sms->psta) FOR ACTION CPS-ONLINE";
    return $result;
}



function view_cmd($node) {
    $cmdObj = new CMD();
    $cmdObj->getCmdList($node);
    if ($cmdObj->rslt == FAIL) {
        $result['rslt'] = $cmdObj->rslt;
        $result['reason'] = $cmdObj->reason;
        return $result;
    }
    $result['rslt'] = SUCCESS;
    $result['reason'] = "VIEW CMD SUCCESS";
    $result['rows'] = $cmdObj->rows;
    return $result;
}

function cps_on($node) {
    // create cps to get data for psta/ssta
    $cpsObj = new CPS($node);
    if ($cpsObj->rslt == FAIL) {
        $result['rslt'] = $cpsObj->rslt;
        $result['reason'] = $cpsObj->reason;
        return $result;
    }
    
    // check if psta/ssta is in right status
    $smsObj = new SMS($cpsObj->psta, $cpsObj->ssta, 'CPS_ON');
    if ($smsObj->rslt == FAIL) {
        $result['rslt'] = $smsObj->rslt;
        $result['reason'] = $smsObj->reason;
        return $result;
    }
    
    // if correct stat
    // update sms psta/ssta
    $cpsObj->setPsta($smsObj->npsta, $smsObj->nssta);
    if ($cpsObj->rslt == FAIL) {
        $result['rslt'] = $cpsObj->rslt;
        $result['reason'] = $cpsObj->reason;
        return $result;
    }
    // post to nodeapi to update node cps stats
    $postReqObj = new POST_REQUEST();
    $url = "ipcDispatch.php";
    $params = ["user"=>"SYSTEM", "api"=>"ipcNodeAdmin",'act'=>'updateCpsCom',"node"=>$node, "cmd"=>"$node-ONLINE"];
    $postReqObj->syncPostRequest($url, $params);
    return json_decode($postReqObj->reply);

}

function cps_off($node) {
    // create cps to get data for psta/ssta
    $cpsObj = new CPS($node);
    if ($cpsObj->rslt == FAIL) {
        $result['rslt'] = $cpsObj->rslt;
        $result['reason'] = $cpsObj->reason;
        return $result;
    }
    
    // check if psta/ssta is in right status
    $smsObj = new SMS($cpsObj->psta, $cpsObj->ssta, 'CPS_OFF');
    if ($smsObj->rslt == FAIL) {
        $result['rslt'] = $smsObj->rslt;
        $result['reason'] = $smsObj->reason;
        return $result;
    }
    
    // if correct stat
    // update sms psta/ssta
    $cpsObj->setPsta($smsObj->npsta, $smsObj->nssta);
    if ($cpsObj->rslt == FAIL) {
        $result['rslt'] = $cpsObj->rslt;
        $result['reason'] = $cpsObj->reason;
        return $result;
    }

    // post to nodeapi to update node cps stats
    $postReqObj = new POST_REQUEST();
    $url = "ipcDispatch.php";
    $params = ["user"=>"SYSTEM", "api"=>"ipcNodeAdmin",'act'=>'updateCpsCom', "node"=>$node, "cmd"=>"$node-OFFLINE"];
    $postReqObj->syncPostRequest($url, $params);
    return json_decode($postReqObj->reply);

}

function queryAll() {
    $cpssObj = new CPSS();
    if ($cpssObj->rslt == FAIL) {
        $result['rslt'] = $cpssObj->rslt;
        $result['reason'] = $cpssObj->reason;
        return $result;
    }

    $result['rslt'] = SUCCESS;
    $result['reason'] = "QUERY SUCCESS";
    $result['rows'] = $cpssObj->rows;
    return $result;
}

function discover($node, $device, $userObj) {

    // add user permission for ipcAdmin
    if ($userObj->grpObj->ipcadm != "Y") {
        $result['rslt'] = 'fail';
        $result['reason'] = 'Permission Denied';
        return $result;
    }

    $cpsObj = new CPS($node);
    $psta = $cpsObj->psta;
    $ssta = $cpsObj->ssta;

    $evt = "CPS_ON";
    // test sms
    $smsObj = new SMS($psta, $ssta, $evt);
    if ($smsObj->rslt == FAIL) {
        $result['rslt'] = $smsObj->rslt;
        $result['reason'] = $smsObj->reason;
        return $result;
    }

    // this cmd will be sent back to be parsed. the ackid must be the NEXT ACT and API
    $cmd = "inst=DISCV_CPS,node=$node,dev=$cpsObj->dev,sn=,cmd=\$status,source=uuid,device=backplane,ackid=$node-cps-dcvd*";

    // call function to send UDP message
    $cmdObj = new CMD();
    $cmdObj->sendCmd($cmd, $node);
    if ($cmdObj->rslt == FAIL) {
        $result['rslt'] = $cmdObj->rslt;
        $result['reason'] = $cmdObj->reason;
        return $result;
    }

    $result['rslt'] = SUCCESS;
    $result['reason'] = "DISCOVER CPS SUCCESS";
    $result['rows'] = [];
    return $result;
}

// not being used on the front end
function start($node, $userObj) {
    // permissions check here
    if ($userObj->grpObj->ipcadm != "Y") {
        $result['rslt'] = 'fail';
        $result['reason'] = 'Permission Denied';
        return $result;
    }
    
    // construct cpsObj to get current psta/ssta of CPS
    $cpsObj = new CPS($node);
    if ($cpsObj->rslt == FAIL) {
        $result['rslt'] = $cpsObj->rslt;
        $result['reason'] = $cpsObj->reason;
        return $result;
    }

    $psta = $cpsObj->psta;
    $ssta = $cpsObj->ssta;

    $evt = "CPS_ON";

    // sms check psta/ssta if it is in correct state
    $smsObj = new SMS($psta, $ssta, $evt);
    if ($smsObj->rslt == FAIL) {
        $result['rslt'] = $smsObj->rslt;
        $result['reason'] = $smsObj->reason;
        return $result;
    }

    // update t_cps psta/ssta with npsta/nssta???
    $cmd = "inst=START_CPS,node=$node,dev=$cpsObj->dev,cmd=\$status,source=all,ackid=$node-CPS*\$status,source=devices,ackid=$node-dev*";

    $cmdObj = new CMD();
    $cmdObj->sendCmd($cmd, $node);    
    if ($cmdObj->rslt == "fail") {
        $result['rslt'] = $cmdObj->rslt;
        $result['reason'] = $cmdObj->reason;
        return;
    }
    $result['rslt'] = SUCCESS;
    $result['reason'] = "START COMMAND SENT SUCCESSFULLY";
    $result['rows'] = [];
    return $result;
}

function stop($node, $serial_no, $userObj) {

    // permissions check here
    if ($userObj->grpObj->ipcadm != "Y") {
        $result['rslt'] = 'fail';
        $result['reason'] = 'Permission Denied';
        return $result;
    }

    $cpsObj = new CPS($node);

    if ($cpsObj->rslt == FAIL) {
        $result['rslt'] = $cpsObj->rslt;
        $result['reason'] = $cpsObj->reason;
        return $result;
    }

    $smsObj = new SMS($cpsObj->psta, $cpsObj->ssta, "CPS_STOP");
    if($smsObj->rslt == FAIL) {
        $result['rslt'] = $smsObj->rslt;
        $result['reason'] = $smsObj->reason;
        return $result;
    }
    
    $cpsObj->setPsta($smsObj->npsta, $smsObj->nssta);
    if ($cpsObj->rslt == FAIL) {
        $result['rslt'] = $cpsObj->rslt;
        $result['reason'] = $cpsObj->reason;
        return $result;
    }
    
    $cmd = "inst=STOP_CPS,sn=$serial_no";
    $cmdObj = new CMD();
    $cmdObj->sendCmd($cmd, $node);
    if ($cmdObj->rslt == "fail") {
        $result['rslt'] = $cmdObj->rslt;
        $result['reason'] = $cmdObj->reason;
        return;
    }
    $result['rslt'] = SUCCESS;
    $result['reason'] = "STOP COMMAND SENT SUCCESSFULLY";
    $result['rows'] = [];
    return $result;
}

function discovered($node, $hwRsp) {
    // $ackid=1-bkpln,status,device=miox(0),uuid=IAMAMIOXUUIDTHATYOUCANTDECODE*
    // UUID is serial number for now, extract uuid from string
    $newHwString = substr($hwRsp, 1, -1);
    $newHwStringArray = explode(",", $newHwString);
    $serialNum = "";
    foreach($newHwStringArray as $parameter) {
        $paraExtract = explode('=',$parameter);
        if($paraExtract[0] == 'uuid') 
            $serialNum = $paraExtract[1];
    }
    if($serialNum === "") {
        $result['rslt'] = "fail";
        $result['reason'] = "SERIAL NUMBER IS MISSING";
        return $result;
    }
    // construct to see if serial number already exists in DB
    $cpssObj = new CPSS();
    if ($cpssObj->rslt == FAIL) {
        $result['rslt'] = $cpssObj->rslt;
        $result['reason'] = $cpssObj->reason;
        return $result;
    }

    if (in_array($serialNum, $cpssObj->serial_no)) {
        // b) if already exists then send UDP->msg($node,$device,STOP)
        $cpsObj = new CPS($node);
        // send message 3 to udp
        $cmd = "inst=STOP_CPS,sn=$serialNum";
        $cmdObj = new CMD();
        $cmdObj->sendCmd($cmd, $node);
        if ($cmdObj->rslt == FAIL)         {
            $result['rslt'] = $cmdObj->rslt;
            $result['reason'] = $cmdObj->reason;
            return $result;
        }

        $result['rslt'] = FAIL;
        $result['reason'] = "SERIAL NUMBER ALREADY EXISTS IN SYSTEM";
        return $result;
    }
    else {
        // a) if $serial_no not exist in t_cps then update CPS->psta/ssta with npsta/nssta obtained from SMS

        $evt = "CPS_ON";
        // gets psta and ssta to create smsObj
        $cpsObj = new CPS($node);
        
        // get nspta and nssta from sms obj
        $smsObj = new SMS($cpsObj->psta, $cpsObj->ssta, $evt);
        if ($smsObj->rslt == FAIL) {
            $result['rslt'] = $smsObj->rslt;
            $result['reason'] = $smsObj->reason;
            return $result;
        }

        $newPsta = $smsObj->npsta;
        $newSsta = $smsObj->nssta;

        // update psta and ssta, write serial number to t_cps, start the cps
        $cpsObj->setPsta($newPsta, $newSsta);
        if ($cpsObj->rslt == FAIL) {
            $result['rslt'] = $cpsObj->rslt;
            $result['reason'] = $cpsObj->reason;
            return $result;
        }
        // if success
        $cpsObj->setSerialNo($serialNum);
        if ($cpsObj->rslt == FAIL) {
            $result['rslt'] = $cpsObj->rslt;
            $result['reason'] = $cpsObj->reason;
            return $result;
        }
        // call message 2
        // requires instruction and serial number
        $cmd = "inst=START_CPS,sn=$serialNum,cmd=\$status,source=all,ackid=$node-cps-csta*\$status,source=devices,ackid=$node-nadm-unds*";
        
        $cmdObj = new CMD();
        $cmdObj->sendCmd($cmd, $node);
        if ($cmdObj->rslt == FAIL) {
            $result['rslt'] = $cmdObj->rslt;
            $result['reason'] = $cmdObj->reason;
            return $result;
        }

        $result['rslt'] = SUCCESS;
        $result['reason'] = $cpsObj->reason;
        return $result;
    }
}

function updateCpsStatus($hwRsp) {
    // checks what type of $hwRsp is being sent
    if (strpos($hwRsp, "voltage") !== false){
        $result = updateCpsVolt($hwRsp);
        return $result;
    }
    else if (strpos($hwRsp, "temperature") !== false) {
        $result = updateCpsTemp($hwRsp);
        return $result;
    }
}

// function called by updateAlm in case string contains voltage only
// str looks like this "$ackid=1-cps-csta,status,voltage1=46587mV,voltage2=47982mV,voltage3=48765mV,voltage4=49234mV,backplane=IAMAMIOXUUIDTHATYOUCANTDECODE*"
function updateCpsVolt($hwRsp) {
    
    $variableArray = ['voltage1', 'voltage2', 'voltage3', 'voltage4'];
    $voltArray = [];
    $responseArray = convertRspToKeyValueArray($hwRsp);
    foreach($responseArray as $key=>$value) {
        if (in_array($key, $variableArray)) {
            sscanf($value, "%d%s", $volt, $unit);
            if (is_numeric($volt)) {
                $voltArray[] = $volt;
            }
        }
    }

    // Check if empty then return fail
    if (count($voltArray) === 0) {
        $result['rslt'] = "fail";
        $result['reason'] = "NO VOLTAGE INFO RECEIVED";
        return $result;
    }

    // Obtain ackid
    $ackid = "";
    if (isset($responseArray['ackid'])) {
        $ackid = $responseArray['ackid'];
    } else {
        $result['rslt'] = "fail";
        $result['reason'] = "MISSING ACKID";
        return $result;
    }

    // Obtain node from ackid
    $ackidArray = explode("-", $ackid);
    $node = $ackidArray[0];

    // get lowest and highest values from volt
    $volt_hi = max($voltArray);
    $volt_low = min($voltArray);
    
    $newVolt_hiVal = round((int)($volt_hi/1000));
    $newVolt_lowVal = round((int)($volt_low/1000));
       
    // put units back onto volt values to prepare sending to t_nodes
    // $newVolt_hi = round((int)($volt_hi/1000)) . 'V';
    // $newVolt_low = round((int)($volt_low/1000)) . 'V';
    $newVolt_hi = round((int)($volt_hi/1000)) . 'V';
    $newVolt_low = round((int)($volt_low/1000)) . 'V';

    $refObj = new REF();
    if ($refObj->rslt == FAIL) {
        $result['rslt'] = $refObj->rslt;
        $result['reason'] = $refObj->reason;
        return $result;
    }

    $voltRange = $refObj->ref['volt_range'];
    
    $voltRangeArray = explode("-", $voltRange);
    $minVolt = $voltRangeArray[0];
    $maxVolt = $voltRangeArray[1];
   
    $nodeObj = new NODE($node);
    if($nodeObj->rslt == 'fail') {
        $result['rslt'] = $nodeObj->rslt;
        $result['reason'] = $nodeObj->reason;
        return $result;
    }
    // write to t_nodes the volt_hi by default or the voltage that is out of range
    if ($volt_low < $minVolt) {
        $nodeObj->updateVolt($newVolt_low);
    }
    else{
        $nodeObj->updateVolt($newVolt_hi);
    }

    // makes new alm if voltage is out of range
    if (($newVolt_hiVal > $maxVolt) || ($newVolt_lowVal < $minVolt)) {
        $almid = $ackid . '-V';
        $almObj = new ALMS($almid);
        if (count($almObj->rows) == 0) {
            $src = 'POWER';
            $almtype = 'VOLTAGE';
            $cond = 'VOLTAGE OUT-OF-RANGE';
            $sa = 'N';
            $sev = 'MIN';
            $remark = $hwRsp . ' - ' . $almid . ' : ' . $cond . 'VOLT_HI=' . $newVolt_hiVal . '||VOLT_LOW=' . $newVolt_lowVal;
            $almObj = new ALMS();
            $almObj->newAlm($almid, $src, $almtype, $cond, $sev, $sa, $remark);
            //logError if failed here
        }
    }

    // sys-clr alm if voltage is in range
    if (($newVolt_hiVal <= $maxVolt) && ($newVolt_lowVal >= $minVolt)) {
        $almid = $ackid . '-V';
        $almObj = new ALMS($almid);
        if (count($almObj->rows) !== 0) {
            $remark = 'SYSTEM CLEAR ALARM: ' . $almid . ' : VOLTAGE IN-RANGE';
            $almObj->sysClr($almid, $remark);
            //logError if failed here
        }
    }
    $result['rslt'] = SUCCESS;
    $result['reason'] = "VOLTAGE ALARM UPDATE SUCCESS";
    return $result;
}

// function called by updateAlm in case string contains temp only
// str looks like this "$ackid=1-cps-csta,status,temperature,zone1=67C,zone2=65C,zone3=66C,zone4=68C,backplane=IAMAMIOXUUIDTHATYOUCANTDECODE*"
function updateCpsTemp($hwRsp) {

    // filters data brought from $cmd and extracts temp values
    $newCmd = substr($hwRsp, 1, -1);
    $splitCmd = explode(',', $newCmd);

    foreach($splitCmd as $parameter) {
        $paraExtract = explode("=", $parameter);
        if ($paraExtract[0] == "ackid") {
            $ackid = strtoupper($paraExtract[1]);
        }
        else if ($paraExtract[0] == "zone1") {
            $temp1 = $paraExtract[1];
        }
        else if ($paraExtract[0] == "zone2") {
            $temp2 = $paraExtract[1];
        }
        else if ($paraExtract[0] == "zone3") {
            $temp3 = $paraExtract[1];
        }
        else if ($paraExtract[0] == "zone4") {
            $temp4 = $paraExtract[1];
        }
    }

    if($ackid === null || $temp1===null || $temp2===null || $temp3===null || $temp4 === null) {
        $result['rslt'] = "fail";
        $result['reason'] = "NO TEMPERATURE INFO RECEIVED";
        return $result;
    }

    sscanf($temp1, "%d%s", $temp1Val, $temp1Unit);
    sscanf($temp2, "%d%s", $temp2Val, $temp2Unit);
    sscanf($temp3, "%d%s", $temp3Val, $temp3Unit);
    sscanf($temp4, "%d%s", $temp4Val, $temp4Unit);

    $temp_hi = max($temp1Val, $temp2Val, $temp3Val, $temp4Val);

    // combine temp value and unit to send to t_nodes
    $newTemp_hi = $temp_hi . $temp1Unit;
    
    // extract node number from cmd
    $ackidArray = explode("-", $ackid);
    $node = $ackidArray[0];

    $nodeObj = new NODE($node);
    if($nodeObj->rslt == 'fail') {
        $result['rslt'] = $nodeObj->rslt;
        $result['reason'] = $nodeObj->reason;
        return $result;
    }
    
    // update t_nodes w/ highest temp
    $nodeObj->updateTemp($newTemp_hi);

    
    $refObj = new REF();
    if ($refObj->rslt == FAIL) {
        $result['rslt'] = $refObj->rslt;
        $result['reason'] = $refObj->reason;
        return $result;
    }

    // obtain temp_max from refObj
    $tempMax = $refObj->ref['temp_max'];

    // makes new alm if temp is out of range
    if ($temp_hi > $tempMax) {
        $almid = $ackid . '-T';
        $almObj = new ALMS($almid);
        if (count($almObj->rows) == 0) {
            $src = 'POWER';
            $almtype = 'TEMPERATURE';
            $cond = 'TEMPERATURE OUT-OF-RANGE';
            $sa = 'N';
            $sev = 'MIN';
            $remark = $almid . ' : ' . $cond;
            $almObj = new ALMS();
            $almObj->newAlm($almid, $src, $almtype, $cond, $sev, $sa, $remark);
            if ($almObj->rslt == 'fail') {
                $result["rslt"] = "fail";
                $result["reason"] = "Fail to create alarm";
                return $result;
            }
        }
    }

    // sys-clr alm if temp is in range
    if ($temp_hi < $tempMax) {
        $almid = $ackid . '-T';
        $almObj = new ALMS($almid);
        if (count($almObj->rows) !== 0) {
            $remark = 'SYSTEM CLEAR ALARM: ' . $almid . ' : TEMPERATURE IN-RANGE';
            $almObj->sysClr($almid, $remark);
            if ($almObj->rslt == 'fail') {
                $result["rslt"] = "fail";
                $result["reason"] = "Fail to clear alarm";
                return $result;
            }
        }
    }
    $result['rslt'] = SUCCESS;
    $result['reason'] = "TEMP ALARM UPDATE SUCCESS";
    return $result;
}

function exec_resp($node, $hwRsp, $userObj) {

    cps_on($node);
    // use cpsloop example foreach processUDPmsg
    // remove $ and * from string
    $rsp = substr($hwRsp, 1, -1);

    // divide string into sections
    $hwRspArray = explode(',', $rsp);

    // go through array and search for serial_no, ackid, node, api and apiAction
    foreach($hwRspArray as $parameter) {
        $paraExtract = explode("=", $parameter);
        if ($paraExtract[0] == "ackid") {
            $cmdArray = explode("-", $paraExtract[1]);
            $ackid = $paraExtract[1];
            $nodeExtract = $cmdArray[0];
            $api_key = $cmdArray[1];
            $apiAct_key = $cmdArray[2];
        }
        else if ($paraExtract[0] == "backplane") {
            $serial_no = $paraExtract[1];
        }
    }
    if($cmdArray === null || $ackid === null 
       || $nodeExtract === null || $api_key === null 
       || $apiAct_key === null || $serial_no === null) {
            $result['rslt'] = "fail";
            $result['reason'] = "NOT ENOUGH INFORMATION IN ACKID AND SERIAL NO";
            return $result;
       }

    $cpsObj = new CPS($node);
    if ($cpsObj->rslt == FAIL) {
        $result['rslt'] = $cpsObj->rslt;
        $result['reason'] = $cpsObj->reason;
        return $result;
    }
    
    // check if serial_no is the same as number in database
    if ($cpsObj->serial_no != '-' && $cpsObj->serial_no != '' && $cpsObj->serial_no !== $serial_no) {
        // create alarm here "almid=node-cps-sn"
        // is serial_no from db or from parsed data??
        $almid = "$node-cps-sn";
        // check if alm with this almid already exists, if no, create a new one
        $almObj = new ALMS($almid);
        if (count($almObj->rows) == 0) {
            $src = 'EQUIP';
            $almtype = 'COMMUNICATION';
            $cond = 'COMMUNICATION';
            $sa = 'N';
            $sev = 'MAJ';
            $remark = 'SERIAL NUMBER IN CONFLICT || EXPECTED: ' . $cpsObj->serial_no . " || INPUT: " . $serial_no;
            $almObj->newAlm($almid, $src, $almtype, $cond, $sev, $sa, $remark);
        }
        
        // send stop UDP:stop
        // $cmd = "inst=STOP_CPS,sn=$serial_no";
        // $cmdObj = new CMD();
        // $cmdObj->sendCmd($cmd, $node);
        // if ($cmdObj->rslt == "fail") {
        //     $result['rslt'] = $cmdObj->rslt;
        //     $result['reason'] = $cmdObj->reason;
        //     return;
        // }
        
        // use existing function that has checks and updates psta/ssta for this node
        stop($node, $cpsObj->serial_no, $userObj);
        
        
        $result['rslt'] = FAIL;
        $result['reason'] = "SERIAL NUMBER IN CONFLICT";
        return $result;
    }
  
    // Obtain full api string from constant and api action from constant
    $api = apiAndActArray[$api_key]['api'];
    $apiAct = apiAndActArray[$api_key][$apiAct_key];

    // post to nodeapi to update node cps stats
    $cmdObj = new CMD($ackid);

    if ($cmdObj->rslt == FAIL) {
        $result['rslt'] = $cmdObj->rslt;
        $result['reason'] = $cmdObj->reason;
        return $result;
    }

    // find the ackid in t_cmdque and update with stat 'COMPL'
    if ($cmdObj->reason == "ACKID FOUND") {
        $stat = "COMPL";
        $cmdObj->updCmd($stat, $hwRsp);
        if ($cmdObj->rslt == FAIL) {
            $result['rslt'] = $cmdObj->rslt;
            $result['reason'] = $cmdObj->reason;
            return $result;
        }
    }
}


function exec_cmd($node, $cmd, $userObj) {
 
    // permissions check here
    if ($userObj->grpObj->ipcadm != "Y") {
        $result['rslt'] = 'fail';
        $result['reason'] = 'Permission Denied';
        return $result;
    }

    // nodeOpe->exec() will send UDP->msg[inst=EXEC,node,comport,serial_no,cmd] to cpsLoop
    // will receive string like this: ACKID=$node-api-act
    $cmdStr = substr($cmd, 1, -1);
    // divide string into sections
    $cmdExtract = explode(',', $cmdStr);
    // go through array and search for ackid, node, api and apiAction
    $ackId = null;
    foreach($cmdExtract as $parameter) {
        $paraExtract = explode("=", $parameter);
        if ($paraExtract[0] == "ackid") {
            $cmdArray = explode("-", $paraExtract[1]);
            $ackId = $paraExtract[1];
        }
    }

    $cmdObj = new CMD($ackId);
    if ($cmdObj->rslt == FAIL) {
        $result['rslt'] = $cmdObj->rslt;
        $result['reason'] = $cmdObj->reason;
        return $result;
    }

    if ($cmdObj->reason == "ACKID NOT FOUND") {
        $cmdObj->addCmd($node, $ackId, $cmd);
        if ($cmdObj->rslt == FAIL) {
            $result['rslt'] = $cmdObj->rslt;
            $result['reason'] = $cmdObj->reason;
            return $result;
        }
    }
    else if ($cmdObj->reason == "ACKID FOUND"){
        $stat = "PENDING";
        $cmdObj->updateStat($stat);
        if ($cmdObj->rslt == FAIL) {
            $result['rslt'] = $cmdObj->rslt;
            $result['reason'] = $cmdObj->reason;
            return $result;
        }
    }
    else {
        $result['rslt'] = 'fail';
        $result['reason'] = 'INVALID ACKID';
        return $result;
    }

    // create cpsObj to get comport and serialnum
    $cpsObj = new CPS($node);
    if ($cpsObj->rslt == FAIL) {
        $result['rslt'] = $cpsObj->rslt;
        $result['reason'] = $cpsObj->reason;
        return $result;
    }

    $newCmd = "inst=EXEC,node=$node,dev=$cpsObj->dev,sn=$cpsObj->serial_no,cmd=$cmd";

    $cmdObj->sendCmd($newCmd, $node);
    if ($cmdObj->rslt == FAIL) {
        $result['rslt'] = $cmdObj->rslt;
        $result['reason'] = $cmdObj->reason;
        return $result;
    }

    $result['rslt'] =   SUCCESS;
    $result['reason'] = "CMD IS IN PROGRESS";
    return $result;
}



?>