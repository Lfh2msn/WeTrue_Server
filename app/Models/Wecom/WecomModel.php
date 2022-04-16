<?php namespace App\Models\Wecom;

use CodeIgniter\Model;
use App\Models\ConfigModel;

class WecomModel extends Model {
//企业微信 Model

	public function __construct(){
		$this->db = \Config\Database::connect('default');
		$this->ConfigModel = new ConfigModel();
    }

	public function sendToWecom($text, $sendkey, $wecom_touid)
	{
		$weConfig     = $this->ConfigModel-> wecomConfig();
		$wecom_cid    = $weConfig['WECOM_CID'];
		$wecom_secret = $weConfig['WECOM_SECRET'];
		$wecom_aid    = $weConfig['WECOM_AID'];
		$wecom_key    = $weConfig['WECOM_KEY'];
		if($wecom_key != $sendkey) die('bad params');

		$access_token = false;

		if (!$access_token) {
			$info = @json_decode(file_get_contents("https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=".urlencode($wecom_cid)."&corpsecret=".urlencode($wecom_secret)), true);
					
			if ($info && isset($info['access_token']) && strlen($info['access_token']) > 0) {
				$access_token = $info['access_token'];
			}
		}

		if ($access_token) {
			$url = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token='.urlencode($access_token);
			$data = new \stdClass();
			$data->touser = $wecom_touid;
			$data->agentid = $wecom_aid;
			$data->msgtype = "text";
			$data->text = ["content"=> $text];
			$data->duplicate_check_interval = 600;

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
		$err->errcode = 0;
		$err->errmsg  = "error";
		$err->msgid   = "";
		return json_encode($err);
	}
}

