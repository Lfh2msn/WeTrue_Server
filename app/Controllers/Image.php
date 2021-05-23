<?php
namespace App\Controllers;

class Image extends BaseController
{
	public function list()
    {//图片列表
        $page = $this->request->getPost('page');
        $size = $this->request->getPost('size');
        $opt  = ['type' => 'imageList'];
		$data = $this->PagesModel-> limit($page, $size, $opt);
		echo $data;
    }
	
	public function toimg($hash)
	{//Tx转照片

        $url = 'https://node.aechina.io/v2/transactions/'.$hash;
        //检测是否th_开头
        $inhash = "tt".$hash;
        if(stripos($inhash,"th_")<1 || strlen($inhash)<48){ 
			echo '无效Hash';
			return;
        }

		//屏蔽错误,防止节点暴露（屏蔽符：@ ）
        @$json = file_get_contents($url);

		//过滤无效hash
        if(empty($json)){
			echo 'Node报错，无Hash记录';
			return;
        }

        $hasharr = (array) json_decode($json,true);

        //过滤空或无效Payload
        if($hasharr['tx']['payload']===null||$hasharr['tx']['payload']==="ba_Xfbg4g=="){
			echo '非法Hash';
			return;
        }

        $wetPayload = !empty($hasharr['tx']['payload'])?$hasharr['tx']['payload']:null;
        $strpl = bin2hex(base64_decode(str_replace("ba_","",$wetPayload)));
        $hexPayload = hex2bin(substr($strpl,0,strlen($strpl)-8));

        $wetArr = (array) json_decode($hexPayload,true);
        $wetBase64Img = !empty($wetArr['img_list'])?$wetArr['img_list']:null;
        $RemovalHead = str_replace("data:image/jpeg;base64,","",$wetBase64Img);
    	$wetImg = base64_decode($RemovalHead);
        $this->response->setHeader('Expires', date(DATE_RFC1123, strtotime("7 day") ) );
		$this->response->setHeader('Content-type', 'image/jpeg');
    	echo $wetImg;
        $this->cachePage(720);
    }

}
