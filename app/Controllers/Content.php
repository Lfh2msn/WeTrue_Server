<?php
namespace App\Controllers;

class Content extends BaseController
{
	public function list()
    {//主贴列表
        $page = $this->request->getPost('page');
        $size = $this->request->getPost('size');
        $opt  = ['type' => 'contentList'];
		$data = $this->PagesModel-> limit($page, $size, $opt);
		echo $data;
    }

	public function tx()
    {//主贴详情
        $hash   = $this->request->getPost('hash');
        $isHash = $this->DisposeModel-> checkAddress($hash);
		if($isHash){
            $opt  = ['select' => 'content'];
            $data = $this->PagesModel-> Alone($hash, $opt);
            echo $data;
        }else{
			$data['code'] = 406;
			$data['msg']  = 'error_hash';
            echo json_encode($data);
		}
    }

    public function hotRec()
    {//热点推荐列表
        $page = $this->request->getPost('page');
        $size = $this->request->getPost('size');
        $opt  = ['type' => 'hotRecList'];
		$data = $this->PagesModel-> limit($page, $size, $opt);
		echo $data;
    }
}