<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	ValidModel,
	DisposeModel
};
use App\Models\Content\ContentPullModel;

class TopicModel extends ComModel
{//话题Model
	public function __construct(){
        parent::__construct();
		$this->ContentPullModel  = new ContentPullModel();
		$this->ValidModel 		 = new ValidModel();
		$this->UserModel 	 	 = new UserModel();
        $this->wet_topic_tag     = "wet_topic_tag";
		$this->wet_topic_content = "wet_topic_content";
    }

	public function isTopic($content)
	{//内容搜索话题
		//$topicTag = preg_match_all("/#[x80-xff\u4e00-\u9fa5\w ,，.。!！-？·\?æÆ]{1,25}#/u", $content, $keywords);
		$topicTag = preg_match_all("/#([\x{4e00}-\x{9fa5}a-zA-Z0-9]{1,24}+\b)(?!;)/u", $content, $keywords);
		return $topicTag ? $keywords[0] : false;
	}

	public function getTopicInfo($keyword, $opt=[])
	{//获取话题信息
		$isTopic = $this->isTopic($keyword);
		if ($isTopic) {
			$selectTag = "SELECT keywords,
								 img_icon, 
								 describe, 
								 sender_id, 
								 state, 
								 utctime, 
								 topic_sum, 
								 read_sum
							FROM $this->wet_topic_tag 
							WHERE keywords ilike '$keyword' AND state = '1' 
							LIMIT 1";
			$getTagRow = $this->db->query($selectTag)-> getRow();
			$data = [
					'total'		=> (int)$getTagRow-> topic_sum,  //总话题量
					'read_sum'	=> (int)$getTagRow-> read_sum,  //阅读量
					'keyword'	=> $getTagRow-> keywords,  //话题关键词
					'imgIcon'	=> $getTagRow-> img_icon,  //话题图标
					'describe'	=> $getTagRow-> describe,  //简介
					'sender_id'	=> $getTagRow-> sender_id,  //创建人
					'nickname'	=> $this->UserModel-> getName($getTagRow->sender_id),  //创建昵称
					'state'		=> (int)$getTagRow-> state,  //状态
					'utctime'	=> (int)$getTagRow-> utctime,  //时间
					];
			if($opt['read']) {
				$updateSql = "UPDATE $this->wet_topic_tag 
							SET read_sum = read_sum + 1
							WHERE keywords ilike '$getTagRow->keywords'";
				$this->db->query($updateSql);
			}
			return $data;
		}
		
	}

	public function getTopicList($page, $size, $offset, $keyword)
	{//获取话题主贴列表
		$page   = max(1, (int)$page);
		$size   = max(1, (int)$size);
		$offset = max(0, (int)$offset);
		$akToken   = $_SERVER['HTTP_KEY'];
		$isAkToken = DisposeModel::checkAddress($akToken);
		if ($isAkToken) $opt['userLogin'] = $akToken;
		$opt['substr']	  = 160; //限制输出

		$isTopic = $this->isTopic($keyword);
		if ($isTopic) {
			$selectTag = "SELECT uid, topic_sum
							FROM $this->wet_topic_tag 
							WHERE keywords ilike '$keyword' 
							AND state = '1' LIMIT 1";
			$getTagRow = $this->db->query($selectTag)-> getRow();

			$data  = [
					'page'		=> $page,  //当前页
					'size'		=> $size,  //每页数量
					'totalPage'	=> (int) ceil(($getTagRow->topic_sum) / $size),  //总页数
					'totalSize'	=> (int) $getTagRow->topic_sum,  //总数量
				];
			if ($getTagRow) {
				/*
				$limitSql = "SELECT wet_topic_content.hash
								FROM wet_topic_content 
								INNER JOIN wet_topic_tag 
								ON wet_topic_content.tag_uid = wet_topic_tag.uid 
								AND wet_topic_content.state = '1' 
								AND wet_topic_tag.keywords ilike '$keyword' 
								ORDER BY wet_topic_content.utctime DESC 
								LIMIT $size OFFSET ".(($page-1) * $size + $offset);
				*/
				$topicTagUid = $getTagRow->uid;
				$limitSql = "SELECT hash FROM wet_topic_content 
								WHERE tag_uid = '$topicTagUid' AND state = '1'
								ORDER BY utctime DESC 
								LIMIT $size OFFSET ".(($page-1) * $size + $offset);
				$query = $this->db-> query($limitSql);
				$getResult = $query-> getResult();

				foreach ($getResult as $row) {
					$arrList[] = $row->hash;
				}
	
				if($page <= 1){
					$addList = [];
					$arrList = DisposeModel::arrayToArray($addList, $arrList);
				}

				$data['data'] = [];
				foreach ($arrList as $hash) {
					$isBloomHash = $this->ValidModel-> isBloomHash($hash);
					if (!$isBloomHash) {
						$isData = $this->ContentPullModel-> txContent($hash, $opt);
						if(isset($isData)) $detaila[] = $isData;
					}
					$data['data'] = $detaila;
				}

				$countList = (int) count($arrList);
				if ($countList > 0) {  //更新阅读数量
					$updateSql = "UPDATE $this->wet_topic_tag 
							SET read_sum = read_sum + '$countList'
							WHERE keywords ilike '$keyword'";
					$this->db->query($updateSql);
				}

				$code = 200;
				$msg  = 'success';
			} else {
				$code = 200;
				$msg  = 'no_data';
			}
		} else {
			$code = 406;
			$msg  = 'error';
		}
		return DisposeModel::wetRt($code, $msg, $data);
	}

