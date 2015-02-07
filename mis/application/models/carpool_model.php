<?php
require_once PHPLIB_PATH."/httpproxy/HttpProxy.class.php";
require_once PHPLIB_PATH."/sms/SmsProxy.class.php";
class Carpool_Model extends CI_Model{
    
    public function __construct(){
        parent::__construct();
        $this->load->database();
    }
    
    public function get_feedback($start, $limit){
        $res = array();
        $query = $this->db->query("select * from complain_info  where status = 0 order by ctime desc limit $start, $limit ");
        foreach ($query->result_array() as $row)$res[]=$row;        
        return $res;    
    }
    public function set_feedback($id){
        $query = $this->db->query("update complain_info  set status =1 where id = $id");
        return 0;
    }
    public function get_driver($start, $limit){
        $res = array();     
        $query = $this->db->query("select * from user_info where status = 1  order by ctime desc limit $start, $limit");
        foreach ($query->result_array() as $row)$res[]=$row;        
        return $res;    

    }

    public function set_driver($phone, $user_id, $status){
        $query = $this->db->query("update user_info  set status =$status where user_id = $user_id");
        //给用户发短信, to do
        if($status == 2)
        {

            $msg = "success";
        }
        else
        {
            $msg = "fail";
        }
        SmsPorxy::getInstance()->push_to_single($phone, $msg);
        return 0;
    }

    public function get_order_list($start, $end){
        $res = array();     
        $query = $this->db->query("select day, hour, item_1 from log_info where day >= $start and day <= $end ");
        foreach ($query->result_array() as $row)$res[]=$row;        
        return $res; 
    }

    public function get_succ_order_list($start, $end){
        $res = array();     
        $query = $this->db->query("select day, hour, item_2 from log_info where day >= $start and day <= $end ");
        foreach ($query->result_array() as $row)$res[]=$row;        
        return $res; 
    }

    public function get_timeout_order_list($start, $end){
        $res = array();     
        $query = $this->db->query("select day, hour, item_3 from log_info where day >= $start and day <= $end ");
        foreach ($query->result_array() as $row)$res[]=$row;        
        return $res; 
    }

}
