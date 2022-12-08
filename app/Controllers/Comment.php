<?php
namespace App\Controllers;

use App\Models\{
    PagesModel,
    DisposeModel
};

class Comment extends BaseController
{

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
        $isHash = DisposeModel::checkAddress($hash);
        $isShid = DisposeModel::checkSuperheroTipid($hash);
        if ($isHash || $isShid) {
            $data = PagesModel::limit($page, $size, $offset, $opt);
            echo $data;
        } else {
            echo DisposeModel::wetJsonRt(406,'error_hash');
		}
    }

	public function tx()
    {//评论详情
        $hash = $this->request->getPost('hash');
        $isHash = DisposeModel::checkAddress($hash);
		if ($isHash) {
            $opt  = ['select' => 'comment'];
            $data = PagesModel::alone($hash, $opt);
            echo $data;
        } else {
            echo DisposeModel::wetJsonRt(406,'error_hash');
		}
    }
}