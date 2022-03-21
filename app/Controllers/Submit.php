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

	public function hash()
	{//发布hash
		$hash    = $this->request->getPost('hash');
		$await   = $this->request->getPost('await');
		$chainId = $_SERVER['HTTP_CHAIN_ID'] ?? 457;
		if ($await){
			$await = true;
		} else {
			$await = false;
		}
		$isHash = $this->DisposeModel-> checkAddress($hash);
		if ($isHash) {
            $data = (new HashReadModel())-> split($hash, $await, $chainId);
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
		$isShid   = $this->DisposeModel-> checkSuperheroTipid($to_hash);

		if ($isHash && ($isToHash || $isShid)) {
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