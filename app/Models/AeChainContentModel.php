<?php namespace App\Models;

use CodeIgniter\Model;

use App\Models\{
	MsgModel,
	UserModel,
	StarModel,
	TopicModel,
	ValidModel,
	FocusModel,
	ConfigModel,
	DisposeModel,
	MentionsModel
};
use App\Models\ServerMdw\WetModel;
use App\Models\Get\GetAeChainModel;

class AeChainContentModel extends Model {
//Ae链上hash入库Model
	private $MsgModel;
	private $GetAeChainModel;
	private $UserModel;
	private $StarModel;
	private $TopicModel;
	private $ValidModel;
	private $FocusModel;
	private $ConfigModel;
	private $WetModel;
	private $DisposeModel;
	private $MentionsModel;
	private $wet_content;
	private $wet_comment;
	private $wet_reply;
	private $wet_users;

	public function __construct(){
		$this->db = \Config\Database::connect('default');
		$this->WetModel   = new WetModel();
		$this->MsgModel   = new MsgModel();
		$this->GetAeChainModel = new GetAeChainModel();
		$this->UserModel  = new UserModel();
		$this->StarModel  = new StarModel();
		$this->TopicModel = new TopicModel();
		$this->ValidModel = new ValidModel();
		$this->FocusModel = new FocusModel();
		$this->ConfigModel   = new ConfigModel();
		$this->DisposeModel  = new DisposeModel();
		$this->MentionsModel = new MentionsModel();
		$this->wet_temp 	 = "wet_temp";
		$this->wet_behavior  = "wet_behavior";
		$this->wet_content 	 = "wet_content";
		$this->wet_comment   = "wet_comment";
		$this->wet_reply	 = "wet_reply";
		$this->wet_users	 = "wet_users";
		$this->wet_topic_tag = "wet_topic_tag";
		$this->wet_content_sh = "wet_content_sh";
		$this->wet_topic_content = "wet_topic_content";
    }

