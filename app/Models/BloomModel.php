<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\DisposeModel;
use App\Models\UserModel;
use App\Models\ConfigModel;

class BloomModel extends Model {
//过滤Model

    public function __construct(){
		parent::__construct();
		$this->DisposeModel  = new DisposeModel();
		$this->UserModel	 = new UserModel();
        $this->ConfigModel	 = new ConfigModel();
		$this->wet_complain  = 'wet_complain';
		$this->wet_bloom     = 'wet_bloom';
	}
    
   public function ipBloom($ip)
   {//过滤IP，存在返回false
        $sql   = "SELECT bf_ip FROM $this->wet_bloom WHERE bf_ip = '$ip' LIMIT 1";
        $query = $this->db->query($sql);
        $row   = $query->getRow();
        return $row ? false : true;
    }
	
	public function txBloom($hash)
    {//过滤TX，存在返回false
        $sql   = "SELECT bf_hash FROM $this->wet_bloom WHERE bf_hash = '$hash' LIMIT 1";
        $query = $this->db->query($sql);
        $row   = $query->getRow();
        return $row ? false : true;
    }

    public function addressBloom($address)
    {//过滤address，存在返回false
        $sql   = "SELECT bf_address FROM $this->wet_bloom WHERE bf_address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
        $row   = $query->getRow();
        return $row ? false : true;
    }

    public function bloomHash($hash)
    {//过滤TX入库bloom
        $akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		$isAdmin   = $this->UserModel-> isAdmin($akToken);
		$data['code'] = 200;
		if (!$isAkToken || !$isAdmin) {
			$data['code'] = 401;
			$data['msg']  = 'error_login';
			return json_encode($data);
		}

        $isComplain = (new ComplainModel())-> isComplain($hash);
        if ($isComplain) {
            $data['code'] = 401;
			$data['msg']  = 'error_no_complain';
			return json_encode($data);
        }

        $isBloom = $this->txBloom($hash);
        if (!$isBloom) {
			$data['msg']  = 'repeat';
			return json_encode($data);
        }

        $insertBloom = "INSERT INTO $this->wet_bloom(bf_hash,bf_reason) VALUES ('$hash','admin_bf')";
        $this->db->query($insertBloom);

        $senderID = (new ComplainModel())-> complainAddress($hash);
        $bsConfig = $this->ConfigModel-> backendConfig();
        $active = $bsConfig['complainActive'];
        $this->UserModel-> userActive($senderID, $active, $e = false);

        (new ComplainModel())-> deleteComplain($hash);

		$insetrBehaviorSql = "INSERT INTO $this->wet_behavior(
                                    address, hash, thing, influence, toaddress
                                ) VALUES (
                                    '$akToken', '$hash', 'admin_bf', '-$active', '$senderID'
                                )";
        $this->db->query($insetrBehaviorSql);

        $data['msg']  = 'success';
        return json_encode($data);
    }

}

