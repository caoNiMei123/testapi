<?php if (!defined('BASEPATH')) die();
/*
 * Home controller for user login

 */
class Passenger extends MY_Controller {
    
    public function __construct()
    {
        parent::__construct();
    }
    public function index(){
        $this->set_position(__CLASS__, __FUNCTION__);
        $this->data['js'][] = "js/jquery.typist";
        $this->load->view('include/header',$this->data);
        $this->load->view('passenger/list');   
        $this->load->view('include/footer');        
        if(!isset($_GET['page'])) {
            $page = 0;
        }else{
            $page = intval($_GET['page']);
        }
        
        $this->get_list( $page * 10, 10);
    }
    public function get_list($start , $limit){
       $this->load->model("carpool_model"); 
       $list = $this->carpool_model->get_passenger($start , $limit);
       $data["list"] = $list;
       $data["page"] = $start/10;
       $this->load->view('passenger/passenger',$data);
    }
    
    public function set(){
        $this->load->model("carpool_model"); 
        $this->carpool_model->set_passenger(intval($_POST['user_id']));
        echo json_encode(array(
            'errno' => 0,
            'errmsg'=> '',
        ));
        return;
    }

    public function batchset(){
        $this->load->model("carpool_model"); 
        $this->carpool_model->batch_set_passenger($_POST['email']);
        echo json_encode(array(
            'errno' => 0,
            'errmsg'=> '',
        ));
        return;
    }

    public function log(){
        
    }
}
