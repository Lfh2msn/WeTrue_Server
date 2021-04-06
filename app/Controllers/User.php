<?php
namespace App\Controllers;

use App\Models\UserModel;
use App\Models\FocusModel;

class User extends BaseController {

	public function info()
	{//获取用户完整信息
        $userAddress  = $this->request->getPost('userAddress');
		$data['code'] = 200;
		$userInfo	  = (new UserModel())-> userAllInfo($userAddress);
		$data['data'] = '';
		if($userInfo && $userAddress){
			$data['data'] = $userInfo;
			$data['msg']  = 'success';
		}else{
			$data['msg']  = 'error_address';
		}
		echo json_encode($data);
    }

	public function contentList()
	{//用户主贴列表
		$page = $this->request->getPost('currentPage');
		$size = $this->request->getPost('perPage');
		$userAddress = $this->request->getPost('userAddress');
		$type = 'userContentList';
		$opt  =	[
				'type' 		=> $type,
				'publicKey' => $userAddress
			];
		$data = $this->pagesModel-> limit($page, $size, $opt);
		echo $data;
	}

	public function focusContent()
	{//关注的用户主贴列表
		$page = $this->request->getPost('currentPage');
		$size = $this->request->getPost('perPage');
		$type = 'userFocusContentList';
		$opt  =	['type' => $type];
		$data = $this->pagesModel-> limit($page, $size, $opt);
		echo $data;
	}

	public function focusList()
	{//关注列表
		$page  = $this->request->getPost('currentPage');
		$size  = $this->request->getPost('perPage');
		$focus = $this->request->getPost('focus');
		$type  = 'userFocusUserList';
		$opt   =	[
				'type'  => $type,
				'focus' => $focus //focus => 可选类型 myFocus\focusMy
			];
		$data  = (new FocusModel())-> limit($page, $size, $opt);
		echo $data;
	}
}