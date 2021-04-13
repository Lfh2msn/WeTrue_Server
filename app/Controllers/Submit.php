<?php
namespace App\Controllers;

use App\Models\PraiseModel;
use App\Models\StarModel;
use App\Models\hashReadModel;

class Submit extends BaseController {

	public function praise()
    {//点赞
        $hash   = $this->request->getPost('hash');
        $type   = $this->request->getPost('type');
		$isHash = $this->DisposeModel-> checkAddress($hash);
        if($isHash && $type == 'topic' || $type == 'comment' || $type == 'reply'){
            $data = (new PraiseModel())-> praise($hash, $type);
		    echo $data;
        }else{
			$data['code'] = 406;
			$data['msg']  = 'error';
			echo json_encode($data);
		}
		
    }

    public function focus()
	{//关注
		$userAddress   = $this->request->getPost('userAddress');
        $isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		if($isUserAddress){
            $data = $this->FocusModel-> focus($userAddress);
		    echo $data;
        }else{
			$data['code'] = 406;
			$data['msg']  = 'error';
            echo json_encode($data);
		}
	}

    public function contentStar()
	{//收藏
		$hash  = $this->request->getPost('hash');
		$isHash = $this->DisposeModel-> checkAddress($hash);
		if($isHash){
            $data = (new StarModel())-> star($hash);
			echo $data;
        }else{
			$data['code'] = 406;
			$data['msg']  = 'error_hash';
            echo json_encode($data);
		}
	}

	public function tx($hash)
	{//发布hash
		//$hash  = $this->request->getPost('hash');
		$isHash = $this->DisposeModel-> checkAddress($hash);

		if($isHash){
            $data = (new hashReadModel())-> split($hash);
			echo $data;
        }else{
			$data['code'] = 406;
			$data['msg']  = 'error_hash';
            echo json_encode($data);
		}
	}
}