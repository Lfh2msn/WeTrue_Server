<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\ConfigModel;
use App\Models\DisposeModel;
use App\Models\UserModel;
use App\Models\BloomModel;
use App\Models\TopicModel;
use App\Models\ValidModel;

class HashReadModel extends Model {
//链上hash入库Model

	public function __construct(){
		$this->db = \Config\Database::connect('default');
		$this->ConfigModel   = new ConfigModel();
		$this->DisposeModel  = new DisposeModel();
		$this->UserModel	 = new UserModel();
		$this->BloomModel	 = new BloomModel();
		$this->TopicModel	 = new TopicModel();
		$this->ValidModel	 = new ValidModel();
		$this->wet_topic_content = 'wet_topic_content';
		$this->wet_topic_tag = 'wet_topic_tag';
		$this->wet_temporary = 'wet_temporary';
		$this->wet_behavior  = 'wet_behavior';
		$this->wet_content 	 = 'wet_content';
		$this->wet_comment   = 'wet_comment';
		$this->wet_reply	 = 'wet_reply';
		$this->wet_users	 = 'wet_users';
    }

	public function split($hash)
	{//上链内容入库
		$isHashSql = "SELECT tp_hash FROM $this->wet_temporary WHERE tp_hash = '$hash' LIMIT 1";
		$query     = $this->db-> query($isHashSql)-> getRow();
		if (!$query) {  //写入临时缓存
			$insertTempSql = "INSERT INTO $this->wet_temporary(tp_hash) VALUES ('$hash')";
			$this->db->query($insertTempSql);
			$data['code'] = 200;
			$data['msg']  = 'success';
			echo json_encode($data);
		} else {
			$data['code'] = 406;
			$data['msg']  = 'repeat';
			log_message('error_repeat_'.$hash, 4);
			echo json_encode($data);
		}

		//fastcgi_finish_request(); //冲刷增速
		
		$delTempSql = "DELETE FROM $this->wet_temporary WHERE tp_time <= now()-interval '1 D'";
		$this->db->query($delTempSql);

		$hashSql = "SELECT tp_hash FROM $this->wet_temporary ORDER BY tp_time DESC";
		$query  = $this->db-> query($hashSql);
		foreach ($query-> getResult() as $row) {
			$tp_hash  = $row-> tp_hash;
			$json 	  = $this->getTxDetails($tp_hash);
			$bloomAddress = $this->BloomModel ->addressBloom( $json['tx']['sender_id'] );

			if ( !$json || $bloomAddress) {
				log_message('bloomAddress'.$tp_hash, 4);
				continue;
			}

			if ( empty(  //过滤无效预设钱包
					$json['tx']['recipient_id'] == $bsConfig['receivingAccount'] || 
					$json['tx']['type'] == 'SpendTx' || 
					$json['tx']['payload'] == null || 
					$json['tx']['payload'] == "ba_Xfbg4g=="
				) ){
					$this->deleteTemp($data['hash']);  //删除临时缓存
					log_message('错误类型'.$data['hash'], 4);
					continue;
			}
			$this->decodeContent($json);
		}
	}

