<?php
class MY_Controller extends CI_Controller
{
   private static $menu = array(
         "home"=>array("home"),
         "tongji"=>array("tongji"), 
         "feedback"=>array("feedback"),
         "driver"=>array("driver"),
         "passenger"=>array("passenger"),
   );
   private static $sidebar = array(
         "home"=>array("index"=>"index"),
         "feedback"=>array("index"=>"index"),
         "tongji"=>array("index"=>"index", "order_num" => "order_num", "suc_order_num" => "suc_order_num", "timeout_order_num" => "timeout_order_num"),
         "driver"=>array("index"=>"index"),
         "passenger"=>array("index"=>"index"),
         );
   public $data = array();
   
      function __construct()
      {
         parent::__construct();
      }
      
      function set_position($class_name,$function_name){
         $class_name = strtolower($class_name);
         $function_name = strtolower($function_name);
         //get the menu
         foreach (self::$menu as $k=>$v){
            if(in_array($class_name, $v)){
               $this->data['menu'] = $k;
               //get the sidebar
               $bars = self::$sidebar[$k]; 
               
               foreach ($bars as $k => $v){
                  if($function_name == $v){
                     $this->data['sidebar'] = $k;
                     return;
                  }
               }
               $this->data['sidebar'] = "other";
               return;
            }
         }
      }
      
      function output_json($var){
         $this->output->set_content_type('application/json')->set_output(json_encode($var));
      }
      
}
