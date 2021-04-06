<?php namespace App\Models;

use CodeIgniter\Model;

class StarModel extends Model {

    public function isStar($hash, $address)
	{//获取点赞状态
		$sql="SELECT hash FROM wet_star WHERE hash='$hash' and sender_id='$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row = $query->getRow();
		if ($row) {
			return true;
        }else{
			return false;
		}
	}

}

