<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\DisposeModel;
use App\Models\HashReadModel;
use App\Models\ContentModel;
use App\Models\CommentModel;
use App\Models\ReplyModel;
use App\Models\BloomModel;
use App\Models\ValidModel;
use App\Models\GetModel;

class ComplainModel extends Model {
//投诉Model

	public function __construct(){
		//parent::__construct();
		$this->db = \Config\Database::connect('default');
		$this->DisposeModel  = new DisposeModel();
		$this->HashReadModel = new HashReadModel();
		$this->ContentModel  = new ContentModel();
		$this->CommentModel  = new CommentModel();
		$this->ReplyModel 	 = new ReplyModel();
		$this->BloomModel 	 = new BloomModel();
		$this->ValidModel 	 = new ValidModel();
		$this->GetModel 	 = new GetModel();
		$this->wet_complain  = "wet_complain";
		$this->wet_bloom     = "wet_bloom";
		$this->wet_behavior  = "wet_behavior";
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
		if (!$isAkToken) {
			$data['code'] = 401;
			$data['msg'] = 'error_login';
			return json_encode($data);
		}

		$txBloom = $this->BloomModel-> txBloom($hash);
		if ($txBloom) {
			$this->deleteComplain($hash);
			$data['msg'] = 'error_repeat';
			return json_encode($data);
        }

		$rpSenderId = $this->GetModel-> getTxSenderId($hash);  //获取tx发送人ID
        if ( empty($rpSenderId) ) {
			$data['msg'] = 'error_unknown';
        	return json_encode($data);
        }
		
		$isComplain = $this->ValidModel->isComplain($hash);
		if ($isComplain) {
			$updateReportSql = "UPDATE $this->wet_complain SET complain_sum = complain_sum + 1 WHERE hash = '$hash'";
			$this->db-> query($updateReportSql);
        } else {
			$insertData = [
				'hash'         => $hash,
				'address'      => $rpSenderId,
				'complain_sum' => 1
			];
			$this->db->table($this->wet_complain)->insert($insertData);
		}

		//入库行为记录
		$insetrBehaviorDate = [
			'address'   => $akToken,
			'hash'      => $hash,
			'thing'     => 'Complain',
			'toaddress' => $rpSenderId
		];
		$this->db->table($this->wet_behavior)->insert($insetrBehaviorDate);
		$data['msg']  = 'success';
		return json_encode($data);
	}

	public function limit($page, $size, $offset, $opt=[])
	{/*投诉列表分页
		opt可选参数
			[

			];*/
		$page   = max(1, (int)$page);
		$size   = max(1, (int)$size);
		$offset = max(0, (int)$offset);
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		$isAdmin   = $this->ValidModel-> isAdmin($akToken);
		$data['data'] = [];
		if (!$isAkToken || !$isAdmin) {
			return $this->DisposeModel-> wetJsonRt(401, 'error_login');
		}
		$opt['userLogin'] = $akToken;

		$countSql = "SELECT count(hash) FROM $this->wet_complain";
		$limitSql = "SELECT hash FROM $this->wet_complain LIMIT $size OFFSET ".(($page-1) * $size + $offset);
		$data = $this->cycle($page, $size, $countSql, $limitSql, $opt);

		return $this->DisposeModel-> wetJsonRt(200, 'success', $data);
    }

	private function cycle($page, $size, $countSql, $limitSql, $opt)
	{//列表循环

		$data = $this->pages($page, $size, $countSql);
		$query = $this->db-> query($limitSql);
		$data['data'] = [];
		foreach ($query-> getResult() as $row) {
			$hash  = $row -> hash;

			$conSql   = "SELECT hash FROM wet_content WHERE hash='$hash' LIMIT 1";
			$conQuery = $this-> db-> query($conSql);
			$conRow   = $conQuery-> getRow();

			if ($conRow) {
				$detaila[] = $this->ContentModel-> txContent($hash, $opt);
			} else {
				$comSql   = "SELECT hash FROM wet_comment WHERE hash='$hash' LIMIT 1";
				$comQuery = $this-> db-> query($comSql);
				$comRow   = $comQuery-> getRow();
			}
			
			if ($comRow) {
				$detaila[] = $this->CommentModel-> txComment($hash, $opt);
			} else {
				$repSql   = "SELECT hash FROM wet_reply WHERE hash='$hash' LIMIT 1";
				$repQuery = $this-> db-> query($repSql);
				$repRow   = $repQuery-> getRow();
			}

			if ($repRow) {
				$detaila[] = $this->ReplyModel-> txReply($hash, $opt);
			}

			$data['data'] = $detaila;
		}
		return $data;
	}

	private function pages($page, $size, $sql)
	{
		$query = $this->db-> query($sql);
		$row   = $query-> getRow();
        $count = $row->count;  //总数量
		$data  = [
			'page'		=> $page,  //当前页
			'size'		=> $size,  //每页数量
			'totalPage'	=> (int)ceil($count/$size),  //总页数
			'totalSize'	=> (int)$count  //总数量
		];
		return $data;
	}

}

