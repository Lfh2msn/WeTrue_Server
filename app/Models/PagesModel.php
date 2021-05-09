<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\BloomModel;
use App\Models\ContentModel;
use App\Models\CommentModel;
use App\Models\ReplyModel;
use App\Models\configModel;
use App\Models\DisposeModel;

class PagesModel extends Model {
//分页列表模型

	public function __construct(){
        parent::__construct();
		$this->bloom   		= new BloomModel();
		$this->content 		= new ContentModel();
		$this->comment 		= new CommentModel();
		$this->reply 		= new ReplyModel();
		$this->configModel 	= new configModel();
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
		if ( $isAkToken ){
			$opt['userLogin'] = $akToken;
		}
		
		$opt['substr']	  = 160; //限制输出

		if ( $opt['type'] == 'contentList' ){
			//主贴列表
			$this->tablename = "wet_content";
			$countSql		 = "SELECT count(hash) FROM $this->tablename";
			$limitSql		 = "SELECT hash FROM $this->tablename 
									ORDER BY utctime DESC LIMIT $size OFFSET ".($page-1) * $size;
			$select			 = "content";
		}

		if ( $opt['type'] == 'commentList' ){  //评论列表
			$this->tablename = "wet_comment";
			$countSql		 = "SELECT count(to_hash) FROM $this->tablename WHERE to_hash='$opt[hash]'";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE to_hash = '$opt[hash]' 
									ORDER BY uid DESC LIMIT $size OFFSET ".($page-1) * $size;
			$select			 = "comment";
		}

		if ( $opt['type'] == 'replyList' ){  //回复列表
			$this->tablename = "wet_reply";
			$countSql		 = "SELECT count(to_hash) FROM $this->tablename WHERE to_hash='$opt[hash]'";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE to_hash = '$opt[hash]' 
									ORDER BY uid DESC LIMIT $size OFFSET ".($page-1) * $size;
			$select			 = "reply";
		}

		if ( $opt['type'] == 'imageList' ){  //图片列表
			$this->tablename = "wet_content";
			$countSql		 = "SELECT count(hash) FROM $this->tablename WHERE img_tx <> ''";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE img_tx <> '' 
									ORDER BY utctime DESC LIMIT $size OFFSET ".($page-1) * $size;
			$select 		 = "content";
		}

		if ( $opt['type'] == 'hotRecList' ){  //热点推荐
			$this->tablename = "wet_content";
			$bsConfig   	 = $this->configModel-> backendConfig();
			$hotRecDay  	 = $bsConfig['hotRecDay'];
			$factorPraise	 = $bsConfig['factorPraise'];
			$factorComment	 = $bsConfig['factorComment'];
			$factorStar		 = $bsConfig['factorStar'];
			$factorTime	 	 = $bsConfig['factorTime'];
			$nowTime		 = time() * 1000;
			$cycleTime 	 	 = $nowTime - 86400000 * $hotRecDay;  //当前时间 - 86400000毫秒 * 天
			$countSql		 = "SELECT count(hash) FROM $this->tablename WHERE utctime >= $cycleTime";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE utctime >= $cycleTime 
									ORDER BY (
											   (praise * $factorPraise)
											 + (comment_num * $factorComment)
											 + (star * $factorStar)
											 - ( ( ($nowTime - utctime) / 8640000) * $factorTime)
											) DESC LIMIT $size OFFSET ".($page-1) * $size;
			$select 		 = "content";
		}

		if ( $opt['type'] == 'userContentList' ){  //用户发帖列表
			$this->tablename = "wet_content";
			$countSql		 = "SELECT count(sender_id) FROM $this->tablename WHERE sender_id='$opt[publicKey]'";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE sender_id='$opt[publicKey]' 
									ORDER BY utctime DESC LIMIT $size OFFSET ".($page-1) * $size;
			$select			 = "content";
		}

		if ( $opt['type'] == 'userFocusContentList' ){  //被关注主贴列表
			$akToken	  = $opt['userLogin'];
			$countSql = "SELECT count(wet_content.hash) FROM wet_content 
							INNER JOIN wet_focus 
							ON wet_content.sender_id = wet_focus.focus 
							AND wet_focus.fans = '$akToken'";
			$limitSql = "SELECT wet_content.hash FROM wet_content 
							INNER JOIN wet_focus ON wet_content.sender_id = wet_focus.focus 
							AND wet_focus.fans = '$akToken' 
							ORDER BY wet_content.uid DESC LIMIT $size OFFSET ".($page-1) * $size;
			$select	  = "content";
		}

		$data = $this->cycle($page, $size, $countSql, $limitSql, $select, $opt);
		return json_encode($data);
    }

	public function Alone($hash, $opt=[])
	{//内容单页
		$data['code'] = 200;
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if($isAkToken){
			$opt['userLogin'] = $akToken;
		}
		
		$data['data'] = '';

		if($opt['select'] == 'content'){
			$Content = $this->content-> txContent($hash, $opt);
		}

		if($opt['select'] == 'comment'){
			$Content = $this->comment-> txComment($hash, $opt);
		}

		if($Content){
			$data['data'] = $Content;
			$data['msg']  = 'success';
		}else{
			$data['msg']  = 'error_hash';
		}

		return json_encode($data);
    }

	private function cycle($page, $size, $countSql, $limitSql, $select, $opt)
	{//列表循环
		$data['code'] = 200;
		$data['data'] = $this->pages($page, $size, $countSql);
		$query = $this->db-> query($limitSql);
		$data['data']['data'] = [];
		foreach ($query-> getResult() as $row){
			$hash  = $row -> hash;
			$bloom = $this->bloom-> txBloom($hash);
			if($bloom){
				if($select  == 'content'){
					$detaila[] = $this->content-> txContent($hash, $opt);
				}

				if($select  == 'comment'){
					$detaila[] = $this->comment-> txComment($hash, $opt);
				}

				if($select == 'reply'){
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

