<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\DisposeModel;

class ConfigModel extends Model {

	public function __construct(){
        parent::__construct();
		$this->DisposeModel = new DisposeModel();
    }

    public function backendConfig()
	{//后端配置
		return array(
			'version'			 => '2.0.0',  //当前版本号
			'requireVersion'	 => '2.0.0',  //最低要求版本号
			'topicAmount'        => 1e14,  //默认，发帖费用 1e17 = 0.1ae
			'commentAmount'      => 1e14,  //默认，评论费用
			'replyAmount'        => 1e14,  //默认，回复费用
			'nicknameAmount'     => 1e14,  //默认，昵称费用
			'portraitAmount'     => 1e14,  //默认，头像费用
			'backendServiceNode' => PUBLIC_NODE,  //后端节点
			'receivingAccount'   => 'ak_dMyzpooJ4oGnBVX35SCvHspJrq55HAAupCwPQTDZmRDT5SSSW',  //接收账户
			'adminUser_1'        => 'ak_2kxt6D65giv4yNt4oa44SjW4jEXfoHMviPFvAreSEXvz25Q3QQ',  // Admin User 1
			'adminUser_2'        => 'ak_2kxt6D65giv4yNt4oa44SjW4jEXfoHMviPFvAreSEXvz25Q3QQ',  // Admin User 2
			'adminUser_3'        => 'ak_2kxt6D65giv4yNt4oa44SjW4jEXfoHMviPFvAreSEXvz25Q3QQ',  // Admin User 3
			'gateioApiUrl'       => 'https://data.gateapi.io/api2/1/ticker/ae_usdt',  //Gate.io AE API
			'AeasyApiUrl'        => 'https://aeasy.io/api/wallet/transfer',  //Aeasy.io API
			'AeasyApp_id'        => '',  //Aeasy.io appid
			'AeasyAmount'        => '0.1',  //活动金额
			'AeasySecretKey'     => '',  //私钥
			'airdropWttRatio'    => 3,  //WTT空投比例
			'hotRecDay'          => 240,  //热点推荐天数
			'factorPraise'		 => 1,  //点赞因子,越大权重越大
			'factorComment'		 => 3,  //评论因子,越大权重越大
			'factorStar'		 => 5,  //收藏因子,越大权重越大
			'factorTime'		 => 0.2,  //时间因子,越大权重越小
			'topicActive'        => 5,  //发帖 +活跃度
			'commentActive'      => 2,  //评论 +活跃度
			'replyActive'        => 2,  //回复 +活跃度
			'praiseActive'       => 1,  //点赞 +活跃度
			'nicknameActive'     => 1,  //昵称 +活跃度
			'portraitActive'     => 1,  //头像 +活跃度
			'complainActive'     => 30  //举报 -活跃度
		);
    }

    public function frontConfig()
	{//前端配置
		$bsConfig = $this-> backendConfig();
		
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if ($isAkToken) { //查询用户自定义费用
			$selectSql = "SELECT topic, 
								comment, 
								reply, 
								nickname, 
								portrait
							FROM wet_amount WHERE address = '$akToken' LIMIT 1";
			$query  = $this->db->query($selectSql);
			$getRow = $query-> getRow();
		}
		
		//根据登录状态，判断某用户自定义费用
		$topicAmount    = $getRow->topic 	?? $bsConfig['topicAmount'];
		$commentAmount  = $getRow->comment  ?? $bsConfig['commentAmount'];
		$replyAmount    = $getRow->reply	?? $bsConfig['replyAmount'];
		$nicknameAmount = $getRow->nickname ?? $bsConfig['nicknameAmount'];
		$portraitAmount = $getRow->portrait ?? $bsConfig['portraitAmount'];

		return array(
			'WeTrue'           => $bsConfig['version'],
			'requireVersion'   => $bsConfig['requireVersion'],
			'topicAmount'      => (int)$topicAmount,
			'commentAmount'    => (int)$commentAmount,
			'replyAmount'      => (int)$replyAmount,
			'nicknameAmount'   => (int)$nicknameAmount,
			'portraitAmount'   => (int)$portraitAmount,
			'receivingAccount' => $bsConfig['receivingAccount'],
			'contentActive'    => $bsConfig['topicActive'],
			'commentActive'    => $bsConfig['commentActive'],
			'praiseActive'     => $bsConfig['praiseActive'],
			'nicknameActive'   => $bsConfig['nicknameActive'],
			'portraitActive'   => $bsConfig['portraitActive'],
			'complainActive'   => $bsConfig['complainActive'],
		);
    }

	public function nodesConfig()
	{//节点配置

		$data[] = array(
			'url'	=> PUBLIC_NODE,
			'name' 	=> 'WeTrue',
		);
		$data[] = array(
			'url'	=> 'https://mainnet.aeternity.io/',
			'name' 	=> 'Aeternity',
		);

		return $data;
    }

}

