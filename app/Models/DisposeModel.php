<?php namespace App\Models;

use CodeIgniter\Model;

class DisposeModel extends Model {
//数据处理Model

    public function checkAddress($address)
    {//校验地址
        $hex = $this->base58_decode($address);
        if (strlen($hex)!=72) {
            return false;
        }
        $substr = hex2bin(substr($hex,0,strlen($hex)-8));
        $checksum = hash('sha256', hash('sha256', $substr, true));
        $checksum = substr($checksum, 0, 8);
        if (substr($hex, 64, 72)!==$checksum) {
            return false;
        }
        return true;
    }

    public function addressToHex($address)
    {//地址转公钥
        $hex = $this->base58_decode($address);
        if (strlen($hex) != 72) {
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
        
            for ($i = 0; $i < strlen($base58); $i++){
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
        $json = (array) json_decode($bin, true);
        return $json;
    }

    public function activeGrade($number)
	{//活跃度等级划分
		(int)$number;
        if ( $number >= 50000 ) {
            $Grade = 9;
        } elseif ( $number >= 20000 ) {
            $Grade = 8;
        } elseif ( $number >= 10000 ) {
            $Grade = 7;
        } elseif ( $number >= 5000 ) {
            $Grade = 6;
        } elseif ( $number >= 2000 ) {
            $Grade = 5;
        } elseif ( $number >= 500 ) {
            $Grade = 4;
        } elseif ( $number >= 200 ) {
            $Grade = 3;
        } elseif ( $number >= 100 ) {
            $Grade = 2;
        } else {
            $Grade = 1;
        }
		return $Grade;
    }

    public function rewardGrade($number)
	{//打赏金额等级划分
		(int)$number = ($number / 1e18);
        if ( $number >= 10000000 ) {
            $Grade = 6;
        } elseif ( $number >= 5000000 ) {
            $Grade = 5;
        } elseif ( $number >= 100000 ) {
            $Grade = 4;
        } elseif ( $number >= 50000 ) {
            $Grade = 3;
        } elseif ( $number >= 10000 ) {
            $Grade = 2;
        } elseif ( $number >= 1000 ) {
            $Grade = 1;
        } else {
            $Grade = 0;
        }
		return $Grade;
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
	*        4.不包括数字 或 负数 的版本号 ,统一按0处理
    */
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

    public function getRealIP()
    {//获取IP
        $ip = FALSE;
        if(!empty($_SERVER["HTTP_CLIENT_IP"])){
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }
            for ($i = 0; $i < count($ips); $i++) {
                if (!eregi ("^(10│172.16│192.168).", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }

    public function delete_xss($string)
    {//xss删除函数
        $string = strip_tags($string);
        $string = htmlspecialchars($string, ENT_QUOTES);
        $string = str_replace("\n\n\n","\n",$string);
        $string = str_replace("\n\n\n","\n",$string);
        $string = str_replace("\n","<br>",$string);
        return $string;
    }

    public function remove_xss($string)
    {/*xss过滤函数
    *   @param $string
    *   @return string
    */
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $string);
        $parm1 = Array('textarea', 'javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
        $parm2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
        $parm = array_merge($parm1, $parm2);
        for ($i = 0; $i < sizeof($parm); $i++) {
        $pattern = '/';
        for ($j = 0; $j < strlen($parm[$i]); $j++) {
            if ($j > 0) {
                $pattern .= '(';
                $pattern .= '(&#[x|X]0([9][a][b]);?)?';
                $pattern .= '|(&#0([9][10][13]);?)?';
                $pattern .= ')?';
            }
            $pattern .= $parm[$i][$j];
        }
        $pattern .= '/i';
        $string = preg_replace($pattern, ' ', $string);
        }
        return $string;
    }

    public function sensitive($string)
    {/*敏感词过滤
    * @todo 敏感词过滤，返回结果
    * @param array $list  定义敏感词一维数组
    * @param string $string 要过滤的内容
    * @return string $log 处理结果
    */
        $sensitive = file_get_contents("keyWords.txt"); // 读取关键字文本信息
        $matchingList = explode("\n",$sensitive); // 把关键字转换为数组

        $count        = 0; //违规词的个数
        //$sensitiveWord = '';  //违规词
        $stringAfter  = $string;  //替换后的内容
        $pattern      = "/".implode("|", $matchingList)."/i"; //定义正则表达式
        if(preg_match_all($pattern, $string, $matches)){ //匹配到了结果
            $patternList = $matches[0];  //匹配到的数组
            $count       = count($patternList);
            //$sensitiveWord = implode(',', $patternList); //敏感词数组转字符串
            $replaceArray  = array_combine($patternList,array_fill(0, count($patternList), '**')); //把匹配到的数组进行合并，替换使用
            $stringAfter   = strtr($string, $replaceArray); //结果替换
        }

        if ($count == 0) {
            $log = $string;
        } else {
            $log = $stringAfter."<br><br>Tips:{$count} sensitive word.";
        }
        return $log;
    }

}