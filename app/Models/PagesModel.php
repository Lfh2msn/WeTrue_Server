<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\BloomModel;
use App\Models\ContentModel;
use App\Models\CommentModel;
use App\Models\ReplyModel;
use App\Models\ConfigModel;
use App\Models\DisposeModel;

class PagesModel extends Model {
//分页列表模型

	public function __construct(){
        //parent::__construct();
		$this->db = \Config\Database::connect('default');
		$this->bloom   		= new BloomModel();
		$this->content 		= new ContentModel();
		$this->comment 		= new CommentModel();
		$this->reply 		= new ReplyModel();
		$this->ConfigModel 	= new ConfigModel();
		$this->DisposeModel = new DisposeModel();
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
			];*/
		$page = max(1, (int)$page);
		$size = max(1, (int)$size);
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if ($isAkToken) $opt['userLogin'] = $akToken;
		$opt['substr']	  = 160; //限制输出

		if ( $opt['type'] == 'contentList' )
		{//主贴列表
			$this->tablename = "wet_content";
			$countSql		 = "SELECT count(hash) FROM $this->tablename";
			$limitSql		 = "SELECT hash FROM $this->tablename 
									ORDER BY utctime DESC LIMIT $size OFFSET ".($page-1) * $size;
			/*$limitSql		 = "SELECT hash FROM $this->tablename 
									ORDER BY (
										(praise + star_sum) * 300000 + read_sum * 10 + utctime
									) DESC LIMIT $size OFFSET ".($page-1) * $size;*/
			$opt['select']	 = "content";

			$upReadSql = "UPDATE $this->tablename 
							SET read_sum = CASE hash WHEN hash THEN read_sum + 1 
											END WHERE hash IN ($limitSql)";
			$this->db-> query($upReadSql);
		}

		if ( $opt['type'] == 'commentList' )
		{//评论列表
			$this->tablename = "wet_comment";
			$countSql		 = "SELECT count(to_hash) FROM $this->tablename WHERE to_hash = '$opt[hash]'";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE to_hash = '$opt[hash]' 
									ORDER BY (
										(praise + comment_sum) * 300000 + utctime
									) DESC LIMIT $size OFFSET ".($page-1) * $size;
			$opt['select']	 = "comment";
		}

		if ( $opt['type'] == 'replyList' )
		{//回复列表
			$this->tablename = "wet_reply";
			$countSql		 = "SELECT count(to_hash) FROM $this->tablename WHERE to_hash = '$opt[hash]'";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE to_hash = '$opt[hash]' 
									ORDER BY utctime DESC LIMIT $size OFFSET ".($page-1) * $size;
			$opt['select']	 = "reply";
		}

		if ( $opt['type'] == 'imageList' )
		{//图片列表
			$this->tablename = "wet_content";
			$countSql		 = "SELECT count(hash) FROM $this->tablename WHERE img_tx <> ''";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE img_tx <> '' 
									ORDER BY (
										(praise + star_sum) * 300000 + read_sum * 10 + utctime
									) DESC LIMIT $size OFFSET ".($page-1) * $size;
			$opt['select']	 = "content";
		}

		if ( $opt['type'] == 'hotRecList' )
		{//热点推荐
			$this->tablename = "wet_content";
			$bsConfig   	 = $this->ConfigModel-> backendConfig();
			$hotRecDay  	 = $bsConfig['hotRecDay'];
			$factorPraise	 = $bsConfig['factorPraise'];
			$factorComment	 = $bsConfig['factorComment'];
			$factorStar		 = $bsConfig['factorStar'];
			$factorRead		 = $bsConfig['factorRead'];
			$factorTime	 	 = $bsConfig['factorTime'];
			$nowTime		 = time() * 1000;
			$cycleTime 	 	 = $nowTime - (86400000 * $hotRecDay);  //当前时间 - 86400000毫秒 * 天 //1614950034235 1621087508000
			$countSql		 = "SELECT count(hash) FROM $this->tablename WHERE utctime >= $cycleTime";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE utctime >= $cycleTime  
									ORDER BY (
												(
													  (praise * $factorPraise)
													+ (
														(SELECT count(distinct wet_comment.sender_id) 
															FROM wet_comment, wet_content 
															WHERE wet_comment.utctime >= wet_content.utctime AND wet_comment.to_hash = wet_content.hash
														) * $factorComment)
													+ (star_sum * $factorStar)
													+ (read_sum * $factorRead)
													+ (comment_sum * $factorComment)
												) * 300000 
													- ( ( ($nowTime - utctime) / 86400000 ) * $factorTime)
											) DESC LIMIT $size OFFSET ".($page-1) * $size;
			$opt['select']	 = "content";
		}

