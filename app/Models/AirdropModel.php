<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	AecliModel,
	ValidModel,
	DisposeModel
};

class AirdropModel extends ComModel
{//空投Model

	public function __construct()
	{
		parent::__construct();
		$this->session      = \Config\Services::session();
		$this->AecliModel   = new AecliModel();
		$this->ValidModel   = new ValidModel();
		$this->DisposeModel = new DisposeModel();
		$this->wet_bloom    = "wet_bloom";
		$this->wet_users    = "wet_users";
    }

	public function airdropAE($address)
	{//新用户空投AE
		$bsConfig  = (new ConfigModel())-> backendConfig();
		$isAirdrop = $bsConfig['airdropAE'];
		$amount    = $bsConfig['airdropAeAmount'];
		$NewUser   = $this->session-> get('NewUser');
		$getIP	   = $this->DisposeModel-> getRealIP();
		$isBloomIp   = $this->ValidModel-> isBloomIp($getIP);

		if ($isBloomIp || $NewUser == 'Repeat' || !$isAirdrop) {
			if ($isAirdrop) {
				$this->session-> set("NewUser","Repeat");
			}
			return "Repeat IP OR Off Airdrop";
		}

		$url = $bsConfig['backendServiceNode'].'/v3/accounts/'.$address;
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
			$code = $this->DisposeModel-> checkAddress($hash) ? 200 : 406;
			if ($code == 200) {
				$this->session ->set("NewUser","Repeat");
				$inSql = "INSERT INTO $this->wet_bloom(bf_ip, bf_reason) VALUES ('$getIP','airdropAE')";
				$this->db->query($inSql);
			}
		}
	}

	public function airdropWTT($opt = [])
	{//空投WTT写入txt
		$akToken   = $_SERVER['HTTP_KEY'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
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

		$bsConfig = (new ConfigModel())-> backendConfig();
		$selSql   = "SELECT address, uactive, last_active FROM $this->wet_users";
        $query    = $this->db-> query($selSql);
		$getRes	  = $query->getResult();
		foreach ($getRes as $row) {
			if($row->uactive != $row->last_active) {
				$uactive    = $row->uactive;
				$lastActive = $row->last_active;
				$address    = $row->address;
				$uaValue	= $uactive - $lastActive;
				$isBloomAddress = $this->ValidModel-> isBloomAddress($address);
				if( $address != "" && !$isBloomAddress && $uactive >= $lastActive) {
					$logMsg = $address.":".($uaValue * $bsConfig['airdropWTTRatio'])."\r\n";
					$logPath = "airdrop/WTT/".date("Y-m-d").".txt"
					$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
				}

				if($uactive >= $lastActive && !$isBloomAddress) {
					$upSql = "UPDATE $this->wet_users SET last_active = uactive WHERE address = '$address'";
					$this->db->query($upSql);
				}
			}
		}

		return $this->DisposeModel-> wetJsonRt(200, 'success');
	}

}

