<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\BloomModel;
use App\Models\ContentModel;
use App\Models\DisposeModel;
use App\Models\UserModel;

class SearchModel extends Model {
//搜索Model

	public function __construct(){
		parent::__construct();
		$this->bloom   		= new BloomModel();
		$this->wet_content 	= new ContentModel();
		$this->DisposeModel = new DisposeModel();
		$this->UserModel	= new UserModel();
	}

	public function search($page, $size, $opt)
	{	
		$page = max(1, (int)$page);
		$size = max(1, (int)$size);
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if ( $isAkToken ) {
			$opt['userLogin'] = $akToken;
		}

		if ( $opt['type'] == 'topic' )
		{//搜索主贴
			$this->tablename = "wet_content";
			$countSql = "SELECT count(hash) FROM $this->tablename WHERE payload ilike '%$opt[key]%'";
			$limitSql = "SELECT hash FROM $this->tablename 
								WHERE payload ilike '%$opt[key]%' ORDER BY utctime DESC LIMIT $size OFFSET ".($page-1) * $size;
		} 
		
		else if ( $opt['type'] == 'user' ) 
		{//搜索用户
			$this->tablename = "wet_users";
			$countSql = "SELECT count(address) FROM $this->tablename WHERE nickname ilike '%$opt[key]%'";
			$limitSql = "SELECT address FROM $this->tablename 
								WHERE nickname ilike '%$opt[key]%' ORDER BY uid DESC LIMIT $size OFFSET ".($page-1) * $size;
		} else {
			$data['code'] = 406;
			$data['msg']  = 'error_type';
			return json_encode($data);
		}

		$data = $this->cycle($page, $size, $countSql, $limitSql, $opt);
		return json_encode($data);
	}


	private function cycle($page, $size, $countSql, $limitSql, $opt)
	{//列表循环
		$data['code'] = 200;
		$data['data'] = $this->pages($page, $size, $countSql);
		$query = $this->db-> query($limitSql);
		$data['data']['data'] = [];
		foreach ($query-> getResult() as $row)
		{
			$hash  	 = $row -> hash;
			$address = $row -> address;
			if ($hash) {
				$txBloom = $this->bloom-> txBloom($hash);
			}

			if ($address) {
				$idBloom = $this->bloom-> addressBloom($address);
			}

			if($txBloom || !$idBloom)
			{
				if($opt['type']  == 'topic') {
					$detaila[] = $this->wet_content-> txContent($hash, $opt);
				}

				if($opt['type']  == 'user') {
					$detaila[] = $this->UserModel-> userAllInfo($address);
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

