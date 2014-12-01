<div class="span3 bs-docs-sidebar">
	<ul class="nav nav-list bs-docs-sidenav">
		<li <?php if($sidebar == "order_list"){?> class="active"<?php }?>><a href="<?php echo base_url("mis/index.php?c=tongji&m=get_order_list")?>">订单总量</a></li>
		<li <?php if($sidebar == "succ_order_list"){?> class="active"<?php }?>><a href="<?php echo base_url("mis/index.php?c=tongji&m=get_succ_order_list")?>">成功订单总量</a></li>
		<li <?php if($sidebar == "timeout_order_list"){?> class="active"<?php }?>><a href="<?php echo base_url("mis/index.php?c=tongji&m=get_timeout_order_list")?>">超时订单总量</a></li>
	</ul>
</div><!--/.well -->
