<?php 
namespace App\Models\Content;

use App\Models\{
	ComModel,
	DisposeModel,
	UserModel,
	ValidModel
};
use App\Models\Config\ActiveConfig;

class AeSuperheroPutModel
{//抓取Superhero内容入库Model

	public function __construct(){
		$this->wet_content_sh = "wet_content_sh";
		$this->wet_users 	  = "wet_users";
    }

	public static function isKey($content)
	{//内容搜索关键词
		$pKey = strtoupper($content); //转大写
		$topicTag = preg_match_all("/(刘少|LIU少|刘SHAO|LIUSHAO|牛少|狗曰)/u", $pKey, $keywords);
		return $topicTag ? true : false;
	}

	public function putContent($page)
	{ //获取TipID及内容，并写入数据库
		$shApiUrl =  'https://raendom-backend.z52da5wt.xyz'; //超级英雄API节点路径
		$url = "{$shApiUrl}/tips?ordering=latest&page={$page}&blacklist=true";
		//$url = "{$shApiUrl}/tips?page={$page}&blacklist=true";
		$post_data = array(
			'ordering'   	  => 'latest',
			'page'    		  => 1,
			'contractVersion' => 'v1',
			'contractVersion' => 'v2',
			'contractVersion' => 'v3',
			'blacklist' 	  => true
		);
		$postdata = http_build_query($post_data);
		$options = array(
						'http' => array(
										'method'  => 'GET',
										'header'  => 'application/json; charset=utf-8',
										'content' => $postdata,
										'timeout' => 30 // 超时时间（单位:s）
								));
		$text = stream_context_create($options);
		@$get = file_get_contents($url, false, $text);
		$json = (array) json_decode($get, true);
		$s_id = $json[0]["id"];

		$num  = 0;
		while ( !$s_id && $num < 10) {
			@$get = file_get_contents($url, false, $text);
			$json = (array) json_decode($get, true);
			$s_id = $json[0]["id"];
			$num++;
		}

		if (!$s_id) {
			$code = 406;
			$msg  = "GetUrlDataError";
			return DisposeModel::wetJsonRt($code, $msg);
		}

		/**获取完整tipID并装入数组
		 * 查询数据库对比数组中值是否存在
		 * 对存在部分进行剔除
		 * 对不存在的值整理写入数据库
		 */
		$getTipIdArr = array();
		foreach ($json as $value) {
			$getTipIdArr[] = $value['id'];
		}

		$toPgArr = DisposeModel::to_pg_val_array($getTipIdArr); //转换为pgsql所需数组
		$sql = "SELECT tmp.tip_id 
					FROM (VALUES $toPgArr) AS tmp(tip_id) 
					WHERE tmp.tip_id 
					NOT IN(
						SELECT tip_id 
						FROM wet_content_sh 
						ORDER BY uid DESC 
						LIMIT 100
					)";
		$query = ComModel::db()-> query($sql);
		$sqlResult = $query-> getResult();

		if (!$sqlResult) {
			$code = 200;
			$msg  = "Not_Update";
			return DisposeModel::wetJsonRt($code, $msg);
		}

		$lastResult = array();
		foreach ($sqlResult as $value) {
			$lastResult[] = $value->tip_id;
		}

		//$acConfig  = ActiveConfig::config();
		//$getActive = $acConfig['topicActive'];

		foreach ($lastResult as $key => $value) {
			if ($value == $json[$key]['id']) {
				
				$pKey = self::isKey($json[$key]['title']); //关键词过滤
				if ($pKey) continue; //跳出

				$isBloomAdd  = ValidModel::isBloomAddress($json[$key]['sender']);
				$isAmountVip = ValidModel::isAmountVip($json[$key]['sender']);
				if (!$isBloomAdd && !$isAmountVip) { //地址过滤
					$insertData = [
						'tip_id'	  => $json[$key]['id'],
						'sender_id'	  => $json[$key]['sender'],
						'contract_id' => $json[$key]['contractId'],
						'source'	  => 'Superhero',
						'type'	   	  => $json[$key]['type'],
						'language' 	  => $json[$key]['language'],
						'payload' 	  => $json[$key]['title'],
						'image' 	  => $json[$key]['linkPreview']['image'],
						'media' 	  => $json[$key]['media'] ? $json[$key]['media'][0] : "",
						'url' 	   	  => $json[$key]['url'],
						'utctime' 	  => strtotime($json[$key]['timestamp']) * 1000
					];
					
					ComModel::db()->table($this->wet_content_sh)->insert($insertData);
					//UserModel::userActive($json[$key]['sender'], $getActive, $e = true);
					//$upSql = "UPDATE $this->wet_users SET topic_sum = topic_sum + 1 WHERE address = '$json[$key][sender]'";
					//ComModel::db()->query($upSql);
				}
			}
		}
		return DisposeModel::wetJsonRt(200);
	}

}

