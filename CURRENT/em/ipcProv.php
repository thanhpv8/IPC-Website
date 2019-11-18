<?php
/*
    Filename: ipcProv.php
    Date: 2018-12-10
    By: Ninh
    Copyright: BHD SOLUTIONS, LLC @ 2018
*/  

include "ipcProvDisconnect.php";
include "ipcProvConnect.php";
// -- Initialize inputs -- //
	$act = "";
	if (isset($_POST['act'])) {
		$act = $_POST['act'];
	}

	$ckid = "";
	if (isset($_POST['ckid'])) {
		$ckid = $_POST['ckid'];
	}

	$cls = "";
	if (isset($_POST['cls'])) {
		$cls = $_POST['cls'];
	}

	$adsr = "";
	if (isset($_POST['adsr'])) {
		$adsr = $_POST['adsr'];
	}

	$prot = "";
	if (isset($_POST['prot'])) {
		$prot = $_POST['prot'];
	}

	$ordno = "";
	if (isset($_POST['ordno'])) {
		$ordno = $_POST['ordno'];
	}

	$mlo = "";
	if (isset($_POST['mlo'])) {
		$mlo = $_POST['mlo'];
	}

	$dd = "";
	if (isset($_POST['dd'])) {
		$dd = $_POST['dd'];
	}

	$fdd = "";
	if (isset($_POST['fdd'])) {
		$fdd = $_POST['fdd'];
	}

	$ctyp = "";
	if (isset($_POST['ctyp'])) {
		$ctyp = $_POST['ctyp'];
	}

	$cktcon = "";
	if (isset($_POST['cktcon'])) {
		$cktcon = $_POST['cktcon'];
	}

	$ffac = "";
	if (isset($_POST['ffac'])) {
		$ffac = $_POST['ffac'];
	}

	$tfac = "";
	if (isset($_POST['tfac'])) {
		$tfac = $_POST['tfac'];
	}

	$idx = "";
	if(isset($_POST['idx'])) {
		$idx = $_POST['idx'];
	}

	$newffac = "";
	if (isset($_POST['newffac'])) {
		$newffac = $_POST['newffac'];
	}

	$newtfac = "";
	if (isset($_POST['newtfac'])) {
		$newtfac = $_POST['newtfac'];
	}

	$fport= "";
	if (isset($_POST['fport'])) {
		$fport = $_POST['fport'];
	}
	
	$tport = "";
    if (isset($_POST['tport'])) {
		$tport = $_POST['tport'];
	}
	
	$newfport= "";
	if (isset($_POST['newfport'])) {
		$newfport = $_POST['newfport'];
	}
	
	$newtport = "";
    if (isset($_POST['newtport'])) {
		$newtport = $_POST['newtport'];
	}

	$tktno = "";
	if (isset($_POST['tktno'])) {
		$tktno = $_POST['tktno'];
	}

	$ackid = "";
	if (isset($_POST['ackid'])) {
		$ackid = strtoupper($_POST['ackid']);
	}


	$evtLog = new EVENTLOG($user, "PROVISIONING", "SETUP SERVICE CONNECTION", $act, '');
	$provLog = new PROVLOG();

	// --- Dispatch by ACTION ---
	if ($act == "query" || $act == "queryCkid") {
		$cktObj = new CKT();
		$cktObj->queryCkid($ckid, "", "", "");
		$result['rslt'] = $cktObj->rslt;
		$result['reason'] = $cktObj->reason;
		$result['rows'] = $cktObj->rows;
    echo json_encode($result);
		mysqli_close($db);
		return;
	}

	// if ($act == "queryOrd") {
	// 	$result = provQueryOrd($ordno, $mlo);
	// 	echo json_encode($result);
	// 	mysqli_close($db);
	// 	return;
	// }
	
	if ($act == "queryCktcon") {
		$cktconObj = new CKTCON();
		$cktconObj->queryCktconWithFac($cktcon);
		$result['rslt'] = $cktconObj->rslt;
		$result['reason'] = $cktconObj->reason;
		$result['rows'] = $cktconObj->rows;
		echo json_encode($result);
		mysqli_close($db);
		return;
	}
	
	if ($act == "queryCktconByCkid") {
		$result = queryCktconByCkid($ckid);
		echo json_encode($result);
		mysqli_close($db);
		return;
	}

	if ($act == "queryFacX" || $act == "queryFacY") {
		$facObj = new FAC();
		if ($act == "queryFacX")
			$facObj->queryFacByPtyp("X");
		else
			$facObj->queryFacByPtyp("Y");

		$result['rslt'] = $facObj->rslt;
		$result['reason'] = $facObj->reason;
		$result['rows'] = $facObj->rows;
		echo json_encode($result);
		mysqli_close($db);
		return;
	}

	if ($act == "UPDATE_CKT") {
		$result = provUpdateCkt($user, $ckid, $cls, $adsr, $prot, $ordno, $mlo, $userObj);
		$evtLog->log($result['rslt'], $result['log'] . " | " . $result['reason']);
		$provLog->log($user, $ordno, $mlo, $ckid, $cls, $adsr, $prot, $dd, $fdd, $act, $ctyp, $ffac, $fport, $tfac, $tport, $result['reason'], $tktno);
		$result['provlog'] = $provLog->reason;
		echo json_encode($result);
		mysqli_close($db);
		return;
	}
	if ($act == "CONNECT") {
    	$provLog = new PROVLOG();
        $result = provConnect($userObj, $ordno, $mlo, $ckid, $cls, $adsr, $prot, $ctyp, $ffac, $tfac);
        $evtLog->log($result["rslt"], $result['log'] . " | " . $result["reason"]);
        $provLog->log($user, $ordno, $mlo, $ckid, $cls, $adsr, $prot, $dd, $fdd, $act, $ctyp, $ffac, $fport, $tfac, $tport, $result['reason'], $tktno);
        echo json_encode($result);
        mysqli_close($db);
        return;
	}

	if ($act == "DISCONNECT") {
		$provLog = new PROVLOG();
        $result = provDisconnect($userObj, $ordno, $mlo, $ckid, $cls, $adsr, $prot, $ctyp, $ffac, $tfac);
        $evtLog->log($result["rslt"], $result["reason"]);
				$provLog->log($user, $ordno, $mlo, $ckid, $cls, $adsr, $prot, $dd, $fdd, $act, $ctyp, $ffac, $fport, $tfac, $tport, $result['reason'], $tktno);
        echo json_encode($result);
        mysqli_close($db);
        return;
	}
	
	if ($act == "CHANGE") {
	    $provLog = new PROVLOG();
        $result = provChange($userObj, $ordno, $mlo, $ckid, $cls, $adsr, $prot, $ctyp, $ffac, $tfac, $newffac, $newtfac, $fport, $tport,$newfport, $newtport, $dd, $fdd, $tktno);
        $evtLog->log($result["rslt"], $result['log'] . " | " . $result["reason"]);
        $provLog->log($user, $ordno, $mlo, $ckid, $cls, $adsr, $prot, $dd, $fdd, $act, $ctyp, $newffac, $newfport, $newtfac, $newtport, $result['reason'], $tktno);
        echo json_encode($result);
        mysqli_close($db);
        return;
		}
		
	if ($act == "PVC") {
		$result = provConnected($ackid);
		$evtLog->log($result["rslt"], $result["reason"]);
		echo json_encode($result);
		mysqli_close($db);
		return;
	}

	if ($act == "PVD") {
		$result = provDisconnected($ackid);
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

	// -- Functions Section -- //

	function provDisconnected($ackid) {
		debugLog(3, "Function: ".__FUNCTION__." Variable: Ackid - $ackid");
		$ackidExtracted = explode("-", $ackid);

		// validate ackid
		if (count($ackidExtracted) != 3 || $ackidExtracted[1] != "PVD")  {
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
		$fportObj = new PORT();
		$fportObj->loadPort($cktconObj->fport);
		if ($fportObj->rslt == 'fail') {
			$result['rslt'] = $fportObj->rslt;
			$result['reason'] = $fportObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}
	
		debugLog(3, "Tport: ".$cktconObj->tport);
		$tportObj = new PORT();
		$tportObj->loadPort($cktconObj->tport);
		if ($tportObj->rslt == 'fail') {
			$result['rslt'] = $tportObj->rslt;
			$result['reason'] = $tportObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}

		// validate state-event
		$sms = new SMS($fportObj->psta, $fportObj->ssta, "SV_DISCON");
		if ($sms->rslt == FAIL) {
			$result['rslt'] = FAIL;
			$result['jeop'] = "SP3:FAC STATUS (" . $fportObj->psta . ")";
			$result['reason'] = "PROVISIONING DISCONNECT - " . "INVALID PSTA ($sms->psta)";
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}
		$fportObj->npsta = $sms->npsta;
		$fportObj->nssta = $sms->nssta;
	
		$sms = new SMS($tportObj->psta, $tportObj->ssta, "SV_DISCON");
		if ($sms->rslt == FAIL) {
			$result['rslt'] = FAIL;
			$result['jeop'] = "SP3:FAC STATUS (" . $tportObj->psta . ")";
			$result['reason'] = "PROVISIONING DISCONNECT - " . "INVALID PSTA ($sms->psta)";
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}
		$tportObj->npsta = $sms->npsta;
		$tportObj->nssta = $sms->nssta;

		//----------------------Finish validation-------------------
		// change status of path to disconnected
		$pathObj->disconnected();
		debugLog(3, "Path PSTA = $pathObj->psta");

		debugLog(3, "Continue to resetPath");
		$pathObj->resetPath();
		$pathObj->drop();

		// Ready for DB updates
		// 1) remove IDX
		debugLog(3, "Delete Idx: Values: ".$cktconObj->con.",".$cktconObj->idx);
		$cktconObj->deleteIdx($cktconObj->con, $cktconObj->idx);
		if ($cktconObj->rslt != SUCCESS) {
			$result['rslt'] = $cktconObj->rslt;
			$result['jeop'] = "SP5:$cktconObj->reason";
			$result['reason'] = "PROVISIONING DISCONNECT - " . $cktconObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}

		// 2) if last IDX removed, then remove CKT as well
		debugLog(3, "Delete Ckt: Value: ".$cktconObj->con);
		$newCktconObj = new CKTCON($cktconObj->con);
		if ($newCktconObj->rslt == FAIL) {
			$cktObj->deleteCkt($cktconObj->ckid);
			if ($cktObj->rslt == FAIL) {
				$result['rslt'] = FAIL;
				$result['jeop'] = "SP5:$cktObj->reason";
				$result['reason'] = "PROVISIONING DISCONNECT - " . $cktObj->reason;
				debugLog(3, $result['rslt'].":".$result['reason']);
				return $result;
			}
		}

		// 3) update PORT's PSTA and link with CKT, CKTCON
		debugLog(3, "Update Fport Status: Values: ".$fportObj->psta."->".$fportObj->npsta);
		$fportObj->updPsta($fportObj->npsta, $fportObj->nssta, "-");
		if ($fportObj->rslt != SUCCESS) {
			$result['rslt'] = $fportObj->rslt;
			$result['jeop'] = "SP5:" . $fportObj->reason;
			$result['reason'] = "PROVISIONING DISCONNECT - " . $fportObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}

		$fportObj->updCktLink(0, 0, 0);
		if ($fportObj->rslt != SUCCESS) {
			$result['rslt'] = $fportObj->rslt;
			$result['jeop'] = "SP5:" . $fportObj->reason;
			$result['reason'] = "PROVISIONING DISCONNECT - " . $fportObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}

		debugLog(3, "Update Tport Status: Values: ".$tportObj->psta."->".$tportObj->npsta);
		$tportObj->updPsta($tportObj->npsta, $tportObj->nssta, "-");
		if ($tportObj->rslt != SUCCESS) {
			$result['rslt'] = $tportObj->rslt;
			$result['jeop'] = "SP5:" . $tportObj->reason;
			$result['reason'] = "PROVISIONING DISCONNECT - " . $tportObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}

		$tportObj->updCktLink(0, 0, 0);
		if ($tportObj->rslt != SUCCESS) {
			$result['rslt'] = $tportObj->rslt;
			$result['jeop'] = "SP5:" . $tportObj->reason;
			$result['reason'] = "PROVISIONING DISCONNECT - " . $tportObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}

		$result['rows'] = [];
		$result["rslt"] = SUCCESS;
		$result["reason"] = "PROVISIONING DISCONNECT - " . "SUCCESSFUL";
		debugLog(3, $result['rslt'].":".$result['reason']);
		return $result;
	}
	
	/// changes path status to connected given an ackid
	function provConnected($ackid) {
		// validate ackid
		debugLog(3, "Function: ".__FUNCTION__." Variable: Ackid - $ackid");
		$ackidExtracted = explode("-", $ackid);
		if (count($ackidExtracted) != 3 || $ackidExtracted[1] != "PVC")  {
			$result['rslt'] = 'fail';
			$result['reason'] = "INVALID ACKID - $ackid";
			return $result;
		} 

		// extract pathid from ackid
		$pathId = $ackidExtracted[2];
		debugLog(3, "PathId Extracted: - $pathId");	
		
		// find existing CKTCON by pathid, if not found - exit
		$cktconObj = new CKTCON();
		$cktconObj->loadCktconByPathId($pathId);
		if ($cktconObj->rslt == 'fail') {
			$result['rslt'] = $cktconObj->rslt;
			$result['reason'] = $cktconObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}
		debugLog(3, "Cktcon: ".$cktconObj->con." | Idx: ".$cktconObj->idx);

		// Validate PathID. load path from path id. If fail, exit
		$pathObj = new PATH();
		$pathObj->loadPathById($pathId);
		if ($pathObj->rslt == 'fail') {
			$result['rslt'] = $pathObj->rslt;
			$result['reason'] = $pathObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}

		// check existence of ackid, if not found, exit
		$cmdqueObj = new CMDQUE($ackid);
		if ($cmdqueObj->rslt == "fail") {
			$result['rslt'] = "fail";
			$result['reason'] = $cmdqueObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}
		

		//--------------------Update database------------------//
		// Update cmd status in t_cmdque
		$cmdqueObj->updateCmdStatusToCompleted();
		if ($cmdqueObj->rslt == "fail") {
			$result['rslt'] = "fail";
			$result['reason'] = $cmdqueObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
		}
		
		// Check for pending Cmds and return fail if query is a success
		$cmdqueObj->checkForPendingCmds();
		if ($cmdqueObj->rslt == "success") {
			$pathObj->connecting();
			$result['rslt'] = "fail";
			$result['reason'] = "Path is connecting |".$cmdqueObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			debugLog(3, $cmdqueObj->rows);
			return $result;
		}

		// change path status to connected
		$pathObj->connected();
		debugLog(3, "Path PSTA = $pathObj->psta");

		$result['rslt'] = 'success';
		$result['reason'] = "PATH IS CONNECTED - pathId ($pathId)";
		debugLog(3, $result['rslt'].":".$result['reason']);
		return $result;
	}

	function queryCktconByCkid($ckid) {

		$cktObj = new CKT($ckid);
		if ($cktObj->rslt == "fail") {
			$result['rslt'] = FAIL;
			$result['reason'] = "CKID $ckid NOT FOUND";
			return $result;
		}

		$cktconObj = new CKTCON();
		$cktconObj->queryCktconByCkid($ckid);
		if ($cktconObj->rslt == "fail") {
			$result['rslt'] = FAIL;
			$result['reason'] = "INVALID CKID ($ckid) for CKTCON";
			return $result;
		}

		$result['rows'] = $cktconObj->rows;

		for ($i=0; $i<count($result['rows']); $i++) {
			$result['rows'][$i]['cls'] = $cktObj->cls;
			$result['rows'][$i]['adsr'] = $cktObj->adsr;
			$result['rows'][$i]['prot'] = $cktObj->prot;
		}
		
		$result['rslt'] = SUCCESS;
		$result['reason'] = "QUERY CKID SUCCESS";
		return $result;
	}

	function provUpdateCkt($user, $ckid, $cls, $adsr, $prot, $ordno, $mlo, $userObj) {

		if ($userObj->grpObj->prov != "Y") {
			$result['rslt'] = 'fail';
			$result['jeop'] = "SP5:PERMISSION DENIED";
            $result['reason'] = 'Permission Denied';
			return $result;
		}
		
		$cktObj = new CKT($ckid);
		if ($cktObj->rslt == FAIL) {
			$result['rslt'] = FAIL;
			$result['jeop'] = "SP5:$cktObj->reason";
			$result['reason'] = $cktObj->reason;
			return $result;
		}

		$result['log'] = "ACTION = UPDATE_CKT";
		if ($ckid != $cktObj->ckid)
		$result['log'] .= " | CKID = " . $cktObj->ckid . " --> " . $ckid;

		if ($cls != $cktObj->cls)
		$result['log'] .= " | CLS = " . $cktObj->cls . " --> " . $cls;

		if ($adsr != $cktObj->adsr)
		$result['log'] .= " | ADSR = " . $cktObj->adsr . " --> " . $adsr;

		if ($prot != $cktObj->prot)
		$result['log'] .= " | PROT = " . $cktObj->prot . " --> " . $prot;

		if ($ordno != $cktObj->ordno)
		$result['log'] .= " | ORDNO = " . $cktObj->ordno . " --> " . $ordno;

		if ($mlo != $cktObj->mlo)
		$result['log'] .= " | MLO = " . $cktObj->mlo . " --> " . $mlo;

		// ORDNO must not already exist
		if ($ordno =="") {
			$result['rslt'] = FAIL;
			$result['jeop'] = "SP5: MISSING ORDNO";
			$result['reason'] = "MISSING ORDNO";
			return $result;
		}
		else {
			$ordObj = new CKT();
			$ordObj->queryCkidByOrdno($ordno);
			if (count($ordObj->rows) > 0) {
				$result['rslt'] = 'fail';
				$result['jeop'] = 'SP2: INVALID ORDNO'; 
				$result['reason'] = "ORDNO $ordno ALREADY EXISTS";
				return $result;    
			}
		}

		$cktObj->updateCkt($cls, $adsr, $prot, $ordno, $mlo);
		if ($cktObj->rslt == FAIL) {
			$result['rslt'] = FAIL;
			$result['jeop'] = "SP5:$cktObj->reason";
			$result['reason'] = $cktObj->reason;
			return $result;
		}

		$cktObj->queryCkid($ckid,"","","");
		if ($cktObj->rslt == FAIL) {
			$result["rslt"] = FAIL;
			$result['jeop'] = "SP5:$cktObj->reason";
			$result["reason"] = $cktObj->reason;
			return $result;
		}

		$result['rows'] = $cktObj->rows;
		$result["rslt"] = SUCCESS;
		$result["reason"] = "UPDATE_CKT_COMPLETED";
		return $result;
	}

	function provChange($userObj, $ordno, $mlo, $ckid, $cls, $adsr, $prot, $ctyp, $ffac, $tfac, $newffac, $newtfac, $fport, $tport,$newfport, $newtport, $dd, $fdd, $tktno) {
		// if(!(($ffac == $newffac && $tfac != $newtfac) || ($ffac != $newffac && $tfac == $newtfac))) {
		// 	$result['rslt'] = 'fail';
		// 	$result['jeop'] = "SP4:ONLY ONE NEW/IN FACILITY MUST BE THE SAME WITH THE OLD/OUT ONE";
		// 	$result['reason'] = "PROVISIONING CHANGE: ONLY ONE NEW/IN FACILITY MUST BE THE SAME WITH THE OLD/OUT ONE";
		// 	$result['log'] = "ACTION = PROV_CHANGE | ORDNO = $ordno | MLO = $mlo | CKID = $ckid | IDX = $idx | CLS = $cls | ADSR = $adsr | PROT = $prot | CONTYP = $ctyp | OLD-FAC(X) = $ffac | OLD-FAC(Y) = $tfac | NEW-FAC(X) = $newffac | NEW-FAC(Y) = $newtfac";
		// 	return $result;
		// }
		if (!validateId($ckid)) {
			$result['rslt'] = 'fail';
			$result['reason'] = "INVALID CKID FORMAT";
			return $result;
		}	
		if (!validateId($ordno)) {
			$result['rslt'] = 'fail';
			$result['reason'] = "INVALID ORDNO FORMAT";
			return $result;
		}

		if ($userObj->grpObj->prov != "Y") {
			$result['rslt'] = 'fail';
			$result['jeop'] = "SP5:PERMISSION DENIED";
            $result['reason'] = 'Permission Denied';
			return $result;
		}

		if($newffac == "" || $newtfac == "") {
			$result['rslt'] = "fail";
			$result['jeop'] = "SP2:FAC IS INVALID";
			$result['reason'] = "PROVISIONING CHANGE: MISSING IN:FAC(X/Y)";
			$result['log'] = "ACTION = PROV_CHANGE | ORDNO = $ordno | MLO = $mlo | CKID = $ckid | CLS = $cls | ADSR = $adsr | PROT = $prot | CONTYP = $ctyp | OLD-FAC(X) = $ffac | OLD-FAC(Y) = $tfac | NEW-FAC(X) = $newffac | NEW-FAC(Y) = $newtfac";
			return $result;
		}	

		$newFfacObj = new FAC($newffac);
		if ($newFfacObj->rslt != SUCCESS || $newFfacObj->port_id == 0) {
			$result['rslt'] = "fail";
			$result['jeop'] = "SP2:FAC IS INVALID";
			$result['reason'] = "PROVISIONING CHANGE: INVALID_FAC - " . $newffac;
			$result['log'] = "ACTION = PROV_CHANGE | ORDNO = $ordno | MLO = $mlo | CKID = $ckid | CLS = $cls | ADSR = $adsr | PROT = $prot | CONTYP = $ctyp | OLD-FAC(X) = $ffac | OLD-FAC(Y) = $tfac | NEW-FAC(X) = $newffac | NEW-FAC(Y) = $newtfac";
			return $result;
		}
        
		// the tfac must exist in DB and must be currently mapped
		$newTfacObj = new FAC($newtfac);
		if ($newTfacObj->rslt != SUCCESS || $newTfacObj->port_id == 0) {
			$result['rslt'] = "fail";
			$result['jeop'] = "SP2:FAC IS INVALID";
            $result['reason'] = "PROVISIONING CHANGE: INVALID_FAC - " . $newtfac;
			$result['log'] = "ACTION = PROV_CHANGE | ORDNO = $ordno | MLO = $mlo | CKID = $ckid | CLS = $cls | ADSR = $adsr | PROT = $prot | CONTYP = $ctyp | OLD-FAC(X) = $ffac | OLD-FAC(Y) = $tfac | NEW-FAC(X) = $newffac | NEW-FAC(Y) = $newtfac";
			return $result;
		}
		
		if(!(($newFfacObj->portObj->ckt_id > 0 && $newTfacObj->portObj->ckt_id == 0) ||  
		($newFfacObj->portObj->ckt_id == 0 && $newTfacObj->portObj->ckt_id > 0) ||
		($newFfacObj->portObj->ckt_id == 0 && $newTfacObj->portObj->ckt_id == 0))) {
			$result['rslt'] = "fail";
			$result['jeop'] = "SP4:ONE FAC MUST BE THE UNCONNECTED ONE";
			$result['reason'] = "PROV CHANGE: INVALID FACS - $newffac AND $newtfac";
			$result['log'] = "ACTION = PROV_CHANGE | ORDNO = $ordno | MLO = $mlo | CKID = $ckid | CLS = $cls | ADSR = $adsr | PROT = $prot | CONTYP = $ctyp | OLD-FAC(X) = $ffac | OLD-FAC(Y) = $tfac | NEW-FAC(X) = $newffac | NEW-FAC(Y) = $newtfac";
			return $result;
		}

		/////////////---Begin---/////////////
		$provLog = new PROVLOG();
		$result = provDisconnect($userObj, $ordno, $mlo, $ckid, $cls, $adsr, $prot, $ctyp, $ffac, $tfac);
		$provLog->log($userObj->uname, $ordno, $mlo, $ckid, $cls, $adsr, $prot, $dd, $fdd, "PROVCHANGE", $ctyp, $ffac, $fport, $tfac, $tport, $result['reason'], $tktno);
		if($result['rslt'] == 'fail') {
			$result['log'] = "ACTION = PROV_CHANGE | ORDNO = $ordno | MLO = $mlo | CKID = $ckid | CLS = $cls | ADSR = $adsr | PROT = $prot | CONTYP = $ctyp | OLD-FAC(X) = $ffac | OLD-FAC(Y) = $tfac | NEW-FAC(X) = $newffac | NEW-FAC(Y) = $newtfac";
			return $result;
		}

		$result = provConnect($userObj, $ordno, $mlo, $ckid, $cls, $adsr, $prot, $ctyp, $newffac, $newtfac);
		$provLog->log($userObj->uname, $ordno, $mlo, $ckid, $cls, $adsr, $prot, $dd, $fdd, "PROVCHANGE", $ctyp, $newffac, $newfport, $newtfac, $newtport, $result['reason'], $tktno);
		$result['log'] = "ACTION = PROV_CHANGE | ORDNO = $ordno | MLO = $mlo | CKID = $ckid | CLS = $cls | ADSR = $adsr | PROT = $prot | CONTYP = $ctyp | OLD-FAC(X) = $ffac | OLD-FAC(Y) = $tfac | NEW-FAC(X) = $newffac | NEW-FAC(Y) = $newtfac";
		return $result;
	}


	