	public function hotRecTopic()
	{//获取热点话题
		$nowTime   = time() * 1000;
		$cycleTime = $nowTime - (86400000 * 7);
		$selectUid = "SELECT tag_uid, count(tag_uid) 
						FROM wet_topic_content 
						WHERE utctime >= '$cycleTime' AND state = '1'
						GROUP BY tag_uid 
						ORDER BY count DESC
						LIMIT 20";
		$query = $this->db-> query($selectUid);
		$getResult = $query-> getResult();
		$data['data'] = [];
		if ($getResult) {
			foreach ($getResult as $row) {
				$tagUid = (int) $row->tag_uid;
				$tagHot = (int) $row->count;
				$keyword = $this->tagUidToKeyWord($tagUid);
				if ($keyword) {
					$isData['keyword'] = $keyword;
					$isData['tagHot']  = $tagHot;
				}
				if(isset($isData)) $detaila[] = $isData;
				$data['data'] = $detaila;
			}
		}
		return DisposeModel::wetRt(200, 'success', $data);
	}

	public function tagUidToKeyWord($uid)
	{//Uid查询话题关键词
			$select = "SELECT keywords
						FROM $this->wet_topic_tag 
						WHERE uid = $uid 
						LIMIT 1";
			$row  = $this->db->query($select)-> getRow();
			return $row->keywords;
	}

	public function insertTopic($topic=[])
	{/*话题入库
		topic = [
			hash	  => hash
			content   => 内容
			sender_id => 发送人
			utctime   => 时间戳
		]
	*/
		$keywords = $this->isTopic($topic['content']);
		$keywords = array_unique($keywords);
		$keywords = array_values($keywords);
		if ($keywords) {
			$count = count($keywords) <= 15 ? count($keywords) : 15;
			for($i=0; $i<$count; $i++) {
				$selectTag = "SELECT uid FROM $this->wet_topic_tag WHERE keywords ilike '$keywords[$i]' LIMIT 1";
				$getTagRow = $this->db->query($selectTag)-> getRow();
				if (!$getTagRow) {
					$inTopicTag = [
									'keywords'	=> $keywords[$i],
									'sender_id' => $topic['sender_id'],
									'utctime'   => $topic['utctime']
								];
					$this->db->table($this->wet_topic_tag)->insert($inTopicTag);
					$getTagRow  = $this->db->query($selectTag)-> getRow();
				}

				$topicTagUid    = $getTagRow->uid;
				$inTopicContent = [
									'tag_uid'	=> $topicTagUid,
									'hash'		=> $topic['hash'],
									'sender_id' => $topic['sender_id'],
									'utctime'   => $topic['utctime']
								];
				$this->db->table($this->wet_topic_content)->insert($inTopicContent);
				$updateSql = "UPDATE $this->wet_topic_tag 
								SET 
									topic_sum = topic_sum + 1, 
									read_sum  = read_sum + 1
								WHERE keywords ilike '$keywords[$i]'";
				$this->db->query($updateSql);
			}
		}
	}
}

