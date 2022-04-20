<?php namespace App\Models\Get;

use App\Models\ComModel;
use App\Models\DisposeModel;

class GetPriceModel extends ComModel {
//获取Model
	public function __construct(){
		$this->DisposeModel = new DisposeModel();
    }

	public function gateioPrice($coin = "AE", $trading = "usdt"){
		//AE价格获取
			// https://data.gateapi.io/api2/1/pairs 所有交易对
			@$json = file_get_contents("https://data.gateapi.io/api2/1/ticker/{$coin}_{$trading}");
			if(empty($json)){
				return '远端报错，无没有读取到价格';
			}
			$arr = (array) json_decode($json, true);

			$last		 = $arr['last'] ?? 0;
			$quoteVolume = round($arr['quoteVolume'], 2) ?? 0;
			$high24hr    = $arr['high24hr'] ?? 0;
			$low24hr	 = $arr['low24hr'] ?? 0;
			$percentChange = $arr['percentChange'] ?? 0;
			$newQuote	 = $this->DisposeModel-> numberFormat($quoteVolume);
			$content = "
			{$coin} Gateio Price
			当前价格: {$last}
			24H成交: {$newQuote}
			24H最高: {$high24hr}
			24H最低: {$low24hr}
			24H涨跌: {$percentChange}%
			";
			return $content;
	}


}

