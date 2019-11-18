<?php
/*
 * Copy Right @ 2018
 * BHD Solutions, LLC.
 * Project: CO-IPC
 * Filename: coQueryPath.php
 * Change history: 
 * 11-2-2018: created (Alex)
 */

//Initialize expected inputs
$act = "";
if (isset($_POST['act'])) {
	$act = $_POST['act'];
}

$id = "";
if (isset($_POST['id'])) {
	$id = $_POST['id'];
}

$node = "";
if (isset($_POST['node'])) {
	$node = $_POST['node'];
}

$slot = "";
if (isset($_POST['slot'])) {
	$slot = $_POST['slot'];
}

$pnum = "";
if (isset($_POST['pnum'])) {
	$pnum = $_POST['pnum'];
}

$ptyp = "";
if (isset($_POST['ptyp'])) {
	$ptyp = $_POST['ptyp'];
}

$ckid = "";
if (isset($_POST['ckid'])) {
	$ckid = $_POST['ckid'];
}

$port = "";
if (isset($_POST['port'])) {
	$port = $_POST['port'];
}

$fac = "";
if (isset($_POST['fac'])) {
	$fac = $_POST['fac'];
}

$cmd = "";
if (isset($_POST['cmd'])) {
	$cmd = $_POST['cmd'];
}
$evtLog = new EVENTLOG($user, "MAINTENANCE", "PATH ADMINISTRATION", $act, $_POST);


$pathObj = new PATHS();
if ($pathObj->rslt == "fail") {
	$result["rslt"] = "fail";
	$result["reason"] = $pathObj->reason;
	$evtLog->log($result["rslt"], $result["reason"]);
	echo json_encode($result);
	mysqli_close($db);
	return;
}



// Dispatch to Functions

if ($act == "queryByNode") {
	$result = queryPathByNode($pathObj, $node, $slot);
	$evtLog->log($result["rslt"], $result["reason"]);
	echo json_encode($result);
	mysqli_close($db);
	return;
}

if ($act == "queryByFac") {
	$result = queryPathByFac($pathObj, $fac);
	$evtLog->log($result["rslt"], $result["reason"]);
	echo json_encode($result);
	mysqli_close($db);
	return;
}

if ($act == "queryByPort") {
	$result = queryPathByPort($pathObj, $port);
	$evtLog->log($result["rslt"], $result["reason"]);
	echo json_encode($result);
	mysqli_close($db);
	return;
}

if ($act == "query") {
	$result = queryPathsByCkid($pathObj, $ckid);
	$evtLog->log($result["rslt"], $result["reason"]);
	echo json_encode($result);
	mysqli_close($db);
	return;
}

if ($act == "cmdResponse") {
	$result = cmdResponse($cmd);
	$evtLog->log($result["rslt"], $result["reason"]);
	echo json_encode($result);
	mysqli_close($db);
	return;
} else {
	$result["rslt"] = "fail";
	$result["reason"] = "Invalid action!";
	$evtLog->log($result["rslt"], $result["reason"]);
	echo json_encode($result);
	mysqli_close($db);
	return;
}

function queryPathByPort($pathObj, $port)
{

	$portObj = new PORT();
	$portObj->loadPort($port);
	if (count($portObj->rows) == 0) {
		$result["rslt"]   = "fail";
		$result["reason"] = "PORT $port DOES NOT EXIST";
		$result["rows"]   = [];
		return $result;
	}

	if ($portObj->cktcon == 0) {
		$result["rslt"]   = "fail";
		$result["reason"] = "NO PATH FOUND";
		$result["rows"]   = [];
		return $result;
	}

	$cktconObj = new CKTCON($portObj->cktcon);
	$cktconObj->loadIdx($portObj->con_idx);

	$pathObj->queryPathById($cktconObj->path);
	$result["rslt"]   = $pathObj->rslt;
	$result["reason"] = $pathObj->reason;
	$result["rows"]   = $pathObj->rows;
	return $result;
}


function queryPathByFac($pathObj, $fac)
{

	$facObj = new FAC($fac);
	if (count($facObj->rows) == 0) {
		$result["rslt"]   = "fail";
		$result["reason"] = "FACILITY $fac DOES NOT EXIST";
		$result["rows"]   = [];
		return $result;
	}

	if ($facObj->port_id == 0) {
		$result["rslt"]   = "fail";
		$result["reason"] = "NO PATH FOUND";
		$result["rows"]   = [];
		return $result;
	}

	if ($facObj->portObj->cktcon == 0) {
		$result["rslt"]   = "fail";
		$result["reason"] = "NO PATH FOUND";
		$result["rows"]   = [];
		return $result;
	}

	$cktconObj = new CKTCON($facObj->portObj->cktcon);
	$cktconObj->loadIdx($facObj->portObj->con_idx);

	$pathObj->queryPathById($cktconObj->path);
	$result["rslt"]   = $pathObj->rslt;
	$result["reason"] = $pathObj->reason;
	$result["rows"]   = $pathObj->rows;
	return $result;
}

