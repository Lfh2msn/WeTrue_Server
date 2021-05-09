<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\UserModel;
use App\Models\DisposeModel;

class FocusModel extends Model {
//关注Model

	public function __construct(){
		parent::__construct();
		$this->tablename    = "wet_focus";
		$this->userModel    = new UserModel();
		$this->DisposeModel = new DisposeModel();
	}

	public function isFocus($focus,$my_id)
	{//获取关注状态
		$sql   = "SELECT focus, fans FROM $this->tablename WHERE fans = '$my_id' AND focus = '$focus' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		if($row) {
			return true;
        }else{
			return false;
		}
	}

    public function limit($page, $size, $opt=[])
	{/*分页
		opt可选参数
			[
				substr	  => (int)截取字节
				type	  => 列表标签类型
				publicKey => 钱包地址
				hash	  => hash
				userLogin => 登录用户钱包地址
				focus	  => 关注类型[myFocus\focusMy]
			];*/
		$page = max(1, (int)$page);
		$size = max(1, (int)$size);
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		$data['code'] = 200;
		$data['data']['data'] = [];
		if(!$isAkToken){
			$data['code'] = 401;
			$data['msg']  = 'error_login';
			return json_encode($data);
		}
		$opt['userLogin'] = $akToken;

		if($opt['type'] == 'userFocusUserList'){
		//关注、被关注列表
			$akToken    = $opt['userLogin'];
			if($opt['focus'] == "myFocus"){
				$field	  = "fans";
				$contrary = "focus";
			}

			if($opt['focus'] == "focusMy"){
				$field	  = "focus";
				$contrary = "fans";
			}
			$countSql = "SELECT count($field) FROM $this->tablename WHERE $field = '$akToken'";
			$limitSql = "SELECT $contrary AS contrary FROM $this->tablename 
								WHERE $field='$akToken' ORDER BY uid DESC LIMIT $size OFFSET ".($page-1) * $size;
		}

		$data = $this->cycle($page, $size, $countSql, $limitSql, $opt);
		return json_encode($data);
    }

	private function cycle($page, $size, $countSql, $limitSql, $opt)
	{//用户列表循环
		$data['data'] = $this->pages($page, $size, $countSql);
		$query = $this-> db-> query($limitSql);
		foreach ($query-> getResult() as $row){
			$userAddress  = $row -> contrary;
			$userInfo[]	  = $this->userModel-> userAllInfo($userAddress, $opt);
			$data['data']['data'] = $userInfo;
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


	public function focus($userAddress)
	{//关注
		$data['code'] = 200;
		$akToken = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if(!$isAkToken){
			$data['code'] = 401;
			$data['msg']  = 'error_login';
			return json_encode($data);
		}

		$verify = $this->isFocus($userAddress, $akToken);
		if(!$verify){
			$focusSql = "INSERT INTO $this->tablename(focus, fans) VALUES ('$userAddress', '$akToken')";
			$e = true;
		}else{
			$focusSql = "DELETE FROM $this->tablename WHERE focus = '$userAddress' AND fans = '$akToken'";
			$e = false;
		}
		$this->db-> query($focusSql);
		$this->userModel-> userFocus($userAddress, $akToken, $e);
		//入库行为记录
		$focusBehaviorSql = "INSERT INTO wet_behavior(address,thing,toaddress) 
								VALUES ('$akToken', 'isFocus', '$userAddress')";
		$this->db->query($focusBehaviorSql);
		$isFocus = $this->isFocus($userAddress, $akToken);
		$data['data']['isFocus'] = $isFocus;
		$data['msg'] = 'success';
		
		return json_encode($data);
	}


}

