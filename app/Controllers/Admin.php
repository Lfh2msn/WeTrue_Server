<?php
namespace App\Controllers;

class Admin extends BaseController
{
    public function hotRec()
    {//热点推荐列表
        $page = $this->request->getPost('currentPage');
        $size = $this->request->getPost('perPage');
        $opt  = ['type' => 'hotRecList'];
		$data = $this->PagesModel-> limit($page, $size, $opt);
		echo $data;
    }
    
	public function GetBloom()
    {//获取已屏蔽帖
		$page = $this->request->getPost('currentPage');
        $size = $this->request->getPost('perPage');

        //$data = $this->AdminModel->Rp_Bloom($page, $size, $opt);
        //echo $data;

	}
}