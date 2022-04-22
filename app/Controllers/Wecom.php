<?php
namespace App\Controllers;

use App\Models\Wecom\SendModel;
use App\Models\Wecom\ReceiveModel;

class Wecom extends BaseController {

	public function send($content, $sendKey, $touser = '@all')
	{ //推送到微信企业应用
		// Wecom/send/要发送到内容/sendkey/Liu|LiuShao
		header("Content-Type: application/json; charset=UTF-8");
		if (strlen(@$sendKey) < 1 || strlen(@$content) < 1) {
			die('bad params');
		}
		$payload = [
			'msgtype' => 'text',
			'content' => $content
		];
		$req = (new SendModel())-> sendToWecom($payload, $sendKey, $touser);
		echo $req;
    }

	public function receive()
	{
		$method = $this->request->getMethod(true);
		if ($method == "GET") {
			$this-> VerifyURL();
		} elseif ($method == "POST") {
			$this-> receivePost();
		}
	}

	public function receivePost()
	{	
		$sReqData   = file_get_contents("php://input");
		$sReqMsgSig = $this->request->getGet('msg_signature');
		$sReqNonce  = $this->request->getGet('nonce');
		$sReqTimeStamp = $this->request->getGet('timestamp');
		$req = (new ReceiveModel())->recFromWecom($sReqMsgSig, $sReqTimeStamp, $sReqNonce, $sReqData);
		echo $req;
	}

	public function VerifyURL()
	{	
		$sVerifyMsgSig    = $this->request->getGet('msg_signature');
		$sVerifyTimeStamp = $this->request->getGet('timestamp');
		$sVerifyNonce     = $this->request->getGet('nonce');
		$sVerifyEchoStr   = $this->request->getGet('echostr');
		$req = (new ReceiveModel())->verifyURLWecom($sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce, $sVerifyEchoStr);
		echo $req;
	}

}