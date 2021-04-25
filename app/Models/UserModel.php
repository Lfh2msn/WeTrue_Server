<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\ConfigModel;
use App\Models\DisposeModel;

class UserModel extends Model {
//用户模块
	public function __construct(){
        parent::__construct();
        $this->tablename    = 'wet_users';
		$this->ConfigModel  = new ConfigModel();
		$this->DisposeModel	= new DisposeModel();
    }

	public function isUser($address)
	{//验证用户是否存在
		$sql   = "SELECT address FROM $this->tablename WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		if ($row) {
			return TRUE;
        }else{
			return FALSE;
		}
	}

    public function getUser($address)
	{//获取用户头像、昵称、等级
		$sql="SELECT 
					nickname,
					uactive,
					last_active,
					portrait
				FROM $this->tablename WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row = $query->getRow();
		if ($row) {
			$data['userAddress'] = $address;
			$nickname = $this->DisposeModel-> delete_xss($row->nickname);
			$data['nickname'] = "";
			if($nickname){
				$data['nickname'] = $nickname;
			}
			$userActive = (int)$row->uactive;
            $data['active'] = $userActive;
			$data['userActive'] = $this->getActiveGrade($userActive);
			$portrait = $this->DisposeModel-> delete_xss($row->portrait);
			$data['portrait'] = "";
			if($portrait){
				$data['portrait'] = $portrait;
			}
        }else{
			return FALSE;
		}
		return $data;
	}

	public function userAllInfo($address, $opt=[])
	{/*获取用户完整信息分页
		opt可选参数
		[
			type => login,登录类型
		];*/
		$sql="SELECT 
					nickname,
					uactive,
					portrait,
					portrait_hash,
					last_active,
					topic_sum,
					focus_sum,
					fans_sum
				FROM $this->tablename WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row = $query->getRow();
		if(!$row && $opt['type'] == 'login'){
			$insertSql = "INSERT INTO $this->tablename(address) VALUES ('$address')";
			$insBehSql = "INSERT INTO wet_behavior(address, thing) VALUES ('$address', 'newUserLogin')";
			$this->db->query($insertSql);
            $this->db->query($insBehSql);
		}
		$data['userAddress'] = $address;
		$nickname = $this->DisposeModel-> delete_xss($row->nickname);
		$data['nickname'] = "";
		if($nickname){
			$data['nickname'] = $nickname;
		}
		$userActive = (int)$row->uactive;
		$data['active'] 	= $userActive;
		$data['userActive'] = $this->getActiveGrade($userActive);
		$bsConfig = $this->ConfigModel-> backendConfig();
		$data['lastActive']   = ($userActive - $row->last_active) * $bsConfig['airdropWttRatio'];
		$portrait 	  		  = $this->DisposeModel-> delete_xss($row->portrait);
		$portraitHash 		  = $row->portrait_hash;
		$data['portrait']	  = "";
		$data['portraitHash'] = "";
		if($portrait){
			$data['portrait']	  = $portrait;
			$data['portraitHash'] = $portraitHash;
		}
		$data['topic'] = (int)$row->topic_sum;
		$data['focus'] = (int)$row->focus_sum;
		$data['fans']  = (int)$row->fans_sum;
		if($opt['type'] == 'login'){
			$isAdmin = $this->isAdmin($address);
			if($isAdmin){
				$data['isAdmin']  = TRUE;
			}
		}
		
		return $data;
	}

	public function getName($address)
	{//获取用户昵称
		$sql   = "SELECT nickname FROM $this->tablename WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		if ($row) {
			$nickname = $this->DisposeModel-> delete_xss($row->nickname);
			$data = "";
			if($nickname){
				$data = $nickname;
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
		$selectSql = "SELECT address FROM $this->tablename WHERE address = '$address' LIMIT 1";
		$query	   = $this->db->query($selectSql);
		$row	   = $query-> getRow();
		if(!$row){
			$insertSql = "INSERT INTO $this->tablename(address) VALUES ('$address')";
			$this->db->query($insertSql);
		}

		if($e){
			$updateSql="UPDATE $this->tablename SET uactive = uactive + '$active' WHERE address = '$address'";
		}else{
			$updateSql="UPDATE $this->tablename SET uactive = uactive - '$active' WHERE address = '$address'";
		}
		$this->db->query($updateSql);
	}

	public function userFocus($focus, $fans, $e)
	{/*用户关注用户
			$focus = 目标地址
			$fans  = 粉丝地址
			$e     = isFocus
	*/
		$selectSql = "SELECT address FROM $this->tablename WHERE address = '$fans' LIMIT 1";
		$query	   = $this->db->query($selectSql);
		$row	   = $query-> getRow();
		if(!$row){
			$insertSql = "INSERT INTO $this->tablename(address) VALUES ('$fans')";
			$this->db->query($insertSql);
		}

		if($e){
			$focusSql = "UPDATE $this->tablename SET focus_sum = focus_sum + 1 WHERE address = '$fans'";
			$fansSql  = "UPDATE $this->tablename SET fans_sum = fans_sum + 1 WHERE address = '$focus'";
		}else{
			$focusSql = "UPDATE $this->tablename SET focus_sum = focus_sum - 1 WHERE address = '$fans'";
			$fansSql  = "UPDATE $this->tablename SET fans_sum = fans_sum - 1 WHERE address = '$focus'";
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

	public function isAdmin($address)
	{//管理员校验
		$bsConfig = $this->ConfigModel-> backendConfig();
		$admin_1  = $bsConfig['adminUser_1'];
		$admin_2  = $bsConfig['adminUser_2'];
		$admin_3  = $bsConfig['adminUser_3'];

		if($address === $admin_1 || $address === $admin_2 || $address === $admin_3){
			return true;
		}else{
			return false;
		}
	}

}

