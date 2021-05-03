<?php
namespace App\Controllers;

class Reply extends BaseController {

	public function list()
    {//回复列表
        $page = $this->request->getPost('currentPage');
        $size = $this->request->getPost('perPage');
		$hash = $this->request->getPost('hash');
        $isHash = $this->DisposeModel-> checkAddress($hash);
		if ($isHash) {
            $opt  = [
                'type'=> 'replyList',
                'hash' => $hash
            ];
            $data = $this->PagesModel-> limit($page, $size, $opt);
            echo $data;
        } else {
			$data['code'] = 406;
			$data['msg']  = 'error_hash';
            echo json_encode($data);
		}
    }
}