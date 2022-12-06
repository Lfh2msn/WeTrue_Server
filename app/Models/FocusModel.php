<?php namespace App\Models;

use CodeIgniter\Model;
use Config\Database;
use App\Models\{
	UserModel,
	DisposeModel,
	ValidModel
};

class FocusModel extends Model {
//关注Model

	public function __construct(){
		//parent::__construct();
		$this->db = Database::connect('default');
		$this->UserModel    = new UserModel();
		$this->ValidModel   = new ValidModel();
		$this->tablename    = "wet_focus";
		$this->wet_behavior = "wet_behavior";
	}

    public function limit($page, $size, $offset, $opt=[])
	{/*分页
	opt可选参数
		[type	 => 列表标签类型
		 focus	 => 关注类型[myFocus\focusMy]
		 address => 地址
		];
	*/
		$page   = max(1, (int)$page);
		$size   = max(1, (int)$size);
		$offset = max(0, (int)$offset);
		$address = $opt['address'];
		if ($opt['type'] == 'userFocusUserList') {
			if($opt['focus'] == "myFocus") {  //关注列表
				$field	  = "fans";
				$contrary = "focus";
			}

			if($opt['focus'] == "focusMy") {  //被关注列表
				$field	  = "focus";
				$contrary = "fans";
			}
			$countSql = "SELECT count($field) FROM $this->tablename WHERE $field = '$address'";
			$limitSql = "SELECT $contrary AS contrary FROM $this->tablename 
								WHERE $field='$address' 
								ORDER BY focus_time DESC LIMIT $size OFFSET ".(($page-1) * $size + $offset);
		}
		
		$data = $this->cycle($page, $size, $countSql, $limitSql);
		return DisposeModel::wetJsonRt(200, 'success', $data);
    }

	private function cycle($page, $size, $countSql, $limitSql)
	{  //用户列表循环

		$data = $this->pages($page, $size, $countSql);
		$data['data'] = [];
		$query = $this-> db-> query($limitSql);
		foreach ($query-> getResult() as $row) {
			$userAddress  = $row -> contrary;
			$userInfo[]	  = $this->UserModel-> userAllInfo($userAddress);
			$data['data'] = $userInfo;
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


	public function focus($focus, $fans, $action)
	{//关注
		$verify = $this->ValidModel-> isUser($fans);
		if (!$verify) $this->UserModel-> userPut($fans);

		$isFocus = $this->ValidModel-> isFocus($focus, $fans);
		if (!$isFocus && $action == 'true') {
			$focusSql = "INSERT INTO $this->tablename(focus, fans) VALUES ('$focus', '$fans')";
			$e = true;
		}
		
		elseif ($isFocus && $action == 'false') {
			$focusSql = "DELETE FROM $this->tablename WHERE focus = '$focus' AND fans = '$fans'";
			$e = false;
	 	}

		else die("focus Error");

		$this->db-> query($focusSql);
		$this->UserModel-> userFocus($focus, $fans, $e);
	}

	public function autoFocus($focus ,$fans)
	{//自动A账户关注B账户
		$verify = $this->ValidModel-> isFocus($focus, $fans);
		if (!$verify) {
			$focusSql = "INSERT INTO $this->tablename(focus, fans) VALUES ('$focus', '$fans')";
			$e = true;
		} else {
			die("isFocus Error");
		}
		$this->db-> query($focusSql);
		$this->UserModel-> userFocus($focus, $fans, $e);
		//入库行为记录
		$insetrBehaviorDate = [
			'address'   => $akToken,
			'thing'     => 'autoFocus',
			'toaddress' => $userAddress
		];
		$this->db->table($this->wet_behavior)->insert($insetrBehaviorDate);
	}

}

