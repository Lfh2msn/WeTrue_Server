<?php
namespace App\Controllers;

use App\Models\{
	FocusModel,
	UserModel,
	PagesModel,
	ValidModel,
	DisposeModel
};

class User extends BaseController {

	public function info()
	{//获取用户完整信息
        $userAddress   = $this->request->getPost('userAddress');
		$typeLogin     = $this->request->getPost('type');
		$isUserAddress = DisposeModel::checkAddress($userAddress);
		$content = '';
		if($isUserAddress){
			$code = 200;
			$opt = '';
			if(isset($typeLogin))
				$opt = ['type' => $typeLogin];
			else
				$opt = ['type' => ''];
			$userInfo = UserModel::userAllInfo($userAddress, $opt);
			if(isset($userInfo)){
				$content = $userInfo;
				$msg = 'success';
			}
			else
				$msg = 'error_address';
			
		}else{
			$code = 406;
			$msg  = 'error';
		}
		echo DisposeModel::wetJsonRt($code, $msg, $content);
    }

	public function contentList()
	{//用户主贴列表
		$page   = $this->request->getPost('page');
        $size   = $this->request->getPost('size');
        $offset = $this->request->getPost('offset');
		$userAddress = $this->request->getPost('userAddress');
		$isUserAddress = DisposeModel::checkAddress($userAddress);
		if($isUserAddress){
			$type = 'userContentList';
			$opt  =	[
					'type' 		=> $type,
					'publicKey' => $userAddress
				];
			$data = PagesModel::limit($page, $size, $offset, $opt);
			echo $data;
		}else{
			$data['code'] = 406;
			$data['msg']  = 'error';
			echo json_encode($data);
		}
	}

	public function focusList()
	{//关注用户列表
		$page    = $this->request->getPost('page');
        $size    = $this->request->getPost('size');
        $offset  = $this->request->getPost('offset');
		$focus   = $this->request->getPost('focus');
		$userAddress   = $this->request->getPost('userAddress');
		$isUserAddress = DisposeModel::checkAddress($userAddress);
		$type  = 'userFocusUserList';
		if($isUserAddress && ($focus == 'myFocus' || $focus == 'focusMy')){
			$opt = [
				'type'    => $type,
				'focus'   => $focus, //focus => 可选类型 myFocus\focusMy
				'address' => $userAddress
			];
			$data  = FocusModel::limit($page, $size, $offset, $opt);
			echo $data;
		}
		else
			echo DisposeModel::wetJsonRt(406,'error');
	}

	public function isNickname()
	{//获取昵称是否存在
		$nickname = $this->request->getPost('nickname');
		$type = ValidModel::isNickname($nickname);
		$data['isNickname'] = $type;
		echo DisposeModel::wetJsonRt(200,'success',$data);
	}

}