<?php 
namespace App\Models\Config;

class IpfsNodeConfig
{//ipfs 节点配置

	public static function urls()
	{
		$data[] = array(
			'url'	=> 'https://dweb.link/ipfs/',
			'name' 	=> 'DwebLink',
		);
		return $data;
	}

}