<?php
namespace App\Controllers;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 *
 * @package CodeIgniter
 */

use CodeIgniter\Controller;
use App\Models\PagesModel;
use App\Models\FocusModel;
use App\Models\DisposeModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;

class BaseController extends Controller {

	/**
	 * An array of helpers to be loaded automatically upon
	 * class instantiation. These helpers will be available
	 * to all other controllers that extend BaseController.
	 *
	 * @var array
	 */
	protected $helpers = [];


	/**
	 * Constructor.
	 */

	public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
	{
		// Do Not Edit This Line
		parent::initController($request, $response, $logger);

		//--------------------------------------------------------------------
		// Preload any models, libraries, etc, here.
		//--------------------------------------------------------------------
		// E.g.:
		// $this->session = \Config\Services::session();
		//$message->header = CodeIgniter\HTTP\Message;
		$this->request 		= $request;
		//$this->request = service('request');
		$this->response 	= $response;
		$this->PagesModel   = new PagesModel();
		$this->FocusModel   = new FocusModel();
		$this->DisposeModel = new DisposeModel();
		$this->UserModel	= new UserModel();
		$this->response->setHeader('Access-Control-Allow-Origin', '*');
		$this->response->setHeader('Access-Control-Allow-Headers', 'ak-token');
		//$this->response->setHeader('Content-Type', 'application/json; charset=utf-8');
	}

}
