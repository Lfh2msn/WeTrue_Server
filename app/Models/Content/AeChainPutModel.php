<?php namespace App\Models\Content;

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
use App\Models\Config\ActiveConfig;

class AeChainPutModel extends Model {
//Ae链上hash入库Model
	private $MsgModel;
	private $UserModel;
	private $StarModel;
	private $TopicModel;
	private $ValidModel;
	private $FocusModel;
	private $ConfigModel;
	private $WetModel;
	private $DisposeModel;
	private $MentionsModel;
	private $GetAeChainModel;
	private $ActiveConfig;
	private $wet_content;
	private $wet_comment;
	private $wet_reply;
	private $wet_users;

	public function __construct(){
		$this->db = \Config\Database::connect('default');
		$this->WetModel   = new WetModel();
		$this->MsgModel   = new MsgModel();
		$this->UserModel  = new UserModel();
		$this->StarModel  = new StarModel();
		$this->TopicModel = new TopicModel();
		$this->ValidModel = new ValidModel();
		$this->FocusModel = new FocusModel();
		$this->ActiveConfig  = new ActiveConfig();
		$this->ConfigModel   = new ConfigModel();
		$this->DisposeModel  = new DisposeModel();
		$this->MentionsModel = new MentionsModel();
		$this->GetAeChainModel = new GetAeChainModel();
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
		$logTime	= date('H:i:s'); //日志时间
		$bsConfig 	= $this->ConfigModel-> backendConfig();
        $microBlock = $json['block_hash'];
		if(!$microBlock){
			$logMsg = "{$logTime} - 错误区块hash:$microBlock\r\n";
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
				$logMsg = "{$logTime} - 非WeTrue格式:{$hash},版本号：{$WeTrue}\r\n";
				$this->DisposeModel->wetFwriteLog($logMsg);
				return $this->DisposeModel-> wetJsonRt(406,'error_WeTrue');
			}
			$logMsg = "{$logTime} - 版本号异常:{$hash},版本号：{$WeTrue}\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return $this->DisposeModel-> wetJsonRt(406,'error_version');
		}

		$data['WeTrue']  = $WeTrue;
		$isSource		 = $payload['source'];
		$sourceDelXSS 	 = $this->DisposeModel-> delete_xss($isSource);
		$sourceSubstr	 = substr($sourceDelXSS, 0, 15);
		$data['source']  = $sourceSubstr ?? 'Other';
		$data['type']    = $this->DisposeModel-> delete_xss($payload['type']);
		$data['hash']    = $hash;
		$data['receipt'] = $json['tx']['recipient_id'];
		$data['sender']  = $json['tx']['sender_id'];
		$data['amount']  = $json['tx']['amount'];
		$data['mb_time'] = $json['mb_time'];
		$data['content'] = $payload['content'];
		$data['chain_id']= 457;

		//用户 活跃度及费用 设置检测
		$ftConfig = $this->ConfigModel-> frontConfig($data['sender']);
		$activeConfig = $this->ActiveConfig->config();

		if ($data['type'] == 'topic') { //主贴
			$userAmount = $ftConfig['topicAmount'];
			$getActive  = $activeConfig['topicActive'];
		}
		if ($data['type'] == 'comment') { //评论
			$userAmount = $ftConfig['commentAmount'];
			$getActive  = $activeConfig['commentActive'];
		}
		if ($data['type'] == 'reply') { //回复
			$userAmount = $ftConfig['replyAmount'];
			$getActive  = $activeConfig['replyActive'];
		}
		if ($data['type'] == 'nickname') { //昵称
			$userAmount = $ftConfig['nicknameAmount'];
			$getActive  = $activeConfig['nicknameActive'];
		}
		if ($data['type'] == 'sex') { //性别
			$userAmount = $ftConfig['sexAmount'];
			$getActive  = $activeConfig['sexActive'];
		}

