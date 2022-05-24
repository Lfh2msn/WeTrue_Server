<?php namespace App\Models\Wecom;

use App\Models\Config\WecomConfig;
use App\Models\Wecom\Callback\WXBizMsgCrypt;
use App\Models\Wecom\ReceiveMsgTypeModel;

class ReceiveModel {
//企业微信被动回复 Model

	public function __construct(){
		$this->$WecomConfig  = new WecomConfig();
		$weConfig      = $this->$WecomConfig-> config();
		$wecomCid_1      = $weConfig['WECOM_CID_1'];
		$wecomToken_1  = $weConfig['WECOM_TOKEN_1'];
		$wecomAesKey_1 = $weConfig['WECOM_AESKEY_1'];

		$this->WXBizMsgCrypt   = new WXBizMsgCrypt($wecomToken_1, $wecomAesKey_1, $wecomCid_1);
		$this->RecMsgTypeModel = new ReceiveMsgTypeModel();
    }

	public function recFromWecom($sReqMsgSig, $sReqTimeStamp, $sReqNonce, $sReqData)
	{
		$sMsg  = "";  // 解析之后的明文
		$errCode = $this->WXBizMsgCrypt-> DecryptMsg($sReqMsgSig, $sReqTimeStamp, $sReqNonce, $sReqData, $sMsg);
		//file_put_contents('log/smg_response1.txt', $sMsg); //debug:查看smg
		if ($errCode == 0) { 
			$xml = new \DOMDocument();
			$xml->loadXML($sMsg); 
			$reqMsgType = $xml->getElementsByTagName('MsgType')->item(0)->nodeValue;

			$sRespData = "<xml></xml>";
			if ($reqMsgType == "text") {
				$sRespData = $this->RecMsgTypeModel-> recTypeText($sMsg);
			} elseif ($reqMsgType == "event") {
				$sRespData = $this->RecMsgTypeModel-> recTypeEvent($sMsg);
			}

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

