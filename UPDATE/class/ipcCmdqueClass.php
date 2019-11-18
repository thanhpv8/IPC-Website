<?php
class CMDQUE {
  public $ackid;
  public $cmd;
  public $id;
  public $node;
  public $stat;
  public $time;
  public $rsp;
  public $rslt;
  public $reason;
  public $rows;

  public function __construct($ackid=NULL)
  {
    global $db;

    if ($ackid == NULL) {
      $this->rslt = SUCCESS;
      $this->reason = "CMDQUE CONSTRUCTED";
      return;
    }

    $qry = "SELECT * FROM t_cmdque WHERE ackid = '$ackid' LIMIT 1";
    $res = $db->query($qry);
        if (!$res) {
            $this->rslt   = FAIL;
            $this->reason = mysqli_error($db);
            return;
        } else {
            $rows = [];
            if ($res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $rows[] = $row;
                }
                $this->rslt     = SUCCESS;
                $this->reason   = "ACKID FOUND - $ackid";

                $this->id     = $rows[0]['id'];
                $this->time   = $rows[0]['time'];
                $this->node   = $rows[0]['node'];
                $this->ackid  = $rows[0]['ackid'];
                $this->stat   = $rows[0]['stat'];
                $this->cmd    = $rows[0]['cmd'];
                $this->rsp    = $rows[0]['rsp'];
            } else {
                $this->rslt   = FAIL;
                $this->reason = "ACKID NOT FOUND - $ackid";
                $this->rows   = $rows;
            }
        }
  }

  public function updateCmdStatusToCompleted() {
    global $db;
    
    $time = $time = date('Y-m-d H:i:s', time());
    $qry = "UPDATE t_cmdque SET stat = 'COMPLETED', time = '$time' WHERE ackid = '$this->ackid'";
    $res = $db->query($qry);
      if (!$res) {
        $this->rslt = 'fail';
        $this->reason = mysqli_error($db);
      } else {
        $this->rslt = 'success';
        $this->reason = "STATUS UPDATED - (COMPLETED) for ACKID ($this->ackid)";
      }
  }

  private function sendCmd()
  {
      //create socket UDP
      $socket = socket_create(AF_INET, SOCK_DGRAM, 0);
      if ($socket === false) {
          $this->rslt = 'fail';
          $this->reason = 'CAN NOT CREATE UDP SOCKET';
          return;
      }
      socket_set_nonblock($socket);
      $ip_port = 9000;
      $sendCmd = socket_sendto($socket, $this->cmd, 1024, 0, '127.0.0.1', $ip_port);
      if ($sendCmd === false) {
          $this->rslt = 'fail';
          $this->reason = "CAN NOT SEND CMD ($this->cmd) TO NODE $this->node";
          return;
      }
      $this->rslt = 'success';
      $this->reason = "CMD SENT TO NODE $this->node: $this->cmd";
  }

  public function sendCmdOpenRelays($node, $ackid, $rowcol_array) 
  {
    global $db;
    
    //create cmd from ackid and rowcol_array
    $cmd = "inst:send;node:$node;sn:;cmd:\$command,action=open";
    foreach ($rowcol_array as $rc) {
      $cmd .= ",row=$rc[0],col=$rc[1]";
    }
    $cmd .= ",ackid=$ackid*";

    $time = date('Y-m-d H:i:s', time());
    $qry = "INSERT INTO t_cmdque (time, node, ackid, stat, cmd) VALUES ('$time', '$node', '$ackid', 'PENDING', '$cmd')";

    $res = $db->query($qry);
      if (!$res) {
        $this->rslt = FAIL;
        $this->reason = mysqli_error($db);
        return false;
      } else {
        $this->cmd = $cmd;
        $this->node = $node;
        $this->ackid = $ackid;
        $this->rslt = SUCCESS;
        $this->reason = "CMD OPEN RELAYS ADDED: $cmd";
      }

    $this->sendCmd();
    

  }

  public function sendCmdCloseRelays($node, $ackid, $rowcol_array) 
  {
    global $db;

    //create cmd from ackid and rowcol_array
    $cmd = "inst:send;node:$node;sn:;cmd:\$command,action=close";
    foreach ($rowcol_array as $rc) {
      $cmd .= ",row=$rc[0],col=$rc[1]";
    }
    $cmd .= ",ackid=$ackid*";

    $time = date('Y-m-d H:i:s', time());
    $qry = "INSERT INTO t_cmdque (time, node, ackid, stat, cmd) VALUES ('$time', '$node', '$ackid', 'PENDING', '$cmd')";

    $res = $db->query($qry);
      if (!$res) {
        $this->rslt = FAIL;
        $this->reason = mysqli_error($db);
        return false;
      } else {
        $this->cmd = $cmd;
        $this->node = $node;
        $this->ackid = $ackid;
        $this->rslt = SUCCESS;
        $this->reason = "CMD CLOSE RELAYS ADDED: $cmd";
      }

      $this->sendCmd();
  }

  public function sendCmdConnectTestBusXAndDspA($node, $ackid) 
  {
    global $db;

    $cmd = "\$command,,action=connect,bus=x,tap=a,ackid=$ackid*";
    $time = date('Y-m-d H:i:s', time());

    $qry = "INSERT INTO t_cmdque (time, node, ackid, stat, cmd) VALUES ('$time','$node','$ackid','PENDING','$cmd')";

    $res = $db->query($qry);
      if (!$res) {
        $this->rslt = FAIL;
        $this->reason = mysqli_error($db);
        return false;
      } else {
        $this->ackd = $ackid;
        $this->cmd = $cmd;
        $this->node = $node;
        $this->rslt = SUCCESS;
        $this->reason = "CMD TB-X AND DSP-A ADDED: $cmd";
      }

      $this->sendCmd();
  }

  public function sendCmdConnectTestBusXAndDspB($node, $ackid)
  {
    global $db;

    $cmd = "\$command,,action=connect,bus=x,tap=b,ackid=$ackid*";

    $time = date('Y-m-d H:i:s', time());

    $qry = "INSERT INTO t_cmdque (time, node, ackid, stat, cmd) VALUES ('$time','$node','$ackid','PENDING','$cmd')";

    $res = $db->query($qry);
      if (!$res) {
        $this->rslt = FAIL;
        $this->reason = mysqli_error($db);
        return false;
      } else {
        $this->ackd = $ackid;
        $this->cmd = $cmd;
        $this->node = $node;
        $this->rslt = SUCCESS;
        $this->reason = "CMD TB-X AND DSP-B ADDED: $cmd";
      }

      $this->sendCmd();
  }

  public function sendCmdConnectTestBusYAndDspA($node, $ackid)
  {
    global $db;

    $cmd = "\$command,,action=connect,bus=y,tap=a,ackid=$ackid*";

    $time = date('Y-m-d H:i:s', time());

    $qry = "INSERT INTO t_cmdque (time, node, ackid, stat, cmd) VALUES ('$time','$node','$ackid','PENDING','$cmd')";

    $res = $db->query($qry);
      if (!$res) {
        $this->rslt = FAIL;
        $this->reason = mysqli_error($db);
        return false;
      } else {
        $this->ackd = $ackid;
        $this->cmd = $cmd;
        $this->node = $node;
        $this->rslt = SUCCESS;
        $this->reason = "CMD TB-Y AND DSP-A ADDED: $cmd";
      }

      $this->sendCmd();
  }

  public function sendCmdConnectTestBusYAndDspB($node, $ackid)
  {
    global $db;

    $cmd = "\$command,,action=connect,bus=y,tap=b,ackid=$ackid*";

    $time = date('Y-m-d H:i:s', time());

    $qry = "INSERT INTO t_cmdque (time, node, ackid, stat, cmd) VALUES ('$time','$node','$ackid','PENDING','$cmd')";

    $res = $db->query($qry);
      if (!$res) {
        $this->rslt = FAIL;
        $this->reason = mysqli_error($db);
        return false;
      } else {
        $this->ackd = $ackid;
        $this->cmd = $cmd;
        $this->node = $node;
        $this->rslt = SUCCESS;
        $this->reason = "CMD TB-Y AND DSP-B ADDED: $cmd";
      }

      $this->sendCmd();
  }

  public function removeCmd($ackid)
  {
      global $db;

      if ($ackid === '') {
          $this->rslt = FAIL;
          $this->reason = "MISSING ACKID ($ackid)";
          return false;
      }

      $qry = "DELETE FROM t_cmdque where ackid = '$ackid'";
      $res = $db->query($qry);
      if (!$res) {
          $this->rslt = FAIL;
          $this->reason = mysqli_error($db);
          return false;
      } else {
          $this->rslt = SUCCESS;
          $this->reason = "CMD (ACKID: $ackid) DELETED SUCCESSFULLY";
          return true;
      }
  }

  public function sendCmdDisconnectTestBusXAndDspA($node, $ackid)
  {
    global $db;

    $cmd = "\$command,action=disconnect,bus=x,tap=a,ackid=$ackid*";

    $time = date("Y-m-d H:i:s", time());

    $qry = "INSERT INTO t_cmdque (time, node, ackid, stat, cmd) VALUES ('$time', '$node', '$ackid', 'PENDING', '$cmd')";

    $res = $db->query($qry);
    if (!$res) {
      $this->rslt = FAIL;
      $this->reason = mysqli_error($db);
      return false;
    } else {
      $this->cmd = $cmd;
      $this->node = $node;
      $this->ackid = $ackid;
      $this->rslt = SUCCESS;
      $this->reason = "CMD ADDED: $cmd";
    }

    $this->sendCmd();

  }

  public function sendCmdDisconnectTestBusXAndDspB($node, $ackid)
  {
    global $db;

    $cmd = "\$command,action=disconnect,bus=x,tap=b,ackid=$ackid*";

    $time = date("Y-m-d H:i:s", time());

    $qry = "INSERT INTO t_cmdque (time, node, ackid, stat, cmd) VALUES ('$time', '$node', '$ackid', 'PENDING', '$cmd')";

    $res = $db->query($qry);
    if (!$res) {
      $this->rslt = FAIL;
      $this->reason = mysqli_error($db);
      return false;
    } else {
      $this->cmd = $cmd;
      $this->node = $node;
      $this->ackid = $ackid;
      $this->rslt = SUCCESS;
      $this->reason = "CMD ADDED: $cmd";
    }

    $this->sendCmd();

  }

  public function sendCmdDisconnectTestBusYAndDspA($node, $ackid)
  {
    global $db;

    $cmd = "\$command,action=disconnect,bus=y,tap=a,ackid=$ackid*";

    $time = date("Y-m-d H:i:s", time());

    $qry = "INSERT INTO t_cmdque (time, node, ackid, stat, cmd) VALUES ('$time', '$node', '$ackid', 'PENDING', '$cmd')";

    $res = $db->query($qry);
    if (!$res) {
      $this->rslt = FAIL;
      $this->reason = mysqli_error($db);
      return false;
    } else {
      $this->cmd = $cmd;
      $this->node = $node;
      $this->ackid = $ackid;
      $this->rslt = SUCCESS;
      $this->reason = "CMD ADDED: $cmd";
    }

    $this->sendCmd();

  }

  public function sendCmdDisconnectTestBusYAndDspB($node, $ackid)
  {
    global $db;

    $cmd = "\$command,action=disconnect,bus=y,tap=b,ackid=$ackid*";

    $time = date("Y-m-d H:i:s", time());

    $qry = "INSERT INTO t_cmdque (time, node, ackid, stat, cmd) VALUES ('$time', '$node', '$ackid', 'PENDING', '$cmd')";

    $res = $db->query($qry);
    if (!$res) {
      $this->rslt = FAIL;
      $this->reason = mysqli_error($db);
      return false;
    } else {
      $this->cmd = $cmd;
      $this->node = $node;
      $this->ackid = $ackid;
      $this->rslt = SUCCESS;
      $this->reason = "CMD ADDED: $cmd";
    }

    $this->sendCmd();

  }

  public function sendCmdConnectTestBusXAndTestAccess($node, $ackid, $tap)
  {
    global $db;

    if ($tap < 1 || $tap > 8) {
      $this->rslt = 'fail';
      $this->reason = "TAP MUST BE BETWEEN 1-8";
      return;
    }
    
    $cmd = "\$command,action=connect,bus=x,tap=$tap,ackid=$ackid*";

    $time = date("Y-m-d H:i:s", time());

    $qry = "INSERT INTO t_cmdque (time, node, ackid, stat, cmd) VALUES ('$time', '$node', '$ackid', 'PENDING', '$cmd')";

    $res = $db->query($qry);
    if (!$res) {
      $this->rslt = FAIL;
      $this->reason = mysqli_error($db);
      return false;
    } else {
      $this->cmd = $cmd;
      $this->node = $node;
      $this->ackid = $ackid;
      $this->rslt = SUCCESS;
      $this->reason = "CMD ADDED: $cmd";
    }

    $this->sendCmd();

  }

  public function sendCmdConnectTestBusYAndTestAccess($node, $ackid, $tap)
  {
    global $db;

    if ($tap < 1 || $tap > 8) {
      $this->rslt = 'fail';
      $this->reason = "TAP MUST BE BETWEEN 1-8";
      return;
    }

    $cmd = "\$command,action=connect,bus=y,tap=$tap,ackid=$ackid*";

    $time = date("Y-m-d H:i:s", time());

    $qry = "INSERT INTO t_cmdque (time, node, ackid, stat, cmd) VALUES ('$time', '$node', '$ackid', 'PENDING', '$cmd')";

    $res = $db->query($qry);
    if (!$res) {
      $this->rslt = FAIL;
      $this->reason = mysqli_error($db);
      return false;
    } else {
      $this->cmd = $cmd;
      $this->node = $node;
      $this->ackid = $ackid;
      $this->rslt = SUCCESS;
      $this->reason = "CMD ADDED: $cmd";
    }

    $this->sendCmd();

  }

  public function sendCmdDisconnectTestBusXAndTestAccess($node, $ackid, $tap)
  {
    global $db;

    if ($tap < 1 || $tap > 8) {
      $this->rslt = 'fail';
      $this->reason = "TAP MUST BE BETWEEN 1-8";
      return;
    }

    $cmd = "\$command,action=disconnect,bus=y,tap=$tap,ackid=$ackid*";

    $time = date("Y-m-d H:i:s", time());

    $qry = "INSERT INTO t_cmdque (time, node, ackid, stat, cmd) VALUES ('$time', '$node', '$ackid', 'PENDING', '$cmd')";

    $res = $db->query($qry);
    if (!$res) {
      $this->rslt = FAIL;
      $this->reason = mysqli_error($db);
      return false;
    } else {
      $this->cmd = $cmd;
      $this->node = $node;
      $this->ackid = $ackid;
      $this->rslt = SUCCESS;
      $this->reason = "CMD ADDED: $cmd";
    }

    $this->sendCmd();

  }

  public function sendCmdDisconnectTestBusTAndTestAccess($node, $ackid, $tap)
  {
    global $db;
    
    if ($tap < 1 || $tap > 8) {
      $this->rslt = 'fail';
      $this->reason = "TAP MUST BE BETWEEN 1-8";
      return;
    }

    $cmd = "\$command,action=disconnect,bus=t,tap=$tap,ackid=$ackid*";

    $time = date("Y-m-d H:i:s", time());

    $qry = "INSERT INTO t_cmdque (time, node, ackid, stat, cmd) VALUES ('$time', '$node', '$ackid', 'PENDING', '$cmd')";

    $res = $db->query($qry);
    if (!$res) {
      $this->rslt = FAIL;
      $this->reason = mysqli_error($db);
      return false;
    } else {
      $this->cmd = $cmd;
      $this->node = $node;
      $this->ackid = $ackid;
      $this->rslt = SUCCESS;
      $this->reason = "CMD ADDED: $cmd";
    }

    $this->sendCmd();

  }

  public function checkForPendingCmds()
  {
    global $db;

    $ackidArray = explode("-", $this->ackid, 2);
    $partialAckid = $ackidArray[1];
    $relatedAckid = "%-" . $partialAckid;

    $qry = "SELECT * from t_cmdque WHERE ackid LIKE '$relatedAckid' AND stat = 'PENDING'";
    $res = $db->query($qry);
    if (!$res) {
        $this->rslt   = FAIL;
        $this->reason = mysqli_error($db);
    } else {
        $rows = [];
        if ($res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
            $this->rslt         = SUCCESS;
            $this->reason       = "PENDING CMDS FOUND WITH ACKID RELATED TO " . $this->ackid;
            $this->rows         = $rows;
        } else {
            $this->rslt   = FAIL;
            $this->reason = "NO CMD FOUND WITH ACKID LIKE $this->ackid AND STAT PENDING";
            $this->rows   = $rows;
        }
    }






  }
}
