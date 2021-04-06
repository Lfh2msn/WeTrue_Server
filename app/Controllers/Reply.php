<?php
namespace App\Controllers;

class Reply extends BaseController {

	public function list()
    {//回复列表
        $page = $this->request->getPost('currentPage');
        $size = $this->request->getPost('perPage');
		$hash = $this->request->getPost('hash');
        $opt  = [
                    'type'=> 'replyList',
                    'hash' => $hash
                ];
		$data = $this->pagesModel-> limit($page, $size, $opt);
		echo $data;
    }
}