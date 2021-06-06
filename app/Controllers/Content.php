<?php
namespace App\Controllers;

class Content extends BaseController
{
	public function list()
    {//主贴列表
        $page = $this->request->getPost('page');
        $size = $this->request->getPost('size');
        $type = 'contentList';
		$opt  =	['type' => $type];
		$data = $this->PagesModel-> limit($page, $size, $opt);
		echo $data;
    }

	public function tx()
    {//主贴详情
        $hash   = $this->request->getPost('hash');
        $isHash = $this->DisposeModel-> checkAddress($hash);
		if($isHash){
            $type = 'content';
		    $opt  =	['select' => $type,
                     'read' => true
                    ];
            $data = $this->PagesModel-> Alone($hash, $opt);
            echo $data;
        }else{
			$data['code'] = 406;
			$data['msg']  = 'error_hash';
            echo json_encode($data);
		}
    }

    public function hotRec()
    {//热点推荐列表
        $page = $this->request->getPost('page');
        $size = $this->request->getPost('size');
        $type = 'hotRecList';
		$opt  =	['type' => $type];
		$data = $this->PagesModel-> limit($page, $size, $opt);
		echo $data;
    }

    public function focusList()
	{//关注的用户主贴列表
		$page = $this->request->getPost('page');
		$size = $this->request->getPost('size');
		$type = 'userFocusContentList';
		$opt  =	['type' => $type];
		$data = $this->PagesModel-> limit($page, $size, $opt);
		echo $data;
	}

    public function starList()
    {//收藏列表
        $page = $this->request->getPost('page');
        $size = $this->request->getPost('size');
        $type = 'userStarContentList';
		$opt  =	['type' => $type];
		$data = $this->PagesModel-> limit($page, $size, $opt);
		echo $data;
    }
}