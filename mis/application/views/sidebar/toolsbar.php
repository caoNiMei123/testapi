<div class="span3 bs-docs-sidebar">
	<ul class="nav nav-list bs-docs-sidenav">
		<li <?php if($sidebar == "uinfo"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=tools&m=uinfo")?>">用户信息</a></li>
		<li <?php if($sidebar == "mod"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=tools&m=mod")?>">分表取模</a></li>
		<li <?php if($sidebar == "ts"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=tools&m=ts")?>">时间戳</a></li>
		<li <?php if($sidebar == "passwd"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=tools&m=passwd")?>">yme解析</a></li>
		<li <?php if($sidebar == "yld"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=tools&m=yld")?>">yld解析</a></li>
		<li <?php if($sidebar == "jsonview"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=tools&m=jsonview")?>">JSON</a></li>
		<li <?php if($sidebar == "urlcode"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=tools&m=urlcode")?>">url编解码</a></li>
	</ul>
</div><!--/.well -->
