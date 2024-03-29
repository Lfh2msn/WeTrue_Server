<?php 
namespace App\Models;

use Config\Services;
use App\Models\{
	ComModel,
	AecliModel,
	ValidModel,
	DisposeModel
};
use App\Models\Config\AirdropConfig;

class AirdropModel
{//空投Model

	public function __construct()
	{
		$this->session      = Services::session();
		$this->AecliModel   = new AecliModel();
		$this->wet_bloom    = "wet_bloom";
		$this->wet_users    = "wet_users";
    }

	public function airdropAE($address)
	{//新用户空投AE
		$aacConfig  = AirdropConfig::config();
		$isAirdrop = $aacConfig['aeOpen'];
		$amount    = $aacConfig['aeAmount'];
		$NewUser   = $this->session-> get('NewUser');
		$getIP	   = DisposeModel::getRealIP();
		$isBloomIp = ValidModel::isBloomIp($getIP);

		if ($isBloomIp || $NewUser == 'Repeat' || !$isAirdrop) {
			if ($isAirdrop) {
				$this->session-> set("NewUser","Repeat");
			}
			return "Repeat IP OR Off Airdrop";
		}

		$url = $bsConfig['backendServiceNode'].'/v3/accounts/'.$address;
		@$GetUrl = file_get_contents($url);

		if (!$GetUrl) {
			$hash = $this->AecliModel-> spendAE($address, $amount);
			$code = DisposeModel::checkAddress($hash) ? 200 : 406;
			if ($code == 200) {
				$this->session ->set("NewUser","Repeat");
				$inSql = "INSERT INTO $this->wet_bloom(bf_ip, bf_reason) VALUES ('$getIP','airdropAE')";
				ComModel::db()->query($inSql);
			}
		}
	}

	public function airdropWTT($opt = [])
	{//空投WTT写入txt
		$akToken   = isset($_SERVER['HTTP_KEY']) ? $_SERVER['HTTP_KEY'] : false;
		$isAkToken = DisposeModel::checkAddress($akToken);
		$isAdmin   = ValidModel::isAdmin($akToken);
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

		$aacConfig  = AirdropConfig::config();
		$selSql   = "SELECT address, uactive, last_active FROM $this->wet_users";
        $query    = ComModel::db()-> query($selSql);
		$getRes	  = $query->getResult();
		foreach ($getRes as $row) {
			if($row->uactive != $row->last_active) {
				$uactive    = $row->uactive;
				$lastActive = $row->last_active;
				$address    = $row->address;
				$uaValue	= $uactive - $lastActive;
				$isBloomAddress = ValidModel::isBloomAddress($address);
				if( $address != "" && !$isBloomAddress && $uactive >= $lastActive) {
					$logMsg = $address.":".($uaValue * $aacConfig['wttRatio'])."\r\n";
					$logPath = "airdrop/WTT/".date("Y-m-d").".txt"
					DisposeModel::wetFwriteLog($logMsg, $logPath);
				}

				if($uactive >= $lastActive && !$isBloomAddress) {
					$upSql = "UPDATE $this->wet_users SET last_active = uactive WHERE address = '$address'";
					ComModel::db()->query($upSql);
				}
			}
		}

		return DisposeModel::wetJsonRt(200, 'success');
	}

}

