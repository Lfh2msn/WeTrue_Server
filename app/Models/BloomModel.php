<?php namespace App\Models;

use CodeIgniter\Model;

class BloomModel extends Model {
	
   public function ipBloom($ip)
   {//过滤IP
        $sql="SELECT bf_ip FROM wet_bloom WHERE bf_ip='$ip' LIMIT 1";
        $query = $this->db->query($sql);
        
        if($query->getRow()){
            $hash_filter = true;
        }else{
            $hash_filter = false;
        }
        return $hash_filter;
    }
	
	public function txBloom($txhash)
    {//过滤TX
        $sql="SELECT bf_hash FROM wet_bloom WHERE bf_hash='$txhash' LIMIT 1";
        $query = $this->db->query($sql);

        if(!$query->getRow()){
            $hash_filter = true;
        }else{
            $hash_filter = false;
        }
        return $hash_filter;
    }
}

