<?php namespace App\Models;

use App\Models\ComModel;
use App\Models\GetModel;
use App\Models\UserModel;
use App\Models\ReplyModel;
use App\Models\ValidModel;
use App\Models\AmountModel;
use App\Models\ConfigModel;
use App\Models\DisposeModel;
use App\Models\ContentModel;
use App\Models\CommentModel;
use App\Models\ComplainModel;

class BloomModel extends ComModel {
//过滤Model

	public function __construct()
	{
		parent::__construct();
		$this->GetModel		= new GetModel();
		$this->UserModel	= new UserModel();
		$this->ValidModel	= new ValidModel();
		$this->AmountModel	= new AmountModel();
		$this->ConfigModel	= new ConfigModel();
		$this->DisposeModel = new DisposeModel();
		$this->wet_reply    = "wet_reply";
		$this->wet_bloom    = "wet_bloom";
        $this->wet_content  = "wet_content";
        $this->wet_comment  = "wet_comment";
        $this->wet_behavior = "wet_behavior";
	}

	public function userCheck($address)
	{//账户检查
		$isNewUser = $this->ValidModel-> isNewUser($address);
		if (!$isNewUser) return true;
		$isAmountVip = $this->ValidModel-> isAmountVip($address);
		if ($isAmountVip) return true;
		$senderList = $this->GetModel-> getSenderByLatestTx($address);
		if (!$senderList) return false;
		foreach ($senderList as $sender) {
			$isBloomAddress = $this->ValidModel-> isBloomAddress($sender);
			$isAmountVip = $this->ValidModel-> isAmountVip($sender);
			if ($isBloomAddress || $isAmountVip) {
				$balance = $this->GetModel-> getAccountsBalance($address);
				if (!$balance) return false;
				$bigAE   = $this->DisposeModel-> bigNumber("div", $balance);
				$floorAE = floor($bigAE);
				$mulAE   = $floorAE-0.01;
				$amount = $this->DisposeModel-> bigNumber("mul", $mulAE);
				if ($floorAE >= 10) $amount = 99999e14;
				$this->AmountModel-> insertAmountUser($address, $amount);
				$logMsg  = date('Y-m-d')."抓到一枚VIP,地址:{$address},收费:{$amount}\r\n";
				$logPath = "log/auto_amount_vip/".date('Y-m').".txt";
				$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			}
			return true;
		}
	}

    public function deleteBloomHash($hash)
	{//删除过滤
		$deleteSql = "DELETE FROM $this->wet_bloom WHERE bf_hash = '$hash'";
		$this->db-> query($deleteSql);
	}

    public function bloomHash($hash)
    {//过滤TX入库bloom
        $akToken   = $_SERVER['HTTP_AK_TOKEN'] ?? $_SERVER['HTTP_KEY'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		$isAdmin   = $this->ValidModel-> isAdmin($akToken);
		if (!$isAkToken || !$isAdmin) {
			return $this->DisposeModel-> wetJsonRt(401, 'error_login');
		}

        $isComplain = $this->ValidModel-> isComplain($hash);
        if (!$isComplain) {
			return $this->DisposeModel-> wetJsonRt(401, 'error_no_complain');
        }

        $isBloomHash = $this->ValidModel-> isBloomHash($hash);
        if ($isBloomHash) {
			return $this->DisposeModel-> wetJsonRt(200, 'error_repeat');
        }

        $insertBloom = "INSERT INTO $this->wet_bloom(bf_hash,bf_reason) VALUES ('$hash','admin_bf')";
        $this->db->query($insertBloom);

        $senderID = (new ComplainModel())-> complainAddress($hash);
        $bsConfig = $this->ConfigModel-> backendConfig();
        $active = $bsConfig['complainActive'];
        $this->UserModel-> userActive($senderID, $active, $e = false);

        (new ComplainModel())-> deleteComplain($hash);

		$insetrBehaviorDate = [
			'address'   => $akToken,
			'hash'      => $hash,
			'thing'     => 'admin_bf',
			'influence' => '-'.$active,
			'toaddress' => $senderID
		];
		$this->db->table($this->wet_behavior)->insert($insetrBehaviorDate);

		return $this->DisposeModel-> wetJsonRt(200, 'success');
    }

    public function unBloom($hash)
    {//撤销过滤
        $akToken   = $_SERVER['HTTP_AK_TOKEN'] ?? $_SERVER['HTTP_KEY'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		$isAdmin   = $this->ValidModel-> isAdmin($akToken);
		if (!$isAkToken || !$isAdmin) {
			return $this->DisposeModel-> wetJsonRt(401, 'error_login');
		}

        (new ComplainModel())-> deleteComplain($hash);
        $this-> deleteBloomHash($hash);
        return $this->DisposeModel-> wetJsonRt(200, 'success');
    }

    public function limit($page, $size, $offset, $opt=[])
	{//屏蔽列表分页
		$page   = max(1, (int)$page);
		$size   = max(1, (int)$size);
		$offset = max(0, (int)$offset);
		$akToken   = $_SERVER['HTTP_AK_TOKEN'] ?? $_SERVER['HTTP_KEY'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		$isAdmin   = $this->ValidModel-> isAdmin($akToken);
		if ( !$isAkToken || !$isAdmin ) {
			return $this->DisposeModel-> wetJsonRt(401, 'error_login');
		}
		$opt['userLogin'] = $akToken;

		$countSql = "SELECT count(bf_hash) FROM $this->wet_bloom";
		$limitSql = "SELECT bf_hash AS hash FROM $this->wet_bloom LIMIT $size OFFSET ".(($page-1) * $size + $offset);
		$data['data'] = [];
		$data = $this->cycle($page, $size, $countSql, $limitSql, $opt);

		return $this->DisposeModel-> wetJsonRt(200, 'success', $data);
    }

	private function cycle($page, $size, $countSql, $limitSql, $opt)
	{//列表循环

		$data = $this->pages($page, $size, $countSql);
		$query = $this->db-> query($limitSql);
		$data['data'] = [];
		foreach ($query-> getResult() as $row) {
			$hash  = $row -> hash;

			$conSql   = "SELECT hash FROM $this->wet_content WHERE hash='$hash' LIMIT 1";
			$conQuery = $this-> db-> query($conSql);
			$conRow   = $conQuery-> getRow();

			if ($conRow) {
				$detaila[] = (new ContentModel())-> txContent($hash, $opt);
			} else {
				$comSql   = "SELECT hash FROM $this->wet_comment WHERE hash='$hash' LIMIT 1";
				$comQuery = $this-> db-> query($comSql);
				$comRow   = $comQuery-> getRow();
			}
			
			if ($comRow) {
				$detaila[] = (new CommentModel())-> txComment($hash, $opt);
			} else {
				$repSql   = "SELECT hash FROM $this->wet_reply WHERE hash='$hash' LIMIT 1";
				$repQuery = $this-> db-> query($repSql);
				$repRow   = $repQuery-> getRow();
			}

			if ($repRow) {
				$detaila[] = (new ReplyModel())-> txReply($hash, $opt);
			}

			$data['data'] = $detaila;
		}
		return $data;
	}

	private function pages($page, $size, $sql)
	{
		$query = $this->db-> query($sql);
		$row   = $query-> getRow();
        $count = $row->count;  //总数量
		$data  = [
			'page'		=> $page,  //当前页
			'size'		=> $size,  //每页数量
			'totalPage'	=> (int)ceil($count/$size),  //总页数
			'totalSize'	=> (int)$count  //总数量
		];
		return $data;
	}
}

