<?php namespace App\Models;

use CodeIgniter\Model;

class BloomModel extends Model {
	
   public function ipBloom($ip)
   {//过滤IP
        $sql   = "SELECT bf_ip FROM wet_bloom WHERE bf_ip = '$ip' LIMIT 1";
        $query = $this->db->query($sql);
        $row   = $query->getRow();
        return $row ? false : true;
    }
	
	public function txBloom($hash)
    {//过滤TX
        $sql   = "SELECT bf_hash FROM wet_bloom WHERE bf_hash = '$hash' LIMIT 1";
        $query = $this->db->query($sql);
        $row   = $query->getRow();
        return $row ? false : true;
    }

    public function addressBloom($address)
    {//过滤TX
        $sql   = "SELECT bf_address FROM wet_bloom WHERE bf_address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
        $row   = $query->getRow();
        return $row ? false : true;
    }
}

