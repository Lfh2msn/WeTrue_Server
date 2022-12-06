<?php namespace App\Models\Wecom;

use Config\Database;
use App\Models\Config\WecomConfig;

class SendModel {
//企业微信发送 Model

	public function __construct() {
		$this->db = Database::connect('default');
		$this->WecomConfig = new WecomConfig();
		$this->wecom_token = "wet_wecom_token";
    }

	public function sendToWecom($payload, $sendKey, $touser)
	{
		$weConfig      = $this->WecomConfig-> config();
		$wecomCid_1    = $weConfig['WECOM_CID_1'];
		$wecomSecret_1 = $weConfig['WECOM_SECRET_1'];
		$wecomAid_1    = $weConfig['WECOM_AID_1'];
		$wetrueKey_1   = $weConfig['WETRUE_KEY_1'];

		if($wetrueKey_1 != $sendKey) die('bad params');

		$sql   = "SELECT access_token FROM $this->wecom_token WHERE token_time >= now()-interval '110 M' LIMIT 1";
		$query = $this->db->query($sql);
		$row   = $query->getRow();
		$accessToken = $row->access_token ?? false;

		if (!$accessToken) {
			$url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=".urlencode($wecomCid_1)."&corpsecret=".urlencode($wecomSecret_1);
			$info = @json_decode(file_get_contents($url), true);
			if ($info && isset($info['access_token']) && strlen($info['access_token']) > 0) {
				$accessToken = $info['access_token'];
				$this->deleteTokenTemp();
				$this->insertTokenTemp($accessToken);
			}
		}

		if ($accessToken) {
			$url = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=".urlencode($accessToken);
			$data = new \stdClass();
			$data->touser = $touser;
			$data->agentid = $wecomAid_1;
			$data->msgtype = $payload['msgtype'];

			if ($data->msgtype == "text"){
				$data->text = ["content" => $payload['content']];
				$data->safe = 0;
				$data->duplicate_check_interval = 600;

			} elseif ($data->msgtype == "textcard") {
				$data->textcard = [
						"title" 	  => $payload['title'],
						"description" => $payload['description'],
						"url" 		  => $payload['url'],
						"btntxt" 	  => $payload['btntxt'] ?? '更多'
					];
			}
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

	private function insertTokenTemp($access_token)
	{//写入临时缓存
		$insertSql = "INSERT INTO $this->wecom_token(access_token) VALUES ('$access_token')";
		$this->db->query($insertSql);
	}

	private function deleteTokenTemp()
	{//删除临时缓存
		$delete = "DELETE FROM $this->wecom_token";
		$this->db->query($delete);
	}
}


