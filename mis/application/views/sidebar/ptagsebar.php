<div class="span3 bs-docs-sidebar">
	<ul class="nav nav-list bs-docs-sidenav">
		<li <?php if($sidebar == "query"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=ptagse&m=query")?>">查询工具</a></li>
		<li <?php if($sidebar == "cluster"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=ptagse&m=cluster")?>">分类demo</a></li>
		<li <?php if($sidebar == "group"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=ptagse&m=group")?>">聚类demo</a></li>
		<li <?php if($sidebar == "search"){?> class="active"<?php }?>><a href="<?php echo base_url("index.php?c=ptagse&m=search")?>">人脸检索demo</a></li>
	</ul>
</div><!--/.well -->
