<?php namespace App\Models\Wecom;

use App\Models\ConfigModel;
use App\Models\Wecom\Callback\WXBizMsgCrypt;
use App\Models\Get\GetPriceModel;

class ReceiveModel {
//企业微信 Model

	public function __construct(){
		$this->$ConfigModel  = new ConfigModel();
		$weConfig      = $this->$ConfigModel-> wecomConfig();
		$wecomCid      = $weConfig['WECOM_CID'];
		$wecomToken_1  = $weConfig['WECOM_TOKEN_1'];
		$wecomAesKey_1 = $weConfig['WECOM_AESKEY_1'];
		$wetrueKey     = $weConfig['WECOM_KEY'];
		$this->WXBizMsgCrypt = new WXBizMsgCrypt($wecomToken_1, $wecomAesKey_1, $wecomCid);
		$this->GetPriceModel = new GetPriceModel();
    }

	public function recFromWecom($sReqMsgSig, $sReqTimeStamp, $sReqNonce, $sReqData)
	{
		$sMsg  = "";  // 解析之后的明文
		$errCode = $this->WXBizMsgCrypt-> DecryptMsg($sReqMsgSig, $sReqTimeStamp, $sReqNonce, $sReqData, $sMsg);
		//file_put_contents('log/smg_response1.txt', $sMsg); //debug:查看smg
		if ($errCode == 0) { 
			$xml = new \DOMDocument();
			$xml->loadXML($sMsg); 
			$reqToUserName   = $xml->getElementsByTagName('ToUserName')->item(0)->nodeValue;
			$reqFromUserName = $xml->getElementsByTagName('FromUserName')->item(0)->nodeValue;
			$reqCreateTime   = $xml->getElementsByTagName('CreateTime')->item(0)->nodeValue;
			$reqMsgType = $xml->getElementsByTagName('MsgType')->item(0)->nodeValue;
			$reqContent = $xml->getElementsByTagName('Content')->item(0)->nodeValue;
			$reqMsgId   = $xml->getElementsByTagName('MsgId')->item(0)->nodeValue;
			$reqAgentID = $xml->getElementsByTagName('AgentID')->item(0)->nodeValue;
			$mycontent  = "";
			if ($reqContent == "刘少") {
				$mycontent = "别装了，我知道你不是！";
			} elseif (
					strtoupper($reqContent) == "AE"  || strtoupper($reqContent) == "BTC" ||
					strtoupper($reqContent) == "ETH" || strtoupper($reqContent) == "ZIL" ||
					strtoupper($reqContent) == "ETC" || strtoupper($reqContent) == "LTC" ||
					strtoupper($reqContent) == "EOS" || strtoupper($reqContent) == "XRP" ||
					strtoupper($reqContent) == "SOL" || strtoupper($reqContent) == "ADA" ||
					strtoupper($reqContent) == "FIL" || strtoupper($reqContent) == "BNB" ||
					strtoupper($reqContent) == "DOGE"|| strtoupper($reqContent) == "GRIN"||
					strtoupper($reqContent) == "PEOPLE"
				) {
				$mycontent = $this->GetPriceModel-> aePrice(strtoupper($reqContent));
			} elseif (substr($reqContent, 0, 6) == "绑定") {
				$mycontent = substr($reqContent, 6);
			} elseif (strtoupper($reqContent) == "USERID") {
				$mycontent = $reqFromUserName;
			}

			$sRespData = 
			"<xml>
			<ToUserName><![CDATA[".$reqFromUserName."]]></ToUserName>
			<FromUserName><![CDATA[".$corpId."]]></FromUserName>
			<CreateTime>".$sReqTimeStamp."</CreateTime>
			<MsgType><![CDATA[text]]></MsgType>
			<Content><![CDATA[".$mycontent."]]></Content>
			</xml>";
			$sEncryptMsg = ""; //xml格式的密文
			$errCode = $this->WXBizMsgCrypt-> EncryptMsg($sRespData, $sReqTimeStamp, $sReqNonce, $sEncryptMsg); //对返回信息加密
			if ($errCode == 0) {
				return $sEncryptMsg;
			} else {
				return "ERR:{$errCode}\n\n";
			}
		} else {
			return "ERR:{$errCode}\n\n";
		}

	}

	public function verifyURLWecom($sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce, $sVerifyEchoStr)
	{// 验证Api Url
		$sEchoStr = "";
		$errCode = $this->WXBizMsgCrypt-> VerifyURL($sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce, $sVerifyEchoStr, $sEchoStr);
		if ($errCode == 0) {
			return $sEchoStr;
		} else {
			return "ERR:{$errCode}\n\n";
		}
	}
	
}

