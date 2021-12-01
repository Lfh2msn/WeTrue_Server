<?php

function url($uri, $query = null, $self = SELF)
{
    if (!$uri) {
        return $self;
    }

    $uri = trim($uri, '/');

    if ($self == 'index.php') {
        $uri = '/' . $uri;
    } else {
        $uri = '/' . $self . '/' . $uri;
    }

    $url = $uri;

    if (is_array($query) && $query) {
        $param = @http_build_query($query);
    } else {
        $param = $query;
    }
    if ($param) {
        return $url . '?' . $param;
    }
    return $url;
}

//生成uid9位
function wet_create_uid($len = 8)
{
    $str = substr((string) microtime(), 2, 6);
    $arr = str_split($str, 1);
    if ($len == 9) {
        $result = mt_rand(100, 999);
    }
    if ($len == 8) {
        $result = mt_rand(10, 99);
    }
    foreach ($arr as $k => $v) {
        if ($v == 0) {
            $v = mt_rand(1, 9);
        }
        $result .= $v;
    }
    return $result;
}

/**
 * 获取随机字符串
 * @param int $randLength 长度
 * @param int $addtime 是否加入当前时间戳
 * @param int $includenumber 是否包含数字
 * @return string
 */
function wet_randstr($randLength = 12, $addtime = 1, $includenumber = 1)
{
    $tokenvalue = '';
    if ($includenumber) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHJKLMNPQESTUVWXYZ123456789';
    } else {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHJKLMNPQESTUVWXYZ';
    }
    $len = strlen($chars);
    $randStr = '';
    for ($i = 0; $i < $randLength; $i++) {
        $randStr .= $chars[mt_rand(0, $len - 1)];
    }
    $tokenvalue = $randStr;
    if ($addtime) {
        $tokenvalue = $randStr . time();
    }
    return $tokenvalue;
}

//生成邀请码
function wet_invite_code($length = 12, $other = '')
{
    if ($other) {
        $other = strtoupper($other);
    }
    $length = $length - 2;
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' . $other;
    $chars = str_shuffle($chars);
    $num = $length < strlen($chars) - 1 ? $length : str_len($chars) - 1;
    $str = mt_rand(1, 9) . substr($chars, 0, $num) . mt_rand(1, 9);
    return $str;
}

function wet_salt()
{
    $str = substr(md5(rand(123, 999)), 0, 10);
    return $str;
}

function wet_rt($code = 0, $msg = 'success', $data = null)
{
    $rt = [
        'code' => $code,
        'msg'  => $msg ? esc($msg) : '',
        'data' => $data,
    ];
    return $rt;
}

function wet_json($code = 0, $msg = 'success', $data = null)
{
    $data = [
        'code' => intval($code),
        'msg'  => $msg ? esc($msg) : '',
        'data' => isset($data) ? $data : null,
    ];
    echo json_encode($data);
    exit();
}

function wet_redirect($url = '', $method = 'auto', $code = null)
{
    switch ($method) {
        case 'refresh':
            header('Refresh:0;url=' . $url);
            break;
        default:
            header('Location: ' . $url, true, $code);
            break;
    }
    exit;
}

function wet_isphone($phone)
{
    if (!is_numeric($phone) || !$phone || strlen($phone) != 11) {
        return false;
    }
    $isMob = "/^1[3456789]\d{9}$/";
    return preg_match($isMob, $phone) ? true : false;
}

//域名补上协议
function wet_http_prefix($url)
{
    if (defined(SITE_HTTPS) && SITE_HTTPS) {
        $url = 'https://' . $url;
    } else {
        $url = 'http://' . $url;
    }
    return $url;
}

//订单号
function wet_ordersn()
{
    $sn = date('Ymd') . mt_rand(1, 9) . substr(microtime(), 2, 6) . mt_rand(10, 99);
    return $sn;
}

/**
 * 格式化搜索关键词参数
 */
function wet_meta_keyword($str = '')
{
    if ($str) {
        $str = str_replace(array('，', '、', '；', ';', ' ', PHP_EOL, "\r", "\n", "\r\n"), ',', $str);
    }
    return $str;
}

function wet_set_cookie($name, $value = '', $expire = WEEK)
{
    if (empty($value)) {
        return;
    }
    $name = esc($name);
    if (empty($expire) || !is_int($expire)) {
        $expire = time() + 86400;
    }
    $_COOKIE[$name] = $value;
    if (is_array($value)) {
        $value = serialize($value);
    }
    set_cookie($name, $value, $expire);
}

function wet_get_cookie($name)
{
    $name = esc($name);
    if (empty($name)) {
        return false;
    }
    if (isset($_COOKIE[$name])) {
        return $_COOKIE[$name];
    }
    $value = get_cookie($name);
    if ($value) {
        return $value;
    }
    return false;
}

