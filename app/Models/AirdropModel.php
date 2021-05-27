<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\ConfigModel;
use App\Models\UserModel;
use App\Models\DisposeModel;
use App\Models\BloomModel;

class AirdropModel extends Model {
//空投Model

	protected $session;

	public function __construct(){
        parent::__construct();
		$this->ConfigModel  = new ConfigModel();
		$this->UserModel    = new UserModel();
		$this->DisposeModel = new DisposeModel();
		$this->BloomModel   = new BloomModel();
		$this->session		= \Config\Services::session();
    }

	public function airdropAE($address)
	{//新用户空投AE
		$bsConfig  = $this->ConfigModel-> backendConfig();
		$isAirdrop = $bsConfig['AeasyAirdropAE'];
		$NewUser   = $this->session ->get('NewUser');
		$getIP	   = $this->DisposeModel ->getRealIP();
		$ipBloom   = $this->BloomModel ->ipBloom($getIP);

		if ($ipBloom || $NewUser || !$isAirdrop) {
			if ($isAirdrop) {
				$this->session ->set("NewUser","Repeat",time()+365*24*60*60);
			}
			return;
		}

		$url = $bsConfig['backendServiceNode'].'v2/accounts/'.$address;
		@$GetUrl = file_get_contents($url);
		if (!$GetUrl) {
			$AeasyApiUrl = $bsConfig['AeasyApiUrl'];
			$post_data   = array(
				'app_id'     => $bsConfig['AeasyAppID'],
				'address'    => $address,
				'amount'     => $bsConfig['AeasyAmount'],
				'signingKey' => $bsConfig['AeasySecretKey'],
			);
			$postdata = http_build_query($post_data);
			$options  = array(
							'http' => array(
											'method'  => 'POST',
											'header'  => 'Content-type:application/x-www-form-urlencoded',
											'content' => $postdata,
											'timeout' => 15 * 60 // 超时时间（单位:s）
									));
			$context = stream_context_create($options);
			$result  = file_get_contents($AeasyApiUrl, false, $context);
			$dejson  = (array) json_decode($result, true);
			$code    = $dejson['code'];
			if ($code == 200) {
				$this->session ->set("NewUser","Repeat",time()+365*24*60*60);
				$inSql = "INSERT INTO wet_bloom(bf_ip, bf_reason) VALUES ('$getIP','airdropAE')";
				$this->db->query($inSql);
			}
		}
	}

}

