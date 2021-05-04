<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\DisposeModel;
use App\Models\UserModel;
use App\Models\ConfigModel;
use App\Models\ContentModel;
use App\Models\CommentModel;
use App\Models\ReplyModel;

class BloomModel extends Model {
//过滤Model

    public function __construct(){
		parent::__construct();
		$this->DisposeModel  = new DisposeModel();
		$this->UserModel	 = new UserModel();
        $this->ConfigModel	 = new ConfigModel();
		$this->wet_complain  = 'wet_complain';
		$this->wet_bloom     = 'wet_bloom';
        $this->wet_content   = 'wet_content';
        $this->wet_comment   = 'wet_comment';
        $this->wet_reply     = 'wet_reply';
        $this->wet_behavior  = 'wet_behavior';
        
	}
    
   public function ipBloom($ip)
   {//过滤IP，存在返回false
        $sql   = "SELECT bf_ip FROM $this->wet_bloom WHERE bf_ip = '$ip' LIMIT 1";
        $query = $this->db->query($sql);
        $row   = $query->getRow();
        return $row ? false : true;
    }
	
	public function txBloom($hash)
    {//过滤TX，存在返回false
        $sql   = "SELECT bf_hash FROM $this->wet_bloom WHERE bf_hash = '$hash' LIMIT 1";
        $query = $this->db->query($sql);
        $row   = $query->getRow();
        return $row ? false : true;
    }

    public function addressBloom($address)
    {//过滤address，存在返回false
        $sql   = "SELECT bf_address FROM $this->wet_bloom WHERE bf_address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
        $row   = $query->getRow();
        return $row ? false : true;
    }

    public function deleteBloom($hash)
	{//删除过滤
		$deleteSql = "DELETE FROM $this->wet_bloom WHERE bf_hash = '$hash'";
		$this->db-> query($deleteSql);
	}

    public function bloomHash($hash)
    {//过滤TX入库bloom
        $akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		$isAdmin   = $this->UserModel-> isAdmin($akToken);
		$data['code'] = 200;
		if (!$isAkToken || !$isAdmin) {
			$data['code'] = 401;
			$data['msg']  = 'error_login';
			return json_encode($data);
		}

        $isComplain = (new ComplainModel())-> isComplain($hash);
        if ($isComplain) {
            $data['code'] = 401;
			$data['msg']  = 'error_no_complain';
			return json_encode($data);
        }

        $isBloom = $this->txBloom($hash);
        if (!$isBloom) {
			$data['msg']  = 'repeat';
			return json_encode($data);
        }

        $insertBloom = "INSERT INTO $this->wet_bloom(bf_hash,bf_reason) VALUES ('$hash','admin_bf')";
        $this->db->query($insertBloom);

        $senderID = (new ComplainModel())-> complainAddress($hash);
        $bsConfig = $this->ConfigModel-> backendConfig();
        $active = $bsConfig['complainActive'];
        $this->UserModel-> userActive($senderID, $active, $e = false);

        (new ComplainModel())-> deleteComplain($hash);

		$insetrBehaviorSql = "INSERT INTO $this->wet_behavior(
                                    address, hash, thing, influence, toaddress
                                ) VALUES (
                                    '$akToken', '$hash', 'admin_bf', '-$active', '$senderID'
                                )";
        $this->db->query($insetrBehaviorSql);

        $data['msg']  = 'success';
        return json_encode($data);
    }

    public function unBloom($hash)
    {//撤销过滤
        $akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		$isAdmin   = $this->UserModel-> isAdmin($akToken);
		$data['code'] = 200;
		if (!$isAkToken || !$isAdmin) {
			$data['code'] = 401;
			$data['msg']  = 'error_login';
			return json_encode($data);
		}

        (new ComplainModel())-> deleteComplain($hash);
        $this-> deleteBloom($hash);

        $data['msg']  = 'success';
        return json_encode($data);
    }

    public function limit($page, $size, $opt=[])
	{//屏蔽列表分页
		$page = max(1, (int)$page);
		$size = max(1, (int)$size);
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		$isAdmin   = $this->UserModel-> isAdmin($akToken);
		$data['code'] = 200;
		$data['data']['data'] = [];
		if (!$isAkToken || !$isAdmin) {
			$data['code'] = 401;
			$data['msg']  = 'error_login';
			return json_encode($data);
		}
		$opt['userLogin'] = $akToken;

		$countSql = "SELECT count(bf_hash) FROM $this->wet_bloom";
		$limitSql = "SELECT bf_hash AS hash FROM $this->wet_bloom LIMIT $size OFFSET ".($page-1)*$size;

		$data = $this->cycle($page, $size, $countSql, $limitSql, $opt);
		return json_encode($data);
    }

	private function cycle($page, $size, $countSql, $limitSql, $opt)
	{//列表循环
		$data['code'] = 200;
		$data['data'] = $this->pages($page, $size, $countSql);
		$query = $this->db-> query($limitSql);
		$data['data']['data'] = [];
		foreach ($query-> getResult() as $row){
			$hash  = $row -> hash;

			$conSql   = "SELECT hash FROM $this->wet_content WHERE hash='$hash' LIMIT 1";
			$conQuery = $this-> db-> query($conSql);
			$conRow   = $conQuery-> getRow();

			if ($conRow) {
				$detaila[] = (new ContentModel())-> txContent($hash, $opt);
			}else{
				$comSql   = "SELECT hash FROM $this->wet_comment WHERE hash='$hash' LIMIT 1";
				$comQuery = $this-> db-> query($comSql);
				$comRow   = $comQuery-> getRow();
			}
			
			if ($comRow) {
				$detaila[] = (new CommentModel())-> txComment($hash, $opt);
			}else{
				$repSql   = "SELECT hash FROM $this->wet_reply WHERE hash='$hash' LIMIT 1";
				$repQuery = $this-> db-> query($repSql);
				$repRow   = $repQuery-> getRow();
			}

			if ($repRow) {
				$detaila[] = (new ReplyModel())-> txReply($hash, $opt);
			}

			$data['data']['data'] = $detaila;
		}
		$data['msg'] = 'success';
		return $data;
	}

	private function pages($page, $size, $sql)
	{
		$query  = $this->db-> query($sql);
		$row	= $query-> getRow();
        $count	= $row->count;  //总数量
		$data	= [
			'currentPage'	=> $page,  //当前页
			'perPage'		=> $size,  //每页数量
			'totalPage'		=> (int)ceil($count/$size),  //总页数
			'lastPage'		=> (int)ceil($count/$size),  //总页数
			'totalSize'		=> (int)$count  //总数量
		];
		return $data;
	}
}

