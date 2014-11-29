<div class="span3 bs-docs-sidebar">
	<ul class="nav nav-list bs-docs-sidenav">
		<li <?php if($sidebar == "uinfo"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=dss&m=uinfo")?>">用户信息</a></li>
		<li <?php if($sidebar == "ubind"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=dss&m=ubind")?>">用户绑定设备信息</a></li>
		<li <?php if($sidebar == "dbind"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=dss&m=dbind")?>">设备绑定用户信息</a></li>
		<li <?php if($sidebar == "register"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=dss&m=register")?>">设备注册信息</a></li>
		<li <?php if($sidebar == "channel"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=dss&m=channel")?>">设备Channel信息</a></li>
		<li <?php if($sidebar == "meta"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=dss&m=meta")?>">设备meta信息</a></li>
		<li <?php if($sidebar == "list"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=dss&m=listbyid")?>">设备任务信息</a></li>
		<li <?php if($sidebar == "listbyuser"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=dss&m=listbyuser")?>">用户任务信息</a></li>
	</ul>
</div><!--/.well -->
