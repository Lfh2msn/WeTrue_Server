<?php namespace App\Models;

use App\Models\{
	ComModel,
	DisposeModel,
	UserModel,
	ConfigModel,
	ValidModel
};

class SuperheroModel extends ComModel {
//抓取Superhero内容入库Model

	public function __construct(){
		parent::__construct();
		$this->DisposeModel   = new DisposeModel();
		$this->UserModel      = new UserModel();
		$this->ConfigModel    = new ConfigModel();
		$this->ValidModel     = new ValidModel();
		$this->wet_content_sh = "wet_content_sh";
		$this->wet_users 	  = "wet_users";
    }

	public function getContent($page)
	{ //获取TipID及内容，并写入数据库
		$bsConfig = $this->ConfigModel-> backendConfig();
		$active   = $bsConfig['topicActive'];
		$shApiUrl = $bsConfig['superheroApiUrl'];

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
		$options  = array(
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
			return $this->DisposeModel-> wetJsonRt($code, $msg);
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

		$toPgArr = $this->DisposeModel->to_pg_val_array($getTipIdArr); //转换为pgsql所需数组
		$sql     = "SELECT tmp.tip_id FROM (VALUES $toPgArr) AS tmp(tip_id) WHERE tmp.tip_id NOT IN(SELECT tip_id FROM wet_content_sh ORDER BY uid DESC LIMIT 100)";
		$query   = $this->db-> query($sql);
		$sqlResult = $query-> getResult();

		if (!$sqlResult) {
			$code = 200;
			$msg  = "Not_Update";
			return $this->DisposeModel-> wetJsonRt($code, $msg);
		}

		$lastResult = array();
		foreach ($sqlResult as $value) {
			$lastResult[] = $value->tip_id;
		}

		foreach ($lastResult as $key => $value) {
			if ($value == $json[$key]['id']) {
				$isBloomAddress = $this->ValidModel ->isBloomAddress($json[$key]['sender']);
				$isAmountVip = $this->ValidModel-> isAmountVip($json[$key]['sender']);
				if (!$isBloomAddress && !$isAmountVip) { //地址过滤
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
					
					$this->db->table($this->wet_content_sh)->insert($insertData);
					$this->UserModel-> userActive($json[$key]['sender'], $active, $e = true);
					$upSql = "UPDATE $this->wet_users SET topic_sum = topic_sum + 1 WHERE address = '$json[$key][sender]'";
					$this->db->query($upSql);
				}
			}
		}
		return $this->DisposeModel-> wetJsonRt(200);
	}

}

