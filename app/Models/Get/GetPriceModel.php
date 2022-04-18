<?php namespace App\Models\Get;

use App\Models\ComModel;
use App\Models\ConfigModel;
use App\Models\DisposeModel;

class GetPriceModel extends ComModel {
//获取Model
	public function __construct(){
		parent::__construct();
		//$this->db = \Config\Database::connect('default');
		$this->ConfigModel  = new ConfigModel();
		$this->DisposeModel = new DisposeModel();
    }

	public function aePrice(){
		//AE价格获取
			@$json = file_get_contents('https://data.gateapi.io/api2/1/ticker/ae_usdt');
			if(empty($json)){
				return '远端报错，无没有读取到价格';
			}
			$arr = (array) json_decode($json, true);

			$last		 = $arr['last'] ?? 0;
			$quoteVolume = round($arr['quoteVolume'], 2) ?? 0;
			$high24hr    = $arr['high24hr'] ?? 0;
			$low24hr	 = $arr['low24hr'] ?? 0;
			$percentChange = $arr['percentChange'] ?? 0;
			
			$content = "
			当前价格: {$last}
			成交数量: {$quoteVolume}
			24H最高: {$high24hr}
			24H最低: {$low24hr}
			24H涨跌: {$percentChange}%
			";
			return $content;
	}


}

