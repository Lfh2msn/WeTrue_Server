<?php
namespace App\Controllers;

use App\Models\Wecom\WecomModel;

class Wecom extends BaseController {

	public function send($text, $sendkey, $wecom_touid = '@all')
	{ //推送到微信企业应用
		// Wecom/send/要发送到内容/sendkey/Liu|LiuShao
		if (strlen(@$sendkey) < 1 || strlen(@$text) < 1) {
			die('bad params');
		}
		header("Content-Type: application/json; charset=UTF-8");
		print(new WecomModel())->sendToWecom($text, $sendkey, $wecom_touid);
    }

	public function receive()
	{
		//header("Content-Type: application/json; charset=UTF-8");
		$method = $this->request->getMethod(true);
		if ($method == "GET") {
			$this->VerifyURL();
		} elseif ($method == "POST") {
			$this-> receivePost();
		}
	}

	public function receivePost()
	{

		$sReqMsgSig = $this->request->getGet('msg_signature');
		$sReqTimeStamp = $this->request->getGet('timestamp');
		$sReqNonce  = $this->request->getGet('nonce');
		$sReqData   = file_get_contents("php://input");
		print(new WecomModel())->receFromWecom($sReqMsgSig, $sReqTimeStamp, $sReqNonce, $sReqData);

	}

	public function VerifyURL()
	{	
		$sVerifyMsgSig    = $this->request->getGet('msg_signature');
		$sVerifyTimeStamp = $this->request->getGet('timestamp');
		$sVerifyNonce     = $this->request->getGet('nonce');
		$sVerifyEchoStr   = $this->request->getGet('echostr');

		print(new WecomModel())->verifyURLWecom($sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce, $sVerifyEchoStr);

	}

}