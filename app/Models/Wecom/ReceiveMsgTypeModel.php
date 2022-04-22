<?php namespace App\Models\Wecom;

use App\Models\{
	DisposeModel,
	ValidModel
};
use App\Models\Get\GetPriceModel;
use App\Models\Wecom\CorpUserModel;
use App\Models\ServerMdw\AeWallet;

class ReceiveMsgTypeModel {
//企业微信被动回复类型处理 Model

	public function __construct(){
		$this->DisposeModel  = new DisposeModel();
		$this->GetPriceModel = new GetPriceModel();
		$this->CorpUserModel = new CorpUserModel();
		$this->AeWallet 	 = new AeWallet();
		$this->ValidModel 	 = new ValidModel();
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
			$mycontent = "支持币价查询:\n例发送:AE\n\n支持绑定WeTrue钱包:\n例:绑定ak_xxxx\n\n支持发布内容上链到AE主网:\n该功能，开发中……";

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
			$mycontent = "当前总绑定：{$countUser}";
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

		if ($reqContent == "V1_Create_Wallet") {
			$newCreateWallet = $this->AeWallet-> newCreateWallet(); //创建钱包
			if ($newCreateWallet['secretKey']){
				$isWecomUserId = $this->ValidModel-> isWecomUserId($reqFromUserName); //验证id是否存在
				if (!$isWecomUserId){ //不存在直接绑定
					$insertData = [
						'address' 		=> $newCreateWallet['publicKey'],
						'wecom_corp_id' => $reqToUserName,
						'wecom_user_id' => $reqFromUserName
					];
					$this->db->table($this->wet_wecom_users)->insert($insertData);
				}

				$isWecomMnemonic = $this->ValidModel-> isWecomMnemonic($reqFromUserName);
				if ($isWecomMnemonic) {
					$mycontent = "创建失败,已存在一个AE钱包";
				} else {
					$publicKey = $this->CorpUserModel-> saveCreateWallet($newCreateWallet, $reqFromUserName);
					$mycontent = "创建成功,请注意保管助记词,\n注意:您创建为托管钱包,助记词或密钥经过网络传输可能存在安全隐患，因此并不适合大额存储,请注意资金安全。您的钱包地址:\n\n{$publicKey}";
				}
			} else {
				$mycontent = "钱包创建失败,请稍后重试";
			}

		} elseif ($reqContent == "V1_Get_Wallet_Address") {
			$WecomWalletAddress = $this->CorpUserModel-> getWecomAddress($reqFromUserName);
			if ($WecomWalletAddress) {
				$mycontent = $WecomWalletAddress;
			} else {
				$mycontent = "读取失败,你可能还未创建钱包?";
			}

		} elseif ($reqContent == "V1_Get_Wallet_Mnemonic") {
			$WecomWalletMnemonic = $this->CorpUserModel-> getWecomMnemonic($reqFromUserName);
			if ($WecomWalletMnemonic) {
				$mycontent = "请注意保管助记词,\n同时请及时删除本条信息,\n助记词泄露将导致您资产的丢失,\nWeTrue不会为您的资产损失负责.\n您的助记词:\n\n".$WecomWalletMnemonic;
			} else {
				$mycontent = "读取失败,你可能还未创建钱包?";
			}

		} elseif ($reqContent == "V1_Bind_WeTrue_Wallet") {
			$mycontent = "发送如下格式[绑定+钱包地址]\n示例:\n绑定ak_XxXxX1273Yts";

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

}

