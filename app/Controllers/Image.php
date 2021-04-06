<?php
namespace App\Controllers;

class Image extends BaseController
{
	public function list()
    {//图片列表
        $page = $this->request->getPost('currentPage');
        $size = $this->request->getPost('perPage');
        $opt  = ['type' => 'imageList'];
		$data = $this->pagesModel-> limit($page, $size, $opt);
		echo $data;
    }

}
