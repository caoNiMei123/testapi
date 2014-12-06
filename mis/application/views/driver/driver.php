<script type="text/javascript"> 
    function Click(obj)
    {
        $.post("http://"+window.location.host+"/mis/index.php?c=driver&m=set",{user_id:obj},function(data){
            location.reload(true);
            return;
        });
        
        
    }
</script>

<div  class="button">
<?php if ($page != 0):?>
             
    <a href="<?php echo base_url();?>mis/index.php?c=driver&m=index&page=<?php echo $page-1;?>"   class="btn btn-success" >上一页</a>

<?php endif;?> 
             
    <a href="<?php echo base_url();?>mis/index.php?c=driver&m=index&page=<?php echo $page+1;?>"   class="btn btn-success" >下一页</a>
</div>
<?php if (!empty($list)):?>
	<?php foreach ($list as $item): ?>
		<div  class="list-group-item">
            <div class="span5">
	           <p><i class="icon-time">创建时间： <?php echo date("Y-m-d",$item['ctime']);?></i></p>
                <p><i class="icon-star">手机号： <?php echo $item['phone'];?></i></p>
                <p><i class="icon-star">车型： <?php echo $item['car_type']."\t";?></i></p>
                <i class="icon-star">车牌号： <?php echo $item['car_num']."\t";?></i></p>
                <i class="icon-star">车架号： <?php echo $item['car_engine_num']?></i></p>
                
            </div>   
            <div class="button" style="margin-bottom: 0px;">               
                <input type="button" id=<?php echo "go".$item['user_id'];?> onclick="Click(<?php echo $item['user_id'];?>)" value="通过" class="btn btn-success"> 
            </div>             
        </div>  
	<?php endforeach;?>
<?php else:?>
    没有数据了    
<?php endif;?>


