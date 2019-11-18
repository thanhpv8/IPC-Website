<?php
/*  Filename: ipcProvDisconnect.php
    Date: 2018-12-18
    By: Ninh
    Copyright: BHD SOLUTIONS, LLC @ 2018
*/

function provDisconnect($userObj, $ordno, $mlo, $ckid, $cls, $adsr, $prot, $ctyp, $ffac, $tfac)
{
    debugLog(3, "Function: ".__FUNCTION__. "Variables: ACTION = PROVDISCONNECT | ORDNO = $ordno | MLO = $mlo | CKID = $ckid | CLS = $cls | ADSR = $adsr | PROT = $prot | CTYP = $ctyp | FFAC = $ffac | TFAC = $tfac");

    $result['log'] = "ACTION = PROVDISCONNECT | ORDNO = $ordno | MLO = $mlo | CKID = $ckid | CLS = $cls | ADSR = $adsr | PROT = $prot | CTYP = $ctyp | FFAC = $ffac | TFAC = $tfac";

    if ($userObj->grpObj->prov != "Y") {
        $result['rslt'] = 'fail';
        $result['jeop'] = "SP5:PERMISSION DENIED";
        $result['reason'] = 'Permission Denied';
        return $result;
    }

    // if ckid is not in DB, then this is an invalid CKT
    $cktObj = new CKT($ckid);
    if ($cktObj->rslt == FAIL) {
        $result['rslt'] = FAIL;
        $result['jeop'] = "SP5:$cktObj->reason";
        $result['reason'] = "PROVISIONING DISCONNECT - " . $cktObj->reason;
        return $result;
    }

    // verify ffac and tfac are currently connected under this CKT
    $cktconObj = new CKTCON($cktObj->cktcon);
    if ($cktconObj->rslt == FAIL) {
        $result['rslt'] = FAIL;
        $result['jeop'] = "SP5:$cktconObj->reason";
        $result['reason'] = "PROVISIONING DISCONNECT - " . $cktconObj->reason;
        return $result;
    }

    // the ffac must exist in DB and currently part of same CKTCON/IDX
    $ffacObj = new FAC($ffac);
    if ($ffacObj->rslt != SUCCESS || $ffacObj->port_id == 0) {
        $result['rslt'] = "fail";
        $result['jeop'] = "SP2:FAC IS INVALID";
        $result['reason'] = "PROVISIONING DISCONNECT - INVALID_FAC: " . $ffac;
        return $result;
    }
    $ffacObj->setPortObj();
    if ($ffacObj->portObj->cktcon != $cktconObj->con) {
        $result['rslt'] = "fail";
        $result['jeop'] = "SP4:FAC IS MAPPED TO DIFFERENT PORT";
        $result['reason'] = "PROVISIONING DISCONNECT - FAC: " . $ffac . " cktcon: " . $ffacObj->portObj->cktcon . " is not part of CKTCON: " . $cktconObj->con;
        return $result;
    }

    // the tfac must exist in DB and must be currently mapped
    $tfacObj = new FAC($tfac);
    if ($tfacObj->rslt != SUCCESS || $tfacObj->port_id == 0) {
        $result['rslt'] = "fail";
        $result['jeop'] = "SP2:FAC IS INVALID";
        $result['reason'] = "PROVISIONING DISCONNECT - INVALID_FAC: " . $tfac;
        return $result;
    }
    
    $tfacObj->setPortObj();
    if ($tfacObj->portObj->cktcon != $cktconObj->con) {
        $result['rslt'] = "fail";
        $result['jeop'] = "SP4:FAC IS MAPPED TO DIFFERENT PORT";
        $result['reason'] = "PROVISIONING DISCONNECT - " . "FAC: " . $tfac . " is not part of CKTCON: " . $cktconObj->con;
        return $result;
    }

    // verify both ffac and tfac are connected on same IDX
    if ($ffacObj->portObj->con_idx != $tfacObj->portObj->con_idx) {
        $result['rslt'] = "fail";
        $result['jeop'] = "SP5:FFAC and TFAC are not connected on same CKTCON/IDX";
        $result['reason'] = "PROVISIONING DISCONNECT - " . "FFAC and TFAC are not connected on same CKTCON/IDX";
        return $result;
    }

    // verify IDX exists in CKTCON
    $cktconObj->loadIdx($ffacObj->portObj->con_idx);
    if ($cktconObj->rslt == FAIL) {
        $result['rslt'] = "fail";
        $result['jeop'] = "SP5:$cktconObj->reason";
        $result['reason'] = "PROVISIONING DISCONNECT - " . $cktconObj->reason;
        return $result;
    }

    // extract node number from portObj
    $fNode = $ffacObj->portObj->node;
    $tNode = $tfacObj->portObj->node;

    // get fromNode stat and toNode stat
    $fNodeObj = new NODE($fNode);
    $tNodeObj = new NODE($tNode);

    // deny action if fnode is not in service
    if ($fNodeObj->stat !== 'INS') {
        $result['rslt'] = 'fail';
        $result['jeop'] = "SP5:ACCESS DENIED; NODE ($fNode) HAS BEEN LOCKED BY SYSTEM ADMINISTRATOR $fNodeObj->user";
        $result['reason'] = "PROVISIONING DISCONNECT - " . "ACCESS DENIED; NODE ($fNode) HAS BEEN LOCKED BY SYSTEM ADMINISTRATOR $fNodeObj->user";
        return $result;
    }

    // deny action if tnode is not in service
    if ($tNodeObj->stat !== 'INS') {
        $result['rslt'] = 'fail';
        $result['jeop'] = "SP5:ACCESS DENIED; NODE ($tNode) HAS BEEN LOCKED BY SYSTEM ADMINISTRATOR $tNode->user";
        $result['reason'] = "PROVISIONING DISCONNECT - " . "ACCESS DENIED; NODE ($tNode) HAS BEEN LOCKED BY SYSTEM ADMINISTRATOR $tNodeObj->user";
        return $result;
    }


    // validate state-event
    $sms = new SMS($ffacObj->portObj->psta, $ffacObj->portObj->ssta, "SV_DISCON");
    if ($sms->rslt == FAIL) {
        $result['rslt'] = FAIL;
        $result['jeop'] = "SP3:FAC STATUS (" . $ffacObj->portObj->psta . ")";
        $result['reason'] = "PROVISIONING DISCONNECT - " . "INVALID PSTA ($sms->psta)";
        return $result;
    }
    $ffacObj->portObj->npsta = $sms->npsta;
    $ffacObj->portObj->nssta = $sms->nssta;

    $sms = new SMS($tfacObj->portObj->psta, $tfacObj->portObj->ssta, "SV_DISCON");
    if ($sms->rslt == FAIL) {
        $result['rslt'] = FAIL;
        $result['jeop'] = "SP3:FAC STATUS (" . $tfacObj->portObj->psta . ")";
        $result['reason'] = "PROVISIONING DISCONNECT - " . "INVALID PSTA ($sms->psta)";
        return $result;
    }
    $tfacObj->portObj->npsta = $sms->npsta;
    $tfacObj->portObj->nssta = $sms->nssta;

    /* will enable this after testing of 100% non-blocking */
    //Check pathId 
    $pathId = $cktconObj->path;
    if ($pathId == "") {
        $result['rslt'] = "fail";
        $result['jeop'] = "SP5: NO PATH_ID FOR THIS CONNECTION (" . $ffacObj->portObj->port . " TO " . $tfacObj->portObj->port . ")";
        $result['reason'] = "PROVISIONING DISCONNECT - NO PATH_ID FOR THIS CONNECTION (" . $ffacObj->portObj->port . " TO " . $tfacObj->portObj->port . ")";
        return $result;
    }
    $pathObj = new PATH();
    $pathObj->loadPathById($pathId);
    // $pathObj = new PATH($ffacObj->portObj->port, $tfacObj->portObj->port);
    // $pathObj->load();
    if ($pathObj->rslt == 'fail') {
        $result['rslt'] = 'fail';
        $result['jeop'] = "SP5:UNABLE TO LOAD PATH";
        $result['reason'] = "PROVISIONING DISCONNECT - " . $pathObj->reason;
        return $result;
    }

    
    debugLog(3, "Path status: " . $pathObj->psta);
    $response = instructCmdToCpsForOpeningRelays($pathObj);
    if ($response['rslt'] == 'success') {
        $pathObj->disconnecting();
        $result['rslt'] = $response['rslt'];
        $result['reason'] = "PROVISIONING DISCONNECT - PATH IS DISCONNECTING";
        debugLog(3, $result['rslt'] . ":" . $result['reason']);
        return $result;
        } else {
        $result['rslt'] = $response['rslt'];
        $result['reason'] = "PROVISIONING DISCONNECT - " . $response['reason'];
        debugLog(3, $result['rslt'] . ":" . $result['reason']);
        return $result;
    }

    debugLog(3, "Continue to resetPath");
    $pathObj->resetPath();
    $pathObj->drop();
    /* */

    // Ready for DB updates
    // 1) remove IDX
    $cktconObj->deleteIdx($cktconObj->con, $cktconObj->idx);
    if ($cktconObj->rslt != SUCCESS) {
        $result['rslt'] = $cktconObj->rslt;
        $result['jeop'] = "SP5:$cktconObj->reason";
        $result['reason'] = "PROVISIONING DISCONNECT - " . $cktconObj->reason;
        return $result;
    }

    // 2) if last IDX removed, then remove CKT as well
    $newCktconObj = new CKTCON($cktconObj->con);
    if ($newCktconObj->rslt == FAIL) {
        $cktObj->deleteCkt($ckid);
        if ($cktObj->rslt == FAIL) {
            $result['rslt'] = FAIL;
            $result['jeop'] = "SP5:$cktObj->reason";
            $result['reason'] = "PROVISIONING DISCONNECT - " . $cktObj->reason;
            return $result;
        }
    }

    // 3) update PORT's PSTA and link with CKT, CKTCON
    $ffacObj->portObj->updPsta($ffacObj->portObj->npsta, $ffacObj->portObj->nssta, "-");
    if ($ffacObj->portObj->rslt != SUCCESS) {
        $result['rslt'] = $ffacObj->portObj->rslt;
        $result['jeop'] = "SP5:" . $ffacObj->portObj->reason;
        $result['reason'] = "PROVISIONING DISCONNECT - " . $ffacObj->portObj->reason;
        return $result;
    }

    $ffacObj->portObj->updCktLink(0, 0, 0);
    if ($ffacObj->portObj->rslt != SUCCESS) {
        $result['rslt'] = $ffacObj->portObj->rslt;
        $result['jeop'] = "SP5:" . $ffacObj->portObj->reason;
        $result['reason'] = "PROVISIONING DISCONNECT - " . $ffacObj->portObj->reason;
        return $result;
    }

    $tfacObj->portObj->updPsta($tfacObj->portObj->npsta, $tfacObj->portObj->nssta, "-");
    if ($tfacObj->portObj->rslt != SUCCESS) {
        $result['rslt'] = $tfacObj->portObj->rslt;
        $result['jeop'] = "SP5:" . $tfacObj->portObj->reason;
        $result['reason'] = "PROVISIONING DISCONNECT - " . $tfacObj->portObj->reason;
        return $result;
    }

    $tfacObj->portObj->updCktLink(0, 0, 0);
    if ($tfacObj->portObj->rslt != SUCCESS) {
        $result['rslt'] = $tfacObj->portObj->rslt;
        $result['jeop'] = "SP5:" . $tfacObj->portObj->reason;
        $result['reason'] = "PROVISIONING DISCONNECT - " . $tfacObj->portObj->reason;
        return $result;
    }

    // update ORDERS table
    // $ordObj = new ORDER();
    // $ordObj->updateOrderStat($ordno,$mlo,'DISCONNECT',$ckid,$cls,$adsr,$prot,$ctyp,$ffac,$tfac);

    $result['rows'] = [];
    $result["rslt"] = SUCCESS;
    $result["reason"] = "PROVISIONING DISCONNECT - " . "SUCCESSFUL";
    return $result;
}



