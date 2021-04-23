<?php
namespace App\Controllers;

use App\Models\DisposeModel;

class Image extends BaseController
{
	public function list()
    {//图片列表
        $page = $this->request->getPost('currentPage');
        $size = $this->request->getPost('perPage');
        $opt  = ['type' => 'imageList'];
		$data = $this->PagesModel-> limit($page, $size, $opt);
		echo $data;
    }

}
