<?php
/*  Filename: ipcMaintRestore.php
    Date: 2019-01-20
    By: Ninh
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

    $mlo = "";
	if (isset($_POST['mlo'])) {
		$mlo = strtoupper($_POST['mlo']);
	}

	$ckid = "";
	if (isset($_POST['ckid'])) {
		$ckid = strtoupper($_POST['ckid']);
	}

    $cls = "";
	if (isset($_POST['cls'])) {
		$cls = strtoupper($_POST['cls']);
	}

    $adsr = "";
	if (isset($_POST['adsr'])) {
		$adsr = strtoupper($_POST['adsr']);
	}

    $prot = "";
	if (isset($_POST['prot'])) {
		$prot = strtoupper($_POST['prot']);
	}

    $ctyp = "";
	if (isset($_POST['ctyp'])) {
		$ctyp = strtoupper($_POST['ctyp']);
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

    $maintLog = new MAINTLOG ();
    $evtLog = new EVENTLOG($user, "MAINTENANCE", "SETUP MAINTENANCE CONNECTION", $act, '');

    //DISPATCH AREA
		
	if ($act == "RESTORE_MTCD") {
        $result = maintRestoreMtcd($userObj, $tktno, $mlo, $ckid, $cls, $adsr, $prot, $ctyp, $ffac, $tfac);
        $evtLog->log($result["rslt"], $result['log'] . " | " . $result["reason"]);
        $maintLog->log($user, $tktno, $mlo, $ckid, $cls, $adsr, $prot, $dd, $fdd, $act, $ctyp, $ffac, $fport, $tfac, $tport, $result['reason'], $ordno);
        echo json_encode($result);
        mysqli_close($db);
        return;
    }

    if ($act == "MTCD") {
        $result = mtcd($ackid);
        $evtLog->log($result['rslt'], $result['reason']);
        echo json_encode($result);
        mysqli_close($db);
        return;
    }
    else {
        $result['rslt'] = FAIL;
        $result['reason'] = INVALID_ACTION;
        $result['rows'] = [];
        $evtLog->log($result["rslt"], $result["reason"]);
        echo json_encode($result);
        mysqli_close($db);
        return;
    }
	
    function maintRestoreMtcd($userObj, $tktno, $mlo, $ckid, $cls, $adsr, $prot, $ctyp, $ffac, $tfac) {

        debugLog(3, "Function: ".__FUNCTION__." ACTION = RESTORE_MTCD | CKID = $ckid | CLS = $cls | ADSR = $adsr | TKTNO = $tktno | MLO = $mlo | PROT = $prot | FFAC = $ffac | TFAC = $tfac");
        
        $result['log'] = "ACTION = RESTORE_MTCD | CKID = $ckid | CLS = $cls | ADSR = $adsr | TKTNO = $tktno | MLO = $mlo | PROT = $prot | FFAC = $ffac | TFAC = $tfac";

        // verify user permission
		if ($userObj->grpObj->maint != "Y") {
            $result['rslt'] = 'fail';
            $result['jeop'] = "SP5:PERMISSION DENIED";            
            $result['reason'] = "MAINTENANCE RESTORE MTCD - " . "PERMISSION DENIED";
			return $result;
        }
        
        if(!validateId($tktno)) {
            $result["rslt"] = 'fail';
            $result['jeop'] = "SP5:TKTNO IS EMPTY";            
			$result["reason"] = "MAINTENANCE RESTORE MTCD - " . "INVALID TKTNO FORMAT";
			return $result;
		}
        
        // the CKT must exist
		$cktObj = new CKT($ckid);
		if ($cktObj->rslt == FAIL) {
            $result['rslt'] = FAIL;
            $result['jeop'] = "SP5:CKID ". $ckid . " DOES NOT EXIST";            
            $result['reason'] = "MAINTENANCE RESTORE MTCD - " . "CKID: " . $ckid . " DOES NOT EXIST";
            return $result;
        }
	
        // the ffac must exist
		$ffacObj = new FAC($ffac);
		if ($ffacObj->rslt == FAIL) {
            $result['rslt'] = "fail";
            $result['jeop'] = "SP2:INVALID FAC(X): " . $ffac;          
            $result['reason'] = "MAINTENANCE RESTORE MTCD - " . "FAC(X): " . $ffac . " DOES NOT EXIST";
			return $result;
        }
        
        // the tfac must exist
        $tfacObj = new FAC($tfac);
        if ($tfacObj->rslt == FAIL) {
            $result['rslt'] = "fail";
            $result['jeop'] = "SP2:INVALID FAC(Y): " . $tfac;          
            $result['reason'] = "MAINTENANCE RESTORE MTCD - " . "FAC(Y): " . $tfac . " DOES NOT EXIST";
            return $result;
        }

        // the ffac must belong to CKID
        if ($ffacObj->portObj->ckt_id != $cktObj->id) {
            $result['rslt'] = "fail";
            $result['jeop'] = "SP4:FAC " . $ffac . " IS ALREADY IN CKT";            
            $result['reason'] = "MAINTENANCE RESTORE MTCD - " . "FAC(X): " . $ffac . " DOES NOT BELONG TO CKID " . $ckid;
            return $result;
        }

        // the tfac must belong to CKID
        if ($tfacObj->portObj->ckt_id != $cktObj->id) {
            $result['rslt'] = "fail";
            $result['jeop'] = "SP4:FAC " . $tfac . " IS ALREADY IN CKT";            
            $result['reason'] = "MAINTENANCE RESTORE MTCD - " . "FAC(Y): " . $tfac . " DOES NOT BELONG TO CKID " . $ckid;
            return $result;
        }

        // the CKTCON must be exisit
        $cktconObj = new CKTCON($cktObj->cktcon);
        if ($cktconObj->rslt == FAIL) {
            $result["rslt"] = "fail";
            $result['jeop'] = "SP5:CKTCON " . $cktObj->cktcon . " DOES NOT EXIST";           
			$result["reason"] = "MAINTENANCE RESTORE MTCD - " . "CKTCON " . $cktObj->cktcon . " DOES NOT EXIST";
			return $result;
		}
        
        // both ffac and tfac must belong to same MAINT CKTCON
        if ($cktconObj->loadIdxByPortIds($ffacObj->portObj->id, $tfacObj->portObj->id) == 0) {
            $result['rslt'] = "fail";
            $result['jeop'] = "SP4:FAC " . $ffac . " or " . $tfac . " are not part of CKT";     
            $result['reason'] = "MAINTENANCE RESTORE MTCD - " . "FAC(X) AND FAC(Y) ARE NOT ON SAME CKTCON";
            return $result;
        }

        // verify both ffac and tfac are on the same MAINT cktcon
        if ($cktconObj->ctyp != 'MAINT') {
            $result['rslt'] = "fail";
            $result['jeop'] = "SP4:FAC " . $ffac . " or " . $tfac . " are not on a MAINT CKTCON";     
            $result['reason'] = "MAINTENANCE RESTORE MTCD - " . "FAC(X) AND FAC(Y) ARE NOT ON A MAINT CKTCON";
            return $result;
        }
        $maint_idx = $cktconObj->idx;


        // locate MTCD CKTCON
        $mtcd_idx = $cktconObj->findIdxByCtyp('MTCD');
        if($mtcd_idx == 0){
            $result["rslt"] = "fail";
            $result['jeop'] = "SP5:UNABLE TO LOCATE MTCD CKTCON";           
			$result["reason"] = "MAINTENANCE RESTORE MTCD - " . "COULD NOT LOCATE MTCD CKTCON";
			return $result;
        }

        // validate state-event
        $sms = new SMS($ffacObj->portObj->psta, $ffacObj->portObj->ssta, "RST_MTCD");
        if ($sms->rslt == FAIL) {
            $result['rslt'] = $sms->rslt;
            $result['jeop'] = "SP3:FAC STATUS (".$ffacObj->portObj->psta.")";            
			$result['reason'] = "MAINTENANCE RESTORE MTCD - " . "INVALID PSTA ($sms->psta)";
			return $result;
		}
		$ffacObj->portObj->npsta = $sms->npsta;
		$ffacObj->portObj->nssta = $sms->nssta;
                
        // validate psta for tfac
        $sms = new SMS($tfacObj->portObj->psta, $tfacObj->portObj->ssta, "RST_MTCD");
        if ($sms->rslt == FAIL) {
            $result['rslt'] = $sms->rslt;
            $result['jeop'] = "SP3:FAC STATUS (".$tfacObj->portObj->psta.")";            
			$result['reason'] = "MAINTENANCE RESTORE MTCD - " . "INVALID PSTA ($sms->psta)";
			return $result;
		}
		$tfacObj->portObj->npsta = $sms->npsta;
		$tfacObj->portObj->nssta = $sms->nssta;

        if ($ffacObj->portObj->npsta == 'MTCD') {
            $mtcdObj = $ffacObj->portObj;
            $tstObj = $tfacObj->portObj;
        }
        else {
            $mtcdObj = $tfacObj->portObj;
            $tstObj = $ffacObj->portObj;
        }

        // update ticketNo
        $cktObj->setTktno($tktno);
        if ($cktObj->rslt == FAIL) {
            $result["rslt"] = FAIL;
            $result['jeop'] = "SP5:".$cktObj->reason;            
            $result["reason"] = "MAINTENANCE RESTORE MTCD - " . $cktObj->reason;
            return $result;
        }

        // now, process the MTC_RESTORE
        // check if TEST PATH using dedicated test port or not
		// if yes, then delete tbus path
		// if no, (meaning test path using both normal ports), delete testpath in t_path table
		if($tstObj->ssta == 'TST_SF' || $tstObj->ssta == 'TST_UAS') {
			//get tbus path id
            $tbpath_id = $cktconObj->tbus;
			$tbusObj = new TBUS();
			$tbusObj->deleteTBpath($tbpath_id);
			if($tbusObj->rslt == 'fail') {
				$result['rslt'] = 'fail';
				$result['jeop'] = "SP5:$tbusObj->reason";            
				$result['reason'] = "MAINTENANCE DISCONNECT - " . $tbusObj->reason;
				return $result; 
			}

        }
        else {
            // 1) locate the PATH and open all relays
            $path_id = 0;
            /* will enable after 100% non-blocking test is done */
            $pathObj = new PATH($ffacObj->port, $tfacObj->port);
            $pathObj->load();
            if($pathObj->rslt == 'fail') {
                $result['rslt'] = 'fail';
                $result['jeop'] = "SP5:UNABLE TO LOAD PATH";            
                $result['reason'] = "MAINTENANCE DISCONNECT - " . $pathObj->reason;
                return $result; 
            }

            // break this here - this becomes  instructCmdToCpsForClosingRelays
            $response = instructCmdToCpsForOpeningRelays_Mtcd($pathObj);
            if ($response['rslt'] == 'success') {
                $pathObj->disconnecting();
                $result['rslt'] = $response['rslt'];
                $result['reason'] = "MAINT RESTORE MTCD - PATH IS DISCONNECTING";
                debugLog(3, $result['rslt'] . ":" . $result['reason']);
                return $result;
            }
            else {
                $result['rslt'] = $response['rslt'];
                $result['reason'] = "MAINT RESTORE MTCD - ".$response['reason'];
                debugLog(3, $result['rslt'] . ":" . $result['reason']);
                return $result;
            }
            // its gonna exit here

            // act = "MTCD" starts here
            $pathObj->resetPath();
            
            // 2) delete the PATH from t_path
            $pathObj->drop();
        }
        // 3) delete MAINT CKTCON IDX
        $cktconObj->deleteIdx($cktconObj->con,$maint_idx);
        if ($cktconObj->rslt == FAIL) {
            $result["rslt"] = FAIL;
            $result['jeop'] = "SP5:$cktconObj->reason";            
            $result["reason"] = "MAINTENANCE RESTORE MTCD - " . $cktconObj->reason;
            return $result;
        }

        // 4) update t_ports for the MTCD port
        $ffacObj->portObj->updPsta($ffacObj->portObj->npsta, $ffacObj->portObj->nssta, $ffacObj->portObj->substa);
        if ($ffacObj->portObj->rslt == FAIL) {
            $result["rslt"] = FAIL;
            $result['jeop'] = "SP5:".$ffacObj->portObj->reason;            
            $result["reason"] = "MAINTENANCE RESTORE MTCD - " . $ffacObj->portObj->reason;
            return $result;
        }

        $tfacObj->portObj->updPsta($tfacObj->portObj->npsta, $tfacObj->portObj->nssta, $tfacObj->portObj->substa);
        if ($tfacObj->portObj->rslt == FAIL) {
            $result["rslt"] = FAIL;
            $result['jeop'] = "SP5:".$tfacObj->portObj->reason;            
            $result["reason"] = "MAINTENANCE RESTORE MTCD - " . $tfacObj->portObj->reason;
            return $result;
        }

        // 5) locate the MTCD CKTCON and updateCktLink
        $cktconObj->loadIdx($mtcd_idx);
        if ($cktconObj->rslt == FAIL) {
            $result["rslt"] = FAIL;
            $result['jeop'] = "SP5:".$cktconObj->reason;            
            $result["reason"] = "MAINTENANCE RESTORE MTCD - " . $cktconObj->reason;
            return $result;
        }

        $mtcdObj->updCktLink($cktObj->id, $cktconObj->con, $mtcd_idx);
        if ($mtcdObj->rslt == FAIL) {
            $result["rslt"] = FAIL;
            $result['jeop'] = "SP5:".$mtcdObj->reason;            
            $result["reason"] = "MAINTENANCE RESTORE MTCD - " . $mtcdObj->reason;
            return $result;
        }

        $tstObj->updCktLink(0, 0, 0);
        if ($tstObj->rslt == FAIL) {
            $result["rslt"] = FAIL;
            $result['jeop'] = "SP5:".$tstObj->reason;            
            $result["reason"] = "MAINTENANCE RESTORE MTCD - " . $tstObj->reason;
            return $result;
        }

        // 6) queryCktConWithFac
        $cktconObj->queryCktConWithFac($cktObj->cktcon);
        if ($cktconObj->rslt == FAIL) {
            $result["rslt"] = FAIL;
            $result['jeop'] = "SP5:".$cktconObj->reason;            
            $result["reason"] = "MAINTENANCE RESTORE MTCD - " . $cktconObj->reason;
            debugLog(3, $result['rslt'] . ":" . $result['reason']);
            return $result;
        }

        $result['rows'] = $cktconObj->rows;
        $result["rslt"] = SUCCESS;
        $result["reason"] = "MAINTENANCE RESTORE MTCD - SUCCESSFUL";
        debugLog(3, $result['rslt'] . ":" . $result['reason']);
        return $result;

    }

    function mtcd($ackid) {
        debugLog(3, "Function: ".__FUNCTION__." Variable: Ackid - $ackid");
		$ackidExtracted = explode("-", $ackid);

		// validate ackid
		if (count($ackidExtracted) != 3 || $ackidExtracted[1] != "MTCD")  {
			$result['rslt'] = 'fail';
            $result['reason'] = "INVALID ACKID - $ackid";
            debugLog(3, $result['rslt'] . ":" . $result['reason']);
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
        
        //load other connection in this cktcon to rows
        $cktconObj->queryCktconByCon($cktconObj->con);
        if ($cktconObj->rslt == 'fail') {
			$result['rslt'] = $cktconObj->rslt;
			$result['reason'] = $cktconObj->reason;
			debugLog(3, $result['rslt'].":".$result['reason']);
			return $result;
        }
        // locate MTCD CKTCON
        $mtcd_idx = $cktconObj->findIdxByCtyp('MTCD');
        if ($mtcd_idx == 0) {
            $result["rslt"] = "fail";
            $result['jeop'] = "SP5:UNABLE TO LOCATE MTCD CKTCON";           
			$result["reason"] = "MAINTENANCE RESTORE MTCD - " . "COULD NOT LOCATE MTCD CKTCON";
			return $result;
        }
		// validate state-event
		$sms = new SMS($fportObj->psta, $fportObj->ssta, "RST_MTCD");
		if ($sms->rslt == FAIL) {
			$result['rslt'] = FAIL;
			$result['jeop'] = "SP3:FAC STATUS (" . $fportObj->psta . ")";
            $result['reason'] = "PROVISIONING DISCONNECT - " . "INVALID PSTA ($sms->psta)";
            debugLog(3, $result['rslt'] . ":" . $result['reason']);
			return $result;
		}
		$fportObj->npsta = $sms->npsta;
		$fportObj->nssta = $sms->nssta;
	
		$sms = new SMS($tportObj->psta, $tportObj->ssta, "RST_MTCD");
		if ($sms->rslt == FAIL) {
			$result['rslt'] = FAIL;
			$result['jeop'] = "SP3:FAC STATUS (" . $tportObj->psta . ")";
            $result['reason'] = "PROVISIONING DISCONNECT - " . "INVALID PSTA ($sms->psta)";
            debugLog(3, $result['rslt'] . ":" . $result['reason']);
			return $result;
		}
		$tportObj->npsta = $sms->npsta;
        $tportObj->nssta = $sms->nssta;
        
        if ($fportObj->npsta == 'MTCD') {
            $mtcdObj = $fportObj;
            $tstObj = $tportObj;
        }
        else {
            $mtcdObj = $tportObj;
            $tstObj = $fportObj;
        }


		//----------------------Finish validation-------------------
		// change status of path to disconnected
		$pathObj->disconnected();
		debugLog(3, "Path PSTA = $pathObj->psta");

		debugLog(3, "Continue to resetPath");


        // act = "MTCD" starts here
        $pathObj->resetPath();
            
        // 2) delete the PATH from t_path
        $pathObj->drop();

        $maint_idx = $cktconObj->idx;

        // 3) delete MAINT CKTCON IDX
        $cktconObj->deleteIdx($cktconObj->con,$maint_idx);
        if ($cktconObj->rslt == FAIL) {
            $result["rslt"] = FAIL;
            $result['jeop'] = "SP5:$cktconObj->reason";            
            $result["reason"] = "MAINTENANCE RESTORE MTCD - " . $cktconObj->reason;
            debugLog(3, $result['rslt'] . ":" . $result['reason']);
            return $result;
        }

        // 4) update t_ports for the MTCD port
        $fportObj->updPsta($fportObj->npsta, $fportObj->nssta, $fportObj->substa);
        if ($fportObj->rslt == FAIL) {
            $result["rslt"] = FAIL;
            $result['jeop'] = "SP5:".$fportObj->reason;            
            $result["reason"] = "MAINTENANCE RESTORE MTCD - " . $fportObj->reason;
            debugLog(3, $result['rslt'] . ":" . $result['reason']);
            return $result;
        }

        $tportObj->updPsta($tportObj->npsta, $tportObj->nssta, $tportObj->substa);
        if ($tportObj->rslt == FAIL) {
            $result["rslt"] = FAIL;
            $result['jeop'] = "SP5:".$tportObj->reason;            
            $result["reason"] = "MAINTENANCE RESTORE MTCD - " . $tportObj->reason;
            debugLog(3, $result['rslt'] . ":" . $result['reason']);
            return $result;
        }

        // 5) locate the MTCD CKTCON and updateCktLink
        $cktconObj->loadIdx($mtcd_idx);
        if ($cktconObj->rslt == FAIL) {
            $result["rslt"] = FAIL;
            $result['jeop'] = "SP5:".$cktconObj->reason;            
            $result["reason"] = "MAINTENANCE RESTORE MTCD - " . $cktconObj->reason;
            debugLog(3, $result['rslt'] . ":" . $result['reason']);
            return $result;
        }

        $mtcdObj->updCktLink($cktObj->id, $cktconObj->con, $mtcd_idx);
        if ($mtcdObj->rslt == FAIL) {
            $result["rslt"] = FAIL;
            $result['jeop'] = "SP5:".$mtcdObj->reason;            
            $result["reason"] = "MAINTENANCE RESTORE MTCD - " . $mtcdObj->reason;
            debugLog(3, $result['rslt'] . ":" . $result['reason']);
            return $result;
        }

        $tstObj->updCktLink(0, 0, 0);
        if ($tstObj->rslt == FAIL) {
            $result["rslt"] = FAIL;
            $result['jeop'] = "SP5:".$tstObj->reason;            
            $result["reason"] = "MAINTENANCE RESTORE MTCD - " . $tstObj->reason;
            debugLog(3, $result['rslt'] . ":" . $result['reason']);
            return $result;
        }

        $result['rows'] = [];
        $result["rslt"] = SUCCESS;
        $result["reason"] = "MAINTENANCE RESTORE MTCD - SUCCESSFUL";
        debugLog(3, $result['rslt'] . ":" . $result['reason']);
        return $result;

    }
    
    function instructCmdToCpsForOpeningRelays_Mtcd($pathObj) {
        debugLog(3, "Function: ".__FUNCTION__." Variable: Path($pathObj->id)");

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
        $resp = createAckidListForPathCmdByNode($rcArray, $pathObj->id, "MTCD");
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