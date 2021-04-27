<?php namespace App\Models;

use CodeIgniter\Model;

class ConfigModel extends Model {

    public function backendConfig()
	{//后端配置
		return array(
			'version'			 => '2.0.0',  //当前版本号
			'requireVersion'	 => '2.0.0',  //最低要求版本号
			'contentAmount'      => (int)'1e14',  //发帖费用 1e17 = 0.1ae
			'commentAmount'      => (int)'1e14',  //评论费用
			'replyAmount'        => (int)'1e14',  //回复费用
			'nicknameAmount'     => (int)'1e14',  //昵称金额
			'portraitAmount'     => (int)'1e14',  //头像费用
			'articleSendNode'    => PUBLIC_NODE,  //前端节点
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
			'airdropWttRatio'    => (int)'3',  //WTT空投比例
			'hotRecDay'          => (int)'365',  //热点推荐天数

			'contentActive'      => (int)'5',  //发帖 +活跃度
			'commentActive'      => (int)'2',  //评论 +活跃度
			'replyActive'        => (int)'2',  //回复 +活跃度
			'praiseActive'       => (int)'1',  //点赞 +活跃度
			'nicknameActive'     => (int)'1',  //昵称 +活跃度
			'portraitActive'     => (int)'1',  //头像 +活跃度
			'complainActive'     => (int)'30'  //举报 -活跃度
		);
    }

    public function frontConfig()
	{//前端配置
		$backendConfig = (new ConfigModel())-> backendConfig();

		return array(
			'WeTrue'           => $backendConfig['version'],
			'requireVersion'   => $backendConfig['requireVersion'],
			'contentAmount'    => $backendConfig['contentAmount'],
			'commentAmount'    => $backendConfig['commentAmount'],
			'replyAmount'      => $backendConfig['replyAmount'],
			'nicknameAmount'   => $backendConfig['nicknameAmount'],
			'portraitAmount'   => $backendConfig['portraitAmount'],
			'receivingAccount' => $backendConfig['receivingAccount'],
			'contentActive'    => $backendConfig['contentActive'],
			'commentActive'    => $backendConfig['commentActive'],
			'praiseActive'     => $backendConfig['praiseActive'],
			'nicknameActive'   => $backendConfig['nicknameActive'],
			'portraitActive'   => $backendConfig['portraitActive'],
			'complainActive'   => $backendConfig['complainActive'],
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

