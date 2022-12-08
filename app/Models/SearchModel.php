<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	DisposeModel,
	UserModel,
	ValidModel,
	TopicModel
};
use App\Models\Content\ContentPullModel;

class SearchModel
{//搜索Model

	public function search($page, $size, $offset, $opt=[])
	{	
		$page   = max(1, (int)$page);
		$size   = max(1, (int)$size);
		$offset = max(0, (int)$offset);
		$akToken   = isset($_SERVER['HTTP_KEY']) ? $_SERVER['HTTP_KEY'] : false;
		$isAkToken = DisposeModel::checkAddress($akToken);
		if ( $isAkToken ) {
			$opt['userLogin'] = $akToken;
		}

		if ( $opt['type'] == 'topic' )
		{//搜索主贴
			$this->tablename = "wet_content";
			$countSql = "SELECT count(hash) FROM $this->tablename WHERE payload ilike '%$opt[key]%'";
			$limitSql = "SELECT hash FROM $this->tablename 
								WHERE payload ilike '%$opt[key]%' ORDER BY utctime DESC LIMIT $size OFFSET ".(($page-1) * $size + $offset);
		} 
		
		elseif ( $opt['type'] == 'user' ) 
		{//搜索用户
			$this->tablename = "wet_users";
			$countSql = "SELECT count(address) FROM $this->tablename WHERE nickname ilike '%$opt[key]%'";
			$limitSql = "SELECT address FROM $this->tablename 
								WHERE nickname ilike '%$opt[key]%' 
								ORDER BY uid DESC 
								LIMIT $size OFFSET ".(($page-1) * $size + $offset);
		}
		
		elseif ( $opt['type'] == 'topicTag' ) 
		{//搜索话题
			$this->tablename = "wet_topic_tag";
			$countSql = "SELECT count(keywords) FROM $this->tablename WHERE keywords ilike '%$opt[key]%'";
			$limitSql = "SELECT keywords FROM $this->tablename 
								WHERE keywords ilike '%$opt[key]%' 
								ORDER BY read_sum DESC, utctime DESC 
								LIMIT $size OFFSET ".(($page-1) * $size + $offset);
			$upReadSql = "UPDATE $this->tablename 
							SET read_sum = CASE keywords 
								WHEN keywords THEN read_sum + 1
							END 
							WHERE keywords IN ($limitSql)";
			ComModel::db()-> query($upReadSql);
		} else {
			return DisposeModel::wetJsonRt(406,'error_type');
		}

		$data = $this->cycle($page, $size, $countSql, $limitSql, $opt);
		return json_encode($data);
	}


	private function cycle($page, $size, $countSql, $limitSql, $opt)
	{//列表循环
		$data = $this->pages($page, $size, $countSql);
		$query  = ComModel::db()-> query($limitSql);
		$getRes = $query-> getResult();
		$data['data'] = [];
		foreach ($getRes as $row) {
			$hash  	  = $row->hash;
			$address  = $row->address;
			$keywords = $row->keywords;
			if ($hash) {
				$isBloomHash = ValidModel::isBloomHash($hash);
			}

			if ($address) {
				$isBloomAddress = ValidModel::isBloomAddress($address);
			}

			if ($keywords){
				$isTopicState = ValidModel::isTopicState($keywords);
			}

			if($isBloomHash || !$isBloomAddress || $isTopicState)
			{
				if($opt['type']  == 'topic') {
					$isData = ContentPullModel::txContent($hash, $opt);
					if(isset($isData)) $detaila[] = $isData;
				}

				if($opt['type']  == 'user') {
					$isData = UserModel::userAllInfo($address);
					if(isset($isData)) $detaila[] = $isData;
				}

				if($opt['type']  == 'topicTag') {
					$isData = TopicModel::getTopicInfo($keywords);
					if(isset($isData)) $detaila[] = $isData;
				}
			}
			$data['data'] = $detaila;
		}
		return DisposeModel::wetRt(200,'success',$data);
	}

	private function pages($page, $size, $sql)
	{
		$query = ComModel::db()-> query($sql);
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

