<?php namespace App\Models;

use CodeIgniter\Model;

class DisposeModel extends Model {

    public function checkAddress($address)
    {//校验地址
        $hex = $this->base58_decode($address);
        if (strlen($hex)!=72){
            return false;
        }
        $substr = hex2bin(substr($hex,0,strlen($hex)-8));
        $checksum = hash('sha256', hash('sha256', $substr, true));
        $checksum = substr($checksum, 0, 8);
        if(substr($hex, 64, 72)!==$checksum){
            return false;
        }
        return true;
    }

    public function addressToHex($address)
    {//地址转公钥
        $hex = $this->base58_decode($address);
        if (strlen($hex) != 72){
            return false;
        }
        $hex = substr($hex, 0, 64);
        return $hex;
    }

    public function base58_decode($address)
    {//ak_地址base58
        $base58 = str_replace("ak_","",$address);
        $base58 = str_replace("th_","",$base58);

            $origbase58 = $base58;
            $return = "0";
        
            for ($i = 0; $i < strlen($base58); $i++) {
                // return = return*58 + current position of $base58[i]in self::$base58chars
                $return = gmp_add(gmp_mul($return, 58), strpos("123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz", $base58[$i]));
            }
            $return = gmp_strval($return, 16);
            for ($i = 0; $i < strlen($origbase58) && $origbase58[$i] == "1"; $i++) {
                $return = "00" . $return;
            }
            if (strlen($return) % 2 != 0) {
                $return = "0" . $return;
            }
            return $return;
    }

    public function hexToAddress($hex)
    {//公钥 转 ak_地址
        $str = hex2bin($hex);
        $bs = substr($str, 0, 64);
        $checksum = hash("sha256", hash("sha256", $bs, true));
        $checksum = substr($checksum, 0, 8);
        $string = hex2bin($hex.$checksum);  

        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);
        if (is_string($string) === false || !strlen($string)) {
            return false;
        }

        $bytes = array_values(unpack('C*', $string));
        $decimal = $bytes[0];
        for ($i = 1, $l = count($bytes); $i < $l; ++$i) {
            $decimal = bcmul($decimal, 256);
            $decimal = bcadd($decimal, $bytes[$i]);
        }

        $output = '';
        while ($decimal >= $base) {
            $div = bcdiv($decimal, $base, 0);
            $mod = bcmod($decimal, $base);
            $output .= $alphabet[$mod];
            $decimal = $div;
        }
        if ($decimal > 0) {
            $output .= $alphabet[$decimal];
        }
        $output = strrev($output);

        return (string) $output;
    }

    public function decodePayload($payload)
	{//解码Payload内容
        $hex  = bin2hex(base64_decode(str_replace("ba_","",$payload)));
        $bin  = hex2bin(substr($hex,0,strlen($hex)-8));
		$json = (array) json_decode($bin,true);
        return $json;
    }

	public function versionCompare($versionA,$versionB)
	{/*版本号比较
	*    @param $version1 版本A 如:5.3.2 
	*    @param $version2 版本B 如:5.3.0 
	*    @return int -1版本A小于版本B , 0版本A等于版本B, 1版本A大于版本B
	*
	*    版本号格式注意：
	*        1.要求只包含:点和大于等于0小于等于2147483646的整数 的组合
	*        2.boole型 true置1，false置0
	*        3.不设位默认补0计算，如：版本号5等于版号5.0.0
	*        4.不包括数字 或 负数 的版本号 ,统一按0处理 */
		if ($versionA>2147483646 || $versionB>2147483646) {
			return false;
		}
		$verListA = explode('.', (string) $versionA);
		$verListB = explode('.', (string) $versionB);

		$len = max(count($verListA),count($verListB));
		$i = -1;
		while ($i++<$len) {
			$verListA[$i] = intval(@$verListA[$i]);
			if ($verListA[$i] < 0 ) {
				$verListA[$i] = 0;
			}
			$verListB[$i] = intval(@$verListB[$i]);
			if ($verListB[$i] < 0 ) {
				$verListB[$i] = 0;
			}

			if ($verListA[$i]>$verListB[$i]) {
                return true;
			}
			if ($verListA[$i]<$verListB[$i]) {
                return false;
			}
			if ($i==($len-1)) {
                return true;
			}
		}
	}
}

