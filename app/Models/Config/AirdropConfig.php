<?php 
namespace App\Models\Config;

class AirdropConfig 
{//空投AE配置

    public static function config()
	{//配置
		return array(
			'aeOpen'    => false, //空投开关 true false
			'aeAmount'  => '30000000000000000', //空投AE金额 1e17 = 0.1ae
			'aePayload' => "Sponsored by China Foundation(中国基金会赞助)", //空投Payload
			'aeAddress' => 'ak_21t5CKNRkKai3fCRm9o3WqvLKgbNKbin5k89FzkxfUEWu6G6GM', //AE空投地址
			'wttRatio'  => 1, //WTT空投比例
		);
    }
}