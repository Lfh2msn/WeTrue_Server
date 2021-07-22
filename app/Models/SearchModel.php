<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\BloomModel;
use App\Models\ContentModel;
use App\Models\DisposeModel;
use App\Models\UserModel;
use App\Models\ValidModel;

class SearchModel extends Model {
//搜索Model

	public function __construct(){
		//parent::__construct();
		$this->db = \Config\Database::connect('default');
		$this->BloomModel   = new BloomModel();
		$this->ContentModel = new ContentModel();
		$this->DisposeModel = new DisposeModel();
		$this->UserModel	= new UserModel();
		$this->ValidModel	= new ValidModel();
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
		
		elseif ( $opt['type'] == 'user' ) 
		{//搜索用户
			$this->tablename = "wet_users";
			$countSql = "SELECT count(address) FROM $this->tablename WHERE nickname ilike '%$opt[key]%'";
			$limitSql = "SELECT address FROM $this->tablename 
								WHERE nickname ilike '%$opt[key]%' ORDER BY uid DESC LIMIT $size OFFSET ".($page-1) * $size;
		}
		
		elseif ( $opt['type'] == 'tag' ) 
		{//搜索话题
			$this->tablename = "wet_topic_tag";
			$countSql = "SELECT count(keywords) FROM $this->tablename WHERE keywords ilike '%$opt[key]%'";
			$limitSql = "SELECT keywords FROM $this->tablename 
								WHERE keywords ilike '%$opt[key]%' ORDER BY uid DESC LIMIT $size OFFSET ".($page-1) * $size;
		} else {
			return $this->DisposeModel-> wetJsonRt(406,'error_type');
		}

		$data = $this->cycle($page, $size, $countSql, $limitSql, $opt);
		return json_encode($data);
	}


	private function cycle($page, $size, $countSql, $limitSql, $opt)
	{//列表循环
		$data = $this->pages($page, $size, $countSql);
		$query  = $this->db-> query($limitSql);
		$getRes = $query-> getResult();
		$data['data'] = [];
		foreach ($getRes as $row)
		{
			$hash  	  = $row->hash;
			$address  = $row->address;
			$keywords = $row->keywords;
			if ($hash) {
				$txBloom = $this->BloomModel-> txBloom($hash);
			}

			if ($address) {
				$idBloom = $this->BloomModel-> addressBloom($address);
			}

			if ($keywords){
				$isTopicState = $this->ValidModel-> isTopicState($keywords);
			}

			if($txBloom || !$idBloom || $isTopicState)
			{
				if($opt['type']  == 'topic') {
					$detaila[] = $this->ContentModel-> txContent($hash, $opt);
				}

				if($opt['type']  == 'user') {
					$detaila[] = $this->UserModel-> userAllInfo($address);
				}

				if($opt['type']  == 'tag') {
					$detaila[] = $keywords;
				}
			}
			$data['data'] = $detaila;
		}
		$data = $this->DisposeModel-> wetRt(200,'success',$data);
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

