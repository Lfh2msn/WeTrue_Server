<?php namespace App\Models\Config;

class AeknowConfig {
//Aeknow地址配置

public function urls()
{
	$data[] = array(
		'url'	=> 'https://www.aeknow.org',
		'name' 	=> 'AeKnow',
	);
	$data[] = array(
		'url'	=> 'https://api.wetrue.io/Aeknow',
		'name' 	=> 'WET-AEK-IO',
	);
	$data[] = array(
		'url'	=> 'https://api.wetrue.cc/Aeknow',
		'name' 	=> 'WET-AEK-CC',
	);
	return $data;
}

}