<?php
namespace App\Controllers;

use App\Models\MsgModel;

class Message extends BaseController {

	public function list()
	{//用户主贴列表
		$page = $this->request->getPost('page');
		$size = $this->request->getPost('size');
		$data = (new MsgModel())-> getMsgList((int)$page, (int)$size);
		if($data){	
			echo $data;
		}else{
			$data['code'] = 406;
			$data['msg']  = 'error';
			echo json_encode($data);
		}
	}

	public function stateSize()
	{//用户主贴列表
		$stateSize = (new MsgModel())-> getStateSize();
		$data['stateSize'] = $stateSize;
		echo $this->DisposeModel-> wetJsonRt(200, 'success', $data);
	}

	

}