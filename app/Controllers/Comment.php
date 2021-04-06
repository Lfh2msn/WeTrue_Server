<?php
namespace App\Controllers;

class Comment extends BaseController {

	public function list()
    {//评论列表
        $page = $this->request->getPost('currentPage');
        $size = $this->request->getPost('perPage');
		$hash = $this->request->getPost('hash');
        $replyLimit = $this->request->getPost('replyLimit');
        $opt  = [
                    'type'       => 'commentList',
                    'hash'       => $hash,
                    'replyLimit' => (int)$replyLimit
                ];
		$data = $this->pagesModel-> limit($page, $size, $opt);
		echo $data;
    }

	public function tx()
    {//评论详情
        $hash = $this->request->getPost('hash');
        $opt  = ['select' => 'comment'];
		$data = $this->pagesModel-> Alone($hash, $opt);
		echo $data;
    }
}