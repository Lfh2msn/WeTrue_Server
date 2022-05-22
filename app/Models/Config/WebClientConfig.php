<?php namespace App\Models\Config;

class WebClientConfig {
//官方mdw地址配置

	public function urls()
	{
		$data[] = array(
			'url'	=> 'https://wetrue.io/#',
			'name' 	=> 'WeTrueIO',
		);
		$data[] = array(
			'url'	=> 'https://wetrue.cc/#',
			'name' 	=> 'WeTrueCC',
		);
		return $data;
	}

}