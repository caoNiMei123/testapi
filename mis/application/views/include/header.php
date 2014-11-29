<!DOCTYPE html>
<html lang="zh-cn">
<head>
   <meta http-equiv="Content-Type" content="text/html" charset="utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <meta name="description" content="">
   <meta name="keywords" content="">
   <meta name="author" content="">

   <title>工具集合</title>

   <link href="<?php echo base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
   <link href="<?php echo base_url('assets/css/bootstrap-responsive.min.css') ?>" rel="stylesheet">
   <link href="<?php echo base_url('assets/css/font-awesome.css') ?>" rel="stylesheet">
   <link href="<?php echo base_url('assets/css/docs.css') ?>" rel="stylesheet">
   <link href="<?php echo base_url('assets/css/custom.css') ?>" rel="stylesheet">
	<?php 
   	if(isset($css)){
   		foreach ($css as $v){
			if(substr($v,0,4) == "http"){
				echo "<link href='".$v.".css' rel='stylesheet' id='theme'>\n";
			}else{
				echo "<link href='".base_url("assets/$v.css")."' rel='stylesheet'>\n";
			}
   		}
   	}
   	?>
   <script src="<?php echo base_url('assets/js/jquery.min.js') ?>"></script>
   <script src="<?php echo base_url('assets/js/jquery-ui.min.js') ?>"></script>
   <script src="<?php echo base_url('assets/js/bootstrap.min.js') ?>"></script>
   <script src="<?php echo base_url('assets/js/custom.js') ?>"></script>
   <?php 
   	if(isset($js)){
   		foreach ($js as $v){
			if (substr($v, 0,4) == "http") {
				echo "<script src='".$v.".js'></script>\n";;
			}else{
				echo "<script src='".base_url("assets/$v.js")."'></script>\n";
			}
   			
   		}
   	}
	?>
   
   <script type="text/javascript">
	var base_url = "<?php echo base_url(); ?>index.php";
   </script>
   
</head>
 <body data-spy="scroll" data-target=".bs-docs-sidebar">
	 <div class="navbar navbar-inverse navbar-fixed-top">
	      <div class="navbar-inner">
	        <div class="container">
	          <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
	            <span class="icon-bar"></span>
	            <span class="icon-bar"></span>
	            <span class="icon-bar"></span>
	          </button>
	          <a class="brand" href="#">mis后台</a>
	          <div class="nav-collapse collapse">
	           
	            <ul class="nav">
	              <li <?php if($menu == "home"){?> class="active"<?php }?>><a href="<?php echo base_url("mis/index.php?c=home&m=index")?>">首页</a></li>
	              <li <?php if($menu == "tongji"){?> class="active"<?php }?>><a href="<?php echo base_url("mis/index.php?c=tongji&m=index")?>">统计</a></li>
	              <li <?php if($menu == "feedback"){?> class="active"<?php }?>><a href="<?php echo base_url("mis/index.php?c=feedback&m=index")?>">反馈</a></li>
	              <li <?php if($menu == "driver"){?> class="active"<?php }?>><a href="<?php echo base_url("mis/index.php?c=driver&m=index")?>">司机审核</a></li>
	            </ul>
	          </div><!--/.nav-collapse -->
	        </div>
	      </div>
	    </div>
	  
