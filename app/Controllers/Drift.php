<?php
namespace App\Controllers;


class Drift extends BaseController {

	public function replyList()
	{//Drift回复列表
		$page = $this->request->getPost('page');
		$size = $this->request->getPost('size');
		$data = $this->DriftModel-> limit((int)$page, (int)$size);
		echo $data;
	}

}