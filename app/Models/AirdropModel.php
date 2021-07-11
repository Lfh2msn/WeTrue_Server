<?php 
namespace App\Models;

use App\Models\ComModel;
use App\Models\AecliModel;
use App\Models\ValidModel;

class AirdropModel extends ComModel
{//空投Model

	public function __construct()
	{
		parent::__construct();
		$this->session    = \Config\Services::session();
		$this->AecliModel = new AecliModel();
		$this->ValidModel = new ValidModel();
		$this->wet_bloom  = 'wet_bloom';
		$this->wet_users  = 'wet_users';
    }

	public function airdropAE($address)
	{//新用户空投AE
		$bsConfig  = (new ConfigModel())-> backendConfig();
		$isAirdrop = $bsConfig['airdropAE'];
		$amount    = $bsConfig['airdropAeAmount'];
		$NewUser   = $this->session-> get('NewUser');
		$getIP	   = (new DisposeModel())-> getRealIP();
		$ipBloom   = (new BloomModel())-> ipBloom($getIP);

		if ($ipBloom || $NewUser == 'Repeat' || !$isAirdrop) {
			if ($isAirdrop) {
				$this->session-> set("NewUser","Repeat");
			}
			return "Repeat IP OR Off Airdrop";
		}

		$url = $bsConfig['backendServiceNode'].'v3/accounts/'.$address;
		@$GetUrl = file_get_contents($url);

		if (!$GetUrl) {
			/*$AeasyApiUrl = $bsConfig['AeasyApiUrl'];
			$post_data   = array(
				'app_id'     => $bsConfig['AeasyAppID'],
				'address'    => $address,
				'amount'     => $amount,
				'signingKey' => $bsConfig['AeasySecretKey'],
			);
			$postdata = http_build_query($post_data);
			$options  = array(
							'http' => array(
											'method'  => 'POST',
											'header'  => 'Content-type:application/x-www-form-urlencoded',
											'content' => $postdata,
											'timeout' => 30 // 超时时间（单位:s）
									));
			$context = stream_context_create($options);
			$result  = file_get_contents($AeasyApiUrl, false, $context);
			$dejson  = (array) json_decode($result, true);
			$code    = $dejson['code'];*/
			
			$hash = $this->AecliModel-> spendAE($address, $amount);
			$code = (new DisposeModel())-> checkAddress($hash) ? 200 : 406;
			if ($code == 200) {
				$this->session ->set("NewUser","Repeat");
				$inSql = "INSERT INTO $this->wet_bloom(bf_ip, bf_reason) VALUES ('$getIP','airdropAE')";
				$this->db->query($inSql);
			}
		}
	}

	public function airdropWTT($opt = [])
	{//空投WTT写入txt
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = (new DisposeModel())-> checkAddress($akToken);
		$isAdmin   = $this->ValidModel-> isAdmin($akToken);
		$data['code'] = 200;
		if (!$isAkToken || !$isAdmin) {
			$data['code'] = 401;
			$data['msg']  = 'error_login';
			return json_encode($data);
		}

		if($opt['type'] == "Reset") {
			//重写 lists.txt
			$File = fopen("airdrop/WTT/".date("Y-m-d").".txt","w");
			$Text = "";
			fwrite($File, $Text);
			fclose($File);
		}

		$selSql    = "SELECT address, uactive, last_active FROM $this->wet_users";
        $query     = $this->db-> query($selSql);
		$bsConfig  = (new ConfigModel())-> backendConfig();

		foreach ($query->getResult() as $row) {
			if($row->uactive != $row->last_active) {
				$uactive    = $row->uactive;
				$lastActive = $row->last_active;
				$address    = $row->address;
				$userBloom  = (new BloomModel())-> addressBloom($address);
				$uaValue	= $uactive - $lastActive;
				if( $address != "" || !$userBloom || $uactive >= $lastActive) {
					$textFile   = fopen("airdrop/WTT/".date("Y-m-d").".txt","a");
					$appendText = $address.":".($uaValue * $bsConfig['airdropWTTRatio'])."\r\n";
					fwrite($textFile, $appendText);
					fclose($textFile);
				}

				if($uactive >= $lastActive || !$userBloom) {
					$upSql = "UPDATE $this->wet_users SET last_active = uactive WHERE address = '$address'";
					$this->db->query($upSql);
				}
			}
		}

		$readFile = file("airdrop/WTT/".date("Y-m-d").".txt"); //返回数组的内容
		$list = [];
		foreach ($readFile as $v) {
			array_push($list, $v);
		}
		return json_encode($list);
	}

}