	public function decodeContent($json)
	{//重构及内容分配
		$bsConfig 	= $this->ConfigModel-> backendConfig();
        $microBlock = $json['block_hash'];
		if(!$microBlock){
			$logMsg = "错误区块hash:$microBlock\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return $this->DisposeModel-> wetJsonRt(406,'error_block_hash');
		}
		$utcTime = $this->GetAeChainModel->microBlockTime($microBlock);
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
				$logMsg = "非WeTrue格式:{$hash},版本号：{$WeTrue}\r\n";
				$this->DisposeModel->wetFwriteLog($logMsg);
				return $this->DisposeModel-> wetJsonRt(406,'error_WeTrue');
			}
			$logMsg = "版本号异常:{$hash},版本号：{$WeTrue}\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return $this->DisposeModel-> wetJsonRt(406,'error_version');
		}

		$data['WeTrue']  = $WeTrue;
		$isSource		 = $payload['source'];// ?? 'WeTrue';
		$sourceDelXSS 	 = $this->DisposeModel-> delete_xss($isSource);
		$sourceSubstr	 = substr($sourceDelXSS, 0, 12);
		$data['source']  = $sourceSubstr;
		$data['type']    = $this->DisposeModel-> delete_xss($payload['type']);
		$data['hash']    = $hash;
		$data['receipt'] = $json['tx']['recipient_id'];
		$data['sender']  = $json['tx']['sender_id'];
		$data['amount']  = $json['tx']['amount'];
		$data['mbTime']  = $json['mb_time'];
		$data['content'] = $payload['content'];
		$data['chainId'] = 457;

		//用户费用检测
		$ftConfig = $this->ConfigModel-> frontConfig($data['sender']);
		if ($data['type'] == 'topic')    {$userAmount = $ftConfig['topicAmount'];}
		if ($data['type'] == 'comment')  {$userAmount = $ftConfig['commentAmount'];}
		if ($data['type'] == 'reply')    {$userAmount = $ftConfig['replyAmount'];}
		if ($data['type'] == 'nickname') {$userAmount = $ftConfig['nicknameAmount'];}
		if ($data['type'] == 'portrait') {$userAmount = $ftConfig['portraitAmount'];}
		if ($data['type'] == 'sex')      {$userAmount = $ftConfig['sexAmount'];}

		if ($data['amount'] < $userAmount) {
			$this->deleteTemp($hash);
			$logMsg = "费用异常:{$hash}\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return $this->DisposeModel-> wetJsonRt(406,'error_amount');
		}
		try{
			//内容分配
			if( $data['type'] == 'topic' )
			{//主贴
				$isContentHash = $this->ValidModel-> isContentHash($data['hash']);
				if ($isContentHash) {
					$this->deleteTemp($hash);
					$logMsg = "重复主贴Hash:{$hash}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}
				
				$holdAE = $bsConfig['usableHoldAE'];  //要求最低持有AE开关
				if ($holdAE) {
					$holdAettos     = $bsConfig['usableHoldAettos'];  //要求最低持有AE
					$accountsAettos = $this->GetAeChainModel->accountsBalance($data['sender']);  //查询链上金额
					if ($accountsAettos < $holdAettos) {
						$this->deleteTemp($hash);
						$logMsg  = date('Y-m-d')."-持有AE不足最低要求:{$hash}\r\n";
						$logPath = "log/chain_read/holdAeLow-".date('Y-m').".txt";
						$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
						return $this->DisposeModel-> wetJsonRt(406,'hold_aettos_low');
					}
				}
				$data['mediaList'] = json_encode($payload['media']);
				$insertData = [
								'hash'		   => $data['hash'],
								'sender_id'	   => $data['sender'],
								'recipient_id' => $data['receipt'],
								'utctime'	   => $data['mbTime'],
								'amount'	   => $data['amount'],
								'type' 		   => $data['type'],
								'payload' 	   => $data['content'],
								'media_list'   => $data['mediaList'],
								'source' 	   => $data['source'],
								'chain_id'	   => $data['chainId']
							];
				$this->db->table($this->wet_content)->insert($insertData);
				$active = $bsConfig['topicActive'];
				//是否话题
				$isTopic = $this->TopicModel-> isTopic($data['content']);
				if($isTopic) {
					$topic = [
						'hash'		=> $data['hash'],
						'content'   => $data['content'],
						'sender_id' => $data['sender'],
						'utctime'   => $data['mbTime']
						];
					$this->TopicModel-> insertTopic($topic);
				}
				//是否“@”
				$isMentions = $this->MentionsModel-> isMentions($data['content']);
				if($isMentions) {
					$mentions = [
						'type'		=> $data['type'],
						'hash'		=> '',
						'toHash'	=> $data['hash'],
						'content'   => $data['content'],
						'sender_id' => $data['sender'],
						'utctime'   => $data['mbTime']
					];
					$this->MentionsModel-> messageMentions($mentions);
				}
			}

			elseif ( $data['type'] == 'comment' )
			{//评论
				$isCommentHash = $this->ValidModel-> isCommentHash($data['hash']);
				if ($isCommentHash) {
					$this->deleteTemp($hash);
					$logMsg = "重复评论hash:{$data['hash']}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}

				$data['toHash'] = $this->DisposeModel-> delete_xss($payload['toHash']);
				$insertData = [
					'hash'		   => $data['hash'],
					'to_hash'	   => $data['toHash'],
					'sender_id'	   => $data['sender'],
					'recipient_id' => $data['receipt'],
					'utctime'	   => $data['mbTime'],
					'amount'	   => $data['amount'],
					'type' 		   => $data['type'],
					'payload' 	   => $data['content'],
					'chain_id'	   => $data['chainId']
				];
				$this->db->table($this->wet_comment)->insert($insertData);
				//验证是否为Superhero ID
				$isShTipid = $this->DisposeModel-> checkSuperheroTipid($data['toHash']);
				if ($isShTipid) {
					$upSql = "UPDATE $this->wet_content_sh SET comment_sum = comment_sum + 1 WHERE tip_id = '$data[toHash]'";
				} else {
					$upSql = "UPDATE $this->wet_content SET comment_sum = comment_sum + 1 WHERE hash = '$data[toHash]'";
				}
				$this->db->query($upSql);
				$active = $bsConfig['commentActive'];

				//写入消息
				$msgOpt = [ 'type'=>$data['type'] ];
				if ($isShTipid) { //Superhero ID
					$msgOpt = [ 'type'=>'shTipid' ];
				}
	
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
				//是否“@”
				$isMentions = $this->MentionsModel-> isMentions($data['content']);
				if($isMentions) {
					$mentions = [
						'type'		=> 'comment',
						'hash'		=> $data['hash'],
						'toHash'	=> $data['toHash'],
						'content'   => $data['content'],
						'sender_id' => $data['sender'],
						'utctime'   => $data['mbTime']
					];
					$this->MentionsModel-> messageMentions($mentions);
				}
			}

			elseif ( $data['type'] == 'reply' )
			{//回复
				$isReplyHash = $this->ValidModel-> isReplyHash($data['hash']);
				if ($isReplyHash) {
					$this->deleteTemp($hash);
					$logMsg = "重复回复hash:{$data['hash']}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}

				$data['replyType'] = $this->DisposeModel-> delete_xss($payload['reply_type']);
				$data['toHash']    = $this->DisposeModel-> delete_xss($payload['to_hash']);
				$data['toAddress'] = $this->DisposeModel-> delete_xss($payload['to_address']);
				$data['replyHash'] = $this->DisposeModel-> delete_xss($payload['reply_hash']);

				if ($data['replyType'] != "comment" && $data['replyType'] != "reply") {
					$this->deleteTemp($hash);
					$logMsg = "无效格式{$data['replyType']}-回复hash：{$data['hash']}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
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
					'payload' 	   => $data['content'],
					'chain_id'	   => $data['chainId']
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
				if($data['replyType'] == 'reply' && $data['toAddress'] && $data['toAddress'] != $toHashID) {
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
				//是否“@”
				$isMentions = $this->MentionsModel-> isMentions($data['content']);
				if($isMentions) {
					$mentions = [
						'type'		=> $data['type'],
						'hash'		=> $data['hash'],
						'toHash'	=> $data['toHash'],
						'content'   => $data['content'],
						'sender_id' => $data['sender'],
						'utctime'   => $data['mbTime']
					];
					$this->MentionsModel-> messageMentions($mentions);
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
					$logMsg = "重复昵称hash:{$data['hash']}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
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
					$logMsg = "hash_err_sex:{$data['hash']}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
					$this->deleteTemp($hash);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}

				$verify = $this->ValidModel-> isUser($data['sender']);
				if (!$verify) $this->UserModel-> userPut($data['sender']);
				$upData = [ 'sex' => $data['content'] ];
				$this->db->table($this->wet_users)->where('address', $data['sender'])->update($upData);
				$active = $bsConfig['sexActive'];
			}

			elseif ( $data['type'] == 'focus' )
			{//关注
				$data['content'] = $this->DisposeModel-> delete_xss($payload['content']);
				$isUser = $this->ValidModel-> isUser($data['content']);
				if (!$isUser){
					$logMsg = "hash_err_focus:{$data['hash']}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
					$this->deleteTemp($hash);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}
				echo $this->FocusModel-> focus($data['content'], $data['sender']);
				$this->deleteTemp($hash);
				return;
			}

			elseif ( $data['type'] == 'star' )
			{//收藏
				$data['content'] = $this->DisposeModel-> delete_xss($payload['content']);
				$isHash = $this->DisposeModel-> checkAddress($data['content']);
				$isShTipid = $this->DisposeModel-> checkSuperheroTipid($data['content']);
				$isCheck   = $isShTipid ? $isShTipid : $isHash;
				$select = 'contentStar';
				if ($isShTipid) {
					$select = "shTipidStar";
				}
				if (!$isCheck){
					$logMsg = "hash_err_star:{$data['hash']}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
					$this->deleteTemp($hash);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}
				echo $this->StarModel-> star($data['sender'], $data['content'], $select);
				$this->deleteTemp($hash);
				return;
			}

			elseif ( $data['type'] == 'portrait' )
			{//头像
				$data['content'] = trim($payload['content']);
				$selectHash = "SELECT hash FROM $this->wet_users WHERE maxportrait = '$data[hash]' LIMIT 1";
				$getRow		= $this->db->query($selectHash)-> getRow();
				if ($getRow) {
					$this->deleteTemp($hash);
					$logMsg = "重复头像hash:{$data['hash']}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
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

			} else {
				$this->deleteTemp($hash);
				$logMsg = "data[type]标签错误:{$hash}\r\n";
				$this->DisposeModel->wetFwriteLog($logMsg);
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
			
			if( $data['type'] == 'topic' ) { //发布主贴用户发帖量+1
				$upSql = "UPDATE $this->wet_users SET topic_sum = topic_sum + 1 WHERE address = '$data[sender]'";
				$this->db->query($upSql);
			}
			$this->deleteTemp($hash);
			if( $data['type'] == 'topic' ) $this->WetModel ->getNewContentList(); //通知中间件提示新内容
			return json_encode($this->DisposeModel-> wetRt(200,'success'));
		} catch (Exception $err) {
			$this->deleteTemp($hash);
			$logMsg = "未知错误:{$err}\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return $this->DisposeModel-> wetJsonRt(406,'error');
		}
    }

	private function deleteTemp($hash)
	{//删除临时缓存
		$delete = "DELETE FROM $this->wet_temp WHERE tp_hash = '$hash'";
		$this->db->query($delete);
	}

}

