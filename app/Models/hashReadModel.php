<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\ConfigModel;
use App\Models\DisposeModel;
use App\Models\UserModel;
use App\Models\BloomModel;
use App\Models\TopicModel;
use App\Models\ValidModel;
use App\Models\MsgModel;

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
		$this->MsgModel	 	 = new MsgModel();
		$this->wet_temp 	 = "wet_temp";
		$this->wet_behavior  = "wet_behavior";
		$this->wet_content 	 = "wet_content";
		$this->wet_comment   = "wet_comment";
		$this->wet_reply	 = "wet_reply";
		$this->wet_users	 = "wet_users";
		$this->wet_topic_tag = "wet_topic_tag";
		$this->wet_topic_content = "wet_topic_content";
    }

	public function split($hash)
	{//上链内容入库
		$tp_type   = "common";
		$isTempHash = $this->ValidModel-> isTempHash($hash);
		if (!$isTempHash) {  //写入临时缓存
			$insertTempSql = "INSERT INTO $this->wet_temp(tp_hash, tp_type) VALUES ('$hash', '$tp_type')";
			$this->db->query($insertTempSql);
			echo $this->DisposeModel-> wetJsonRt(200);
		} else {
			log_message('error_repeat_'.$hash, 4);
			echo $this->DisposeModel-> wetJsonRt(406,'error_repeat');
		}

		//fastcgi_finish_request(); //冲刷增速
		
		$delTempSql = "DELETE FROM $this->wet_temp WHERE tp_time <= now()-interval '1 D' AND tp_type = '$tp_type'";
		$this->db->query($delTempSql);

		$tpSql   = "SELECT tp_hash FROM $this->wet_temp WHERE tp_type = '$tp_type' ORDER BY tp_time DESC LIMIT 30";
		$tpquery = $this->db-> query($tpSql);
		$result  = $tpquery-> getResult();
		foreach ($result as $row) {
			$tp_hash  = $row-> tp_hash;
			$json 	  = $this->getTxDetails($tp_hash);
			$bloomAddress = $this->BloomModel ->addressBloom( $json['tx']['sender_id'] );

			if ( !$json || $bloomAddress) {
				$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
				$appendText = "bloomAddress:$tp_hash\r\n";
				fwrite($textFile, $appendText);
				fclose($textFile);
				continue;
			}

			if ( empty(  //过滤无效预设钱包
					$json['tx']['recipient_id'] == $bsConfig['receivingAccount'] || 
					$json['tx']['type'] == 'SpendTx' || 
					$json['tx']['payload'] == null || 
					$json['tx']['payload'] == "ba_Xfbg4g=="
				) ){
					$this->deleteTemp($data['hash']);  //删除临时缓存
					$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
					$appendText = "错误类型:$hash\r\n";
					fwrite($textFile, $appendText);
					fclose($textFile);
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
			$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
			$appendText = "error_block_hash:$microBlock\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
			return $this->DisposeModel-> wetJsonRt(406,'error_block_hash');
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
				$this->deleteTemp($hash);
				$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
				$appendText = "非WeTrue格式:{$hash},版本号：{$WeTrue}\r\n";
				fwrite($textFile, $appendText);
				fclose($textFile);
				return $this->DisposeModel-> wetJsonRt(406,'error_WeTrue');
			}

			$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
			$appendText = "版本号异常:{$hash},版本号：{$WeTrue}\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
			return $this->DisposeModel-> wetJsonRt(406,'error_version');
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
		if ($data['type'] == 'sex')      $userAmount = $ftConfig['sexAmount'];

		if ($data['amount'] < $userAmount) {
			$this->deleteTemp($hash);
			$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
			$appendText = "费用异常:{$data['hash']}\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
			return $this->DisposeModel-> wetJsonRt(406,'error_amount');
		}
		try{
			//内容分配
			if( $data['type'] == 'topic' )
			{//主贴
				$isContentHash = $this->ValidModel-> isContentHash($data['hash']);
				if ($isContentHash) {
					$this->deleteTemp($hash);
					$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
					$appendText = "重复主贴Hash:{$data['hash']}\r\n";
					fwrite($textFile, $appendText);
					fclose($textFile);
					return $this->DisposeModel-> wetJsonRt(406,'error');
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
				$this->db->table($this->wet_content)->insert($insertData);
				$upSql       = "UPDATE $this->wet_users SET topic_sum = topic_sum + 1 WHERE address = '$data[sender]'";
				$this->db->query($upSql);
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
				$isCommentHash = $this->ValidModel-> isCommentHash($data['hash']);
				if ($isCommentHash) {
					$this->deleteTemp($hash);
					$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
					$appendText = "重复评论hash:{$data['hash']}\r\n";
					fwrite($textFile, $appendText);
					fclose($textFile);
					return $this->DisposeModel-> wetJsonRt(406,'error');
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
				$this->db->table($this->wet_comment)->insert($insertData);
				$upSql  	 = "UPDATE $this->wet_content SET comment_sum = comment_sum + 1 WHERE hash = '$data[toHash]'";
				$this->db->query($upSql);
				$active 	 = $bsConfig['commentActive'];

				//写入消息
				$msgOpt = [ 'type'=>$data['type'] ];
				$toHashID = $this->MsgModel-> toHashSendID($data['toHash'], $msgOpt);  //获取被评论ID
				if($toHashID) {
					$msgData = [
						'hash' 		   => $data['hash'],
						'to_hash' 	   => $data['toHash'],
						'type'	   	   => $data['type'],
						'sender_id'	   => $data['sender'],
						'recipient_id' => $toHashID,
						'utctime' 	   => $data['mbTime']
					];
					$this->MsgModel-> addMsg($msgData);
				}
			}

			elseif ( $data['type'] == 'reply' )
			{//回复
				$isReplyHash = $this->ValidModel-> isReplyHash($data['hash']);
				if ($isReplyHash) {
					$this->deleteTemp($hash);
					$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
					$appendText = "重复回复hash:{$data['hash']}\r\n";
					fwrite($textFile, $appendText);
					fclose($textFile);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}

				$data['replyType'] = trim($payload['reply_type']);
				$data['toHash']    = trim($payload['to_hash']);
				$data['toAddress'] = trim($payload['to_address']);
				$data['replyHash'] = trim($payload['reply_hash']);

				if ($data['replyType'] != "comment" && $data['replyType'] != "reply") {
					$this->deleteTemp($hash);
					$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
					$appendText = "无效格式{$data['replyType']}-回复hash：{$data['hash']}\r\n";
					fwrite($textFile, $appendText);
					fclose($textFile);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}

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
				$this->db->table($this->wet_reply)->insert($insertData);
				$upSql = "UPDATE $this->wet_comment SET comment_sum = comment_sum + 1 WHERE hash = '$data[toHash]'";
				$this->db->query($upSql);
				$active = $bsConfig['replyActive'];

				//写入消息
				$msgOpt = [ 'type'=>$data['type'] ];
				$toHashID = $this->MsgModel-> toHashSendID($data['toHash'], $msgOpt);  //获取被评论ID
				if($toHashID) {
					$msgData = [
						'hash' 		   => $data['hash'],
						'to_hash' 	   => $data['toHash'],
						'type'	   	   => $data['type'],
						'sender_id'	   => $data['sender'],
						'recipient_id' => $toHashID,
						'utctime' 	   => $data['mbTime']
					];
					$this->MsgModel-> addMsg($msgData);
				}
				//@回复写入消息
				if($data['replyType'] == 'reply' && $data['toAddress']) {
					$msgData = [
						'hash' 		   => $data['hash'],
						'to_hash' 	   => $data['toHash'],
						'type'	   	   => $data['type'],
						'sender_id'	   => $data['sender'],
						'recipient_id' => $data['toAddress'],
						'utctime' 	   => $data['mbTime']
					];
					$this->MsgModel-> addMsg($msgData);
				}
			}

			elseif ( $data['type'] == 'nickname' )
			{//昵称
				$data['content'] = trim($payload['content']);
				$data['content'] = mb_substr($data['content'], 0, 15);
				$verify     = $this->ValidModel-> isUser($data['sender']);
				$isNickname = $this->ValidModel-> isNickname($data['content']);
				if ($isNickname) {
					$this->deleteTemp($hash);
					$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
					$appendText = "重复昵称hash:{$data['hash']}\r\n";
					fwrite($textFile, $appendText);
					fclose($textFile);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}

				if($verify){  //用户是否存在
					$upData = [ 'nickname' => $data['content'] ];
					$this->db->table($this->wet_users)->where('address', $data['sender'])->update($upData);
				}else{
					$insertData = [
						'address'  => $data['sender'],
						'nickname' => $data['content']
					];
					$this->db->table($this->wet_users)->insert($insertData);
				}
				$active = $bsConfig['nicknameActive'];
			}

			elseif ( $data['type'] == 'sex' )
			{//性别
				$data['content'] = (int)trim($payload['content']);
				if (!is_numeric($data['content']) || $data['content'] >= 3){
					$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
					$appendText = "性别hash_err_sex:{$data['hash']}\r\n";
					fwrite($textFile, $appendText);
					fclose($textFile);
					$this->deleteTemp($hash);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}

				$verify = $this->ValidModel-> isUser($data['sender']);
				if (!$verify) $this->UserModel-> userPut($data['sender']);
				$upData = [ 'sex' => $data['content'] ];
				$this->db->table($this->wet_users)->where('address', $data['sender'])->update($upData);
				$active = $bsConfig['sexActive'];
			}

			elseif ( $data['type'] == 'portrait' )
			{//头像
				$data['content'] = trim($payload['content']);
				$selectHash = "SELECT hash FROM $this->wet_users WHERE maxportrait = '$data[hash]' LIMIT 1";
				$getRow		= $this->db->query($selectHash)-> getRow();
				if ($getRow) {
					$this->deleteTemp($hash);
					$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
					$appendText = "重复头像hash:{$data['hash']}\r\n";
					fwrite($textFile, $appendText);
					fclose($textFile);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}

				$verify = $this->ValidModel-> isUser($data['sender']);
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
					$this->db->table($this->wet_users)->insert($insertData);
				}
				$active = $bsConfig['portraitActive'];
			}

			elseif ( $data['type'] == 'drift' )
			{//漂流瓶
				$selectHash = "SELECT hash FROM $this->wet_reply WHERE hash = '$data[hash]' LIMIT 1";
				$getRow		= $this->db->query($selectHash)-> getRow();
				if ($getRow) {
					$this->deleteTemp($hash);
					log_message('Repeat_Drift_Hash:'.$data['hash'], 4);
					return $this->DisposeModel-> wetJsonRt(406,'error');
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
				$this->db->table($this->wet_reply)->insert($insertData);
				$upSql  = "UPDATE $this->wet_comment SET comment_sum = comment_sum + 1 WHERE hash = '$data[toHash]'";
				$this->db->query($upSql);
				$active = $bsConfig['replyActive'];
			} else {
				$this->deleteTemp($hash);
				$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
				$appendText = "data[type]标签错误:{$hash}\r\n";
				fwrite($textFile, $appendText);
				fclose($textFile);
				return $this->DisposeModel-> wetJsonRt(406,'error');
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
			$this->UserModel-> userActive($data['sender'], $active, $e = true);
			$this->deleteTemp($hash);
			return json_encode($this->DisposeModel-> wetRt(200,'success'));
		} catch (Exception $err) {
			$this->deleteTemp($hash);
			$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
			$appendText = "未知错误:{$err}\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
			return $this->DisposeModel-> wetJsonRt(406,'error');
		}
    }

	public function getSenderId($hash)
	{//获取tx 发送人
        $json = $this->getTxDetails($hash);
		if (empty($json)) {
			$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
			$appendText = "查不到发送人:{$hash}\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
        	return "empty";
        }
		return $json['tx']['sender_id'];
	}

	public function getMicroBlockTime($microBlock)
	{//微块时间
        $bsConfig = $this->ConfigModel-> backendConfig();
        $url	  = $bsConfig['backendServiceNode'].'v3/micro-blocks/hash/'.$microBlock.'/header';
        @$get	  = file_get_contents($url);
		$num = 0;
		while ( !$get && $num < 20 ) {
			@$get = file_get_contents($url);
			$num++;
			$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
			$appendText = "读取micro_blocks失败:{$url}\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
			sleep(6);
		}

		if (empty($get)) {
			$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
			$appendText = "读取微块时间失败:{$url}\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
        	return "Get MicroBlock Time Error";
        }

        $json = (array) json_decode($get, true);
		$utcTime = $json['time'];
		return $utcTime;
	}

	public function getTxDetails($hash)
	{//获取tx 详情
		$bsConfig  = $this->ConfigModel-> backendConfig();
		$url 	   = $bsConfig['backendServiceNode'].'v3/transactions/'.$hash;
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
			$textFile   = fopen("log/hash_read/".date("Y-m-d").".txt", "a");
			$appendText = "节点读取错误:{$hash}\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
        	return "Node Data Error";
        }

        $json = (array) json_decode($get, true);
		return $json;
	}

	private function deleteTemp($hash)
	{//删除临时缓存
		$delete = "DELETE FROM $this->wet_temp WHERE tp_hash = '$hash'";
		$this->db->query($delete);
	}

}

