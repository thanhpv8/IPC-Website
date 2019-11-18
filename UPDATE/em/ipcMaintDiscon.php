<?php
/*  Filename: ipcMaintDiscon.php
    Date: 2018-11-20
    By: Thanh
    Copyright: BHD SOLUTIONS, LLC @ 2018
*/  
	    
	// -- Initialize inputs --
	$act = "";
	if (isset($_POST['act'])) {
		$act = $_POST['act'];
	}

	$ordno = "";
	if (isset($_POST['ordno'])) {
		$ordno = strtoupper($_POST['ordno']);
	}

	$ckid = "";
	if (isset($_POST['ckid'])) {
		$ckid = strtoupper($_POST['ckid']);
	}

	$ffac = "";
	if (isset($_POST['ffac'])) {
		$ffac = $_POST['ffac'];
	}

	$tfac = "";
	if (isset($_POST['tfac'])) {
		$tfac = $_POST['tfac'];
	}

	$tktno = "";
	if (isset($_POST['tktno'])) {
		$tktno = strtoupper($_POST['tktno']);
	}
    
	$dd = "";
	if (isset($_POST['dd'])) {
		$dd = $_POST['dd'];
	}
    
	$fdd = "";
	if (isset($_POST['fdd'])) {
		$fdd = $_POST['fdd'];
	}
    
	$fport = "";
	if (isset($_POST['fport'])) {
		$fport = $_POST['fport'];
	}
    
	$tport = "";
	if (isset($_POST['tport'])) {
		$tport = $_POST['tport'];
	}	
	
	$ackid = "";
	if (isset($_POST['ackid'])) {
		$ackid = strtoupper($_POST['ackid']);
	}	
	
	$maintLog 	= new MAINTLOG();
	$evtLog = new EVENTLOG($user, "MAINTENANCE", "SETUP MAINTENANCE CONNECTION", $act, '');

	// --- Dispatch to Funcitons by ACTION ---
		
	if ($act == "MTC_DISCON") {
		$result = maintDisconnect($ckid, $ffac, $tfac, $tktno, $userObj);
		$evtLog->log($result["rslt"], $result['log'] . " | " . $result["reason"]);
		$maintLog->log($user, $tktno, $mlo, $ckid, $cls, $adsr, $prot, $dd, $fdd, $act, $ctyp, $ffac, $fport, $tfac, $tport, $result['reason'], $ordno);
		echo json_encode($result);
		mysqli_close($db);
		return;
	}

	if ($act == "MTD") {
		$result = maintDisconnected($ackid);
		$evtLog->log($result["rslt"], $result["reason"]);
		echo json_encode($result);
		mysqli_close($db);
		return;
	}

	else {
		$result["rslt"] = FAIL;
		$result["reason"] = "INVALID_ACTION";
		$evtLog->log($result['rslt'], $result['reason']);
		echo json_encode($result);
		mysqli_close($db);
		return;
	}
	//this function will validate infor of path 
	//and send cmd to cps to disconnect path for maintenance
  	function maintDisconnect($ckid, $ffac, $tfac, $tktno, $userObj) {
		debugLog(3, "Function: ".__FUNCTION__. "Variables: tktn = $tktno | ckid = $ckid | ffac = $ffac | tfac = $tfac");
		$result['log'] = "ACTION = MAINTDISCON | CKID = $ckid | FFAC = $ffac | TFAC = $tfac | TKTNO = $tktno";
		
		if ($userObj->grpObj->maint != "Y") {
			$result['rslt'] = 'fail';
            $result['jeop'] = "SP5:PERMISSION DENIED";            
			$result['reason'] = "MAINTENANCE DISCONNECT - " . 'PERMISSION DENIED';
			return $result;
		}

		if(!validateId($tktno)) {
			$result["rslt"] = 'fail';
            $result['jeop'] = "SP5:TKTNO IS EMPTY";            
			$result["reason"] = "MAINTENANCE DISCONNECT - " . "INVALID TKTNO FORMAT";
			return $result;
		}

		// Check REF table for auto_ordno & auto_ckid
		$refObj = new REF();
		if ($tktno == '' || $tktno == null) {
			if ($refObj->ref['auto_ordno'] == 'Y') {
				$time = new DateTime('now');
				$timestr = $time->format('H:i:s');
				$ordno = strtoupper(substr($userObj->uname, 0, 4)) . $timestr;
			} else {
				$result['rslt'] = 'fail';
				$result['reason'] = "MAINTENANCE DISCONNECT - " . "TKTNO MISSING";
				return $result;
			}
		}
    

		// the ckid must exist in DB and must attach a cktcon
		$cktObj = new CKT($ckid);
		if ($cktObj->rslt == FAIL || $cktObj->cktcon == 0) {
			$result["rslt"] = FAIL;
            $result['jeop'] = "SP5:CKID ". $ckid . " DOES NOT EXIST";            
			$result["reason"] = "MAINTENANCE DISCONNECT - " . "CKID ". $ckid . " DOES NOT EXIST";
			return $result;
		}

		// the cktObj->cktcon must exist in DB
		$cktconObj = new CKTCON($cktObj->cktcon);
		if ($cktconObj->rslt == FAIL) {
			$result["rslt"] = FAIL;
            $result['jeop'] = "SP5:CKTCON " . $cktObj->cktcon . " DOES NOT EXIST";           
			$result["reason"] = "MAINTENANCE DISCONNECT - " . "CKTCON " . $cktObj->cktcon . " DOES NOT EXIST";
			return $result;
		}
		
		// the ffac must exist in DB and must be currently mapped
		$ffacObj = new FAC($ffac);
		if ($ffacObj->rslt != SUCCESS || $ffacObj->port_id == 0) {
			$result['rslt'] = "fail";
            $result['jeop'] = "SP2:INVALID FAC(X): " . $ffac;          
			$result['reason'] = "MAINTENANCE DISCONNECT - " . "INVALID FAC(X): " . $ffac;
			return $result;
		}
		
		// the tfac must exist in DB and must be currently mapped
		$tfacObj = new FAC($tfac);
		if ($tfacObj->rslt != SUCCESS || $tfacObj->port_id == 0) {
			$result['rslt'] = "fail";
            $result['jeop'] = "SP2:INVALID FAC(Y): " . $tfac;          
			$result['reason'] = "MAINTENANCE DISCONNECT - " . "INVALID FAC(Y): " . $tfac;
			return $result;
		}

		// first, there must not be any MTCD in the CKT currently (only one MTCD can be at given time)
		if($cktconObj->findIdxByCtyp('MTCD') != 0){
			$result['rslt'] = "fail";
            $result['jeop'] = "SP5:MAX ALLOWED NUMBER OF MTCD IS 1. | THERE IS ALREADY ONE MTCD ON THIS CKT";         
			$result['reason'] = "MAINTENANCE DISCONNECT - " . "MAX ALLOWED NUMBER OF MTCD IS 1. | THERE IS ALREADY ONE MTCD ON THIS CKT";
			return $result;
		}

		// second, the pair fac(x) and fac(Y) must exist in one of cktObj->cktcon[idx]
		if ($cktconObj->loadIdxByPortIds($ffacObj->port_id, $tfacObj->port_id) == 0) {
			$result["rslt"] = FAIL;
            $result['jeop'] = "SP4:FAC " . $ffac . " or " . $tfac . " are not part of CKT";     
			$result["reason"] = "MAINTENANCE DISCONNECT - " . "FAC: " . $ffac . " or " . $tfac . " are not part of CKT";
			return $result;
		}

						
		// validate state-event
		$fpObj = new PORT($ffacObj->port_id);
		$tpObj = new PORT($tfacObj->port_id);

		$sms = new SMS($fpObj->psta, $fpObj->ssta, "MT_DISCON");
		if ($sms->rslt != SUCCESS) {
			$result['rslt'] = $sms->rslt;
            $result['jeop'] = "SP3:FAC STATUS (".$fpObj->psta.")";            
			$result['reason'] = "MAINTENANCE DISCONNECT - " . "INVALID PSTA ($sms->psta)";
			return $result;
		}
		$fpObj->npsta = $sms->npsta;
		$fpObj->nssta = $sms->nssta;

		$sms = new SMS($tpObj->psta, $tpObj->ssta, "MT_DISCON");
		if ($sms->rslt != SUCCESS) {
			$result['rslt'] = $sms->rslt;
            $result['jeop'] = "SP3:FAC STATUS (".$tpObj->psta.")";            
			$result['reason'] = "MAINTENANCE DISCONNECT - " . "INVALID PSTA ($sms->psta)";
			return $result;
		}
		$tpObj->npsta = $sms->npsta;
		$tpObj->nssta = $sms->nssta;

		// update ticketNo
		$cktObj->setTktno($tktno);
		if ($cktObj->rslt == FAIL) {
			$result["rslt"] = FAIL;
			$result['jeop'] = "SP5:".$cktObj->reason;            
			$result["reason"] = "MAINTENANCE CONNECT - " . $cktObj->reason;
			return $result;
		}
		
		// now, process the MTC_DISCONNECT
		// 1) locate the PATH and open all relays
		/* will enable after 100% non-blocking test is done */
		$pathObj = new PATH($fpObj->port, $tpObj->port);
		$pathObj->load();
        if($pathObj->rslt == 'fail') {
			$result['rslt'] = 'fail';
            $result['jeop'] = "SP5:UNALBE TO LOAD PATH";            
            $result['reason'] = "MAINTENANCE DISCONNECT - " . $pathObj->reason;
            return $result; 
		}
		
		debugLog(3, "Path status: " . $pathObj->psta);
		$response = instructCmdToCpsForOpeningRelays_mtd($pathObj);
		if ($response['rslt'] == 'success') {
			$pathObj->disconnecting();
			$result['rslt'] = $response['rslt'];
			$result['reason'] = "MAINTENANCE DISCONNECT - PATH IS DISCONNECTING";
			debugLog(3, $result['rslt'] . ":" . $result['reason']);
			return $result;
		} else {
			$result['rslt'] = $response['rslt'];
			$result['reason'] = "MAINTENANCE DISCONNECT - " . $response['reason'];
			debugLog(3, $result['rslt'] . ":" . $result['reason']);
			return $result;
		}
	}

	//this function process response from cps and 
	//update database for maintenance
	function maintDisconnected($ackid) {
		debugLog(3, "Function: ".__FUNCTION__." Variable: Ackid - $ackid");
		$ackidExtracted = explode("-", $ackid);

		// validate ackid
		if (count($ackidExtracted) != 3 || $ackidExtracted[1] != "MTD")  {
			$result['rslt'] = 'fail';
			$result['reason'] = "INVALID ACKID - $ackid";
			return $result;
		} 

		// extract pathId
		$pathId = $ackidExtracted[2];
		debugLog(3, "PathId Extracted: - $pathId");

		//check existence of ckcon by PathID, if not found, exit
		$cktconObj = new CKTCON();
		$cktconObj->loadCktconByPathId($pathId);
		if ($cktconObj->rslt == 'fail') {
			$result['rslt'] = $cktconObj->rslt;
			$result['reason'] = $cktconObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}
		debugLog(3, "Cktcon: ".$cktconObj->con." | Idx: ".$cktconObj->idx);
		
		// Check existenc of path inside database. load path from pathId, if fail, exit
		$pathObj = new PATH();
		$pathObj->loadPathById($pathId);
		if ($pathObj->rslt == 'fail') {
			$result['rslt'] = $pathObj->rslt;
			$result['reason'] = $pathObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}

		//check existence of ackid in database, if not found, exit
		$cmdqueObj = new CMDQUE($ackid);
		if ($cmdqueObj->rslt == 'fail') {
			$result['rslt'] = $cmdqueObj->rslt;
			$result['reason'] = $cmdqueObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}

		// update status in table to COMPLETED
		$cmdqueObj->updateCmdStatusToCompleted();
		if ($cmdqueObj->rslt == 'fail') {
			$result['rslt'] = $cmdqueObj->rslt;
			$result['reason'] = $cmdqueObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}

		// check if any other cmds are pending associted with ackid in db - if yes exit
		$cmdqueObj->checkForPendingCmds();
		if ($cmdqueObj->rslt == 'success') {
			$pathObj->disconnecting();
			$result['rslt'] = $cmdqueObj->rslt;
			$result['reason'] = "Path is disconnecting |".$cmdqueObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			debugLog(3, $cmdqueObj->rows);
			return $result;
		}

		// continue if no other cmds are PENDING in t_cmdque
		//---------create ckt, cktcon, port, path objs ---------
		debugLog(3, "Ckid: ".$cktconObj->ckid);
		$cktObj = new CKT($cktconObj->ckid);
		if ($cktObj->rslt == FAIL) {
			$result['rslt'] = FAIL;
			$result['jeop'] = "SP5:$cktObj->reason";
			$result['reason'] = "PROVISIONING DISCONNECT - " . $cktObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}

		debugLog(3, "Fport: ".$cktconObj->fport);
		$fpObj = new PORT();
		$fpObj->loadPort($cktconObj->fport);
		if ($fpObj->rslt == 'fail') {
			$result['rslt'] = $fpObj->rslt;
			$result['reason'] = $fpObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}
	
		debugLog(3, "Tport: ".$cktconObj->tport);
		$tpObj = new PORT();
		$tpObj->loadPort($cktconObj->tport);
		if ($tpObj->rslt == 'fail') {
			$result['rslt'] = $tpObj->rslt;
			$result['reason'] = $tpObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}

		// validate state-event
		debugLog(3, "Fport: PSTA-$fpObj->psta, SSTA-$fpObj->ssta");
		$sms = new SMS($fpObj->psta, $fpObj->ssta, "MT_DISCON");
		if ($sms->rslt == FAIL) {
			$result['rslt'] = FAIL;
			$result['jeop'] = "SP3:FAC STATUS (" . $fpObj->psta . ")";
			$result['reason'] = "PROVISIONING DISCONNECT - " . "INVALID PSTA ($sms->psta)";
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}
		$fpObj->npsta = $sms->npsta;
		$fpObj->nssta = $sms->nssta;
	
		debugLog(3, "Fport: PSTA($fpObj->psta), SSTA($fpObj->ssta)");
		$sms = new SMS($tpObj->psta, $tpObj->ssta, "MT_DISCON");
		if ($sms->rslt == FAIL) {
			$result['rslt'] = FAIL;
			$result['jeop'] = "SP3:FAC STATUS (" . $tpObj->psta . ")";
			$result['reason'] = "PROVISIONING DISCONNECT - " . "INVALID PSTA ($sms->psta)";
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}
		$tpObj->npsta = $sms->npsta;
		$tpObj->nssta = $sms->nssta;

		//----------------------Finish validation-------------------
		// change status of path to disconnected
		$pathObj->disconnected();
		debugLog(3, "Path PSTA = $pathObj->psta");

		debugLog(3, "Continue to resetPath");

		$pathObj->resetPath();
		
		// // 2) delete the PATH from t_path
		$pathObj->drop();
		
		// 2.5) update CKTCON path=0
		debugLog(3, "Update CKTCON (idx: $cktconObj->idx) PATH to 0");
		$cktconObj->updPath($cktconObj->con, $cktconObj->idx, 0);
		if ($cktconObj->rslt == FAIL) {
			$result["rslt"] = FAIL;
            $result['jeop'] = "SP5:$cktconObj->reason";            
			$result["reason"] = "MAINTENANCE DISCONNECT - " . $cktconObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}
		// 3) update t_ports ///check ckt_id
		debugLog(3, "Update Fport Status: Values: ".$fpObj->psta."->".$fpObj->npsta);
		$fpObj->updPsta($fpObj->npsta, $fpObj->nssta, $fpObj->substa);
		if ($fpObj->rslt != SUCCESS) {
			$result['rslt'] = $fpObj->rslt;
            $result['jeop'] = "SP5:$fpObj->reason";            
			$result['reason'] = "MAINTENANCE DISCONNECT - " . $fpObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return;
		}
		debugLog(3, "Update Tport Status: Values: ".$fpObj->psta."->".$fpObj->npsta);
		$tpObj->updPsta($tpObj->npsta, $tpObj->nssta, $tpObj->substa);
		if ($tpObj->rslt != SUCCESS) {
			$result['rslt'] = $tpObj->rslt;
            $result['jeop'] = "SP5:$tpObj->reason";            
			$result['reason'] = "MAINTENANCE DISCONNECT - " . $tpObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return;
		}

		// 4) update ctyp with MTCD
		debugLog(3, "Update CKTCON (idx: $cktconObj->idx) type to MTCD. Original CTYP: $cktconObj->ctyp");
		$cktconObj->updCtyp($cktObj->cktcon, $cktconObj->idx, 'MTCD', $cktconObj->ctyp);
		if ($cktconObj->rslt == FAIL) {
			$result["rslt"] = FAIL;
            $result['jeop'] = "SP5:$cktconObj->reason";            
			$result["reason"] = "MAINTENANCE DISCONNECT - " . $cktconObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}
		
		$result['rows'] = [];
		$result["rslt"] = SUCCESS;
		$result["reason"] = "MAINTENANCE DISCONNECT - " . "SUCCESSFUL";
		debugLog(3, $result['rslt'].":".$result['reason']);
		return $result;

	}

	// function to get row/col, make cmd, add to t_cmdque, send to ipcCps
	function instructCmdToCpsForOpeningRelays_mtd($pathObj)
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
		$resp = createAckidListForPathCmdByNode($rcArray, $pathObj->id, "MTD");
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

	
?>