function queryPathsByCkid($pathObj, $ckid)
{

	if ($ckid !== '') {
		$cktObj = new CKT($ckid);
		if (count($cktObj->rows) == 0) {
			$result["rslt"]   = 'fail';
			$result["reason"] = "CKID ($ckid) DOES NOT EXIST";
			$result["rows"]   = [];
			return $result;
		}
	}

	$pathObj->queryPathsByCkid($ckid);
	$result["rslt"]   = $pathObj->rslt;
	$result["reason"] = $pathObj->reason;
	$result["rows"]   = $pathObj->rows;
	return $result;
}

function queryPathByNode($pathObj, $node, $slot)
{

	if ($node == "") {
		$result["rslt"]   = 'fail';
		$result["reason"] = "MISSING NODE";
		$result["rows"]   = [];
		return $result;
	}
	if ($slot == "") {
		$result["rslt"]   = 'fail';
		$result["reason"] = "MISSING SLOT";
		$result["rows"]   = [];
		return $result;
	}
	$node = $node - 1;
	$slot = $slot - 1;
	$pathObj->queryPathByNode($node, $slot);
	$result["rslt"]   = $pathObj->rslt;
	$result["reason"] = $pathObj->reason;
	$result["rows"]   = $pathObj->rows;
	return $result;
}

function cmdResponse($cmd)
{
	debugLog(3, "Function: " . __FUNCTION__ . " Variable: Cmd= " . $cmd);
	// Extract and Validate ACKID, extract and validate PathId
	$result = validateCmdResponse($cmd);
	if ($result['rslt'] == "fail") {
		debugLog(3, $result['rslt'] . " : " . $result['reason']);
		return $result;
	}

	$ackid = $result['row']['ackid'];
	$pathId = $result['row']['pathId'];
	$cmd = $result['row']['cmd'];
	$ackidCode = $result['row']['ackidCode'];

	// Determine what $pathStatus should be based on the ackidCode
	if ($ackidCode == "PVC") 
		$pathStatus = "CONNECTING";
	else if ($ackidCode == "PVD") 
		$pathStatus = "DISCONNECTING";
	

	// Validate PATH exists and that PATH->psta is appropriate using $pathId and $pathStatus
	$result = validatePathStatus($pathId, $pathStatus);
	if ($result['rslt'] == 'fail') {
		debugLog(3, $result['rslt'] . " : " . $result['reason']);
		return $result;
	}

	// update t_cmdque with response based on ackid
	$result = updateCmdQueWithResp($ackid, $cmd);
	if ($result['rslt'] == "fail") {
		debugLog(3, $result['rslt'] . " : " . $result['reason']);
		return $result;
	}

	// Determine appropriate new path status
	if ($pathStatus == "CONNECTING") {
		$newPathStatus = "CONNECTED"; 
	} else if ($pathStatus == "DISCONNECTING") {
		$newPathStatus = "DISCONNECTED";
	}

	// update path status based on pathId
	$result = updatePathStatus($pathId, $newPathStatus);
	if ($result['rslt'] == "fail") {
		debugLog(3, $result['rslt'] . " : " . $result['reason']);
		return $result;
	}

	$result['rslt'] = "success";
	$result['reason'] = "PROVISIONING - PATH IS " . $newPathStatus;
	debugLog(3, $result['rslt'] . " : " . $result['reason']);
	return $result;
}


function validateCmdResponse($cmd)
{
	debugLog(3, "Function: " . __FUNCTION__ . " Variable: Cmd= " . $cmd);
	// cmd may look like "$ackid=1-PAC-68,row=,col,. . ."
	$ackid = extractAckid($cmd);
	if ($ackid == null) {
		$response['rslt'] = "fail";
		$response['reason'] = "Missing ACKID";
		return $response;
	}
	debugLog(3, "ackid extracted: $ackid");

	// $ackidCode should be in format: node-PAC-pathId or node-PAD-pathId
	$ackidArray = explode('-', $ackid);
	$ackidCode = $ackidArray[1];

	debugLog(3, "ackidCode extracted: $ackidCode");
	// Determine ACKID CODE and determine PATH STATUS for validation
	if ($ackidCode == "PAC" || $ackidCode == "PAD") {
		
		// Extract pathId from ackid
		$pathId = $ackidArray[2];
		debugLog(3, "pathId extracted: $pathId");

		$response['rslt'] = "success";
		$response['reason'] = "ACKID validated";
		$response['row'] = array(
			"ackid" 	=> $ackid,
			"pathId" 	=> $pathId,
			"cmd" 		=> $cmd,
			"ackidCode" => $ackidCode
		);
	} else {
		$response['rslt'] = 'fail';
		$response['reason'] = "INVALID ACKID FORMAT - $ackid";
	}
	debugLog(3, $response['rslt'] . " : " . $response['reason']);
	return $response;

}