	public function decodeContent($json)
	{//重构及内容分配
		$bsConfig 	= $this->ConfigModel-> backendConfig();
        $microBlock = $json['block_hash'];
		if(!$microBlock){
			log_message('block_hash'.$microBlock, 4);
			$data['code'] = 406;
			$data['msg']  = 'error_block_hash';
			return json_encode($data);
		}
		$utcTime = $this->getMicroBlockTime($microBlock);
		$json['mb_time'] = $utcTime;
		$payload = $this->DisposeModel-> decodePayload($json['tx']['payload']);
		$hash	 = $json['hash'];
		$WeTrue  = $payload['WeTrue'];
		$require = $bsConfig['requireVersion'];
		$version = $this->DisposeModel-> versionCompare($WeTrue, $require);  //版本检测
		if (!$version)
		{  //版本号错误或低
			if(!$WeTrue){ //非WeTrue
				$this->deleteTemp($data['hash']);
				log_message('非WeTrue格式-'.$hash, 4);
				$data['code'] = 406;
				$data['msg']  = 'error_WeTrue';
				return json_encode($data);
			}

			$updateSql  = "UPDATE $this->wet_temporary SET tp_source = 'versionError' WHERE tp_hash = '$hash'";
	        $this->db-> query($updateSql);
			log_message('版本号异常-'.$hash, 4);
			$data['code'] = 406;
			$data['msg']  = 'error_version';
			return json_encode($data);
		}

		$data['WeTrue']  = $WeTrue;
		$isSource		 = $payload['source'];// ?? 'WeTrue';
		$sourceDelXSS 	 = $this->DisposeModel-> delete_xss($isSource);
		$sourceSubstr	 = substr($sourceDelXSS, 0, 10);
		$data['source']  = $sourceSubstr;
		$data['type']    = $payload['type'];
		$data['hash']    = $hash;
		$data['receipt'] = $json['tx']['recipient_id'];
		$data['sender']  = $json['tx']['sender_id'];
		$data['amount']  = $json['tx']['amount'];
		$data['mbTime']  = $json['mb_time'];
		$data['content'] = $payload['content'];

		//用户费用检测
		$ftConfig = $this->ConfigModel-> frontConfig($data['sender']);
		if ($data['type'] == 'topic')    $userAmount = $ftConfig['topicAmount'];
		if ($data['type'] == 'comment')  $userAmount = $ftConfig['commentAmount'];
		if ($data['type'] == 'reply')    $userAmount = $ftConfig['replyAmount'];
		if ($data['type'] == 'nickname') $userAmount = $ftConfig['nicknameAmount'];
		if ($data['type'] == 'portrait') $userAmount = $ftConfig['portraitAmount'];

		if ($data['amount'] < $userAmount) {
			$this->deleteTemp($data['hash']);
			log_message('费用异常-'.$data['hash'], 4);
			$data['code'] = 406;
			$data['msg']  = 'error_amount';
			return json_encode($data);
		}
		try{
			//内容分配
			if( $data['type'] == 'topic' )
			{//主贴
				$isContentHash = $this->ValidModel-> isContentHash($data['hash']);
				if ($isContentHash) {
					$this->deleteTemp($data['hash']);
					log_message('Repeat_Content_Hash:'.$data['hash'], 4);
					$data['code'] = 406;
					$data['msg']  = 'error';
					return json_encode($data);
				}

				$data['imgList'] = trim($payload['img_list']);
				$insertData = [
								'hash'		   => $data['hash'],
								'sender_id'	   => $data['sender'],
								'recipient_id' => $data['receipt'],
								'utctime'	   => $data['mbTime'],
								'amount'	   => $data['amount'],
								'type' 		   => $data['type'],
								'payload' 	   => $data['content'],
								'img_tx' 	   => $data['imgList'],
								'source' 	   => $data['source']
							];
				$insertTable = $this->wet_content;
				$this->db->table($insertTable)->insert($insertData);
				$upSql       = "UPDATE $this->wet_users SET topic_sum = topic_sum + 1 WHERE address = '$data[sender]'";
				$active      = $bsConfig['topicActive'];

				$isTopic = $this->TopicModel-> isTopic($data['content']);
				if($isTopic) {
					$topic = [
						'hash'		=> $data['hash'],
						'content'   => $data['content'],
						'sender_id' => $data['sender'],
						'utctime'   => $data['mbTime']
						];
					$isTopic = $this->TopicModel-> insertTopic($topic);
				}
			}

			elseif ( $data['type'] == 'comment' )
			{//评论
				$isContentHash = $this->ValidModel-> isCommentHash($data['hash']);
				if ($isCommentHash) {
					$this->deleteTemp($data['hash']);
					log_message('重复评论hash:'.$data['hash'], 4);
					$data['code'] = 406;
					$data['msg']  = 'error';
					return json_encode($data);
				}

				$data['toHash'] = trim($payload['toHash']);
				$insertData = [
					'hash'		   => $data['hash'],
					'to_hash'	   => $data['toHash'],
					'sender_id'	   => $data['sender'],
					'recipient_id' => $data['receipt'],
					'utctime'	   => $data['mbTime'],
					'amount'	   => $data['amount'],
					'type' 		   => $data['type'],
					'payload' 	   => $data['content']
				];
				$insertTable = $this->wet_comment;
				$this->db->table($insertTable)->insert($insertData);
				$upSql  	 = "UPDATE $this->wet_content SET comment_sum = comment_sum + 1 WHERE hash = '$data[toHash]'";
				$active 	 = $bsConfig['commentActive'];
			}

			elseif ( $data['type'] == 'reply' )
			{//回复
				$isReplyHash = $this->ValidModel-> isReplyHash($data['hash']);
				if ($isReplyHash) {
					$this->deleteTemp($data['hash']);
					log_message('Repeat_Reply_Hash:'.$data['hash'], 4);
					$data['code'] = 406;
					$data['msg']  = 'error';
					return json_encode($data);
				}

				$data['replyType'] = trim($payload['reply_type']);
				$data['toHash']    = trim($payload['to_hash']);
				$data['toAddress'] = trim($payload['to_address']);
				$data['replyHash'] = trim($payload['reply_hash']);
				$insertData = [
					'hash'		   => $data['hash'],
					'to_hash'	   => $data['toHash'],
					'reply_hash'   => $data['replyHash'],
					'reply_type'   => $data['replyType'],
					'to_address'   => $data['toAddress'],
					'sender_id'	   => $data['sender'],
					'recipient_id' => $data['receipt'],
					'utctime'	   => $data['mbTime'],
					'amount'	   => $data['amount'],
					'payload' 	   => $data['content']
				];
				$insertTable = $this->wet_reply;
				$this->db->table($insertTable)->insert($insertData);
				$upSql  	 = "UPDATE $this->wet_comment SET comment_sum = comment_sum + 1 WHERE hash = '$data[toHash]'";
				$active 	 = $bsConfig['replyActive'];
			}

			elseif ( $data['type'] == 'nickname' )
			{//昵称
				$data['content'] = trim($payload['content']);
				$verify = $this->UserModel-> isUser($data['sender']);
				$isNickname = $this->UserModel-> isNickname($data['content']);
				if ($isNickname) {
					$this->deleteTemp($data['hash']);
					log_message('Repeat_Nickname:'.$data['hash'], 4);
					$data['code'] = 406;
					$data['msg']  = 'error';
					return json_encode($data);
				}

				if($verify){  //用户是否存在
					$upData = [ 'nickname' => $data['content'] ];
					$this->db->table($this->wet_users)->where('address', $data['sender'])->update($upData);
				}else{
					$insertData = [
						'address'  => $data['sender'],
						'nickname' => $data['content']
					];
					$insertTable = $this->wet_users;
					$this->db->table($insertTable)->insert($insertData);
				}
				$active = $bsConfig['nicknameActive'];
			}

			elseif ( $data['type'] == 'sex' )
			{//性别
				$data['content'] = (int) trim($payload['content']);
				if (!is_numeric($data['content']) || $data['content'] >= 3){
					$this->deleteTemp($data['hash']);
					log_message('no_sex:'.$data['hash'], 4);
					$data['code'] = 406;
					$data['msg']  = 'error';
					return json_encode($data);
				}

				$verify = $this->UserModel-> isUser($data['sender']);
				if ($verify) {
					$upData = [ 'sex' => $data['content'] ];
					$this->db->table($this->wet_users)->where('address', $data['sender'])->update($upData);
				} else {
					$insertData = [
						'address' => $data['sender'],
						'sex'     => $data['content']
					];
					$insertTable = $this->wet_users;
					$this->db->table($insertTable)->insert($insertData);
				}
				$active = $bsConfig['sexActive'];
			}

			elseif ( $data['type'] == 'portrait' )
			{//头像
				$data['content'] = trim($payload['content']);
				$selectHash = "SELECT hash FROM $this->wet_users WHERE maxportrait = '$data[hash]' LIMIT 1";
				$getRow		= $this->db->query($selectHash)-> getRow();
				if ($getRow) {
					$this->deleteTemp($data['hash']);
					log_message('Repeat_Portrait_Hash:'.$data['hash'], 4);
					$data['code'] = 406;
					$data['msg']  = 'error';
					return json_encode($data);
				}

				$verify = $this->UserModel-> isUser($data['sender']);
				if ($verify) {
					$upData = [
								'portrait'    => $data['content'],
								'maxportrait' => $data['hash']
							];
					$this->db->table($this->wet_users)->where('address', $data['sender'])->update($upData);
				} else {
					$insertData = [
						'address'     => $data['sender'],
						'portrait'    => $data['content'],
						'maxportrait' => $data['hash']
					];
					$insertTable = $this->wet_users;
					$this->db->table($insertTable)->insert($insertData);
				}
				$active = $bsConfig['portraitActive'];
			}

			elseif ( $data['type'] == 'drift' )
			{//漂流瓶
				$selectHash = "SELECT hash FROM $this->wet_reply WHERE hash = '$data[hash]' LIMIT 1";
				$getRow		= $this->db->query($selectHash)-> getRow();
				if ($getRow) {
					$this->deleteTemp($data['hash']);
					log_message('Repeat_Drift_Hash:'.$data['hash'], 4);
					$data['code'] = 406;
					$data['msg']  = 'error';
					return json_encode($data);
				}

				$data['replyType'] = trim($payload['reply_type']);
				$data['toHash']    = trim($payload['to_hash']);
				$data['toAddress'] = trim($payload['to_address']);
				$data['replyHash'] = trim($payload['reply_hash']);
				$insertData = [
					'hash'		   => $data['hash'],
					'to_hash'	   => $data['toHash'],
					'reply_hash'   => $data['replyHash'],
					'reply_type'   => $data['replyType'],
					'to_address'   => $data['toAddress'],
					'sender_id'	   => $data['sender'],
					'recipient_id' => $data['receipt'],
					'utctime'	   => $data['mbTime'],
					'amount'	   => $data['amount'],
					'payload' 	   => $data['content']
				];
				$insertTable = $this->wet_reply;
				$this->db->table($insertTable)->insert($insertData);
				$upSql  = "UPDATE $this->wet_comment SET comment_sum = comment_sum + 1 WHERE hash = '$data[toHash]'";
				$active = $bsConfig['replyActive'];
			} else {
				$this->deleteTemp($data['hash']);
				log_message("data[type]标签错误".$hash, 4);
				$data['code'] = 406;
				$data['msg']  = 'error';
				return json_encode($data);
			}

			//入库行为记录
			$insetrBehaviorDate = [
				'address'   => $data['sender'],
				'hash'      => $data['hash'],
				'thing'     => $data['type'],
				'influence' => $active,
				'toaddress' => $data['receipt']
			];
			$this->db->table($this->wet_behavior)->insert($insetrBehaviorDate);
			$this->UserModel-> userActive($data['sender'], $active, $e = TRUE);
			$this->db->query($upSql);
			$this->deleteTemp($data['hash']);
			$data['code'] = 200;
			$data['msg']  = 'success';
			return json_encode($data);
		} catch (Exception $err) {
			$this->deleteTemp($data['hash']);
			log_message('error:'.$err, 4);
			$data['code'] = 406;
			$data['msg']  = 'error';
			return json_encode($data);
		}
    }

