<?php 
namespace App\Models;

use CodeIgniter\Model;
use Config\Database;

class ComModel extends Model
{
    protected $db;
    protected $session;
    protected $request;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::connect('default');
        //$this->request = \Config\Services::request();
        //$this->session = \Config\Services::session();
    }
}