function updateCmdAndSendToCps($pathObj)
{
    debugLog(3, "Function: " . __FUNCTION__ . " Variable: PathId=" . $pathObj->id);
    // construct partial ackid
    $pathId = $pathObj->id;
    $partialAckid = "%-PAC-$pathId";


    // query associated cmds based on partial ackid
    // if no rows exist exit function and continue to $pathObj->resetPath()
    $cmdObj = new CMD();
    $cmdObj->queryCmdByAckid($partialAckid);

    if ($cmdObj->rslt == 'fail') {
        $response['rslt'] = $cmdObj->rslt;
        $response['reason'] = $cmdObj->reason;
        debugLog(3, "Function: CMD->queryCmdByAckid | " . $response['rslt'] . ":" . $response['reason']);
        return $response;
    }

    // assign returned rows from query to variable
    $cmdList = $cmdObj->rows;

    // store each cmd into cmdArray
    $cmdArray = [];
    foreach ($cmdList as $cmdRow) {
        foreach ($cmdRow as $key => $value) {
            if ($key == 'cmd') {
                $cmdArray[] = $value;
            }
        }
    }
    debugLog(3, "Cmds extracted: " . implode($cmdArray));

    // store existing ackid into ackidArray
    $ackidArray = [];
    foreach ($cmdList as $ackidRow) {
        foreach ($ackidRow as $key => $value)
            if ($key == 'ackid') {
                $ackidArray[] = $value;
            }
    }
    debugLog(3, "Ackids extracted: " . implode($ackidArray));

    // send cmdArray to be modified
    // array of cmds stored in response rows; assign to modifiedCmdArray
    $response = modifyCmdsForDisconnect($cmdArray);
    $modifiedCmdArray = $response['rows'];
    debugLog(3, "Modified cmds: " . implode($modifiedCmdArray));

    // send ackidArray to be modified
    // array of ackids stored in response rows; assign to modifiedAckidArray
    $response = modifyAckidForDisconnect($ackidArray);
    $modifiedAckidArray = $response['rows'];
    debugLog(3, "Modified ackid: " . implode($modifiedAckidArray));

    // change status to PENDING, clear rsp, update timestamp for each original ackid
    $stat = "PENDING";
    $rsp = "";
    for ($i = 0; $i < count($ackidArray); $i++) {
        debugLog(3, "Update Ackid: $ackidArray[$i] -> $modifiedAckidArray[$i] | Cmd: $modifiedCmdArray[$i]");

        $cmdObj = new CMD($ackidArray[$i]);
        $cmdObj->updateCmd($modifiedAckidArray[$i], $modifiedCmdArray[$i], $stat, $rsp);
        if ($cmdObj->rslt == 'fail') {
            $result['rslt'] = $cmdObj->rslt;
            $result['reason'] = $cmdObj->reason;
            debugLog(3, $result['rslt'] . ":" . $result['reason']);
            return $result;
        }
    }

    //send cmds to cps for each new ackid
    foreach ($modifiedAckidArray as $ackid) {
        $cmdObj = new CMD($ackid);
        debugLog(3, "Send Cmd: " . $cmdObj->cmd);
        $cmdObj->sendCmd();
    }

    $response['rslt'] = 'success';
    $response['reason'] = "CMDS UPDATED AND SENT TO CPS";
    return $response;
}

