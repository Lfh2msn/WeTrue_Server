<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\{
	ValidModel,
	ContentModel,
	CommentModel,
	ReplyModel,
	ConfigModel,
	DisposeModel,
	SuperheroContentModel
};

class PagesModel extends Model {
//分页列表模型

	public $tablename;

	public function __construct(){
        //parent::__construct();
		$this->db = \Config\Database::connect('default');
		$this->ValidModel   = new ValidModel();
		$this->ContentModel = new ContentModel();
		$this->CommentModel = new CommentModel();
		$this->ReplyModel 	= new ReplyModel();
		$this->ConfigModel 	= new ConfigModel();
		$this->DisposeModel = new DisposeModel();
		$this->SuperheroContentModel = new SuperheroContentModel();
		
    }

    public function limit($page, $size, $offset, $opt=[])
	{/*分页
		opt可选参数
			[
				substr	  => (int)截取字节
				type	  => 列表标签类型
				publicKey => 钱包地址
				hash	  => hash
				userLogin => 登录用户钱包地址
			];*/
		$page   = max(1, (int)$page);
		$size   = max(1, (int)$size);
		$offset = max(0, (int)$offset);
		$akToken   = $_SERVER['HTTP_KEY'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if ($isAkToken) $opt['userLogin'] = $akToken;
		$opt['substr'] = 160; //限制输出

		if ( $opt['type'] == 'contentList' )
		{//最新主贴列表
			$this->tablename = "wet_content";
			$countSql		 = "SELECT count(hash) FROM $this->tablename";
			$limitSql		 = "SELECT hash FROM $this->tablename 
									ORDER BY utctime DESC LIMIT $size OFFSET ".(($page-1) * $size + $offset);
			/*
			$limitSql		 = "SELECT hash FROM $this->tablename 
									ORDER BY (
										( (praise + star_sum) * 300000)
										+ (reward_sum / 3e16) 
										+ utctime
									) DESC LIMIT $size OFFSET ".($page-1) * $size;
			*/
			$opt['select']	 = "content";
			$upReadSql = "UPDATE $this->tablename 
							SET read_sum = CASE hash 
								WHEN hash THEN read_sum + 1 
							END 
							WHERE hash IN ($limitSql)";
			$this->db-> query($upReadSql);
		}

		if ( $opt['type'] == 'commentList' )
		{//评论列表
			$this->tablename = "wet_comment";
			$countSql		 = "SELECT count(to_hash) FROM $this->tablename WHERE to_hash = '$opt[hash]'";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE to_hash = '$opt[hash]' 
									ORDER BY utctime /*DESC*/ LIMIT $size OFFSET ".(($page-1) * $size + $offset);
			$opt['select']	 = "comment";
		}

		if ( $opt['type'] == 'replyList' )
		{//回复列表
			$this->tablename = "wet_reply";
			$countSql		 = "SELECT count(to_hash) FROM $this->tablename WHERE to_hash = '$opt[hash]'";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE to_hash = '$opt[hash]' 
									ORDER BY utctime /*DESC*/ LIMIT $size OFFSET ".(($page-1) * $size + $offset);
			$opt['select']	 = "reply";
		}

		if ( $opt['type'] == 'imageList' )
		{//图片列表
			$this->tablename = "wet_content";
			$countSql		 = "SELECT count(hash) FROM $this->tablename WHERE img_tx <> ''";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE img_tx <> '' 
									ORDER BY utctime DESC LIMIT $size OFFSET ".(($page-1) * $size + $offset);
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
			$factorReward	 = $bsConfig['factorReward'];
			$nowTime		 = time() * 1000;
			$cycleTime 	 	 = $nowTime - (86400000 * $hotRecDay);  //当前时间 - 86400000毫秒 * 天 //1614950034235 1621087508000
			$countSql		 = "SELECT count(hash) FROM $this->tablename WHERE utctime >= $cycleTime";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE utctime >= $cycleTime  
									ORDER BY (
												(
												(
													  (praise * $factorPraise)
													+ (
														(SELECT count(distinct wet_comment.sender_id) 
															FROM wet_comment, wet_content 
															WHERE wet_comment.utctime >= wet_content.utctime 
															AND wet_comment.to_hash = wet_content.hash
														) * $factorComment)
													+ (star_sum * $factorStar)
													+ (read_sum * $factorRead)
													+ (comment_sum * $factorComment)
												) * 300000 
													+ (wet_content.reward_sum / $factorReward)
												)
													- ( ( ($nowTime - utctime) / 86400000 ) * $factorTime)
											) DESC LIMIT $size OFFSET ".(($page-1) * $size + $offset);
			$opt['select']	 = "content";
		}

		if ( $opt['type'] == 'userContentList' )
		{//用户发帖列表
			$this->tablename = "wet_content";
			$countSql		 = "SELECT count(sender_id) FROM $this->tablename WHERE sender_id = '$opt[publicKey]'";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE sender_id = '$opt[publicKey]' 
									ORDER BY utctime DESC LIMIT $size OFFSET ".(($page-1) * $size + $offset);
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
							ORDER BY wet_content.utctime DESC 
							LIMIT $size OFFSET ".(($page-1) * $size + $offset);
			$opt['select'] = "content";
		}

		if ( $opt['type'] == 'userStarContentList' )
		{//收藏的帖子
			$this->tablename = "wet_star";
			$countSql		 = "SELECT count(hash) FROM $this->tablename WHERE sender_id = '$opt[address]'";
			$limitSql		 = "SELECT hash FROM $this->tablename WHERE sender_id = '$opt[address]' 
									ORDER BY star_time DESC LIMIT $size OFFSET ".(($page-1) * $size + $offset);
			//收藏可能包含sh
			//$opt['select'] = "content";
			$opt['select']   = "contentAndSH";
		}

		if ( $opt['type'] == 'shTipidList' )
		{//最新Superhero主贴列表
			$this->tablename = "wet_content_sh";
			$countSql		 = "SELECT count(tip_id) FROM $this->tablename";
			$limitSql		 = "SELECT tip_id AS hash FROM $this->tablename 
									ORDER BY utctime DESC LIMIT $size OFFSET ".(($page-1) * $size + $offset);
			$opt['select']	 = "content_sh";
		}

		$data = $this->cycle($page, $size, $countSql, $limitSql, $opt);
		return json_encode($data);
    }

	public function alone($hash, $opt=[])
	{//内容单页
		$akToken   = $_SERVER['HTTP_KEY'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if($isAkToken) {
			$opt['userLogin'] = $akToken;
		}

		if($opt['select'] == 'content') {
			$opt['rewardList'] = true;
			$Content = $this->ContentModel-> txContent($hash, $opt);
		}

		if($opt['select'] == 'comment') {
			$Content = $this->CommentModel-> txComment($hash, $opt);
		}

		if($opt['select'] == 'shTipid') {
			$opt['rewardList'] = true;
			$Content = $this->SuperheroContentModel-> txContent($hash, $opt);
		}

		if($Content) {
			$code = 200;
			$msg  = 'success';
			$data = $Content;
		} else {
			$code = 406;
			$msg  = 'error_hash_or_id';
		}

		return $this->DisposeModel-> wetJsonRt($code, $msg, $data);
    }

	private function cycle($page, $size, $countSql, $limitSql, $opt)
	{//列表循环
		$data  = $this->pages($page, $size, $countSql);
		$query = $this->db-> query($limitSql);
		$getResult = $query-> getResult();
		$data['data'] = [];

		if($getResult){
			foreach ($getResult as $row) {
				$arrList[] = $row->hash;
			}

			$addList = ['th_2ZaQiNxpN2scykBsSd8npcwGkXo36hrP4oTWxCdUuBTJxbMv1U'];
			if($addList && $opt['type'] == 'contentList' && $page=1) {
				$arrList = $this->DisposeModel-> arrayToArray($addList, $arrList);
				$arrList = array_unique($arrList);
				$arrList = array_values($arrList);
			}

			foreach ($arrList as $hash) {
				$isBloomHash = $this->ValidModel-> isBloomHash($hash);
				if (!$isBloomHash) {
					if ($opt['select']  == 'content') {
						$isData = $this->ContentModel-> txContent($hash, $opt);
						if(isset($isData)) $detaila[] = $isData;
					}
	
					if ($opt['select']  == 'comment') {
						$isData = $this->CommentModel-> txComment($hash, $opt);
						if(isset($isData)) $detaila[] = $isData;
					}
	
					if ($opt['select'] == 'reply') {
						$isData = $this->ReplyModel-> txReply($hash, $opt);
						if(isset($isData)) $detaila[] = $isData;
					}

					if ($opt['select']  == 'content_sh') {
						$isData = $this->SuperheroContentModel-> txContent($hash, $opt);
						if(isset($isData)) $detaila[] = $isData;
					}

					if ($opt['select']  == 'contentAndSH') { //收藏，包含主贴和sh主贴
						$isData = $this->ContentModel-> txContent($hash, $opt);
						if(isset($isData)){
							$detaila[] = $isData;
						} else {
							$isData = $this->SuperheroContentModel-> txContent($hash, $opt);
							if(isset($isData)) $detaila[] = $isData;
						}
					}
					
				}
				$data['data'] = $detaila;
			}
		}
		
		return $this->DisposeModel-> wetRt(200,'success',$data);
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

