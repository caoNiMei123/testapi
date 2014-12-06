<?php if (!defined('BASEPATH')) die();
/*
 * Home controller for user login

 */
class Tongji extends MY_Controller {
    
    public function __construct()
    {
        parent::__construct();
    }
    public function index(){
        $this->set_position(__CLASS__, __FUNCTION__);
        $this->data['js'][] = "js/jquery.typist";
        $this->load->view('include/header',$this->data);
        $this->load->view('tongji/index');   
        $this->load->view('include/footer');
        
    }

    public function get_order_list(){
        $this->set_position(__CLASS__, __FUNCTION__);
        $this->data['js'][] = "js/jquery.typist";
        $this->data['js'][] = "plugins/highcharts/js/highcharts";
        $this->data['js'][] = "plugins/highcharts/js/modules/exporting";
        $this->data['js'][] = "plugins/calendar/calendarDateInput";
        $this->load->view('include/header',$this->data);
        $this->load->model("carpool_model"); 
        if(isset($_GET['startdate'])){
            $start = $_GET['startdate'];
        }else{
            $start = date("Ymd", strtotime('-1 month'));
        }
        if(isset($_GET['enddate'])){
            $end = $_GET['enddate'];
        }else{
            $end = date("Ymd");
        }

        $list = $this->carpool_model->get_order_list($start , $end);        
        $data["list"] = $list;
        $this->load->view('tongji/order_list',$data);       
        $this->load->view('include/footer');
    }

    public function get_succ_order_list(){
        $this->set_position(__CLASS__, __FUNCTION__);
        $this->data['js'][] = "js/jquery.typist";
        $this->data['js'][] = "plugins/highcharts/js/highcharts";
        $this->data['js'][] = "plugins/highcharts/js/modules/exporting";
        $this->data['js'][] = "plugins/calendar/calendarDateInput";
        $this->load->view('include/header',$this->data);
        $this->load->model("carpool_model"); 
        if(isset($_GET['startdate'])){
            $start = $_GET['startdate'];
        }else{
            $start = date("Ymd", strtotime('-1 month'));
        }
        if(isset($_GET['enddate'])){
            $end = $_GET['enddate'];
        }else{
            $end = date("Ymd");
        }
        $list = $this->carpool_model->get_succ_order_list($start , $end);  
        $data["list"] = $list;
        $this->load->view('tongji/succ_order_list',$data);       
        $this->load->view('include/footer');
       
    }

     public function get_timeout_order_list(){
        $this->set_position(__CLASS__, __FUNCTION__);
        $this->data['js'][] = "js/jquery.typist";
        $this->data['js'][] = "plugins/highcharts/js/highcharts";
        $this->data['js'][] = "plugins/highcharts/js/modules/exporting";
        $this->data['js'][] = "plugins/calendar/calendarDateInput";
        $this->load->view('include/header',$this->data);
        $this->load->model("carpool_model"); 
        if(isset($_GET['startdate'])){
            $start = $_GET['startdate'];
        }else{
            $start = date("Ymd", strtotime('-1 month'));
        }
        if(isset($_GET['enddate'])){
            $end = $_GET['enddate'];
        }else{
            $end = date("Ymd");
        }
        $list = $this->carpool_model->get_timeout_order_list($start , $end);  
        $data["list"] = $list;
        $this->load->view('tongji/timeout_order_list',$data);       
        $this->load->view('include/footer');
        
    }


    
    
    public function log(){
        
    }
}