<?php namespace App\Models\Config;

class CompilerConfig {
//编译器配置

public function urls()
{
	$data[] = array(
		'url'	=> 'https://compiler.wetrue.cc',
		'name' 	=> 'WeTrue',
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