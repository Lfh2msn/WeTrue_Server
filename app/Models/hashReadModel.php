<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\ConfigModel;

class hashReadModel extends Model {

	public function __construct(){
        parent::__construct();
        $this->tablename   = 'wet_temporary';
		$this->ConfigModel = new ConfigModel();
    }

	public function split($hash)
	{//获取用户完整信息
		//写入临时数据库
		$insertTempSql="INSERT INTO $this->tablename(tp_hash) VALUES ('$hash')";
		$this->db->query($insertTempSql);

		$bsConfig = $this->ConfigModel-> backendConfig();
		$url = $bsConfig['backendServiceNode'].'v2/transactions/'.$hash;
		$i   = 0;
		while ( !$get && $i < 10 ) {
			@$get = file_get_contents($url);
			$i++;
			//sleep(1);
		}

        if(empty($get)){
        	return;
        }

        $json = (array) json_decode($get, true);
		
        //过滤无效预设钱包
        if(empty(
			$json['tx']['recipient_id'] == $bsConfig['receivingAccount'] || 
			$json['tx']['type'] == 'SpendTx' || 
			$json['tx']['payload']===null || 
			$json['tx']['payload']==="ba_Xfbg4g=="
			)){
        	//删除临时缓存
	        $deleteTempSql = "DELETE FROM $this->tablename WHERE tp_hash='$hash'";
	        $this->db->query($deleteTempSql);
	        echo '非法提交';
        return;
        }


		$data = $this->decodeContent($json);

		return $data ;
	print_r(json_encode($json, true));
	echo '<br><br><br>';
	print_r(json_encode($data, true));
    return;
	}

	public function decodeContent($json)
	{//解码以及分配内同
		$bsConfig = $this->ConfigModel-> backendConfig();
        //TX获取UTC时间
        $microBlock      = $json['block_hash'];
        $microBlockUrl   = $bsConfig['backendServiceNode'].'v2/micro-blocks/hash/'.$microBlock.'/header';
        @$microBlockJson = file_get_contents($microBlockUrl);
        $microBlockArray = (array) json_decode($microBlockJson, true);
		$json['mb_time'] = $microBlockArray['time'];

		$payload		 = $this->decodePayload($json['tx']['payload']);
		$hash			 = $json['hash'];

		//版本检测
		$WeTrue  = $payload['WeTrue'];
		$require = $bsConfig['requireVersion'];
		$version = $this->versionCompare($WeTrue, $require);
		/*if(!$version){
			$versionLow = "versionLow";
			$updateSql  = "UPDATE $this->tablename SET tp_source = '$versionLow' WHERE tp_hash = '$hash'";
	        $this->db-> query($updateSql);
			return;
		}*/
		$type 			 = $payload['type'];
		$data['WeTrue']  = $WeTrue;
		$data['type']    = $type;
		$data['hash']    = $hash;
		$data['receipt'] = $json['tx']['recipient_id'];
		$data['sender']  = $json['tx']['sender_id'];
		$data['amount']  = $json['tx']['amount'];
		$data['mb_time'] = $json['mb_time'];
		$data['content'] = $payload['wet_content'];
		//内容分配
		if($type == 'topic' ){  //主贴
			$data['imgList'] = $payload['img_list'];
		}

		if($type == 'comment' ){  //评论
			
		}

		if($type == 'reply' ){  //回复
			$data['reply_type'] = $payload['reply_type'];
			$data['to_hash']    = $payload['to_hash'];
			$data['reply_hash'] = $payload['reply_hash'];
		}

		if($type == 'nickname' ){  //昵称
			
		}

		if($type == 'portrait' ){  //头像
			
		}

		print_r(json_encode($data));
		return;
		
    }

	public function decodePayload($payload)
	{//解码Payload内容
        $hex  = bin2hex(base64_decode(str_replace("ba_","",$payload)));
        $bin  = hex2bin(substr($hex,0,strlen($hex)-8));
		$json = (array) json_decode($bin,true);
        return $json;
    }

	public function versionCompare($versionA,$versionB)
	{/*版本号比较
	*    @param $version1 版本A 如:5.3.2 
	*    @param $version2 版本B 如:5.3.0 
	*    @return int -1版本A小于版本B , 0版本A等于版本B, 1版本A大于版本B
	*
	*    版本号格式注意：
	*        1.要求只包含:点和大于等于0小于等于2147483646的整数 的组合
	*        2.boole型 true置1，false置0
	*        3.不设位默认补0计算，如：版本号5等于版号5.0.0
	*        4.不包括数字 或 负数 的版本号 ,统一按0处理 */
		if ($versionA>2147483646 || $versionB>2147483646) {
			return false;
		}
		$verListA = explode('.', (string) $versionA);
		$verListB = explode('.', (string) $versionB);

		$len = max(count($verListA),count($verListB));
		$i = -1;
		while ($i++<$len) {
			$verListA[$i] = intval(@$verListA[$i]);
			if ($verListA[$i] < 0 ) {
				$verListA[$i] = 0;
			}
			$verListB[$i] = intval(@$verListB[$i]);
			if ($verListB[$i] < 0 ) {
				$verListB[$i] = 0;
			}

			if ($verListA[$i]>$verListB[$i]) {
                return true;
			}
			if ($verListA[$i]<$verListB[$i]) {
                return false;
			}
			if ($i==($len-1)) {
                return true;
			}
		}
	}

}

