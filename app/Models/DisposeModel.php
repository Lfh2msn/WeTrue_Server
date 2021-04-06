<?php namespace App\Models;

use CodeIgniter\Model;

class DisposeModel extends Model {

	public function decodePayload($payload)
	{//解码Payload内容
        $hex  = bin2hex(base64_decode(str_replace("ba_","",$payload)));
        $bin  = hex2bin(substr($hex,0,strlen($hex)-8));
		$json = (array) json_decode($bin,true);
        return $json;
    }

}

