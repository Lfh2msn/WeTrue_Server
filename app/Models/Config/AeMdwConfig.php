<?php namespace App\Models\Config;

class AeMdwConfig
{//官方mdw地址配置

	public static function urls()
	{
		$data[] = array(
			'url'	=> 'https://mainnet.aeternity.io/mdw',
			'name' 	=> 'Aeternity',
		);
		$data[] = array(
			'url'	=> 'https://mainnet.wetrue.io/mdw',
			'name' 	=> 'WeTrueIO-MDW',
		);
		$data[] = array(
			'url'	=> 'https://mainnet.wetrue.cc/mdw',
			'name' 	=> 'WeTrueCC-MDW2',
		);
		return $data;
	}

}