		if ( $opt['type'] == 'userContentList' )
		{//用户发帖列表
			$this->tablename = "wet_content";
			$countSql		 = "SELECT count(sender_id) FROM $this->tablename WHERE sender_id = '$opt[publicKey]'";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE sender_id = '$opt[publicKey]' 
									ORDER BY (
										(praise + star_sum) * 300000 + read_sum * 10 + utctime
									) DESC LIMIT $size OFFSET ".($page-1) * $size;
			$opt['select']	 = "content";
		}

		if ( $opt['type'] == 'userFocusContentList' )
		{//被关注主贴列表
			$akToken	  = $opt['userLogin'];
			$countSql = "SELECT count(wet_content.hash) FROM wet_content 
							INNER JOIN wet_focus 
							ON wet_content.sender_id = wet_focus.focus 
							AND wet_focus.fans = '$akToken'";
			$limitSql = "SELECT wet_content.hash FROM wet_content 
							INNER JOIN wet_focus 
							ON wet_content.sender_id = wet_focus.focus 
							AND wet_focus.fans = '$akToken' 
							ORDER BY (
								(wet_content.praise + wet_content.star_sum) * 300000 + wet_content.read_sum * 10 + wet_content.utctime
							) DESC LIMIT $size OFFSET ".($page-1) * $size;
			$opt['select'] = "content";
		}

		if ( $opt['type'] == 'userStarContentList' )
		{//收藏的帖子
			if (!$isAkToken) {
				$data['code'] = 401;
				$data['msg']  = 'error_login';
				return json_encode($data);
			}
			
			$this->tablename = "wet_star";
			$countSql		 = "SELECT count(hash) FROM $this->tablename WHERE sender_id = '$opt[userLogin]'";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE sender_id = '$opt[userLogin]' 
									ORDER BY star_time DESC LIMIT $size OFFSET ".($page-1) * $size;
			$opt['select']	 = "content";
		}

		$data = $this->cycle($page, $size, $countSql, $limitSql, $opt);
		return json_encode($data);
    }

	public function Alone($hash, $opt=[])
	{//内容单页
		$data['code'] = 200;
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if($isAkToken) {
			$opt['userLogin'] = $akToken;
		}
		
		$data['data'] = '';

		if($opt['select'] == 'content') {
			$opt['rewardList'] = true;
			$Content = $this->content-> txContent($hash, $opt);
		}

		if($opt['select'] == 'comment') {
			$Content = $this->comment-> txComment($hash, $opt);
		}

		if($Content) {
			$data['data'] = $Content;
			$data['msg']  = 'success';
		} else {
			$data['msg']  = 'error_hash';
		}

		return json_encode($data);
    }

	private function cycle($page, $size, $countSql, $limitSql, $opt)
	{//列表循环
		$data['code'] = 200;
		$data['data'] = $this->pages($page, $size, $countSql);
		$query = $this->db-> query($limitSql);
		$data['data']['data'] = [];
		$getResult = $query-> getResult();
		foreach ($getResult as $row) {
			$hash  = $row -> hash;
			$txBloom = $this->bloom-> txBloom($hash);
			if (!$txBloom) {
				if ($opt['select']  == 'content') {
					$detaila[] = $this->content-> txContent($hash, $opt);
				}

				if ($opt['select']  == 'comment') {
					$detaila[] = $this->comment-> txComment($hash, $opt);
				}

				if ($opt['select'] == 'reply') {
					$detaila[] = $this->reply-> txReply($hash, $opt);
				}
			}
			$data['data']['data'] = $detaila;
		}
		$data['msg'] = 'success';
		return $data;
	}

	private function pages($page, $size, $sql)
	{
		$query = $this->db-> query($sql);
		$row   = $query-> getRow();
        $count = $row->count;//总数量
		$data  = [
			'page'		=> $page,  //当前页
			'size'		=> $size,  //每页数量
			'totalPage'	=> (int)ceil($count/$size),  //总页数
			'totalSize'	=> (int)$count  //总数量
		];
		return $data;
	}

}

