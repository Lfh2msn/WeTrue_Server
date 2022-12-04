<?php namespace App\Models\Config;

class NodesConfig
{//节点配置

	public function urls()
	{
		$data[] = array(
			'url'	=> 'https://mainnet.aeternity.io',
			'name' 	=> 'Aeternity',
		);
		$data[] = array(
			'url'	=> 'https://mainnet.wetrue.io',
			'name' 	=> 'WeTrueIO',
		);
		$data[] = array(
			'url'	=> 'https://mainnet.wetrue.cc',
			'name' 	=> 'WeTrueCC',
		);
		$data[] = array(
			'url'	=> 'https://node.aeasy.io',
			'name' 	=> 'BoxAepp',
		);
		return $data;
    }

}