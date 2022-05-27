<?php
namespace App\Controllers;

use App\Models\ValidModel;

class User extends BaseController {

	public function info()
	{//获取用户完整信息
        $userAddress   = $this->request->getPost('userAddress');
		$typeLogin     = $this->request->getPost('type');
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		if($isUserAddress){
			$code = 200;
			if($typeLogin){
				$opt = ['type' => $typeLogin];
			}
			$userInfo = $this->UserModel-> userAllInfo($userAddress, $opt);
			$content  = '';
			if($userInfo){
				$content = $userInfo;
				$msg = 'success';
			}else{
				$msg = 'error_address';
			}
		}else{
			$code = 406;
			$msg  = 'error';
		}
		echo $this->DisposeModel-> wetJsonRt($code, $msg, $content);
    }

	public function contentList()
	{//用户主贴列表
		$page   = $this->request->getPost('page');
        $size   = $this->request->getPost('size');
        $offset = $this->request->getPost('offset');
		$userAddress = $this->request->getPost('userAddress');
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		if($isUserAddress){
			$type = 'userContentList';
			$opt  =	[
					'type' 		=> $type,
					'publicKey' => $userAddress
				];
			$data = $this->PagesModel-> limit($page, $size, $offset, $opt);
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
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		$type  = 'userFocusUserList';
		if($isUserAddress && ($focus == 'myFocus' || $focus == 'focusMy')){
			$opt = [
				'type'    => $type,
				'focus'   => $focus, //focus => 可选类型 myFocus\focusMy
				'address' => $userAddress
			];
			$data  = $this->FocusModel-> limit($page, $size, $offset, $opt);
			echo $data;
		}else{
			echo $this->DisposeModel-> wetJsonRt(406,'error');
		}
	}

	public function isNickname()
	{//获取昵称是否存在
		$nickname = $this->request->getPost('nickname');
		$type = (new ValidModel())-> isNickname($nickname);
		$data['isNickname'] = $type;
		echo $this->DisposeModel-> wetJsonRt(200,'success',$data);
	}

}