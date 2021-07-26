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
			$data['code'] = 200;
			if($typeLogin){
				$opt = ['type' => $typeLogin];
			}
			$userInfo	  = $this->UserModel-> userAllInfo($userAddress, $opt);
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

	public function portraitUrl($address)
	{//获取头像地址
		$isAddress = $this->DisposeModel-> checkAddress($address);
		if ($isAddress) {
			$data['url'] = $this->UserModel-> getPortraitUrl($address);
			return $this->DisposeModel-> wetJsonRt(200,'success',$data);
		}
	}

	public function portrait($address)
	{//获取头像
		$portrait = $this->UserModel-> getPortrait($address);
		$portrait = str_replace("data:image/jpeg;base64,","",$portrait);
		$portrait = base64_decode($portrait);
		$this->response->setHeader('Expires', date(DATE_RFC1123, strtotime("+7 day") ) );
		$this->response->setHeader('Content-type', 'image/jpeg');
		echo $portrait;
		$this->cachePage(30);
	}

	public function isNickname()
	{//获取昵称是否存在
		$nickname = $this->request->getPost('nickname');
		$data['code'] = 200;
		$data['msg']  = 'success';
		$type = (new ValidModel())-> isNickname($nickname);
		$data['isNickname'] = $type;
		$data['data']['isNickname'] = $type;
		echo json_encode($data);
		//$this->DisposeModel-> wetJsonRt(200,'success',$data);
	}

}