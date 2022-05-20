<?php namespace App\Models\Wecom;

use App\Models\{
	DisposeModel,
	ValidModel
};
use App\Models\Wecom\CorpUserModel;
use App\Models\ServerMdw\AeWallet;
use App\Models\Get\{
	GetPriceModel,
	GetAeChainModel
};
class ReceiveMsgTypeModel {
//企业微信被动回复类型处理 Model

	public function __construct(){
		$this->db = \Config\Database::connect('default');
		$this->DisposeModel  = new DisposeModel();
		$this->GetPriceModel = new GetPriceModel();
		$this->CorpUserModel = new CorpUserModel();
		$this->AeWallet 	 = new AeWallet();
		$this->ValidModel 	 = new ValidModel();
		$this->GetAeChainModel = new GetAeChainModel();
		$this->wet_wecom_users = "wet_wecom_users";
    }

	public function recTypeText($sMsg)
	{
		$xml = new \DOMDocument();
		$xml->loadXML($sMsg); 
		$reqToUserName   = $xml->getElementsByTagName('ToUserName')->item(0)->nodeValue;
		$reqFromUserName = $xml->getElementsByTagName('FromUserName')->item(0)->nodeValue;
		$reqCreateTime   = $xml->getElementsByTagName('CreateTime')->item(0)->nodeValue;
		$reqMsgType 	 = $xml->getElementsByTagName('MsgType')->item(0)->nodeValue;
		$reqContent 	 = $xml->getElementsByTagName('Content')->item(0)->nodeValue;
		$reqMsgId   	 = $xml->getElementsByTagName('MsgId')->item(0)->nodeValue;
		$reqAgentID 	 = $xml->getElementsByTagName('AgentID')->item(0)->nodeValue;

		$mycontent  = "";
		$coinFile = file_get_contents("gateioCoinList.txt"); //读取gate币种A列表
		$coinList = explode("\n" ,$coinFile); //转数组
		$reqContentUpper = strtoupper($reqContent); //转大写

		if ($reqContent == "帮助" || $reqContentUpper == "HELP" ) {
			$mycontent = "支持查询:\n回复关键词:AE\n\n支持绑定WeTrue钱包:\n例:绑定ak_xxxx\n\n更多功能,开发中……";

		} elseif ( in_array($reqContentUpper, $coinList) ) {
			$mycontent = $this->GetPriceModel-> gateioPrice($reqContentUpper);

		} elseif (substr($reqContent, 0, 6) == "绑定") {
			$aeAddress = substr($reqContent, 6);
			$isAddress = $this->DisposeModel-> checkAddress($aeAddress);
			if ($isAddress) {
				$mycontent = $this->CorpUserModel-> bindUser($aeAddress, $reqToUserName, $reqFromUserName);
			}else {
				$mycontent = "格式错误[请勿带回车等],示例：\n绑定ak_11111111111111111111111111111111273Yts";
			}

		} elseif (strtoupper($reqContent) == "USERID") {
			$mycontent = $reqFromUserName;

		} elseif (strtoupper($reqContent) == "USERCOUNT") {
			$countUser = $this->CorpUserModel-> getCountUser();
			$mycontent = "当前总账户:{$countUser}";

		} elseif (substr($reqContent, 0, 6) == "打赏") {
			$openReward = false; //开启或关闭打赏
			if ($openReward) {
				$coinKey   = "ae|wtt|abc|aeg|ans|wet";
				$upperKey  = strtoupper($coinKey); //转大写
				$matchList = "{$coinKey}|{$upperKey}"; //合并
				$wallet  = "ak_[1-9A-HJ-NP-Za-km-z]{48,50}"; //钱包匹配规则
				$isMatch = preg_match(
					"/^(打赏)+( )+([0-9]?\.?([0-9]){1})+($matchList)+( )+($wallet)$/",
					$reqContent,
					$match
				);
				$mycontent = "格式错误,示例:\n打赏+空格+金额Name+空格+地址\n\n如:\n打赏 0.1ae ak_xxooXYZ";
				/** 备注: 上链周期太长,容易反复请求造成重复上链,应先存后处理,成功再推送消息 */
				if ($isMatch) {
					$coinToken = strtoupper($match[5]); //提取Token
					$toAddress = $match[7]; //提取目标地址
					$keyList   = explode("|", $upperKey); //匹配Token转数组
					$isCoin    = in_array($coinToken, $keyList);
					$isAddress = $this->DisposeModel-> checkAddress($toAddress);
					if ($isCoin && $isAddress) {
						$offset = stripos($reqContent, $match[5]);
						$amount = substr($reqContent, 6, $offset-6);
						$wecomAddress = $this->CorpUserModel-> getWecomAddress($reqFromUserName);
						$wecomPrivate = $this->CorpUserModel-> getWecomPrivate($reqFromUserName);
						$subAddress   = mb_substr($wecomAddress, -4);
						$subToAddress = mb_substr($toAddress, -4);
						if ($coinToken == "AE") {
							$data = [
								"recipientId" => $toAddress,
								"secretKey"   => $wecomPrivate,
								"amount" 	  => $amount,
								"payload" 	  => 'From WeTrue WeCom'
							];
							$response = $this->AeWallet-> spendAE($data); //发送AE
						} else {
							$contractId = $this->contractAddress($coinToken);
							$data = [
								"contractId"  => $contractId,
								"recipientId" => $toAddress,
								"secretKey"   => $wecomPrivate,
								"amount" 	  => $amount
							];
							$response = $this->AeWallet-> transferToken($data); //发送Token
						}
						$json_arr = (array) json_decode($response, true);
						$hash = $json_arr['hash'];
						$mycontent = "打赏 {$coinToken} 失败";
						if ($response && $hash) {
							$mycontent = "成功打赏{$amount}{$coinToken}->{$subToAddress}\n具体以链上为准,Hash:\n\nhttps://www.aeknow.org/block/transaction/{$hash}";	
						}
					}
				}
			} else {
				$mycontent = "打赏已关闭";
			}
			
		}
		
		$sReqTimeStamp = time();
		$sRespData = 
		"<xml>
		<ToUserName><![CDATA[".$reqFromUserName."]]></ToUserName>
		<FromUserName><![CDATA[".$reqToUserName."]]></FromUserName>
		<CreateTime>".$sReqTimeStamp."</CreateTime>
		<MsgType><![CDATA[text]]></MsgType>
		<Content><![CDATA[".$mycontent."]]></Content>
		</xml>";
		return $sRespData;
	}

