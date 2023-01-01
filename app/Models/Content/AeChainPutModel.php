<?php 
namespace App\Models\Content;

use App\Models\{
	ComModel,
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
use App\Models\Config\{
	AeChainPutConfig,
	ActiveConfig
};

class AeChainPutModel
{//Ae链上hash入库Model

	public function __construct(){
		$this->WetModel   = new WetModel();
		$this->MsgModel   = new MsgModel();
		$this->FocusModel = new FocusModel();
		$this->MentionsModel = new MentionsModel();
		$this->wet_temp 	 = 'wet_temp';
		$this->wet_behavior  = 'wet_behavior';
		$this->wet_content 	 = 'wet_content';
		$this->wet_comment   = 'wet_comment';
		$this->wet_reply	 = 'wet_reply';
		$this->wet_users	 = 'wet_users';
		$this->wet_topic_tag = 'wet_topic_tag';
		$this->wet_content_sh = 'wet_content_sh';
		$this->wet_topic_content = 'wet_topic_content';
    }

	public function decodeContent($json)
	{//重构及内容分配
		try{
			$bsConfig 	= ConfigModel::backendConfig();
			$microBlock = $json['block_hash'];
			if(!$microBlock){
				DisposeModel::wetFwriteLog("错误区块Hash:{$microBlock}");
				return DisposeModel::wetJsonRt(406,'error_block_hash');
			}
			$utcTime = GetAeChainModel::microBlockTime($microBlock);
			$json['mb_time'] = $utcTime;
			$payload = DisposeModel::decodePayload($json['tx']['payload']);
			$hash	 = $json['hash'];
			$WeTrue  = $payload['WeTrue'];
			$require = $bsConfig['requireVersion'];
			$version = DisposeModel::versionCompare($WeTrue, $require);  //版本检测
			if (!$version)
			{  //版本号错误或低
				if(!$WeTrue){ //非WeTrue
					DisposeModel::wetFwriteLog("非WeTrue格式:{$hash},版本号:{$WeTrue}");
					return DisposeModel::wetJsonRt(406,'error_WeTrue');
				}
				DisposeModel::wetFwriteLog("版本号异常:{$hash},版本号:{$WeTrue}");
				$this->deleteTemp($hash);
				return DisposeModel::wetJsonRt(406,'error_version');
			}

			$data['WeTrue']  = $WeTrue;
			$isSource		 = $payload['source'];
			$sourceDelXSS 	 = DisposeModel::delete_xss($isSource);
			$sourceSubstr	 = substr($sourceDelXSS, 0, 15);
			$data['source']  = $sourceSubstr ?? 'Other';
			$data['type']    = DisposeModel::delete_xss($payload['type']);
			$data['hash']    = $hash;
			$data['receipt'] = $json['tx']['recipient_id'];
			$data['sender']  = $json['tx']['sender_id'];
			$data['amount']  = $json['tx']['amount'];
			$data['mb_time'] = $json['mb_time'];
			$data['content'] = $payload['content'];
			$data['chainId']= 457;

			//用户 活跃度及费用 设置检测
			$amountConfig = AeChainPutConfig::amount($data['sender']);
			$activeConfig = ActiveConfig::config();

			if ($data['type'] == 'topic'){ //主贴
				$userAmount = $amountConfig['topic'];
				$getActive  = $activeConfig['topicActive'];
				$repeatHash = ValidModel::isContentHash($data['hash']);
			}
			elseif ($data['type'] == 'comment'){ //评论
				$userAmount = $amountConfig['comment'];
				$getActive  = $activeConfig['commentActive'];
				$repeatHash = ValidModel::isCommentHash($data['hash']);
			}
			elseif ($data['type'] == 'reply'){ //回复
				$userAmount = $amountConfig['reply'];
				$getActive  = $activeConfig['replyActive'];
				$repeatHash = ValidModel::isReplyHash($data['hash']);
			}
			elseif ($data['type'] == 'nickname'){ //昵称
				$userAmount = $amountConfig['nickname'];
				$getActive  = $activeConfig['nicknameActive'];
				$repeatHash = ValidModel::isNickname($data['content']);
			}
			elseif ($data['type'] == 'sex'){ //性别
				$userAmount = $amountConfig['sex'];
				$getActive  = $activeConfig['sexActive'];
			}
			elseif ( $data['type'] == 'star' ){ //收藏帖
				$repeatHash = ValidModel::isStar($data['hash'], $data['sender']);
			}
			elseif ( $data['type'] == 'focus' ){ //关注用户
				$repeatHash = ValidModel::isFocus($data['content'], $data['sender']);
			}else{}

			if ($repeatHash) { //重复检测
				$this->deleteTemp($hash);
				DisposeModel::wetFwriteLog("重复Hash:{$hash}");
				return DisposeModel::wetJsonRt(406,'error');
			}

			if ($data['amount'] < $userAmount) {
				$this->deleteTemp($hash);
				DisposeModel::wetFwriteLog("费用异常:{$hash}");
				return DisposeModel::wetJsonRt(406,'error_amount');
			}

			//内容分配
			if( $data['type'] == 'topic' )
			{//主贴
				$holdAE = $bsConfig['usableHoldAE'];  //要求最低持有AE开关
				if ($holdAE) {
					$holdAettos     = $bsConfig['usableHoldAettos'];  //要求最低持有AE
					$accountsAettos = GetAeChainModel::accountsBalance($data['sender']);  //查询链上金额
					if ($accountsAettos < $holdAettos) {
						$this->deleteTemp($hash);
						$logPath = "log/chain/holdAeLow-".date('Y-m').".txt";
						$logMsg  = "持有AE不足最低要求:{$hash}";
						DisposeModel::wetFwriteLog($logMsg, $logPath);
						return DisposeModel::wetJsonRt(406,'hold_aettos_low');
					}
				}
				$data['mediaList'] = json_encode($payload['media']);
				$insertData = [
								'hash'		   => $data['hash'],
								'sender_id'	   => $data['sender'],
								'recipient_id' => $data['receipt'],
								'utctime'	   => $data['mb_time'],
								'amount'	   => $data['amount'],
								'type' 		   => $data['type'],
								'payload' 	   => $data['content'],
								'media_list'   => $data['mediaList'],
								'source' 	   => $data['source'],
								'chain_id'	   => $data['chainId']
							];
				ComModel::db()->table($this->wet_content)->insert($insertData);
				//是否话题
				$isTopic = TopicModel::isTopic($data['content']);
				if($isTopic) {
					$topic = [
						'hash'		=> $data['hash'],
						'content'   => $data['content'],
						'sender_id' => $data['sender'],
						'utctime'   => $data['mb_time']
						];
					TopicModel::insertTopic($topic);
				}
				//是否“@”
				$isMentions = $this->MentionsModel-> isMentions($data['content']);
				if($isMentions) {
					$mentions = [
						'type'		=> $data['type'],
						'hash'		=> '',
						'to_hash'	=> $data['hash'],
						'content'   => $data['content'],
						'sender_id' => $data['sender'],
						'utctime'   => $data['mb_time']
					];
					$this->MentionsModel-> messageMentions($mentions);
				}
			}

			elseif ( $data['type'] == 'comment' )
			{//评论
				$s_to_hash = $payload['toHash'] ?? $payload['to_hash']; //即将废弃to_hash
				$data['toHash'] = DisposeModel::delete_xss($s_to_hash);
				$insertData = [
					'hash'		   => $data['hash'],
					'to_hash'	   => $data['toHash'],
					'sender_id'	   => $data['sender'],
					'recipient_id' => $data['receipt'],
					'utctime'	   => $data['mb_time'],
					'amount'	   => $data['amount'],
					'type' 		   => $data['type'],
					'payload' 	   => $data['content'],
					'chain_id'	   => $data['chainId']
				];
				ComModel::db()->table($this->wet_comment)->insert($insertData);
				//验证是否为Superhero ID
				$isShTipid = DisposeModel::checkSuperheroTipid($data['toHash']);
				if ($isShTipid) {
					$upSql = "UPDATE $this->wet_content_sh SET comment_sum = comment_sum + 1 WHERE tip_id = '$data[toHash]'";
				} else {
					$upSql = "UPDATE $this->wet_content SET comment_sum = comment_sum + 1 WHERE hash = '$data[toHash]'";
				}
				ComModel::db()->query($upSql);
				//写入消息
				$msgOpt = [ 'type'=>$data['type'] ];
				if ($isShTipid) { //Superhero ID
					$msgOpt = [ 'type'=>'shTipid' ];
				}
	
				$toHashID = $this->MsgModel-> toHashSendID($data['toHash'], $msgOpt);  //获取被评论ID
				if($toHashID) {
					$msgData = [
						'hash' 		   => $data['hash'],
						'toHash' 	   => $data['toHash'],
						'type'	   	   => $data['type'],
						'sender_id'	   => $data['sender'],
						'recipient_id' => $toHashID,
						'utctime' 	   => $data['mb_time']
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
						'utctime'   => $data['mb_time']
					];
					$this->MentionsModel-> messageMentions($mentions);
				}
			}

			elseif ( $data['type'] == 'reply' )
			{//回复
				$replyType = $payload['reply_type'] ?? $payload['replyType']; //即将废弃reply_type
				$toHash = $payload['toHash'] ?? $payload['to_hash']; //即将废弃to_hash
				$toAddress = $payload['to_address'] ?? $payload['toAddress']; //即将废弃to_address
				$replyHash = $payload['reply_hash'] ?? $payload['replyHash']; //即将废弃reply_hash

				$data['replyType'] = DisposeModel::delete_xss($replyType);
				$data['toHash']    = DisposeModel::delete_xss($toHash);
				$data['toAddress'] = DisposeModel::delete_xss($toAddress);
				$data['replyHash'] = DisposeModel::delete_xss($replyHash);

				if ($data['replyType'] != 'comment' && $data['replyType'] != 'reply') {
					$this->deleteTemp($hash);
					DisposeModel::wetFwriteLog("无效格式回复贴格式:{$data['replyType']}:{$data['hash']}");
					return DisposeModel::wetJsonRt(406,'error');
				}

				$insertData = [
					'hash'		   => $data['hash'],
					'to_hash'	   => $data['toHash'],
					'reply_hash'   => $data['replyHash'],
					'reply_type'   => $data['replyType'],
					'to_address'   => $data['toAddress'],
					'sender_id'	   => $data['sender'],
					'recipient_id' => $data['receipt'],
					'utctime'	   => $data['mb_time'],
					'amount'	   => $data['amount'],
					'payload' 	   => $data['content'],
					'chain_id'	   => $data['chainId']
				];
				ComModel::db()->table($this->wet_reply)->insert($insertData);
				$upSql = "UPDATE $this->wet_comment SET comment_sum = comment_sum + 1 WHERE hash = '$data[toHash]'";
				ComModel::db()->query($upSql);
				//写入消息
				$msgOpt = [ 'type'=>$data['type'] ];
				$toHashID = $this->MsgModel-> toHashSendID($data['toHash'], $msgOpt);  //获取被评论ID
				if($toHashID) {
					$msgData = [
						'hash' 		   => $data['hash'],
						'toHash' 	   => $data['toHash'],
						'type'	   	   => $data['type'],
						'sender_id'	   => $data['sender'],
						'recipient_id' => $toHashID,
						'utctime' 	   => $data['mb_time']
					];
					$this->MsgModel-> addMsg($msgData);
				}
				
				//@回复写入消息
				if($data['replyType'] == 'reply' && $data['toAddress'] && $data['toAddress'] != $toHashID) {
					$msgData = [
						'hash' 		   => $data['hash'],
						'toHash' 	   => $data['toHash'],
						'type'	   	   => $data['type'],
						'sender_id'	   => $data['sender'],
						'recipient_id' => $data['toAddress'],
						'utctime' 	   => $data['mb_time']
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
						'utctime'   => $data['mb_time']
					];
					$this->MentionsModel-> messageMentions($mentions);
				}
			}

			elseif ( $data['type'] == 'nickname' )
			{//昵称
				$data['content'] = trim($payload['content']);
				$data['content'] = mb_substr($data['content'], 0, 15);
				$verify = ValidModel::isUser($data['sender']);

				if($verify){  //用户是否存在
					$upData = [ 'nickname' => $data['content'] ];
					ComModel::db()->table($this->wet_users)->where('address', $data['sender'])->update($upData);
				}else{
					$insertData = [
						'address'  => $data['sender'],
						'nickname' => $data['content']
					];
					ComModel::db()->table($this->wet_users)->insert($insertData);
				}
			}

			elseif ( $data['type'] == 'sex' )
			{//性别
				$data['content'] = (int)trim($payload['content']);
				if (!is_numeric($data['content']) || $data['content'] >= 3){
					DisposeModel::wetFwriteLog("sex type Error:{$data['hash']}");
					$this->deleteTemp($hash);
					return DisposeModel::wetJsonRt(406,'error');
				}

				$verify = ValidModel::isUser($data['sender']);
				if (!$verify) UserModel::userPut($data['sender']);
				$upData = [ 'sex' => $data['content'] ];
				ComModel::db()->table($this->wet_users)->where('address', $data['sender'])->update($upData);
			}

			elseif ( $data['type'] == 'focus' )
			{//关注
				if ($payload['action'] != "true" && $payload['action'] !== "false"){
					DisposeModel::wetFwriteLog("focus action Error:{$data['hash']}");
					$this->deleteTemp($hash);
					return;
				}
				$data['action']  = $payload['action'];
				$data['content'] = $payload['content'];
				$isAddress = DisposeModel::checkAddress($data['content']);
				$isUser    = ValidModel::isUser($data['content']);

				if (!$isUser || !$isAddress){
					DisposeModel::wetFwriteLog("focus isUser Error:{$data['hash']}");
					$this->deleteTemp($hash);
					return;
				}
				$this->FocusModel-> focus($data['content'], $data['sender'], $data['action']);
				$this->deleteTemp($hash);
				return;
			}

			elseif ( $data['type'] == 'star' )
			{//收藏
				if ($payload['action'] != "true" && $payload['action'] != "false"){
					DisposeModel::wetFwriteLog("star action Error:{$data['hash']}");
					$this->deleteTemp($hash);
					return;
				}
				$data['action']  = $payload['action'];
				$data['content'] = DisposeModel::delete_xss($payload['content']);
				$isHash = DisposeModel::checkAddress($data['content']);
				$isShTipid = DisposeModel::checkSuperheroTipid($data['content']);
				$isCheck   = $isShTipid ? $isShTipid : $isHash;
				$select = 'contentStar';
				if ($isShTipid) {
					$select = "shTipidStar";
				}
				if (!$isCheck){
					DisposeModel::wetFwriteLog("star isCheck Error:{$data['hash']}");
					$this->deleteTemp($hash);
					return DisposeModel::wetJsonRt(406,'error');
				}
				StarModel::star($data['sender'], $data['content'], $data['action'],$select);
				$this->deleteTemp($hash);
				return;
			}

			elseif ( $data['type'] == 'drift' )
			{//漂流瓶
				return;
			} else {
				$this->deleteTemp($hash);
				DisposeModel::wetFwriteLog("Payload [type]标签错误:{$hash}");
				return DisposeModel::wetJsonRt(406,'error');
			}

			UserModel::userActive($data['sender'], $getActive, $e = true);
			
			if( $data['type'] == 'topic' ) { //发布主贴用户发帖量+1
				$upSql = "UPDATE $this->wet_users SET topic_sum = topic_sum + 1 WHERE address = '$data[sender]'";
				ComModel::db()->query($upSql);
			}
			$this->deleteTemp($hash);
			if( $data['type'] == 'topic' ) $this->WetModel ->getNewContentList(); //通知中间件提示新内容
			return DisposeModel::wetJsonRt(200,'success');
		} catch (Exception $err) {
			$this->deleteTemp($hash);
			DisposeModel::wetFwriteLog("未知错误:{$err}");
			return DisposeModel::wetJsonRt(406,'error');
		}
    }

	private function deleteTemp($hash)
	{//删除临时缓存
		$delete = "DELETE FROM $this->wet_temp WHERE tp_hash = '$hash'";
		ComModel::db()->query($delete);
	}

}