/**
 * @param $val 需要过滤值
 * @return string
 */
function xss_clean($val)
{
    $val = strip_tags($val);
    $val = htmlspecialchars($val, ENT_QUOTES);
    return $val;
}

function wet_clean_xss(&$string, $low = false)
{
    if (!is_array($string)) {
        $string = trim($string);
        $string = strip_tags($string);
        $string = htmlspecialchars($string);
        if ($low) {
            return $string;
        }
        $string = str_replace(['"', "\\", "'", "/", "..", "../", "./", "//"], '', $string);
        $no = '/%0[0-8bcef]/';
        $string = preg_replace($no, '', $string);
        $no = '/%1[0-9a-f]/';
        $string = preg_replace($no, '', $string);
        $no = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';
        $string = preg_replace($no, '', $string);
        return $string;
    }
    $keys = array_keys($string);
    foreach ($keys as $key) {
        wet_clean_xss($string[$key]);
    }
    return $string;
}

/**
 * 验证码图片获取
 */
function wet_captcha($width = 120, $height = 40, $url = '')
{
    $url = url('opi/captcha', ['width' => $width, 'height' => $height], 'index.php');
    return '<img align="absmiddle" alt="点击更换验证码" style="border:1px solid #9a9a9a;cursor:pointer" title="点击更换验证码" class="imgcaptcha" style="cursor:pointer;" onclick="this.src=\'' . $url . '&\'+Math.random();" src="' . $url . '" />';
}

function wet_dir_map($source_dir, $directory_depth = 0, $hidden = false)
{
    if ($fp = @opendir($source_dir)) {
        $filedata = array();
        $new_depth = $directory_depth - 1;
        $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        while (false !== ($file = readdir($fp))) {
            if ($file === '.' or $file === '..'
                or ($hidden === false && $file[0] === '.')
                or !@is_dir($source_dir . $file)) {
                continue;
            }
            if (($directory_depth < 1 or $new_depth > 0)
                && @is_dir($source_dir . $file)) {
                $filedata[$file] = wet_dir_map($source_dir . DIRECTORY_SEPARATOR . $file, $new_depth, $hidden);
            } else {
                $filedata[] = $file;
            }
        }
        closedir($fp);
        return $filedata;
    }
    return false;
}

/*
 * 源图的路径 img 也可以是远程图片
 * 最终保存图片的宽 width
 * 最终保存图片的高 height
 */
function thumb($img = null, $width = 0, $height = 0, $water = 0)
{
    $thumb_path = WEBPATH . 'writable/uploads/thumb/';
    $default = SITE_URL . 'public/assets/image/no-img.png';
    $thumb_file = $thumb_path . md5($img) . '_' . $width . 'x' . $height . '_' . $water . '.png';

    if (!$img) {
        return $default;
    }

    $img = WEBPATH . trim($img, '/');
    //是否存在
    if (is_file($thumb_file)) {
        $thumb_file = str_replace(WEBPATH, '/', $thumb_file);
        return SITE_URL . trim($thumb_file, '/');
    } elseif (!is_file($img)) {
        return $default;
    }

    //源图对象
    $src_image = imagecreatefromstring(file_get_contents($img));
    $src_width = imagesx($src_image);
    $src_height = imagesy($src_image);
    //缩放比例计算
    if (!$width && !$height) {
        $width = $src_width;
        $height = $src_height;
    }

    if ($width > $src_width) {
        $width = $src_width;
    }
    if ($height > $src_height) {
        $height = $src_height;
    }

    $center_width = $width / 1; //按宽度缩放
    $center_height = $height / 1; //按高度缩放
    $scale = $src_width / $center_width; //缩略图的宽度缩放比(本身宽度/组合后的宽度) 按宽度缩放
    $scale2 = $src_height / $center_height; //缩略图的宽度缩放比(本身高度/组合后的高度) 按高度缩放

    if ($center_width < $src_width) {
        $center_height = $src_height / $scale; //组合之后缩略图的高度
        $center_width = $src_width / $scale; //组合之后缩略图的高度
    } else {
        $center_height = $src_height / $scale2; //组合之后缩略图的高度
        $center_width = $src_width / $scale2; //组合之后缩略图的高度
    }

    $from_width = ($width - $center_width) / 2; //组合之后缩略图左上角所在坐标点
    $from_height = ($height - $center_height) / 2;

    //生成等比例的缩略图
    $tmpImage = imagecreatetruecolor($width, $height); //生成画布
    $color = imagecolorallocatealpha($tmpImage, 255, 255, 255, 127);
    imagefill($tmpImage, 0, 0, $color);
    imagealphablending($tmpImage, false); //不合并颜色,直接用$img图像颜色替换,包括透明色;
    imagesavealpha($tmpImage, true); //不要丢了$thumb图像的透明色;

    //重新组合图片，并调整大小
    /**
     * 函数 imagecopyresampled():将一幅图像中的一块正方形区域拷贝到另一个图像中，平滑地插入像素值，因此，尤其是，减小了图像的大小而仍然保持了极大的清晰度。参数详解
     * bool imagecopyresampled ( resource $dst_image , resource $src_image , int $dst_x , int $dst_y , int $src_x , int $src_y , int $dst_w , int $dst_h , int $src_w , int $src_h )
     * dst_image 目标图象连接资源。
     * src_image 源图象连接资源。
     * dst_x 目标 X 坐标点。
     * dst_y 目标 Y 坐标点。
     * src_x 源的 X 坐标点。
     * src_y 源的 Y 坐标点。
     * dst_w 目标宽度。
     * dst_h 目标高度。
     * src_w 源图象的宽度。
     * src_h 源图象的高度。
     */
    imagecopyresampled($tmpImage, $src_image, $from_width, $from_height, 0, 0, $center_width, $center_height, $src_width, $src_height);
    //生成缩略图图片
    imagepng($tmpImage, $thumb_file);
    imagedestroy($tmpImage);
    if (is_file($thumb_file)) {
        $thumb_file = str_replace(WEBPATH, '/', $thumb_file);
        return SITE_URL . trim($thumb_file, '/');
    } else {
        return $default;
    }
}

