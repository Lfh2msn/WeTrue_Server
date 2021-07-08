<?php namespace App\Models;

use CodeIgniter\Model;

class ValidModel extends Model {
//验证Model

	public function __construct(){
		$this->db = \Config\Database::connect('default');
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

	public function isRewardHash($hash)
	{//打赏Hash是否存在
		$sql   = "SELECT hash FROM wet_reward WHERE hash = '$hash' LIMIT 1";
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

	public function isComplain($hash)
	{//投诉hash是否存在
		$sql   = "SELECT hash FROM wet_complain WHERE hash = '$hash' LIMIT 1";
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
	{//验证是否已经映射
		$sql   = "SELECT state FROM wet_mapping WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row->state ? true : false;
	}

}

