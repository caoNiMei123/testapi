<div class="container">
<div class="page-header">
</div>

<div class="input-prepend input-append" style="margin-bottom: 0px;">
    <span class="add-on">邮箱后缀</span>
    <input class="span2" id="email" type="text">
    <button class="btn" type="button" id="go">批量通过（不带@）</button>
</div>
<br />
<br />
<br />
<!-- Rating List - START -->
<div class="container">
    <div class="row" style="margin-left: 0">
        <div class="well">
            <h1 class="text-center">乘客列表</h1>
            <div class="list-group">
                
            </div>
        </div>
    </div>
</div>

<style>
.list-group-item {
	position: relative;
	display: block;
	padding: 10px 15px;
	margin-bottom: -1px;
	background-color: #fff;
	border: 1px solid #ddd;
	box-sizing: border-box;
	height: auto;
    min-height: 180px;
    border-left:10px solid transparent;
    border-right:10px solid transparent;
}

div.list-group-item:hover, div.list-group-item:focus {
    border-left:10px solid #5CB85C;
    border-right:10px solid #5CB85C;
}


</style>

</div>
<!-- Rating List - END -->

<!--<script type="text/javascript">
	$.post("<?php echo base_url();?>index.php?c=coupon&m=coupons",{},function(data){
		$(".list-group").html(data);
	})
	
	var filter = function(type){
		$.post("<?php echo base_url();?>index.php?c=coupon&m=coupons",{use_cate1:type},function(data){
			$(".list-group").html(data);
		})
	}
</script>-->
