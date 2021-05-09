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

}
