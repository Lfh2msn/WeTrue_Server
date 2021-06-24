<?php
namespace App\Controllers;

use App\Models\TopicModel;

class Topic extends BaseController {

	public function info()
	{//话题信息
		$keyword = $this->request->getPost('keyword');
		$data	 = (new TopicModel())-> getTopicInfo($keyword);
		echo json_encode($data);

	}

	public function contentList()
	{//话题列表
		$page	 = $this->request->getPost('page');
		$size	 = $this->request->getPost('size');
		$keyword = $this->request->getPost('keyword');
		$data	 = (new TopicModel())-> getTopicList($page, $size, $keyword);
		echo json_encode($data);

	}

}