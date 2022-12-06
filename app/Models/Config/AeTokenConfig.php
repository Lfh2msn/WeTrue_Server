<?php namespace App\Models\Config;

class AeTokenConfig
{//AEX-9 Token 配置

	public static function list()
	{
		$data[] = array(
			'symbol'      => 'WTT',
			'name'        => 'WeTrue Token',
			'decimals' 	  => 18,
			'contract_id' => 'ct_KeTvHnhU85vuuQMMZocaiYkPL9tkoavDRT3Jsy47LK2YqLHYb',
		);
		$data[] = array(
			'symbol'      => 'WET',
			'name'        => 'WeTrue Token(OLD)',
			'decimals' 	  => 18,
			'contract_id' => 'ct_uGk1rkSdccPKXLzS259vdrJGTWAY9sfgVYspv6QYomxvWZWBM',
		);
		$data[] = array(
			'symbol'      => 'ABC',
			'name'        => 'AE BOX Coin',
			'decimals' 	  => 18,
			'contract_id' => 'ct_7UfopTwsRuLGFEcsScbYgQ6YnySXuyMxQWhw6fjycnzS5Nyzq',
		);
		$data[] = array(
			'symbol'      => 'AEG',
			'name'        => 'AEKnow Token',
			'decimals' 	  => 18,
			'contract_id' => 'ct_BwJcRRa7jTAvkpzc2D16tJzHMGCJurtJMUBtyyfGi2QjPuMVv',
		);
		return $data;
	}

	public static function getContractId($name)
	{
		$tokenList = self::list();
		$count = count($tokenList);

		for($i=0; $i<$count; $i++) {
			if ($tokenList[$i]['symbol'] == $name) {
				$contract_id = $tokenList[$i]['contract_id'];
			}
		}
		return $contract_id;
	}

}