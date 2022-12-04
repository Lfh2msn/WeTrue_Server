<?php
namespace App\Controllers;

use App\Models\{
    DisposeModel,
    ConfigModel
};

class Image extends BaseController
{
	public function list()
    {//图片列表
        $page   = $this->request->getPost('page');
        $size   = $this->request->getPost('size');
        $offset = $this->request->getPost('offset');
        $opt    = ['type' => 'imageList'];
		$data   = $this->PagesModel-> limit($page, $size, $offset, $opt);
		echo $data;
    }
	
	public function toimg($hash)
	{//Tx转照片
        $isHash = DisposeModel::checkAddress($hash);

        if (!$isHash) { 
			echo '无效Hash';
			return;
        }

        $bsConfig = (NEW ConfigModel())-> backendConfig();
        $url = $bsConfig['backendServiceNode'].'/v3/transactions/'.$hash;
		//屏蔽错误,防止节点暴露（屏蔽符：@ ）
        @$json_data = file_get_contents($url);

		//过滤无效hash
        if(empty($json_data)){
			echo 'Node报错,无Hash记录';
			return;
        }

        $data = (array) json_decode($json_data,true);

        //过滤空或无效Payload
        if( $data['tx']['payload']===null || $data['tx']['payload']==="ba_Xfbg4g==" ){
			echo '非法Hash';
			return;
        }

        $wetPayload = !empty($data['tx']['payload'])?$data['tx']['payload']:null;
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
