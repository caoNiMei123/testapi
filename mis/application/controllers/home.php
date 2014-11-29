<?php if (!defined('BASEPATH')) die();
/*
 * Home controller for user login

 */
class Home extends MY_Controller {
	
	public function __construct()
	{
		parent::__construct();
	}
	
	
	public function index(){
		$this->set_position(__CLASS__, __FUNCTION__);
		$this->data['js'][] = "js/jquery.typist";
		$this->load->view('include/header',$this->data);
		$this->load->view('homepage/home');
		$this->load->view('include/footer');
	}
	
}

