<?php namespace App\Models;

use CodeIgniter\Model;

class PraiseModel extends Model {

    public function isPraise($hash, $address)
	{//获取点赞状态
		$sql="SELECT hash FROM wet_praise WHERE hash='$hash' and sender_id='$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row = $query->getRow();
		if ($row) {
			return TRUE;
        }else{
			return FALSE;
		}
	}

}

