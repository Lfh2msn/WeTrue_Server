<?php
namespace App\Controllers;

class Comment extends BaseController {

	public function list()
    {//评论列表
        $page   = $this->request->getPost('page');
        $size   = $this->request->getPost('size');
        $offset = $this->request->getPost('offset');
		$hash   = $this->request->getPost('hash');
        $replyLimit = $this->request->getPost('replyLimit');
        $opt  = [
                    'type'       => 'commentList',
                    'hash'       => $hash,
                    'replyLimit' => (int)$replyLimit
                ];
        $isHash = $this->DisposeModel-> checkAddress($hash);
        if ($isHash) {
            $data = $this->PagesModel-> limit($page, $size, $offset, $opt);
            echo $data;
        } else {
            $data['code'] = 406;
			$data['msg']  = 'error_hash';
            echo json_encode($data);
		}
    }

	public function tx()
    {//评论详情
        $hash = $this->request->getPost('hash');
        $isHash = $this->DisposeModel-> checkAddress($hash);
		if ($isHash) {
            $opt  = ['select' => 'comment'];
            $data = $this->PagesModel-> Alone($hash, $opt);
            echo $data;
        } else {
			$data['code'] = 406;
			$data['msg']  = 'error_hash';
            echo json_encode($data);
		}
    }
}