function modifyCmdsForDisconnect($cmdArray)
{
    debugLog(3, "Function: " . __FUNCTION__ . " Variable: cmdArray= " . implode($cmdArray));
    //cmd looks like
    // $command,action=close,row=0,col=63,row=13,col=18,row=40,col=156,row=40,col=163,row=25,col=84,row=34,col=101,ackid=1-PAC-124*

    $response['rows'] = [];

    for ($i = 0; $i < count($cmdArray); $i++) {
        $cmdString = str_replace("close", "open", $cmdArray[$i]);
        $cmdString = str_replace("PAC", "PAD", $cmdString);
        $response['rows'][] = $cmdString;
    }


    $response['rslt'] = 'success';
    $response['reason'] = "CMDS MODIFIED";

    return $response;
}

function modifyAckidForDisconnect($ackidArray)
{
    debugLog(3, "Function: " . __FUNCTION__ . " Variable: ackidArray=" . implode($ackidArray));
    // ackid looks like
    // node-PAC-pathId
    $response['rows'] = [];

    for ($i = 0; $i < count($ackidArray); $i++) {
        $ackidString = str_replace("PAC", "PAD", $ackidArray[$i]);
        $response['rows'][] = $ackidString;
    }

    $response['rslt'] = "success";
    $response['reason'] = "ACKIDS MODIFIED";
    return $response;
}


