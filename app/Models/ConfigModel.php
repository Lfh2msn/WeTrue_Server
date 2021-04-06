<?php namespace App\Models;

use CodeIgniter\Model;

class ConfigModel extends Model {

    public function backendConfig(){
	//后端配置
		return array(
			'version'			 => '2.0.0', //当前版本号
			'requireVersion'	 => '2.0.0', //最低要求版本号
			'contentAmount'      => (int)'1e14', //发帖费用 1e17 = 0.1ae
			'commentAmount'      => (int)'1e14', //评论费用
			'replyAmount'        => (int)'1e14', //回复费用
			'nickNameAmount'     => (int)'1e14', //昵称金额
			'portraitAmount'     => (int)'1e14', //头像费用
			'articleSendNode'    => PUBLIC_NODE, //内容发送节点
			'backendServiceNode' => PUBLIC_NODE, //内容发送节点
			'receivingAccount'   => 'ak_dMyzpooJ4oGnBVX35SCvHspJrq55HAAupCwPQTDZmRDT5SSSW', //接收账户
			'adminUser_1'        => 'ak_2kxt6D65giv4yNt4oa44SjW4jEXfoHMviPFvAreSEXvz25Q3QQ', // Admin User 1
			'adminUser_2'        => 'ak_2kxt6D65giv4yNt4oa44SjW4jEXfoHMviPFvAreSEXvz25Q3QQ', // Admin User 2
			'adminUser_3'        => 'ak_2kxt6D65giv4yNt4oa44SjW4jEXfoHMviPFvAreSEXvz25Q3QQ', // Admin User 3
			'gateioApiUrl'       => 'https://data.gateapi.io/api2/1/ticker/ae_usdt', //Gate.io AE API
			'AeasyApiUrl'        => 'https://aeasy.io/api/wallet/transfer', //Aeasy.io API
			'AeasyApp_id'        => '', //Aeasy.io appid
			'AeasyAmount'        => '0.1', //活动金额
			'AeasySecretKey'     => '', //私钥
			'airdropWttRatio'    => (int)'3', //WTT空投比例
			'hotRecDay'          => (int)'3', //热点推荐天数

			'contentActive'      => (int)'5', //发帖增加活跃度
			'commentActive'      => (int)'2', //评论增加活跃度
			'praiseActive'       => (int)'1', //点赞增加活跃度
			'nickNameActive'     => (int)'1', //昵称增加活跃度
			'portraitActive'     => (int)'1', //头像增加活跃度
			'reportActive'       => (int)'30',//举报扣除活跃度
		);
    }

    public function frontConfig(){	
	 //前端配置
		$backendConfig = (new ConfigModel())-> backendConfig();

		return array(
			'WeTrue'           => $backendConfig['version'],
			'requireVersion'   => $backendConfig['requireVersion'],
			'contentAmount'    => $backendConfig['contentAmount'],
			'commentAmount'    => $backendConfig['commentAmount'],
			'replyAmount'      => $backendConfig['replyAmount'],
			'nickNameAmount'   => $backendConfig['nickNameAmount'],
			'portraitAmount'   => $backendConfig['portraitAmount'],
			'receivingAccount' => $backendConfig['receivingAccount'],
			'contentActive'    => $backendConfig['contentActive'],
			'commentActive'    => $backendConfig['commentActive'],
			'praiseActive'     => $backendConfig['praiseActive'],
			'nickNameActive'   => $backendConfig['nickNameActive'],
			'portraitActive'   => $backendConfig['portraitActive'],
			'reportActive'     => $backendConfig['reportActive'],
		);
    }

}

