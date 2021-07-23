<?php
namespace App\Controllers;

class Reply extends BaseController {

	public function list()
    {//回复列表
        $page   = $this->request->getPost('page');
        $size   = $this->request->getPost('size');
        $offset = $this->request->getPost('offset');
		$hash   = $this->request->getPost('hash');
        $isHash = $this->DisposeModel-> checkAddress($hash);
		if ($isHash) {
            $opt  = [
                'type' => 'replyList',
                'hash' => $hash
            ];
            $data = $this->PagesModel-> limit($page, $size, $offset, $opt);
            echo $data;
        } else {
			$data['code'] = 406;
			$data['msg']  = 'error_hash';
            echo json_encode($data);
		}
    }
}