// function to get row/col, make cmd, add to t_cmdque, send to ipcCps
function instructCmdToCpsForOpeningRelays($pathObj)
{
    debugLog(3, "Function: " . __FUNCTION__ . " Variable: Path($pathObj->id)");

    // obtain row/col of path, store in rcArray
    $resp = loadRelaysRowColForCmd($pathObj);
    if ($resp['rslt'] == 'fail') {
        $response['rslt'] = 'fail';
        $response['reason'] = $resp['reason'];
        $response['rows'] = [];
        debugLog(3, $response['rslt'] . "|" . $response['reason']);
        return $response;
    }

    $rcArray = $resp['rows'];
    $resp = createAckidListForPathCmdByNode($rcArray, $pathObj->id, "PVD");
    if ($resp['rslt'] == 'fail') {
        $response['rslt'] = 'fail';
        $response['reason'] = $resp['reason'];
        $response['rows'] = [];
        debugLog(3, $response['rslt'] . "|" . $response['reason']);
        return $response;
    }

    $ackidArray = $resp['rows'];

    $resp = createCmdAndSendToCps($rcArray, $ackidArray, "open");
    if ($resp['rslt'] == 'fail') {
        $result['rslt'] = $resp['rslt'];
        $result['reason'] = $resp['reason'];
        return $result;
    }

    $response['rslt'] = 'success';
    $response['reason'] = 'CMD INSTRUCTED TO CPS FOR OPENING RELAYS SUCCESSFULLY';
    $response['rows'] = [];
    return $response;
}
