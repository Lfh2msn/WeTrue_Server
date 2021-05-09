<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\DisposeModel;
use App\Models\HashReadModel;
use App\Models\UserModel;
use App\Models\ContentModel;
use App\Models\CommentModel;
use App\Models\ReplyModel;
use App\Models\BloomModel;

class ComplainModel extends Model {
//投诉Model

	public function __construct(){
		parent::__construct();
		$this->DisposeModel  = new DisposeModel();
		$this->HashReadModel = new HashReadModel();
		$this->UserModel	 = new UserModel();
		$this->ContentModel  = new ContentModel();
		$this->CommentModel  = new CommentModel();
		$this->ReplyModel 	 = new ReplyModel();
		$this->BloomModel 	 = new BloomModel();
		$this->wet_complain  = 'wet_complain';
		$this->wet_bloom     = 'wet_bloom';
		$this->wet_behavior  = 'wet_behavior';
	}

	public function isComplain($hash)
	{//投诉hash，存在返回false
		$sql   = "SELECT hash FROM $this->wet_complain WHERE hash = '$hash' LIMIT 1";
		$query = $this->db->query($sql);
        $row   = $query->getRow();
		return $row ? false : true;
	}

	public function complainAddress($hash)
	{//投诉hash账户地址
		$sql   = "SELECT address FROM $this->wet_complain WHERE hash = '$hash' LIMIT 1";
		$query = $this->db->query($sql);
        $row   = $query->getRow();
		return $row-> address;
	}

	public function deleteComplain($hash)
	{//删除举报
		$deleteSql = "DELETE FROM $this->wet_complain WHERE hash = '$hash'";
		$this->db-> query($deleteSql);
	}

	public function txHash($hash)
	{//投诉hash入库
		$data['code'] = 200;
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if(!$isAkToken){
			$data['code'] = 401;
			$data['msg'] = 'error_login';
			return json_encode($data);
		}

		$isBloom = $this->BloomModel-> txBloom($hash);
		if (!$isBloom) {
			$this->deleteComplain($hash);
			$data['msg'] = 'repeat';
			return json_encode($data);
        }

		$rpSenderId = $this->HashReadModel-> getSenderId($hash);  //获取tx发送人ID
        if ( empty($rpSenderId) ) {
			$data['msg'] = 'error_unknown';
        	return json_encode($data);
        }
		
		$isComplain = $this->isComplain($hash);
		if ($isComplain) {
			$updateReportSql = "INSERT INTO $this->wet_complain(
									hash, address, cp_num
								) VALUES (
									'$hash', '$rpSenderId', '1'
								)";
        } else {
			$updateReportSql = "UPDATE $this->wet_complain SET cp_num = cp_num+1 WHERE hash = '$hash'";
		}

		//入库行为记录
		$behaviorSql = "INSERT INTO $this->wet_behavior(
							address, hash, thing, toaddress
						) VALUES (
							'$akToken', '$hash', 'Complain', '$rpSenderId'
						)";

		$this->db-> query($updateReportSql);
		$this->db->query($behaviorSql);

		$data['msg']  = 'success';
		return json_encode($data);

	}

	public function limit($page, $size, $opt=[])
	{/*投诉列表分页
		opt可选参数
			[

			];*/
		$page = max(1, (int)$page);
		$size = max(1, (int)$size);
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		$isAdmin   = $this->UserModel-> isAdmin($akToken);
		$data['code'] = 200;
		$data['data']['data'] = [];
		if (!$isAkToken || !$isAdmin) {
			$data['code'] = 401;
			$data['msg']  = 'error_login';
			return json_encode($data);
		}
		$opt['userLogin'] = $akToken;

		$countSql = "SELECT count(hash) FROM $this->wet_complain";
		$limitSql = "SELECT hash FROM $this->wet_complain LIMIT $size OFFSET ".($page-1) * $size;

		$data = $this->cycle($page, $size, $countSql, $limitSql, $opt);
		return json_encode($data);
    }

	private function cycle($page, $size, $countSql, $limitSql, $opt)
	{//列表循环
		$data['code'] = 200;
		$data['data'] = $this->pages($page, $size, $countSql);
		$query = $this->db-> query($limitSql);
		$data['data']['data'] = [];
		foreach ($query-> getResult() as $row){
			$hash  = $row -> hash;

			$conSql   = "SELECT hash FROM wet_content WHERE hash='$hash' LIMIT 1";
			$conQuery = $this-> db-> query($conSql);
			$conRow   = $conQuery-> getRow();

			if ($conRow) {
				$detaila[] = $this->ContentModel-> txContent($hash, $opt);
			}else{
				$comSql   = "SELECT hash FROM wet_comment WHERE hash='$hash' LIMIT 1";
				$comQuery = $this-> db-> query($comSql);
				$comRow   = $comQuery-> getRow();
			}
			
			if ($comRow) {
				$detaila[] = $this->CommentModel-> txComment($hash, $opt);
			}else{
				$repSql   = "SELECT hash FROM wet_reply WHERE hash='$hash' LIMIT 1";
				$repQuery = $this-> db-> query($repSql);
				$repRow   = $repQuery-> getRow();
			}

			if ($repRow) {
				$detaila[] = $this->ReplyModel-> txReply($hash, $opt);
			}

			$data['data']['data'] = $detaila;
		}
		$data['msg'] = 'success';
		return $data;
	}

	private function pages($page, $size, $sql)
	{
		$query  = $this->db-> query($sql);
		$row	= $query-> getRow();
        $count	= $row->count;  //总数量
		$data	= [
			'currentPage'	=> $page,  //当前页
			'perPage'		=> $size,  //每页数量
			'totalPage'		=> (int)ceil($count/$size),  //总页数
			'lastPage'		=> (int)ceil($count/$size),  //总页数
			'totalSize'		=> (int)$count  //总数量
		];
		return $data;
	}

}

