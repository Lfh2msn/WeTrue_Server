<?php 
namespace App\Models;

use App\Models\DisposeModel;

class DeleteModel extends ComModel
{//删除Model
	public function __construct(){
        parent::__construct();
		$this->DisposeModel = new DisposeModel();
    }

	public function deleteAll($address)
	{//删除用户全部信息
		$this->deleteReward($address);
		$this->deleteMsg($address);
		$this->deleteFocus($address);
		$this->deleteStar($address);
		$this->deletePraise($address);
		$this->deleteReply($address);
		$this->deleteComment($address);
		$this->deleteContent($address);
		$this->deleteShContent($address);
		$this->deleteUser($address);
		$logMsg  = date('Y-m-d')."用户被自动删除,地址:{$address}";
		$logPath = "auto_delete_user/".date('Y-m');
		$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
		return "ok";
	}

	public function deleteUser($address)
	{//删除用户
		$sql = "DELETE FROM wet_users WHERE address = '$address'";
		$query = $this->db->query($sql);
	}

	public function deleteContent($address)
	{//删除主贴
		$sql = "DELETE FROM wet_content WHERE sender_id = '$address'";
		$query = $this->db->query($sql);
	}

	public function deleteShContent($address)
	{//删除SH主贴
		$sql = "DELETE FROM wet_content_sh WHERE sender_id = '$address'";
		$query = $this->db->query($sql);
	}

	public function deleteComment($address)
	{//删除评论
		$sql = "DELETE FROM wet_comment WHERE sender_id = '$address'";
		$query = $this->db->query($sql);
	}

	public function deleteReply($address)
	{//删除回复
		$sql = "DELETE FROM wet_reply WHERE sender_id = '$address'";
		$query = $this->db->query($sql);
	}

	public function deleteMsg($address)
	{//删除消息
		$sql = "DELETE FROM wet_message WHERE sender_id = '$address' OR recipient_id = '$address'";
		$query = $this->db->query($sql);
	}

	public function deleteFocus($address)
	{//删除关注
		$sql = "DELETE FROM wet_focus WHERE focus = '$address' OR fans = '$address'";
		$query = $this->db->query($sql);
	}

	public function deleteStar($address)
	{//删除收藏
		$sql = "DELETE FROM wet_star WHERE sender_id = '$address'";
		$query = $this->db->query($sql);
	}

	public function deletePraise($address)
	{//删除点赞
		$sql = "DELETE FROM wet_praise WHERE sender_id = '$address'";
		$query = $this->db->query($sql);
	}

	public function deleteReward($address)
	{//删除打赏
		$sql = "DELETE FROM wet_reward WHERE sender_id = '$address'";
		$query = $this->db->query($sql);
	}

}

