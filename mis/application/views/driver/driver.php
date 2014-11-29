<script type="text/javascript"> 
    function Click(obj)
    {
        alert(obj);  //单击事件完成的功能－输出提示
        $.post(base_url+"?c=driver&m=set",{user_id:obj},function(data){
            if(data.errno == 0){
                return;
            }else{
                $('#message').html(data.errmsg);
                $('#messagebox').modal('show');
                return;
            }
        });
        
    }
</script>
<?php if (!empty($list)):?>
	<?php foreach ($list as $item): ?>
		<div  class="list-group-item">
            <div class="span5">
	           <p><i class="icon-time">创建时间： <?php echo date("Y-m-d",$item['ctime']);?></i></p>
                <p><i class="icon-star">手机号： <?php echo $item['phone'];?></i></p>
                <p><i class="icon-star">车型： <?php echo $item['car_type']."\t";?></i><i class="icon-star">车牌号： <?php echo $item['car_num']."\t";?></i><i class="icon-star">车架号： <?php echo $item['car_engine_num']?></i></p>
                <p><i class="icon-star">状态： <?php if($item['status'] == 0)echo '未审核';else echo '已审核';?></i></p>
            </div>   
            <div class="button" style="margin-bottom: 0px;">               
                <input type="button" id=<?php echo "go".$item['user_id'];?> onclick="Click(<?php echo $item['user_id'];?>)" value="通过"> 
            </div>             
        </div>  
	<?php endforeach;?>
<?php else:?>
    暂时没有司机    
<?php endif;?>