		if ($data['amount'] < $userAmount) {
			$this->deleteTemp($hash);
			$logMsg = "{$logTime} - 费用异常:{$hash}\r\n";
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
					$logMsg = "{$logTime} - 重复主贴Hash:{$hash}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}
				
				$holdAE = $bsConfig['usableHoldAE'];  //要求最低持有AE开关
				if ($holdAE) {
					$holdAettos     = $bsConfig['usableHoldAettos'];  //要求最低持有AE
					$accountsAettos = $this->GetAeChainModel->accountsBalance($data['sender']);  //查询链上金额
					if ($accountsAettos < $holdAettos) {
						$this->deleteTemp($hash);
						$logMsg  = "{$logTime} - 持有AE不足最低要求:{$hash}\r\n";
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
								'utctime'	   => $data['mb_time'],
								'amount'	   => $data['amount'],
								'type' 		   => $data['type'],
								'payload' 	   => $data['content'],
								'media_list'   => $data['mediaList'],
								'source' 	   => $data['source'],
								'chain_id'	   => $data['chain_id']
							];
				$this->db->table($this->wet_content)->insert($insertData);
				//是否话题
				$isTopic = $this->TopicModel-> isTopic($data['content']);
				if($isTopic) {
					$topic = [
						'hash'		=> $data['hash'],
						'content'   => $data['content'],
						'sender_id' => $data['sender'],
						'utctime'   => $data['mb_time']
						];
					$this->TopicModel-> insertTopic($topic);
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
				$isCommentHash = $this->ValidModel-> isCommentHash($data['hash']);
				if ($isCommentHash) {
					$this->deleteTemp($hash);
					$logMsg = "{$logTime} - 重复评论hash:{$data['hash']}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}

				$s_to_hash = $payload['toHash'] ?? $payload['to_hash']; //即将废弃toHash
				$data['to_hash'] = $this->DisposeModel-> delete_xss($s_to_hash);
				$insertData = [
					'hash'		   => $data['hash'],
					'to_hash'	   => $data['to_hash'],
					'sender_id'	   => $data['sender'],
					'recipient_id' => $data['receipt'],
					'utctime'	   => $data['mb_time'],
					'amount'	   => $data['amount'],
					'type' 		   => $data['type'],
					'payload' 	   => $data['content'],
					'chain_id'	   => $data['chain_id']
				];
				$this->db->table($this->wet_comment)->insert($insertData);
				//验证是否为Superhero ID
				$isShTipid = $this->DisposeModel-> checkSuperheroTipid($data['to_hash']);
				if ($isShTipid) {
					$upSql = "UPDATE $this->wet_content_sh SET comment_sum = comment_sum + 1 WHERE tip_id = '$data[to_hash]'";
				} else {
					$upSql = "UPDATE $this->wet_content SET comment_sum = comment_sum + 1 WHERE hash = '$data[to_hash]'";
				}
				$this->db->query($upSql);
				//写入消息
				$msgOpt = [ 'type'=>$data['type'] ];
				if ($isShTipid) { //Superhero ID
					$msgOpt = [ 'type'=>'shTipid' ];
				}
	
				$toHashID = $this->MsgModel-> toHashSendID($data['to_hash'], $msgOpt);  //获取被评论ID
				if($toHashID) {
					$msgData = [
						'hash' 		   => $data['hash'],
						'to_hash' 	   => $data['to_hash'],
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
						'to_hash'	=> $data['to_hash'],
						'content'   => $data['content'],
						'sender_id' => $data['sender'],
						'utctime'   => $data['mb_time']
					];
					$this->MentionsModel-> messageMentions($mentions);
				}
			}

			elseif ( $data['type'] == 'reply' )
			{//回复
				$isReplyHash = $this->ValidModel-> isReplyHash($data['hash']);
				if ($isReplyHash) {
					$this->deleteTemp($hash);
					$logMsg = "{$logTime} - 重复回复hash:{$data['hash']}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}

				$data['reply_type'] = $this->DisposeModel-> delete_xss($payload['reply_type']);
				$data['to_hash']    = $this->DisposeModel-> delete_xss($payload['to_hash']);
				$data['to_address'] = $this->DisposeModel-> delete_xss($payload['to_address']);
				$data['reply_hash'] = $this->DisposeModel-> delete_xss($payload['reply_hash']);

				if ($data['reply_type'] != "comment" && $data['reply_type'] != "reply") {
					$this->deleteTemp($hash);
					$logMsg = "{$logTime} - 无效格式回复贴格式:{$data['reply_type']}:{$data['hash']}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}

				$insertData = [
					'hash'		   => $data['hash'],
					'to_hash'	   => $data['to_hash'],
					'reply_hash'   => $data['reply_hash'],
					'reply_type'   => $data['reply_type'],
					'to_address'   => $data['to_address'],
					'sender_id'	   => $data['sender'],
					'recipient_id' => $data['receipt'],
					'utctime'	   => $data['mb_time'],
					'amount'	   => $data['amount'],
					'payload' 	   => $data['content'],
					'chain_id'	   => $data['chain_id']
				];
				$this->db->table($this->wet_reply)->insert($insertData);
				$upSql = "UPDATE $this->wet_comment SET comment_sum = comment_sum + 1 WHERE hash = '$data[to_hash]'";
				$this->db->query($upSql);
				//写入消息
				$msgOpt = [ 'type'=>$data['type'] ];
				$toHashID = $this->MsgModel-> toHashSendID($data['to_hash'], $msgOpt);  //获取被评论ID
				if($toHashID) {
					$msgData = [
						'hash' 		   => $data['hash'],
						'to_hash' 	   => $data['to_hash'],
						'type'	   	   => $data['type'],
						'sender_id'	   => $data['sender'],
						'recipient_id' => $toHashID,
						'utctime' 	   => $data['mb_time']
					];
					$this->MsgModel-> addMsg($msgData);
				}
				
				//@回复写入消息
				if($data['reply_type'] == 'reply' && $data['to_address'] && $data['to_address'] != $toHashID) {
					$msgData = [
						'hash' 		   => $data['hash'],
						'to_hash' 	   => $data['to_hash'],
						'type'	   	   => $data['type'],
						'sender_id'	   => $data['sender'],
						'recipient_id' => $data['to_address'],
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
						'to_hash'	=> $data['to_hash'],
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
				$verify     = $this->ValidModel-> isUser($data['sender']);
				$isNickname = $this->ValidModel-> isNickname($data['content']);
				if ($isNickname) {
					$this->deleteTemp($hash);
					$logMsg = "{$logTime} - 重复昵称:{$data['hash']}\r\n";
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
			}

			elseif ( $data['type'] == 'sex' )
			{//性别
				$data['content'] = (int)trim($payload['content']);
				if (!is_numeric($data['content']) || $data['content'] >= 3){
					$logMsg = "{$logTime} - sex type Error:{$data['hash']}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
					$this->deleteTemp($hash);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}

				$verify = $this->ValidModel-> isUser($data['sender']);
				if (!$verify) $this->UserModel-> userPut($data['sender']);
				$upData = [ 'sex' => $data['content'] ];
				$this->db->table($this->wet_users)->where('address', $data['sender'])->update($upData);
			}

			elseif ( $data['type'] == 'focus' )
			{//关注
				if ($payload['action'] != "true" && $payload['action'] !== "false"){
					$logMsg = "{$logTime} - focus action Error:{$data['hash']}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
					$this->deleteTemp($hash);
					return;
				}
				$data['action']  = $payload['action'];
				$data['content'] = $payload['content'];
				$isAddress = $this->DisposeModel-> checkAddress($data['content']);
				$isUser    = $this->ValidModel-> isUser($data['content']);

				if (!$isUser || !$isAddress){
					$logMsg = "{$logTime} - focus isUser Error:{$data['hash']}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
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
					$logMsg = "{$logTime} - star action Error:{$data['hash']}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
					$this->deleteTemp($hash);
					return;
				}
				$data['action']  = $payload['action'];
				$data['content'] = $this->DisposeModel-> delete_xss($payload['content']);
				$isHash = $this->DisposeModel-> checkAddress($data['content']);
				$isShTipid = $this->DisposeModel-> checkSuperheroTipid($data['content']);
				$isCheck   = $isShTipid ? $isShTipid : $isHash;
				$select = 'contentStar';
				if ($isShTipid) {
					$select = "shTipidStar";
				}
				if (!$isCheck){
					$logMsg = "{$logTime} - star isCheck Error:{$data['hash']}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
					$this->deleteTemp($hash);
					return $this->DisposeModel-> wetJsonRt(406,'error');
				}
				$this->StarModel-> star($data['sender'], $data['content'], $data['action'],$select);
				$this->deleteTemp($hash);
				return;
			}

			elseif ( $data['type'] == 'drift' )
			{//漂流瓶
				return;
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
				'influence' => $getActive,
				'toaddress' => $data['receipt']
			];
			$this->db->table($this->wet_behavior)->insert($insetrBehaviorDate);
			$this->UserModel-> userActive($data['sender'], $getActive, $e = true);
			
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

