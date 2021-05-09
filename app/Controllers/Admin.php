<?php
namespace App\Controllers;

use App\Models\ComplainModel;
use App\Models\BloomModel;

class Admin extends BaseController
{//管理
	public function complainList()
	{//投诉列表
		$page = $this->request->getPost('page');
		$size = $this->request->getPost('size');
		$data = (new ComplainModel())-> limit($page, $size);
		echo $data;
	}

	public function bloomList()
	{//屏蔽列表
		$page = $this->request->getPost('page');
		$size = $this->request->getPost('size');
		$data = (new BloomModel())-> limit($page, $size);
		echo $data;
	}

	public function bloom()
	{//投诉hash屏蔽
		$hash   = $this->request->getPost('hash');
		$isHash = $this->DisposeModel-> checkAddress($hash);
        if ($isHash) {
            $data = (new BloomModel())-> bloomHash($hash);
			echo $data;
        } else {
            $data['code'] = 406;
			$data['msg']  = 'error_hash';
            echo json_encode($data);
		}
	}

	public function unBloom()
	{//取消屏蔽
		$hash   = $this->request->getPost('hash');
		$isHash = $this->DisposeModel-> checkAddress($hash);
        if ($isHash) {
            $data = (new BloomModel())-> unBloom($hash);
			echo $data;
        } else {
            $data['code'] = 406;
			$data['msg']  = 'error_hash';
            echo json_encode($data);
		}
	}
}