function updateCmdQueWithResp($ackid, $cmd)
{
	debugLog(3, "Function: " . __FUNCTION__ . " Variable: ackid = " . $ackid . " | cmd = " . $cmd);

	// 5) If ackid does not exist in table then exit
	$cmdObj = new CMD($ackid);
	if ($cmdObj->rslt == "fail") {
		$response['rslt'] = $cmdObj->rslt;
		$response['reason'] = $cmdObj->reason;
		return $response;
	}

	// 6) update stat, time, and insert cmd into t_cmdque.rsp
	$cmdObj->updCmd("COMPLETED", $cmd);
	debugLog(3, "CMD for ackid($cmdObj->ackid) is $cmdObj->stat");

	$partialAckidFormat = explode('-', $ackid, 2);
	$partialAckidFormat = "%-" . $partialAckidFormat[1];

	// 7) If query was not successful then return fail
	$cmdObj->queryCmdByAckid($partialAckidFormat);
	if ($cmdObj->rslt == "fail") {
		$response['rslt'] = $cmdObj->rslt;
		$response['reason'] = $cmdObj->reason;
		return $response;
	}
	debugLog(3, "CMD(s) queried for ackid format ($partialAckidFormat)");
	debugLog(3, $cmdObj->rows);
	// Loop through rows to check for PENDING status, if so, then exit
	for ($i = 0; $i < count($cmdObj->rows); $i++) {
		if ($cmdObj->rows[$i]['stat'] == "PENDING") {
			$response['rslt'] = 'fail';
			$response['reason'] = "Path CMDs are not FINISHED: ".$cmdObj->rows[$i]['cmd'] ;
			return $response;
		}
	}

	// updateCmdQueWithResp is successful
	$response['rslt'] = "success";
	$response['reason'] = "Path CMDs are COMPLETED";
	debugLog(3, $response['rslt'] . " : " . $response['reason']);
	return $response;
}

function updatePathStatus($pathId, $newPathStatus)
{
	debugLog(3, "Function: " . __FUNCTION__ . " Variable: pathId = " . $pathId . " | newPathStatus = " . $newPathStatus);
	// 10)

	// Create shell to call functions from PATH class
	$pathObj = new PATH();
	if ($pathObj->rslt == "fail") {
		$response['rslt'] = $pathObj->rslt;
		$response['reason'] = $pathObj->reason;
		return $response;
	}

	// load information into path object to call function connected afterwards
	$pathObj->loadPathById($pathId);
	if ($pathObj->rslt == "fail") {
		$response['rslt'] = $pathObj->rslt;
		$response['reason'] = $pathObj->reason;
		return $response;
	}

	if ($newPathStatus == "CONNECTED") {
		// update psta of the path to connected from connecting
		$pathObj->connected();
	} else if ($newPathStatus == "DISCONNECTED") {
		// update psta of the path to disconnected from disconnecting
		$pathObj->disconnected();
	} else {
		$response['rslt'] = "fail";
		$response['reason'] = "INVALID PATH STATUS - " . $newPathStatus;
		return $response;
	}

	if ($pathObj->rslt == "fail") {
		$response['rslt'] = $pathObj->rslt;
		$response['reason'] = $pathObj->reason;
		return $response;
	}

	// updatePathStatus successful
	$response['rslt'] = "success";
	$response['reason'] = "PATH PSTA UPDATED - $pathObj->psta";
	debugLog(3, $response['rslt'] . " : " . $response['reason']);
	return $response;
}

function validatePathStatus($pathId, $pathStatus) {

	debugLog(3, "Function: " . __FUNCTION__ . " Variable: pathId = " . $pathId . " | pathStatus = " . $pathStatus);

	// Check that pathId exists in t_path
	$pathObj = new PATH();
	if ($pathObj->rslt == "fail") {
		$response['rslt'] = $pathObj->rslt;
		$response['reason'] = $pathObj->reason;
		return $response;
	}

	// load information into the PATH object so that we can obtain psta
	$pathObj->loadPathById($pathId);
	if ($pathObj->rslt == "fail") {
		$response['rslt'] = $pathObj->rslt;
		$response['reason'] = $pathObj->reason;
		return $response;
	}

	debugLog(3, "path psta extracted: " . $pathObj->psta);

	// Check that PATH->psta is == $pathStatus sent in
	if ($pathObj->psta != $pathStatus) {
		$response['rslt'] = 'fail';
		$response['reason'] = "INVALID PATH STATUS - " . $pathObj->psta;
		return $response;
	}

	$response['rslt'] = 'success';
	$response['reason'] = "PATH VALIDATED - pathId = " . $pathId . ", PATH STATUS = " . $pathStatus;
	return $response;

}
