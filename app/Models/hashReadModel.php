<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\ConfigModel;
use App\Models\DisposeModel;

class hashReadModel extends Model {

	public function __construct(){
        parent::__construct();
		$this->ConfigModel  = new ConfigModel();
		$this->DisposeModel = new DisposeModel();
		$this->Temporary    = 'wet_temporary';
    }

	public function split($hash)
	{//上链内容入库
		$isHashSql = "SELECT tp_hash FROM $this->Temporary WHERE tp_hash = '$hash' LIMIT 1";
		$query = $this->db-> query($isHashSql)-> getRow();
		if(!$query){//写入临时缓存
			$insertTempSql = "INSERT INTO $this->Temporary(tp_hash) VALUES ('$hash')";
			$this->db->query($insertTempSql);
		}

		$bsConfig = $this->ConfigModel-> backendConfig();
		$url = $bsConfig['backendServiceNode'].'v2/transactions/'.$hash;
		$num = 0;
		while ( !$get && $num < 10 ) {
			@$get = file_get_contents($url);
			$num++;
			//sleep(1);
		}

        if(empty($get)){
        	return;
        }

        $json = (array) json_decode($get, true);
		
        //过滤无效预设钱包
        if(empty(
			$json['tx']['recipient_id'] == $bsConfig['receivingAccount'] || 
			$json['tx']['type'] == 'SpendTx' || 
			$json['tx']['payload']===null || 
			$json['tx']['payload']==="ba_Xfbg4g=="
			)){
        	//删除临时缓存
	        $deleteTempSql = "DELETE FROM $this->Temporary WHERE tp_hash='$hash'";
	        $this->db->query($deleteTempSql);
        	return;
        }

		$data = $this->decodeContent($json);
		return $data ;
	}

	public function decodeContent($json)
	{//重构及内容分配
		$bsConfig 		 = $this->ConfigModel-> backendConfig();
        $microBlock      = $json['block_hash'];
        $microBlockUrl   = $bsConfig['backendServiceNode'].'v2/micro-blocks/hash/'.$microBlock.'/header';
        @$microBlockJson = file_get_contents($microBlockUrl);
        $microBlockArray = (array) json_decode($microBlockJson, true);
		$json['mb_time'] = $microBlockArray['time'];

		$payload = $this->DisposeModel ->decodePayload($json['tx']['payload']);
		$hash	 = $json['hash'];
		$WeTrue  = $payload['WeTrue'];
		$require = $bsConfig['requireVersion'];
		$version = $this->DisposeModel ->versionCompare($WeTrue, $require); //版本检测
		if(!$version){
			$versionLow = "versionLow";
			$updateSql  = "UPDATE $this->Temporary SET tp_source = '$versionLow' WHERE tp_hash = '$hash'";
	        $this->db-> query($updateSql);
			return;
		}
		$type 			 = $payload['type'];
		$data['WeTrue']  = $WeTrue;
		$data['type']    = $type;
		$data['hash']    = $hash;
		$data['receipt'] = $json['tx']['recipient_id'];
		$data['sender']  = $json['tx']['sender_id'];
		$data['amount']  = $json['tx']['amount'];
		$data['mbTime']  = $json['mb_time'];
		$data['content'] = $payload['wet_content'];
		//内容分配
		if( $type == 'topic' ){  //主贴
			$data['imgList'] = $payload['img_list'];
			$this->tablename = 'wet_content';
			$insertSql = "INSERT INTO $this->tablename(
								hash,sender_id,recipient_id,utctime,amount,type,payload,img_tx
							) VALUES (   
								'$data[hash]', '$data[sender]', '$data[receipt]', '$data[mbTime]', '$data[amount]', '$data[type]', '$data[content]', '$data[imgList]'
							)";
			$active = $bsConfig['contentActive'];
		}

		if( $type == 'comment' ){  //评论
			$data['toHash'] = $payload['toHash'];
			$this->tablename = 'wet_comment';
			$insertSql = "INSERT INTO $this->tablename(
								hash, to_hash, sender_id, recipient_id, utctime, amount, type, payload
							) VALUES (
								'$data[hash]', '$data[toHash]', '$data[sender]', '$data[receipt]', '$data[mbTime]', '$data[amount]', '$data[type]', '$data[content]'
							)";
			$active = $bsConfig['commentActive'];
		}

		if( $type == 'reply' ){  //回复
			$data['replyType'] = $payload['reply_type'];
			$data['toHash']    = trim($payload['to_hash']);
			$data['toAddress'] = trim($payload['to_address']);
			$data['replyHash'] = trim($payload['reply_hash']);
			$this->tablename = 'wet_reply';
			$insertSql = "INSERT INTO $this->tablename(
								hash, to_hash, reply_hash, reply_type, to_address, sender_id, recipient_id, utctime, amount, payload
							) VALUES (
								'$data[hash]', '$data[toHash]', '$data[replyHash]', '$data[replyType]', '$data[toAddress]', 
								'$data[sender]', '$data[receipt]', '$data[mbTime]', '$data[amount]', '$data[content]'
							)";
			$active = $bsConfig['replyActive'];
		}

		if( $type == 'nickname' ){  //昵称
			$data['content'] = trim($payload['wet_content']);
			$this->tablename = 'wet_users';
			$insertSql = "INSERT INTO $this->tablename(
								address, username
							) VALUES (
								'$data[sender]', '$data[content]'
							)";
			$active = $bsConfig['nicknameActive'];
		}

		if( $type == 'portrait' ){  //头像
			$this->tablename = 'wet_users';
			$insertSql = "INSERT INTO $this->tablename(
								address, portrait, maxportrait
							) VALUES (
								'$data[sender]', '$data[content]', '$data[hash]'
							)";
			$active = $bsConfig['portraitActive'];
		}else{
			return;
		}

		$this->db->query($insertSql);

		//入库行为记录
		$insetrBehaviorSql = "INSERT INTO wet_behavior(
									address, hash, thing, influence, toaddress
								) VALUES (
									'$data[sender]', '$data[hash]', '$data[type]', '$active', '$data[receipt]'
								)";
		$this->db->query($insetrBehaviorSql);

		//删除临时缓存
		$sql_del_tp = "DELETE FROM $this->Temporary WHERE tp_hash='$data[hash]'";
		$this->db->query($sql_del_tp);
    }

}

