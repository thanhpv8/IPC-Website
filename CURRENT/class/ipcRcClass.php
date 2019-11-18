<?php

class RC
{
    public $rslt;
    public $reason;
    public $row;
    public $col;
    public $rel;

    public function __construct($rel)
    {
        global $db;
        $this->rows = [];
        $qry = "SELECT * FROM t_rc WHERE rel = '$rel'";
        $res = $db->query($qry);
        if (!$res) {
            $this->rslt = 'fail';
            $this->reason = mysqli_error($db);
        } else {
            if ($res->num_rows == 1) {
                $data = $res->fetch_assoc();
                $this->row = $data['row'];
                $this->col = $data['col'];
                $this->rel = $rel;

                $this->rslt = 'success';
                $this->reason = "RC_QUERIED";
            } else {
                $this->rslt = 'success';
                $this->reason = "INVALID RELAY ($rel)";
                $this->row = -1;
                $this->col = -1;
                $this->rel = $rel;
            }
        }
    }
}
