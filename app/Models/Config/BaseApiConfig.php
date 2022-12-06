<?php namespace App\Models\Config;

class BaseApiConfig
{//基础api配置

	public static function urls()
	{
		$data[] = array(
			'url'	=> 'https://api.wetrue.io',
			'name' 	=> 'WeTrueIO',
		);
		$data[] = array(
			'url'	=> 'https://api.wetrue.cc',
			'name' 	=> 'WeTrueCC',
		);
		return $data;
	}

}