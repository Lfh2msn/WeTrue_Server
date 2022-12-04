<?php namespace App\Models\Config;

class AeknowConfig
{//Aeknow地址配置

	public function urls()
	{
		$data[] = array(
			'url'	=> 'https://www.aeknow.org',
			'name' 	=> 'AEKnow',
		);
		$data[] = array(
			'url'	=> 'https://api.wetrue.io/Aeknow',
			'name' 	=> 'WeTrueIO-AEKnow',
		);
		$data[] = array(
			'url'	=> 'https://api.wetrue.cc/Aeknow',
			'name' 	=> 'WeTrueCC-AEKnow',
		);
		return $data;
	}

}