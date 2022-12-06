<?php 
namespace App\Models;

use Config\Database;

class ComModel
{//通讯模块

    public static function db()
	{//连接数据库
		return Database::connect('default');
    }

}