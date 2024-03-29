<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	UserModel,
	DisposeModel,
	ValidModel
};

class FocusModel
{//关注Model

    public static function limit($page, $size, $offset, $opt=[])
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
			$countSql = "SELECT count($field) FROM wet_focus WHERE $field = '$address'";
			$limitSql = "SELECT $contrary AS contrary FROM wet_focus 
								WHERE $field='$address' 
								ORDER BY focus_time DESC LIMIT $size OFFSET ".(($page-1) * $size + $offset);
		}
		
		$data = self::cycle($page, $size, $countSql, $limitSql);
		return DisposeModel::wetJsonRt(200, 'success', $data);
    }

	private static function cycle($page, $size, $countSql, $limitSql)
	{  //用户列表循环

		$data = self::pages($page, $size, $countSql);
		$data['data'] = [];
		$query = ComModel::db()-> query($limitSql);
		foreach ($query-> getResult() as $row) {
			$userAddress  = $row -> contrary;
			$userInfo[]	  = UserModel::userAllInfo($userAddress);
			$data['data'] = $userInfo;
		}
		return $data;
	}

	private static function pages($page, $size, $sql)
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


	public static function focus($focus, $fans, $action)
	{//关注
		$verify = ValidModel::isUser($fans);
		if (!$verify) UserModel::userPut($fans);

		$isFocus = ValidModel::isFocus($focus, $fans);
		if (!$isFocus && $action == 'true') {
			$focusSql = "INSERT INTO wet_focus(focus, fans) VALUES ('$focus', '$fans')";
			$e = true;
		}
		
		elseif ($isFocus && $action == 'false') {
			$focusSql = "DELETE FROM wet_focus WHERE focus = '$focus' AND fans = '$fans'";
			$e = false;
	 	}

		else die("focus Error");

		ComModel::db()-> query($focusSql);
		UserModel::userFocus($focus, $fans, $e);
	}

	public static function autoFocus($focus ,$fans)
	{//自动A账户关注B账户
		$verify = ValidModel::isFocus($focus, $fans);
		if (!$verify) {
			$focusSql = "INSERT INTO wet_focus(focus, fans) VALUES ('$focus', '$fans')";
			$e = true;
		} else {
			die("isFocus Error");
		}
		ComModel::db()-> query($focusSql);
		UserModel::userFocus($focus, $fans, $e);
		//入库行为记录
		$insetrBehaviorDate = [
			'address'   => $akToken,
			'thing'     => 'autoFocus',
			'toaddress' => $userAddress
		];
		ComModel::db()->table('wet_behavior')->insert($insetrBehaviorDate);
	}

}