	public function recTypeEvent($sMsg)
	{
		/*
		<xml>
			<ToUserName><![CDATA[wwead6fb26267e8663]]></ToUserName>
			<FromUserName><![CDATA[LiuShao]]></FromUserName>
			<CreateTime>1650543800</CreateTime>
			<MsgType><![CDATA[event]]></MsgType>
			<AgentID>1000002</AgentID>
			<Event><![CDATA[click]]></Event>
			<EventKey><![CDATA[V1_Create_Wallet]]></EventKey>
		</xml>
		*/
		$xml = new \DOMDocument();
		$xml->loadXML($sMsg);

		$reqToUserName   = $xml->getElementsByTagName('ToUserName')->item(0)->nodeValue;
		$reqFromUserName = $xml->getElementsByTagName('FromUserName')->item(0)->nodeValue;
		$reqCreateTime   = $xml->getElementsByTagName('CreateTime')->item(0)->nodeValue;
		$reqMsgType 	 = $xml->getElementsByTagName('MsgType')->item(0)->nodeValue;
		$reqEvent 	 	 = $xml->getElementsByTagName('Event')->item(0)->nodeValue;
		$reqContent 	 = $xml->getElementsByTagName('EventKey')->item(0)->nodeValue;
		$reqAgentID 	 = $xml->getElementsByTagName('AgentID')->item(0)->nodeValue;
		$mycontent  = "";

		if ($reqContent == "V1_Wallet_Get_Create") {
			$newCreateWallet = $this->AeWallet-> newCreateWallet(); //创建钱包
			if ($newCreateWallet['secretKey']){
				$isWecomUserId = $this->ValidModel-> isWecomUserId($reqFromUserName); //验证id是否存在
				if (!$isWecomUserId){ //不存在直接写入
					$insertData = [
						'wecom_user_id' => $reqFromUserName
					];
					$this->db->table($this->wet_wecom_users)->insert($insertData);
				}

				$isWecomMnemonic = $this->ValidModel-> isWecomMnemonic($reqFromUserName);
				if ($isWecomMnemonic) {
					$mycontent = "创建失败,已存在一个AE钱包";
				} else {
					$publicKey = $this->CorpUserModel-> saveCreateWallet($newCreateWallet, $reqFromUserName);
					if ($publicKey) {
						$mycontent = "创建成功\n\n注意:您创建为托管钱包,并不适合大量存储,请注意风险安全。AE地址:\n\n{$publicKey}";
					} else {
						$mycontent = "创建失败,MDW报错";
					}
					
				}
			} else {
				$mycontent = "钱包创建失败,请稍后重试";
			}

		} elseif ($reqContent == "V1_Wallet_Get_Address") {
			$wecomWalletAddress = $this->CorpUserModel-> getWecomAddress($reqFromUserName);
			if ($wecomWalletAddress) {
				$mycontent = $wecomWalletAddress;
			} else {
				$mycontent = "读取失败,还未创建钱包?";
			}

		} elseif ($reqContent == "V1_Wallet_Get_Mnemonic") {
			$openGetWalletMnemonic = false; //开启或关闭助记词提取
			$wecomWalletMnemonic = $this->CorpUserModel-> getWecomMnemonic($reqFromUserName);
			if ($wecomWalletMnemonic) {
				if ($openGetWalletMnemonic) {
					$mycontent = "请注意保管助记词,\n同时请及时删除本条信息,\n助记词泄露将导致您资产的丢失,\nWeTrue不会为您的资产损失负责.\n您的助记词:\n\n".$wecomWalletMnemonic;
				} else {
					$mycontent = "涉及安全泄露,助记词提取已关闭.\n该钱包开发仅为互动所用.\n请勿大额存储任何资产";
				}
			} else {
				$mycontent = "读取失败,还未创建钱包?";
			}

		} elseif ($reqContent == "V1_Wallet_Get_Balance") {
			$wecomWalletAddress = $this->CorpUserModel-> getWecomAddress($reqFromUserName);
			if ($wecomWalletAddress) {
				$balance = $this->GetAeChainModel->accountsBalance($wecomWalletAddress); //查询链上金额
				$upperAE = $balance / 1e18;
				$substrAddress = mb_substr($wecomWalletAddress, -4);
				$largeAE = $upperAE >= 100 ? "\n\n注意:\n您所存储资产较大(大于100AE),托管钱包经网络传输并不安全,请尽快转移" : null;
				$mycontent = "{$substrAddress} 余额:\n{$upperAE} AE{$largeAE}";
			} else {
				$mycontent = "查询失败,还未创建钱包?";
			}
		} elseif ($reqContent == "V1_WeTrue_Wallet_Bind") {
			$mycontent = "发送如下格式[绑定+钱包地址]\n示例:\n绑定ak_XxXxX1273Yts";

		} elseif ($reqContent == "V1_Wallet_Transfer_Balance") {
			$mycontent = "转账开发中...";//"发送如下格式,示例:\n发送 1.2ae ak_XxXx3Yts";
		}
		
		$sReqTimeStamp = time();
		$sRespData = 
		"<xml>
		<ToUserName><![CDATA[".$reqFromUserName."]]></ToUserName>
		<FromUserName><![CDATA[".$reqToUserName."]]></FromUserName>
		<CreateTime>".$sReqTimeStamp."</CreateTime>
		<MsgType><![CDATA[text]]></MsgType>
		<Content><![CDATA[".$mycontent."]]></Content>
		</xml>";
		return $sRespData;
	}

	private function contractAddress($token)
	{ //Token 地址匹配
		$contractId = "";
		if ($token == "WTT") {
			$contractId = "ct_KeTvHnhU85vuuQMMZocaiYkPL9tkoavDRT3Jsy47LK2YqLHYb";
		} elseif ($token == "WET") {
			$contractId = "ct_uGk1rkSdccPKXLzS259vdrJGTWAY9sfgVYspv6QYomxvWZWBM";
		} elseif ($token == "ABC") {
			$contractId = "ct_7UfopTwsRuLGFEcsScbYgQ6YnySXuyMxQWhw6fjycnzS5Nyzq";
		} elseif ($token == "AEG") {
			$contractId = "ct_BwJcRRa7jTAvkpzc2D16tJzHMGCJurtJMUBtyyfGi2QjPuMVv";
		} elseif ($token == "ANS") {
			$contractId = "ct_2649d1du9jTBUKe8BsGNyaNiXe5gMZRXxx4iuQ7VFdCuKS1DJp";
		}
		return $contractId;
	}

}

