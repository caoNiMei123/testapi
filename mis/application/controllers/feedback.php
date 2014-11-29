<?php if (!defined('BASEPATH')) die();
/*
 * Home controller for user login

 */
class Feedback extends MY_Controller {
	
	public function __construct()
	{
		parent::__construct();
	}
	public function index(){
		$this->set_position(__CLASS__, __FUNCTION__);
		$this->data['js'][] = "js/jquery.typist";
		$this->load->view('include/header',$this->data);
		$this->load->view('feedback/list');   
		$this->load->view('include/footer');
		$this->get_list(0, 10);
	}
    public function get_list($start , $limit){
       $this->load->model("carpool_model"); 
       $list = $this->carpool_model->get_feedback($start , $limit);
       $data["list"] = $list;
       $this->load->view('feedback/feedback',$data);
    }
	public function test(){
	}

	public function log(){
		
	}
}