	public function getSenderId($hash)
	{//获取tx 发送人
        $json = $this->getTxDetails($hash);
		if (empty($json)) {
			log_message('查不到发送人:'.$hash, 4);
        	return "empty";
        }
		return $json['tx']['sender_id'];
	}

	public function getMicroBlockTime($microBlock)
	{//微块时间
        $bsConfig = $this->ConfigModel-> backendConfig();
        $url	  = $bsConfig['backendServiceNode'].'v2/micro-blocks/hash/'.$microBlock.'/header';
        @$get	  = file_get_contents($url);
		$num = 0;
		while ( !$get && $num < 20 ) {
			@$get = file_get_contents($url);
			$num++;
			log_message('读取micro_blocks失败:'.$url, 4);
			sleep(6);
		}

		if (empty($get)) {
			log_message('读取微块时间失败:'.$url, 4);
        	return "Get MicroBlock Time Error";
        }

        $json = (array) json_decode($get, true);
		$utcTime = $json['time'];
		return $utcTime;
	}

	public function getTxDetails($hash)
	{//获取tx 详情
		$bsConfig  = $this->ConfigModel-> backendConfig();
		$url 	   = $bsConfig['backendServiceNode'].'v2/transactions/'.$hash;
		@$get	   = file_get_contents($url);
		$json	   = (array) json_decode($get, true);
		$blockHash = substr($json['block_hash'], 0, 3);
		$number	   = 0;
		while ( !$get && $number < 20 || $blockHash != "mh_") {
			@$get	   = file_get_contents($url);
			$json	   = (array) json_decode($get, true);
			$blockHash = substr($json['block_hash'], 0, 3);
			$number++;
			sleep(6);
		}

        if (!$get || $blockHash != "mh_") {
			log_message('节点读取错误:'.$hash, 4);
        	return "Node Data Error";
        }

        $json = (array) json_decode($get, true);
		return $json;
	}

	public function deleteTemp($hash)
	{//删除临时缓存
		$delete = "DELETE FROM wet_temporary WHERE tp_hash = '$hash'";
		$this->db->query($delete);
	}

}

