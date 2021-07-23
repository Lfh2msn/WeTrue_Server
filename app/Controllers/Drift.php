<?php
namespace App\Controllers;


class Drift extends BaseController {

	public function replyList()
	{//Drift回复列表
		$page   = $this->request->getPost('page');
        $size   = $this->request->getPost('size');
        $offset = $this->request->getPost('offset');
		$data   = $this->DriftModel-> limit($page, $size, $offset);
		echo $data;
	}

}