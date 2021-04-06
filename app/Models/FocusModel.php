<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\UserModel;

class FocusModel extends Model {
//关注Model

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
		$opt['userLogin'] = $_SERVER['HTTP_AK_TOKEN'];

		if($opt['type'] == 'userFocusUserList'){
			//关注、被关注列表
			$this->tablename = "wet_follow";
			$my_id    = $opt['userLogin'];

			if($opt['focus'] == "myFocus"){
				$field	  = "followers";
				$contrary = "following";
			}

			if($opt['focus'] == "focusMy"){
				$field	  = "following";
				$contrary = "followers";
			}
			$countSql = "SELECT count($field) FROM $this->tablename WHERE $field = '$my_id'";
			$limitSql = "SELECT $contrary AS contrary FROM $this->tablename WHERE $field='$my_id' 
						ORDER BY uid DESC LIMIT $size OFFSET ".($page-1)*$size;
		}

		$data = $this->cycle($page, $size, $countSql, $limitSql, $opt);
		return json_encode($data);
    }

	private function cycle($page, $size, $countSql, $limitSql, $opt){
		//用户列表循环
		$data['code'] = 200;
		$data['data'] = $this->pages($page, $size, $countSql);
		$query = $this-> db-> query($limitSql);
		$data['data']['data'] = [];
			foreach ($query-> getResult() as $row){
				$userAddress  = $row -> contrary;
				$userInfo[]	  = (new UserModel())-> userAllInfo($userAddress, $opt);
				$data['data']['data'] = $userInfo;
			}
		$data['msg'] = 'success';
		return $data;
	}

	private function pages($page, $size, $sql){
		$query  = $this->db-> query($sql);
		$row	= $query-> getRow();
        $count	= $row->count;//总数量
		$data	= [
			'currentPage'	=> $page, //当前页
			'perPage'		=> $size, //每页数量
			'totalPage'		=> (int)ceil($count/$size), //总页数
			'lastPage'		=> (int)ceil($count/$size), //总页数
			'totalSize'		=> (int)$count  //总数量
		];
		return $data;
	}

}

