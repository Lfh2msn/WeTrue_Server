<?php
namespace App\Controllers;

use App\Models\{
	DisposeModel,
	MsgModel
};


class Message extends BaseController
{

	public function list()
	{//用户消息列表
		$page   = $this->request->getPost('page');
        $size   = $this->request->getPost('size');
        $offset = $this->request->getPost('offset');
		$data   = (new MsgModel())-> getMsgList($page, $size, $offset);
		if($data){	
			echo $data;
		}else{
			echo DisposeModel::wetJsonRt(406, 'error');
		}
	}

	public function stateSize()
	{//用户主贴列表
		$stateSize = (new MsgModel())-> getStateSize();
		$data['stateSize'] = $stateSize;
		echo DisposeModel::wetJsonRt(200, 'success', $data);
	}

}