<?php namespace App\Models\Wecom;

use App\Models\ConfigModel;

class SendModel {
//企业微信发送 Model

	public function __construct() {
		$this->db = \Config\Database::connect('default');
		$this->$ConfigModel = new ConfigModel();
		$this->wecom_token  = "wet_wecom_token";
    }

	public function sendToWecom($text, $sendKey, $touser)
	{
		$weConfig    = $this->$ConfigModel-> wecomConfig();
		$wecomCid    = $weConfig['WECOM_CID'];
		$wecomSecret = $weConfig['WECOM_SECRET'];
		$wecomAid_1  = $weConfig['WECOM_AID_1'];
		$wetrueKey   = $weConfig['WECOM_KEY'];

		if($wetrueKey != $sendKey) die('bad params');

		$sql   = "SELECT access_token FROM $this->wecom_token WHERE token_time >= now()-interval '110 M' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		$accessToken = $row->access_token ?? false;

		if (!$accessToken) {
			$url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=".urlencode($wecomCid)."&corpsecret=".urlencode($wecomSecret);
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
			$data->msgtype = "text";
			$data->text = ["content"=> $text];
			$data->safe = 0;
			$data->duplicate_check_interval = 600;
			
			//$data = new \stdClass();
			//$data->touser = $touser;
			//$data->agentid = $wecomAid_1;
			//$data->msgtype = "textcard";
			//$data->textcard = ["title"=> $title];
			//$data->textcard = ["description"=> $text];
			//$data->textcard = ["url"=> $url];
			//$data->textcard = ["btntxt"=> '更多'];			

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


