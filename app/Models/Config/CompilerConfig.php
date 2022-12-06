<?php namespace App\Models\Config;

class CompilerConfig
{//编译器配置

	public static function urls()
	{	
		$data[] = array(
			'url'	=> 'https://compiler.wetrue.io',
			'name' 	=> 'WeTrueIO',
		);
		$data[] = array(
			'url'	=> 'https://compiler.wetrue.cc',
			'name' 	=> 'WeTrueCC',
		);
		$data[] = array(
			'url'	=> 'https://compiler.aeasy.io',
			'name' 	=> 'BoxAepp',
		);
		$data[] = array(
			'url'	=> 'https://compiler.aeternity.io',
			'name' 	=> 'Aeternity',
		);
		return $data;
	}

}