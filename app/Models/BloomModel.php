<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	UserModel,
	ReplyModel,
	ValidModel,
	AmountModel,
	DisposeModel,
	CommentModel,
	ComplainModel
};
use App\Models\Get\{
	GetAeChainModel,
	GetAeknowModel
};
use App\Models\Content\ContentPullModel;
use App\Models\Config\ActiveConfig;

class BloomModel
{//过滤Model

	public function __construct()
	{
		$this->wet_reply    = "wet_reply";
		$this->wet_bloom    = "wet_bloom";
        $this->wet_content  = "wet_content";
        $this->wet_comment  = "wet_comment";
        $this->wet_behavior = "wet_behavior";
	}

	public function userCheck($address)
	{//账户检查
		$isNewUser = ValidModel::isNewUser($address);
		if (!$isNewUser) return true;
		$isAmountVip = ValidModel::isAmountVip($address);
		if ($isAmountVip) {
			$balance = GetAeChainModel::accountsBalance($address);
			$bigAE   = DisposeModel::bigNumber("div", $balance);
			$floorAE = floor($bigAE);
			$mulAE   = $floorAE-0.01;
			$amount  = DisposeModel::bigNumber("mul", $mulAE);
			if ($floorAE >= 10) $amount = 99999e14;
			AmountModel::insertAmountUser($address, $amount);
			return true;
		}

		$senderList = GetAeknowModel::latestSpendTx($address);
		if (!$senderList) return false;

		//提取账户链上余额，及查询写入金额
		$balance = GetAeChainModel::accountsBalance($address);
		if (!$balance) return false;
		$bigAE   = DisposeModel::bigNumber("div", $balance);
		$floorAE = floor($bigAE);
		$mulAE   = $floorAE-0.01;
		$amount  = DisposeModel::bigNumber("mul", $mulAE);
		if ($floorAE >= 10) $amount = 99999e14;
		
		foreach ($senderList as $sender) {
			$isBloomAddress = ValidModel::isBloomAddress($sender);
			$isAmountVip = ValidModel::isAmountVip($sender);
			if ($isBloomAddress || $isAmountVip) {
				AmountModel::insertAmountUser($address, $amount);
				$logMsg  = date('Y-m-d')."-抓到一枚VIP,地址:{$address},收费:{$amount}";
				$logPath = "auto_amount_vip/".date('Y-m');
				DisposeModel::wetFwriteLog($logMsg, $logPath);
			}
			return true;
		}
	}

    public function deleteBloomHash($hash)
	{//删除过滤
		$deleteSql = "DELETE FROM $this->wet_bloom WHERE bf_hash = '$hash'";
		ComModel::db()-> query($deleteSql);
	}

    public function bloomHash($hash)
    {//过滤TX入库bloom
        $akToken   = isset($_SERVER['HTTP_KEY']) ? $_SERVER['HTTP_KEY'] : false;
		$isAkToken = DisposeModel::checkAddress($akToken);
		$isAdmin   = ValidModel::isAdmin($akToken);
		if (!$isAkToken || !$isAdmin) {
			return DisposeModel::wetJsonRt(401, 'error_login');
		}

        $isComplain = ValidModel::isComplain($hash);
        if (!$isComplain) {
			return DisposeModel::wetJsonRt(401, 'error_no_complain');
        }

        $isBloomHash = ValidModel::isBloomHash($hash);
        if ($isBloomHash) {
			return DisposeModel::wetJsonRt(200, 'error_repeat');
        }

        $insertBloom = "INSERT INTO $this->wet_bloom(bf_hash,bf_reason) VALUES ('$hash','admin_bf')";
        ComModel::db()->query($insertBloom);

        $senderID = (new ComplainModel())-> complainAddress($hash);
        $acConfig = ActiveConfig::config();
        $clActive = $acConfig['complainActive'];
        UserModel::userActive($senderID, $clActive, $e = false);

        (new ComplainModel())-> deleteComplain($hash);

		$insetrBehaviorDate = [
			'address'   => $akToken,
			'hash'      => $hash,
			'thing'     => 'admin_bf',
			'influence' => '-'.$clActive,
			'toaddress' => $senderID
		];
		ComModel::db()->table($this->wet_behavior)->insert($insetrBehaviorDate);

		return DisposeModel::wetJsonRt(200, 'success');
    }

    public function unBloom($hash)
    {//撤销过滤
        $akToken   = isset($_SERVER['HTTP_KEY']) ? $_SERVER['HTTP_KEY'] : false;
		$isAkToken = DisposeModel::checkAddress($akToken);
		$isAdmin   = ValidModel::isAdmin($akToken);
		if (!$isAkToken || !$isAdmin) {
			return DisposeModel::wetJsonRt(401, 'error_login');
		}

        (new ComplainModel())-> deleteComplain($hash);
        $this-> deleteBloomHash($hash);
        return DisposeModel::wetJsonRt(200, 'success');
    }

    public function limit($page, $size, $offset, $opt=[])
	{//屏蔽列表分页
		$page   = max(1, (int)$page);
		$size   = max(1, (int)$size);
		$offset = max(0, (int)$offset);
		$akToken   = isset($_SERVER['HTTP_KEY']) ? $_SERVER['HTTP_KEY'] : false;
		$isAkToken = DisposeModel::checkAddress($akToken);
		$isAdmin   = ValidModel::isAdmin($akToken);
		if ( !$isAkToken || !$isAdmin ) {
			return DisposeModel::wetJsonRt(401, 'error_login');
		}
		$opt['userLogin'] = $akToken;

		$countSql = "SELECT count(bf_hash) FROM $this->wet_bloom";
		$limitSql = "SELECT bf_hash AS hash FROM $this->wet_bloom LIMIT $size OFFSET ".(($page-1) * $size + $offset);
		$data['data'] = [];
		$data = $this->cycle($page, $size, $countSql, $limitSql, $opt);

		return DisposeModel::wetJsonRt(200, 'success', $data);
    }

	private function cycle($page, $size, $countSql, $limitSql, $opt)
	{//列表循环

		$data = $this->pages($page, $size, $countSql);
		$query = ComModel::db()-> query($limitSql);
		$data['data'] = [];
		foreach ($query-> getResult() as $row) {
			$hash  = $row -> hash;

			$conSql   = "SELECT hash FROM $this->wet_content WHERE hash='$hash' LIMIT 1";
			$conQuery = ComModel::db()-> query($conSql);
			$conRow   = $conQuery-> getRow();

			if ($conRow) {
				$detaila[] = ContentPullModel::txContent($hash, $opt);
			} else {
				$comSql   = "SELECT hash FROM $this->wet_comment WHERE hash='$hash' LIMIT 1";
				$comQuery = ComModel::db()-> query($comSql);
				$comRow   = $comQuery-> getRow();
			}
			
			if ($comRow) {
				$detaila[] = CommentModel::txComment($hash, $opt);
			} else {
				$repSql   = "SELECT hash FROM $this->wet_reply WHERE hash='$hash' LIMIT 1";
				$repQuery = ComModel::db()-> query($repSql);
				$repRow   = $repQuery-> getRow();
			}

			if ($repRow) {
				$detaila[] = ReplyModel::txReply($hash, $opt);
			}

			$data['data'] = $detaila;
		}
		return $data;
	}

	private function pages($page, $size, $sql)
	{
		$query = ComModel::db()-> query($sql);
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

