<?php
namespace App\Controllers;

use App\Models\DisposeModel;

class Content extends BaseController
{
    public function tx()
    {//主贴详情
        $hash   = $this->request->getPost('hash');
        $isHash = DisposeModel::checkAddress($hash);
		if($isHash){
            $type = 'content';
		    $opt  =	['select' => $type,
                     'read' => true
                    ];
            $data = $this->PagesModel-> alone($hash, $opt);
            echo $data;
        }else{
            echo DisposeModel::wetJsonRt(406, 'error_hash');
		}
    }

	public function list()
    {//主贴列表
        $page   = $this->request->getPost('page');
        $size   = $this->request->getPost('size');
        $offset = $this->request->getPost('offset');
        $type   = 'contentList';
		$opt    =	['type' => $type];
		$data   = $this->PagesModel-> limit($page, $size, $offset, $opt);
		echo $data;
    }

    public function hotRec()
    {//热点推荐列表
        $page   = $this->request->getPost('page');
        $size   = $this->request->getPost('size');
        $offset = $this->request->getPost('offset');
        $type = 'hotRecList';
		$opt  =	['type' => $type];
		$data = $this->PagesModel-> limit($page, $size, $offset, $opt);
		echo $data;
    }

    public function focusList()
	{//关注的用户主贴列表
		$page   = $this->request->getPost('page');
        $size   = $this->request->getPost('size');
        $offset = $this->request->getPost('offset');
		$type = 'userFocusContentList';
		$opt  =	['type' => $type];
		$data = $this->PagesModel-> limit($page, $size, $offset, $opt);
		echo $data;
	}

    public function starList()
    {//收藏列表
        $page   = $this->request->getPost('page');
        $size   = $this->request->getPost('size');
        $offset = $this->request->getPost('offset');
        $userAddress   = $this->request->getPost('userAddress');
        $isUserAddress = DisposeModel::checkAddress($userAddress);
        if ($isUserAddress) {
            $type = 'userStarContentList';
            $opt  =	[
                    'type' => $type,
                    'address' => $userAddress
                    ];
            $data = $this->PagesModel-> limit($page, $size, $offset, $opt);
            echo $data;
        } else {
            echo DisposeModel::wetJsonRt(406, 'error');
		}
    }

    public function shTipid()
    {//Superhero主贴详情
        $shTipid   = $this->request->getPost('shTipid');
        $isShTipid = $this->DisposeModel-> checkSuperheroTipid($shTipid);
		if($isShTipid){
            $type = 'shTipid';
		    $opt  =	['select' => $type,
                     'read' => true
                    ];
            $data = $this->PagesModel-> alone($shTipid, $opt);
            echo $data;
        }else{
            echo DisposeModel::wetJsonRt(406, 'error_superhero_tipid');
		}
    }

    public function shTipidList()
    {//Superhero主贴列表
        $page   = $this->request->getPost('page');
        $size   = $this->request->getPost('size');
        $offset = $this->request->getPost('offset');
        $type   = 'shTipidList';
		$opt    =	['type' => $type];
		$data   = $this->PagesModel-> limit($page, $size, $offset, $opt);
		echo $data;
    }

    public function getCount()
    {//主贴总数
		$data = $this->PagesModel-> contentCount();
        echo DisposeModel::wetJsonRt(200,'success',$data);
    }

}