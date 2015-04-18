<script type="text/javascript"> 
    function Click(ph ,obj, st)
    {  
    var key1 = "car_type_"+obj; 
        var ct = document.getElementById(key1).value;
    var key2 = "car_num_"+obj;
    var cn = document.getElementById(key2).value;
    $.post("http://"+window.location.host+"/mis/index.php?c=driver&m=set",{phone:ph,user_id:obj, status:st, car_type:ct, car_num:cn},function(data){
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
            <div>
               <p><i class="icon-time">创建时间： <?php echo date("Y-m-d",$item['ctime']);?></i></p>
                <p><i class="icon-star">手机号： <?php echo $item['phone'];?></i></p>
                <form method="post" action="">
                <p>车  型： <input type="text" id="<?php echo "car_type_".$item['user_id'];?>" ></p>
                <p>车牌号：<input type="text" id="<?php echo "car_num_".$item['user_id'];?>" ></p>
                </form>
            </div>   
            <div >               
                <img src="<?php echo $item['driver_url']?>"  alt="驾照" style="width:150px;height:150px;"/>
                <img src="<?php echo $item['licence_url']?>"  alt="行驶证" style="width:150px;height:150px;"/>
            </div>             
            <div class="button" style="margin-bottom: 0px;">               
                <input type="button" id=<?php echo "go_suc".$item['user_id'];?> onclick="Click(<?php echo $item['phone'];?>,<?php echo $item['user_id'];?>,2)" value="通过" class="btn btn-success"> 
                <input type="button" id=<?php echo "go_fail".$item['user_id'];?> onclick="Click(<?php echo $item['phone'];?>,<?php echo $item['user_id'];?>,3)" value="拒绝" class="btn btn-success"> 
            </div>             
        </div>  
    <?php endforeach;?>
<?php else:?>
    没有数据了    
<?php endif;?>


