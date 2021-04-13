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
        return (bool)$a=true;
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

}

