<?php
namespace App\Controllers;

use App\Models\UserModel;

class User extends BaseController {

	public function info()
	{//获取用户完整信息
        $userAddress   = $this->request->getPost('userAddress');
		$typeLogin     = $this->request->getPost('type');
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		if($isUserAddress){
			$data['code'] = 200;
			if($typeLogin){
				$opt = ['type' => $typeLogin];
			}
			$userInfo	  = (new UserModel())-> userAllInfo($userAddress, $opt);
			$data['data'] = '';
			if($userInfo){
				$data['data'] = $userInfo;
				$data['msg']  = 'success';
			}else{
				$data['msg']  = 'error_address';
			}
		}else{
			$data['code'] = 406;
			$data['msg']  = 'error';
		}
		echo json_encode($data);
    }

	public function contentList()
	{//用户主贴列表
		$page = $this->request->getPost('page');
		$size = $this->request->getPost('size');
		$userAddress = $this->request->getPost('userAddress');
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		if($isUserAddress){
			$type = 'userContentList';
			$opt  =	[
					'type' 		=> $type,
					'publicKey' => $userAddress
				];
			$data = $this->PagesModel-> limit((int)$page, (int)$size, $opt);
			echo $data;
		}else{
			$data['code'] = 406;
			$data['msg']  = 'error';
			echo json_encode($data);
		}
	}

	public function focusContent()
	{//关注的用户主贴列表
		$page = $this->request->getPost('page');
		$size = $this->request->getPost('size');
		$type = 'userFocusContentList';
		$opt  =	['type' => $type];
		$data = $this->PagesModel-> limit($page, $size, $opt);
		echo $data;
	}

	public function focusList()
	{//关注列表
		$page  = $this->request->getPost('page');
		$size  = $this->request->getPost('size');
		$focus = $this->request->getPost('focus');
		$type  = 'userFocusUserList';
		if($focus == 'myFocus' || $focus == 'focusMy'){
			$opt = [
				'type'  => $type,
				'focus' => $focus  //focus => 可选类型 myFocus\focusMy
			];
			$data  = $this->FocusModel-> limit($page, $size, $opt);
			echo $data;
		}else{
			$data['code'] = 406;
			$data['msg']  = 'error';
			echo json_encode($data);
		}
	}

}