<?php 
namespace App\Models;

use App\Models\ComModel;
use App\Models\ContentModel;

class TopicModel extends ComModel
{//话题Model

	public function __construct(){
        parent::__construct();
		$this->content 			 = new ContentModel();
		$this->BloomModel 		 = new BloomModel();
		$this->DisposeModel 	 = new DisposeModel();
		$this->UserModel 	 	 = new UserModel();
        $this->wet_topic_tag     = 'wet_topic_tag';
		$this->wet_topic_content = 'wet_topic_content';
    }

	public function isTopic($content)
	{//验证话题,存在返回结果
		$topicTag  = preg_match_all("/#[x80-xff\u4e00-\u9fa5\w ,，.。!！-]{1,25}#/u", $content, $keywords);
		return $topicTag ? $keywords[0] : false;
	}

	public function getTopicInfo($keyword)
	{//获取话题信息
		$isTopic = $this->isTopic($keyword);
		if ($isTopic) {
			$selectTag = "SELECT keywords, img_icon, describe, sender_id, state, utctime, topic_sum 
							FROM $this->wet_topic_tag WHERE keywords ilike '%$keyword%' AND state = '1' LIMIT 1";
			$getTagRow = $this->db->query($selectTag)-> getRow();
			$data['code'] = 200;
			$data['data']  = [
								'total'	=> (int)$getTagRow-> topic_sum,  //总话题量
								'keyword'	=> $getTagRow-> keywords,  //话题关键词
								'imgIcon'	=> $getTagRow-> img_icon,  //话题图标
								'describe'	=> $getTagRow-> describe,  //简介
								'sender_id'	=> $getTagRow-> sender_id,  //创建人
								'nickname'	=> $this->UserModel-> getName($getTagRow->sender_id),  //创建昵称
								'state'		=> (int)$getTagRow-> state,  //状态
								'utctime'	=> (int)$getTagRow-> utctime,  //时间
							];
			$data['msg']  = 'success';
		} else {
			$data['code'] = 406;
			$data['msg']  = 'error';
		}
		return $data;
	}

	public function getTopicList($page, $size, $keyword)
	{//获取话题列表
		$page = max(1, (int)$page);
		$size = max(1, (int)$size);
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if ($isAkToken) $opt['userLogin'] = $akToken;
		$opt['substr']	  = 160; //限制输出

		$isTopic = $this->isTopic($keyword);
		if ($isTopic) {
			$selectTag = "SELECT topic_sum 
							FROM $this->wet_topic_tag 
							WHERE keywords ilike '%$keyword%' 
							AND state = '1' LIMIT 1";
			$getTagRow = $this->db->query($selectTag)-> getRow();
			$data['code'] = 200;
			$data['data']  = [
								'page'		=> $page,  //当前页
								'size'		=> $size,  //每页数量
								'totalPage'	=> (int)ceil($getTagRow-> topic_sum/$size),  //总页数
								'totalSize'	=> (int)$getTagRow-> topic_sum,  //总数量
							];
			if ($getTagRow) {
				$limitSql = "SELECT wet_topic_content.hash 
								FROM wet_topic_content 
								INNER JOIN wet_topic_tag 
								ON wet_topic_content.tag_uid = wet_topic_tag.uid 
								AND wet_topic_content.state = '1' 
								AND wet_topic_tag.keywords ilike '%$keyword%'
								ORDER BY wet_topic_content.utctime DESC 
								LIMIT $size OFFSET ".($page-1) * $size;
				

				$query = $this->db-> query($limitSql);
				foreach ($query-> getResult() as $row) {
					$hash  = $row -> hash;
					$txBloom = $this->BloomModel-> txBloom($hash);
					if (!$txBloom) {
						$detaila[] = $this->content-> txContent($hash, $opt);
					}
					$data['data']['data'] = $detaila;
				}
				$data['msg'] = 'success';
			} else {
				$data['data'] = [];
				$data['msg'] = 'success';
			}

		} else {
			$data['code'] = 406;
			$data['msg']  = 'error';
		}
		return $data;
	}

	public function insertTopic($topic=[])
	{/*话题入库
		topic = [
			hash	  => hash
			content   => 内容
			keywords  => 话题关键词
			sender_id => 发送人
			utctime   => 时间戳
		]
	*/
		$keywords = $this->isTopic($topic['content']);
		if ($keywords) {
			$count = count($keywords) <= 5 ? count($keywords) : 5;
			for($i=0; $i<$count; $i++) {
				$selectTag = "SELECT uid FROM $this->wet_topic_tag WHERE keywords ilike '%$keywords[$i]%' LIMIT 1";
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
				$updateSql = "UPDATE $this->wet_topic_tag SET topic_sum = topic_sum + 1 WHERE keywords ilike '%$keywords[$i]%'";
				$this->db->query($updateSql);
			}
		}
	}
}

