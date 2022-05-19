<?php namespace App\Models\Config;

class BaseApiConfig {
//基础api配置

public function urls()
{
	$data[] = array(
		'url'	=> 'https://api.wetrue.io',
		'name' 	=> 'WeTrue-IO',
	);
	$data[] = array(
		'url'	=> 'https://api.wetrue.cc',
		'name' 	=> 'WeTrue-CC',
	);
	return $data;
}

}