function wet_strcut($string, $length, $dot = '...')
{

    $charset = 'utf-8';
    if (strlen($string) <= $length) {
        return $string;
    }

    $string = str_replace(['&amp;', '&quot;', '&lt;', '&gt;'], ['&', '"', '<', '>'], $string);
    $strcut = '';

    if (strtolower($charset) == 'utf-8') {
        $n = $tn = $noc = 0;
        while ($n < strlen($string)) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n++;
                $noc++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t <= 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n++;
            }
            if ($noc >= $length) {
                break;
            }

        }
        if ($noc > $length) {
            $n -= $tn;
        }

        $strcut = substr($string, 0, $n);
    } else {
        for ($i = 0; $i < $length; $i++) {
            $strcut .= ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
        }
    }

    $strcut = str_replace(['&', '"', '<', '>'], ['&amp;', '&quot;', '&lt;', '&gt;'], $strcut);

    return $strcut . $dot;
}

//清除HTML标记
function wet_clearhtml($str)
{
    $str = str_replace(
        ['&nbsp;', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&moddot;', '&hellip;'],
        [' ', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'],
        $str
    );
    $str = preg_replace("/\<[a-z]+(.*)\>/iU", "", $str);
    $str = preg_replace("/\<\/[a-z]+\>/iU", "", $str);
    $str = preg_replace("/{.+}/U", "", $str);
    $str = str_replace([chr(13), chr(10), '&nbsp;'], '', $str);
    $str = strip_tags($str);
    return trim($str);
}

//写日志
function wet_write_log($name, $data)
{
    if (!$name) {
        $name = 'noname';
    }
    $date = date('Y-m');
    $url = WRITEPATH . 'txtlog/' . $date . '/' . date('Ymd') . '_' . $name . '.log';
    $dir_name = dirname($url);
    if (!file_exists($dir_name)) {
        $res = mkdir(iconv("UTF-8", "GBK", $dir_name), 0777, true);
    }
    $fp = fopen($url, "a");
    fwrite($fp, var_export($data, true) . "\r\n");
    fclose($fp);
}

//数组合并
function wet_array2array($a1, $a2)
{
    $a = [];
    $a = $a1 ? $a1 : $a;
    if ($a2) {
        foreach ($a2 as $t) {
            $a[] = $t;
        }
    }
    return $a;
}

function mok_safe_replace($string, $diy = null)
{
    $replace = ['%20', '%27', '%2527', '*', "'", '"', ';', '<', '>', "{", '}'];
    if (isset($diy) && $diy) {
        if (is_array($diy)) {
            $replace = wet_array2array($replace, $diy);
        } else {
            $replace[] = $diy;
        }
    }
    return str_replace($replace, '', $string);
}

function wet_safe_filename($string = '')
{
    return str_replace(
        ['..', "/", '\\', ' ', '<', '>', "{", '}', ';', '[', ']', '\'', '"', '*', '?'],
        '',
        $string
    );
}

function wet_safe_username($string = '')
{
    return str_replace(
        ['..', "/", '\\', ' ', "#", '\'', '"'],
        '',
        $string
    );
}

function wet_safe_password($string = '')
{
    return trim(str_replace(["'", '"', '&', '?'], '', $string));
}

function wet_apps_routers()
{
    foreach (glob(APPPATH . 'Modules/*', GLOB_ONLYDIR) as $dir) {
        if (file_exists($dir . '/Config/Routes.php')) {
            require_once $dir . '/Config/Routes.php';
        }
    }
}