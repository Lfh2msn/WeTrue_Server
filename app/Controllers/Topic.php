<?php
namespace App\Controllers;

use App\Models\{
	TopicModel,
	DisposeModel
};

class Topic extends BaseController {

	public function info()
	{//话题信息
		$keyword = $this->request->getPost('keyword');
		$opt = ['read' => true];
		$data	 = TopicModel::getTopicInfo($keyword, $opt);
		if ($data) {
			$data = DisposeModel::wetJsonRt(200, 'success', $data);
		} else {
			$data = DisposeModel::wetJsonRt(406,'error');
		}
		echo $data;
	}

	public function contentList()
	{//话题列表
		$page    = $this->request->getPost('page');
        $size    = $this->request->getPost('size');
        $offset  = $this->request->getPost('offset');
		$keyword = $this->request->getPost('keyword');
		$data	 = TopicModel::getTopicList($page, $size, $offset, $keyword);
		echo json_encode($data);

	}

	public function hotTopic()
	{//热点话题
		$data = TopicModel::hotRecTopic();
		echo json_encode($data);
		$this->cachePage(1);
	}

}