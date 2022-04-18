<?php namespace App\Models\Wecom;

use CodeIgniter\Model;
use App\Models\ConfigModel;
use App\Models\Wecom\Callback\WXBizMsgCrypt;
use App\Models\Get\GetPriceModel;

class WecomModel extends Model {
//企业微信 Model

	public function __construct(){
		$ConfigModel   = new ConfigModel();
		$weConfig      = $ConfigModel-> wecomConfig();
		$wecomCid      = $weConfig['WECOM_CID'];
		$wecomSecret   = $weConfig['WECOM_SECRET'];
		$wecomAid_1    = $weConfig['WECOM_AID_1'];
		$wecomToken_1  = $weConfig['WECOM_TOKEN_1'];
		$wecomAesKey_1 = $weConfig['WECOM_AESKEY_1'];
		$wetrueKey     = $weConfig['WECOM_KEY'];
		$this->WXBizMsgCrypt = new WXBizMsgCrypt($wecomToken_1, $wecomAesKey_1, $wecomCid);
		$this->GetPriceModel = new GetPriceModel();
    }

	public function sendToWecom($text, $sendkey, $touser)
	{
		//$weConfig     = $this->ConfigModel-> wecomConfig();
		//$wecomCid    = $weConfig['WECOM_CID'];
		//$wecomSecret = $weConfig['WECOM_SECRET'];
		//$wecomAid_1  = $weConfig['WECOM_AID_1'];
		//$wecomkey    = $weConfig['WECOM_KEY'];
		if($wetrueKey != $sendkey) die('bad params');

		$accessToken = false;

		if (!$accessToken) {
			$info = @json_decode(file_get_contents("https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=".urlencode($wecomCid)."&corpsecret=".urlencode($wecomSecret)), true);
					
			if ($info && isset($info['access_token']) && strlen($info['access_token']) > 0) {
				$accessToken = $info['access_token'];
			}
		}

		if ($accessToken) {
			$url = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token='.urlencode($accessToken);
			$data = new \stdClass();
			$data->touser = $touser;
			$data->agentid = $wecomAid_1;
			$data->msgtype = "text";
			$data->text = ["content"=> $text];
			$data->safe = 0;
			$data->duplicate_check_interval = 600;
			/*
			$data = new \stdClass();
			$data->touser = $touser;
			$data->agentid = $wecomAid_1;
			$data->msgtype = "textcard";
			$data->textcard = ["title"=> $title];
			$data->textcard = ["description"=> $text];
			$data->textcard = ["url"=> $url];
			$data->textcard = ["btntxt"=> '更多'];			
			*/

			$data_json = json_encode($data);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);

			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			
			$response = curl_exec($ch);
			return $response;
		}

		$err = new \stdClass();
		$err->errcode = 1;
		$err->errmsg  = "error";
		$err->msgid   = "";
		return json_encode($err);
	}

	public function receFromWecom($sReqMsgSig, $sReqTimeStamp, $sReqNonce, $sReqData)
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
				$mycontent ="别装了，我知道你不是！";
			} elseif (strtoupper($reqContent) == "AE") {
				$mycontent = $this->GetPriceModel-> aePrice();
			} elseif (substr($reqContent, 0, 6) == "绑定") {
				$mycontent = substr($reqContent, 6);
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

