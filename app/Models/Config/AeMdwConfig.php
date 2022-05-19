<?php namespace App\Models\Config;

class AeMdwConfig {
//官方mdw地址配置

public function urls()
{
	$data[] = array(
		'url'	=> 'https://mainnet.aeternity.io/mdw',
		'name' 	=> 'Aeternity',
	);
	$data[] = array(
		'url'	=> 'https://mainnet.wetrue.cc/mdw',
		'name' 	=> 'WeTrue-MDW',
	);
	return $data;
}

}