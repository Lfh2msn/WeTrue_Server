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
		echo (new WecomModel())->sendToWecom($text, $sendkey, $wecom_touid);
    }

}