<?php if (!defined('BASEPATH')) die();
/*
 * Home controller for user login

 */
class Driver extends MY_Controller {
	
	public function __construct()
	{
		parent::__construct();
	}
	public function index(){
		$this->set_position(__CLASS__, __FUNCTION__);
		$this->data['js'][] = "js/jquery.typist";
		$this->load->view('include/header',$this->data);
		$this->load->view('driver/list');   
		$this->load->view('include/footer');
		$this->get_list(0, 10);
	}
    public function get_list($start , $limit){
       $this->load->model("carpool_model"); 
       $list = $this->carpool_model->get_driver($start , $limit);
       $data["list"] = $list;
       $this->load->view('driver/driver',$data);
    }
	public function test(){
	}
    public function set($user_id){
    }

	public function log(){
		
	}
}
