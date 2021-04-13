<?php
namespace App\Controllers;

class Content extends BaseController
{
	public function list()
    {//主贴列表
        $page = $this->request->getPost('currentPage');
        $size = $this->request->getPost('perPage');
        $opt  = ['type' => 'contentList'];
		$data = $this->pagesModel-> limit($page, $size, $opt);
		echo $data;
    }

	public function tx()
    {//主贴详情
        $hash = $this->request->getPost('hash');
        $isHash = $this->DisposeModel-> checkAddress($hash);
		if($isHash){
            $opt  = ['select' => 'content'];
            $data = $this->pagesModel-> Alone($hash, $opt);
            echo $data;
        }else{
			$data['code'] = 406;
			$data['msg']  = 'error_hash';
            echo json_encode($data);
		}
    }

    public function hotRec()
    {//热点推荐列表
        $page = $this->request->getPost('currentPage');
        $size = $this->request->getPost('perPage');
        $opt  = ['type' => 'hotRecList'];
		$data = $this->pagesModel-> limit($page, $size, $opt);
		echo $data;
    }
}