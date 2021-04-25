<?php
namespace App\Controllers;

use App\Models\ComplainModel;

class Admin extends BaseController
{//管理
	public function complain()
    {//获取已屏蔽帖
		$page = $this->request->getPost('currentPage');
        $size = $this->request->getPost('perPage');
        $data = (new ComplainModel())-> limit($page, $size);
        echo $data;
	}
}