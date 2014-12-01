<?php
class Carpool_Model extends CI_Model{
	
	public function __construct(){
		parent::__construct();
		$this->load->database();
	}
	
	public function get_feedback($start, $limit){
		$res = array();
		$query = $this->db->query("select * from complain_info  order by ctime desc limit $start, $limit ");
		foreach ($query->result_array() as $row)$res[]=$row;		
        return $res; 	
	}
	public function set_feedback($id){
		$query = $this->db->query("update complain_info  set status =1 where id = $id");
		return 0;
	}
	public function get_driver($start, $limit){
		$res = array();		
		$query = $this->db->query("select * from user_info where user_type = 1 and status = 0  order by ctime desc limit $start, $limit");
		foreach ($query->result_array() as $row)$res[]=$row;		
        return $res; 	

    }
    public function set_driver($user_id){
		$query = $this->db->query("update user_info  set status =2 where user_id = $user_id");
		return 0;
	}

	public function get_order_list($start, $end){
		$res = array();		
		$query = $this->db->query("select item1 from log_info where day > $start and day < $end ");
		foreach ($query->result_array() as $row)$res[]=$row;		
        return $res; 
    }

    public function get_succ_order_list($start, $end){
		$res = array();		
		$query = $this->db->query("select item2 from log_info where day > $start and day < $end ");
		foreach ($query->result_array() as $row)$res[]=$row;		
        return $res; 
    }

    public function get_timeout_order_list($start, $end){
		$res = array();		
		$query = $this->db->query("select item3 from log_info where day > $start and day < $end ");
		foreach ($query->result_array() as $row)$res[]=$row;		
        return $res; 
    }

}
