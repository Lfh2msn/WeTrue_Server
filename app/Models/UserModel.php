<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\ConfigModel;

class UserModel extends Model {

    public function getUser($address)
	{//获取用户头像、昵称、等级
		$sql="SELECT 
					username,
					uactive,
					last_active,
					portrait
				FROM wet_users WHERE address='$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row = $query->getRow();
		if ($row) {
			$data['userAddress'] = $address;
			$userName = $row->username;
			if(!$userName){
				$data['userName'] = "";
			}else{
				$data['userName'] = htmlentities($userName);
			}
			$userActive = (int)$row->uactive;
            $data['active'] = $userActive;
			$data['userActive'] = $this->getActiveGrade($userActive);
			$portrait = $row->portrait;
			if($portrait){
				$data['portrait'] = htmlentities($portrait);
			}else{
				$data['portrait'] = "";
			}
        }else{
			return FALSE;
		}
		return $data;
	}

	public function userAllInfo($address, $opt=[])
	{//获取用户完整信息
		$sql="SELECT 
					username,
					uactive,
					portrait,
					portrait_hash,
					last_active,
					topic_sum,
					focus_sum,
					fans_sum
				FROM wet_users WHERE address='$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row = $query->getRow();
		if ($row) {
			$data['userAddress'] = $address;
			$userName = $row->username;
			if(!$userName){
				$data['userName'] = "";
			}else{
				$data['userName'] = htmlentities($userName);
			}
			$userActive = (int)$row->uactive;
            $data['active'] = $userActive;
			$data['userActive'] = $this->getActiveGrade($userActive);
			$wetConfig = (new ConfigModel())-> backendConfig();
			$data['lastActive'] = ($userActive - $row->last_active) * $backendConfig['airdropWttRatio'];
			$portrait 	  = $row->portrait;
			$portraitHash = $row->portrait_hash;
			if($portrait){
				$data['portrait']	  = htmlentities($portrait);
				$data['portraitHash'] = htmlentities($portraitHash);
			}else{
				$data['portrait']	  = "";
				$data['portraitHash'] = "";
			}
			$data['topic'] = (int)$row->topic_sum;
			$data['focus'] = (int)$row->focus_sum;
			$data['fans']  = (int)$row->fans_sum;
        }else{
			return FALSE;
		}
		return $data;
	}

	public function getName($address)
	{//获取用户昵称
		$sql="SELECT username FROM wet_users WHERE address='$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row = $query->getRow();
		if ($row) {
			$userName = $row->username;
			if($userName){
				$data = htmlentities($userName);
			}else{
				$data = "";
			}
        }else{
			return FALSE;
		}
		return $data;
	}

	public function getActiveGrade($num)
	{//等级划分
		(int)$num;
        if($num>=10000){
            $Grade = 7;
            return $Grade;

        }elseif($num>=5000){
            $Grade = 6;
            return $Grade;

        }elseif ($num>=2000) {
            $Grade = 5;
            return $Grade;

        }elseif ($num>=500) {
            $Grade = 4;
            return $Grade;

        }elseif ($num>=100) {
            $Grade = 3;
            return $Grade;
        }elseif ($num>=50) {
            $Grade = 2;
            return $Grade;
        }else{
            $Grade = 1;
            return $Grade;
        }
    }

}

