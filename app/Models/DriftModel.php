<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\BloomModel;
use App\Models\DisposeModel;

class DriftModel extends Model {
//Drift模型

	public function __construct(){
        //parent::__construct();
		$this->db = \Config\Database::connect('default');
		$this->bloom		   = new BloomModel();
		$this->DisposeModel	   = new DisposeModel();
		$this->wet_drift_topic = "wet_drift_topic";
		$this->wet_drift_reply = "wet_drift_reply";
		
    }

    public function limit($page, $size)
	{//回复分页
		$page = max(1, (int)$page);
		$size = max(1, (int)$size);

		$data['code'] = 200;
		$akToken = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if (!$isAkToken) {
			$data['code'] = 401;
			$data['msg']  = 'error_login';
			return json_encode($data);
		}

		$countSql = "SELECT count(to_hash) FROM $this->wet_drift_reply WHERE to_hash = '$opt[hash]'";
		$limitSql = "SELECT hash FROM $this->wet_drift_reply WHERE to_hash = '$opt[hash]' 
						ORDER BY uid DESC LIMIT $size OFFSET ".($page-1) * $size;

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
			$txBloom = $this->bloom-> txBloom($hash);
			if (!$txBloom) {
				if ($opt['select']  == 'content') {
					$detaila[] = $this->content-> txContent($hash, $opt);
				}

				if ($opt['select']  == 'comment') {
					$detaila[] = $this->comment-> txComment($hash, $opt);
				}

				if ($opt['select'] == 'reply') {
					$detaila[] = $this->reply-> txReply($hash, $opt);
				}
			}
			$data['data']['data'] = $detaila;
		}
		$data['msg'] = 'success';
		return $data;
	}

	private function pages($page, $size, $sql)
	{
		$query = $this->db-> query($sql);
		$row   = $query-> getRow();
        $count = $row->count;//总数量
		$data  = [
			'page'		=> $page,  //当前页
			'size'		=> $size,  //每页数量
			'totalPage'	=> (int)ceil($count/$size),  //总页数
			'totalSize'	=> (int)$count  //总数量
		];
		return $data;
	}

}

