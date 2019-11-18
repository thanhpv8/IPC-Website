<?php

class CPSS {
    public $node        = [];
    public $serial_no   = [];
    public $psta        = [];
    public $ssta        = [];
    public $dev         = [];

    public $rslt        = "";
    public $reason      = "";
    public $rows        = [];

    public function __construct() {
        global $db;

        $qry = "SELECT * FROM t_cps";
        $res = $db->query($qry);
        if (!$res) {
            $this->rslt   = FAIL;
            $this->reason = mysqli_error($db);
            return;
        }
        else {
            $rows = [];
            if ($res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $rows[] = $row;
                }
                $this->rslt     = SUCCESS;
                $this->reason   = QUERY_MATCHED;
                $this->rows     = $rows;
                
                for ($i = 0; $i < count($rows); $i++) {

                    // populate member arrays
                    array_push($this->node,      $rows[$i]['node']);
                    array_push($this->serial_no, $rows[$i]['serial_no']);
                    array_push($this->psta,      $rows[$i]['psta']);
                    array_push($this->ssta,      $rows[$i]['ssta']);
                    array_push($this->dev,       $rows[$i]['dev']);
                }
            }
            else {
                $this->rslt   = FAIL;
                $this->reason = "CPS QUERY ALL SUCCESS";
                $this->rows   = $rows;
            }
        }
    }

}

class CPS {
    public $node        = "";
    public $serial_no   = "";
    public $psta        = "";
    public $ssta        = "";
    public $dev         = "";

    public $rslt        = "";
    public $reason      = "";
    public $rows        = [];

    public function __construct($node) {
        global $db;

        $qry = "SELECT * FROM t_cps WHERE node = '$node'";
        $res = $db->query($qry);
        if (!$res) {
            $this->rslt   = FAIL;
            $this->reason = mysqli_error($db);
        }
        else {
            $rows = [];
            if ($res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $rows[] = $row;
                }
                $this->rslt         = SUCCESS;
                $this->reason       = QUERY_MATCHED;
                $this->rows         = $rows;
                $this->node         = $rows[0]['node'];
                $this->serial_no    = $rows[0]['serial_no'];
                $this->psta         = $rows[0]['psta'];
                $this->ssta         = $rows[0]['ssta'];
                $this->dev          = $rows[0]['dev'];

            }
            else {
                $this->rslt   = FAIL;
                $this->reason = "CPS QUERY BY NODE " . $node . " SUCCESS";
                $this->rows   = $rows;
            }
        }
    }

    public function setPsta($psta, $ssta) {
        global $db;

        if ($psta == "") {
            $this->rslt = FAIL;
            $this->reason = "INVALID PSTA";
            return;
        }
        if ($ssta == "") {
            $this->rslt = FAIL;
            $this->reason = "INVALID SSTA";
            return;
        }

        $qry = "UPDATE t_cps SET psta='$psta', ssta='$ssta' WHERE node='$this->node'";
        $res = $db->query($qry);
        if (!$res) {
            $this->rslt   = FAIL;
            $this->reason = mysqli_error($db);
            return;
        }
        $this->psta = $psta;
        $this->ssta = $ssta;
        $this->rslt = SUCCESS;
        $this->reason = "CPS STATUS UPDATED";
       
    }

    public function setSerialNo($serial_no) {
        global $db;

        $qry = "UPDATE t_cps SET serial_no='$serial_no' WHERE node='$this->node'";
        $res = $db->query($qry);
        if (!$res) {
            $this->rslt   = FAIL;
            $this->reason = mysqli_error($db);
        }
        else {
            $this->rslt = SUCCESS;
            $this->reason = "SERIAL NUMBER UPDATED";
        }
    }


}


?>