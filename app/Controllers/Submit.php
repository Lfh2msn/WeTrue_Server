<?php
namespace App\Controllers;

use App\Models\PraiseModel;
use App\Models\StarModel;
use App\Models\HashReadModel;
use App\Models\RewardModel;
use App\Models\ComplainModel;
use App\Models\SearchModel;

class Submit extends BaseController {

	public function praise()
    {//点赞
        $hash   = $this->request->getPost('hash');
        $type   = $this->request->getPost('type');
		$isHash = $this->DisposeModel-> checkAddress($hash);
        if ($isHash && $type == 'topic' || $type == 'comment' || $type == 'reply') {
            $data = (new PraiseModel())-> praise($hash, $type);
		    echo $data;
        } else {
			$data['code'] = 406;
			$data['msg']  = 'error';
			echo json_encode($data);
		}
		
    }

    public function focus()
	{//关注
		$userAddress   = $this->request->getPost('userAddress');
        $isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		if ($isUserAddress) {
            $data = $this->FocusModel-> focus($userAddress);
		    echo $data;
        } else {
			$data['code'] = 406;
			$data['msg']  = 'error';
            echo json_encode($data);
		}
	}

    public function contentStar()
	{//收藏
		$hash  = $this->request->getPost('hash');
		$isHash = $this->DisposeModel-> checkAddress($hash);
		if ($isHash) {
            $data = (new StarModel())-> star($hash);
			echo $data;
        } else {
			$data['code'] = 406;
			$data['msg']  = 'error_hash';
            echo json_encode($data);
		}
	}

	public function hash()
	{//发布hash
		$hash  = $this->request->getPost('hash');
		$isHash = $this->DisposeModel-> checkAddress($hash);
		if ($isHash) {
            $data = (new HashReadModel())-> split($hash);
			echo $data;
        } elseif (!$hash) {
			$data['code'] = 406;
			$data['msg']  = 'error_type';
			echo json_encode($data);
		} else {
			$data['code'] = 406;
			$data['msg']  = 'error_hash';
			echo json_encode($data);
		}
	}

	public function reward()
	{//打赏
		$hash  	  = $this->request->getPost('hash');
		$to_hash  = $this->request->getPost('toHash');
		$isHash   = $this->DisposeModel-> checkAddress($hash);
		$isToHash = $this->DisposeModel-> checkAddress($to_hash);

		if ($isHash && $isToHash) {
            $data = (new RewardModel())-> reward($hash, $to_hash);
			echo $data;
        } else {
			$data['code'] = 406;
			$data['msg']  = 'error_hash';
			echo json_encode($data);
		}
	}

	public function complain()
	{//投诉hash
		$hash  = $this->request->getPost('hash');
		$isHash = $this->DisposeModel-> checkAddress($hash);
		if ($isHash) {
            $data = (new ComplainModel())-> txHash($hash);
			echo $data;
        } else {
			$data['code'] = 406;
			$data['msg']  = 'error_hash';
            echo json_encode($data);
		}
	}

	public function search()
    {//搜索
		$page = $this->request->getPost('page');
        $size = $this->request->getPost('size');
		$type = $this->request->getPost('type');
		$key  = $this->request->getPost('key');

		$opt  = [
			'type' => $type,
			'key'  => $key
		];
        if ($type && $key) {
            $data = (new SearchModel())-> search($page, $size, $opt);
		    echo $data;
		} else {
			$data['code'] = 406;
			$data['msg']  = 'error';
			echo json_encode($data);
		}
		
    }

}