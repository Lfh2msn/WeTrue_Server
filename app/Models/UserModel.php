<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\ConfigModel;

class UserModel extends Model {

    public function getUser($address)
	{//获取用户头像、昵称、等级
		$sql="SELECT 
					nickname,
					uactive,
					last_active,
					portrait
				FROM wet_users WHERE address='$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row = $query->getRow();
		if ($row) {
			$data['userAddress'] = $address;
			$nickname = $row->nickname;
			if(!$nickname){
				$data['nickname'] = "";
			}else{
				$data['nickname'] = stripslashes($nickname);
			}
			$userActive = (int)$row->uactive;
            $data['active'] = $userActive;
			$data['userActive'] = $this->getActiveGrade($userActive);
			$portrait = $row->portrait;
			if($portrait){
				$data['portrait'] = stripslashes($portrait);
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
					nickname,
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
			$nickname = $row->nickname;
			if($nickname){
				$data['nickname'] = stripslashes($nickname);
			}else{
				$data['nickname'] = "";
			}
			$userActive = (int)$row->uactive;
            $data['active'] = $userActive;
			$data['userActive'] = $this->getActiveGrade($userActive);
			$backendConfig = (new ConfigModel())-> backendConfig();
			$data['lastActive'] = ($userActive - $row->last_active) * $backendConfig['airdropWttRatio'];
			$portrait 	  = $row->portrait;
			$portraitHash = $row->portrait_hash;
			if($portrait){
				$data['portrait']	  = stripslashes($portrait);
				$data['portraitHash'] = stripslashes($portraitHash);
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
		$sql   = "SELECT nickname FROM wet_users WHERE address='$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		if ($row) {
			$nickname = $row->nickname;
			if($nickname){
				$data = stripslashes($nickname);
			}else{
				$data = "";
			}
        }else{
			return FALSE;
		}
		return $data;
	}

	public function userActive($address, $active, $e)
	{/*用户活跃入库
		$address = 目标地址
		$active  = 数量
		$e       = true增 或 false减
	*/
		$selectSql = "SELECT address FROM wet_users WHERE address='$address' LIMIT 1";
		$query	   = $this->db->query($selectSql);
		$row	   = $query-> getRow();
		if(!$row){
			$insertSql = "INSERT INTO wet_users(address) VALUES ('$address')";
			$this->db->query($insertSql);
		}

		if($e){
			$updateSql="UPDATE wet_users SET uactive = uactive + '$active' WHERE address='$address'";
		}else{
			$updateSql="UPDATE wet_users SET uactive = uactive - '$active' WHERE address='$address'";
		}
		$this->db->query($updateSql);
	}

	public function userFocus($focus, $fans, $e)
	{/*用户关注用户
			$focus = 目标地址
			$fans  = 粉丝地址
			$e     = isFocus
	*/
		$selectSql = "SELECT address FROM wet_users WHERE address='$fans' LIMIT 1";
		$query	   = $this->db->query($selectSql);
		$row	   = $query-> getRow();
		if(!$row){
			$insertSql = "INSERT INTO wet_users(address) VALUES ('$fans')";
			$this->db->query($insertSql);
		}

		if($e){
			$focusSql = "UPDATE wet_users SET focus_sum = focus_sum + 1 WHERE address='$fans'";
			$fansSql  = "UPDATE wet_users SET fans_sum = fans_sum + 1 WHERE address='$focus'";
		}else{
			$focusSql = "UPDATE wet_users SET focus_sum = focus_sum - 1 WHERE address='$fans'";
			$fansSql  = "UPDATE wet_users SET fans_sum = fans_sum - 1 WHERE address='$focus'";
		}
		$this->db->query($focusSql);
		$this->db->query($fansSql);
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

