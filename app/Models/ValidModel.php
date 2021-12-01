<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\ConfigModel;

class ValidModel extends Model {
//验证Model

	public function __construct(){
		$this->db = \Config\Database::connect('default');
		$this->ConfigModel  = new ConfigModel();
    }

	public function isContentHash($hash)
	{//主贴Hash是否存在
		$sql   = "SELECT hash FROM wet_content WHERE hash = '$hash' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public function isCommentHash($hash)
	{//评论Hash是否存在
		$sql   = "SELECT hash FROM wet_comment WHERE hash = '$hash' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public function isReplyHash($hash)
	{//回复Hash是否存在
		$sql   = "SELECT hash FROM wet_reply WHERE hash = '$hash' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public function isPraise($hash, $address)
	{//点赞是否存在
		$sql   = "SELECT hash FROM wet_praise WHERE hash = '$hash' and sender_id = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public function isNickname($nickname)
	{//昵称是否存在
		$sql   = "SELECT nickname FROM wet_users WHERE nickname ilike '$nickname' LIMIT 1";
		$query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public function isUser($address)
	{//用户ID是否存在
		$sql   = "SELECT address FROM wet_users WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public function isAuthUser($address)
	{//用户ID是否认证
		$sql   = "SELECT is_auth FROM wet_users WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row->is_auth ? true : false;
	}

	public function isAdmin($address)
	{//管理员校验
		$bsConfig = $this->ConfigModel-> backendConfig();
		$admin_1  = $bsConfig['adminUser_1'];
		$admin_2  = $bsConfig['adminUser_2'];
		$admin_3  = $bsConfig['adminUser_3'];
		if($address === $admin_1 || $address === $admin_2 || $address === $admin_3) {
			return true;
		} else {
			return false;
		}
	}

	public function isRewardHash($hash)
	{//打赏Hash是否存在
		$sql   = "SELECT hash FROM wet_reward WHERE hash = '$hash' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public function isTempHash($hash)
	{//临时Hash是否存在
		$sql   = "SELECT tp_hash FROM wet_temp WHERE tp_hash = '$hash' ORDER BY tp_time DESC LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public function isStar($hash, $address)
	{//收藏是否存在
		$sql   ="SELECT hash FROM wet_star WHERE hash = '$hash' AND sender_id = '$address' LIMIT 1";
        $query = $this->db-> query($sql);
		$row   = $query-> getRow();
		return $row ? true : false;
	}

	public function isFocus($focus,$my_id)
	{//关注是否存在
		$sql   = "SELECT focus, fans FROM wet_focus WHERE fans = '$my_id' AND focus = '$focus' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public function isTopicState($keywords)
	{//话题状态是否正常
		$sql   = "SELECT state FROM wet_topic_tag WHERE keywords = '$keywords' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public function isComplain($hash)
	{//投诉hash是否存在
		$sql   = "SELECT hash FROM wet_complain WHERE hash = '$hash' LIMIT 1";
		$query = $this->db->query($sql);
        $row   = $query->getRow();
		return $row ? true : false;
	}

	public function isSubmitOpenState($address)
	{//映射挖矿开通地址是否提交
		$sql   = "SELECT tp_sender_id FROM wet_temp WHERE tp_sender_id = '$address' AND tp_type = 'mapping' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public function isMapAccount($address)
	{//映射挖矿是否开通
		$sql   = "SELECT is_map FROM wet_users WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row->is_map ? true : false;
	}

	public function isMapAddress($address)
	{//映射挖矿地址是否存在
		$sql   = "SELECT address FROM wet_mapping WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public function isMapState($address)
	{//映射挖矿状态是否存在
		$sql   = "SELECT state FROM wet_mapping WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row->state ? true : false;
	}

}

