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
		if ($type == 'shTipid') {
			$isHash = $this->DisposeModel-> checkSuperheroTipid($hash);
		} else {
			$isHash = $this->DisposeModel-> checkAddress($hash);
		}

        if (
			$isHash 
			&& $type == 'topic' 
			|| $type == 'comment' 
			|| $type == 'reply' 
			|| $type == 'shTipid'
		) {
            $data = (new PraiseModel())-> praise($hash, $type);
		    echo $data;
        } else {
			echo $this->DisposeModel-> wetJsonRt(406, 'error');
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
			echo $this->DisposeModel-> wetJsonRt(406, 'error');
		}
	}

    public function contentStar()
	{//收藏
		$getHash   = $this->request->getPost('hash');
		$isHash    = $this->DisposeModel-> checkAddress($getHash);
		$isShTipid = $this->DisposeModel-> checkSuperheroTipid($getHash);
		$isCheck   = $isHash ?? $isShTipid;

		$select == 'contentStar';
		if ($isShTipid) {
			$select == 'shTipidStar';
		}

		if ($isCheck) {
            $data = (new StarModel())-> star($getHash, $select);
			echo $data;
        } else {
            echo $this->DisposeModel-> wetJsonRt(406, 'error_hash');
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
			echo $this->DisposeModel-> wetJsonRt(406, 'error_type');
		} else {
			echo $this->DisposeModel-> wetJsonRt(406, 'error_hash');
		}
	}

	public function reward()
	{//打赏
		$hash  	  = $this->request->getPost('hash');
		$to_hash  = $this->request->getPost('toHash');
		$isHash   = $this->DisposeModel-> checkAddress($hash);
		$isToHash = $this->DisposeModel-> checkAddress($to_hash);

		if ($isHash && $isToHash) {
            (new RewardModel())-> reward($hash, $to_hash);
        } else {
			echo $this->DisposeModel-> wetJsonRt(406, 'error_hash');
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
			echo $this->DisposeModel-> wetJsonRt(406, 'error_hash');
		}
	}

	public function search()
    {//搜索
		$page   = $this->request->getPost('page');
        $size   = $this->request->getPost('size');
        $offset = $this->request->getPost('offset');
		$type   = $this->request->getPost('type');
		$key    = $this->request->getPost('key');

		$opt  = [
			'type' => $type,
			'key'  => $key
		];
        if ($type && $key) {
            $data = (new SearchModel())-> search($page, $size, $offset, $opt);
		    echo $data;
		} else {
			echo $this->DisposeModel-> wetJsonRt(406, 'error');
		}
		
    }

}