<script type="text/javascript"> 
    function Click(obj,st)
    {
        $.post("http://"+window.location.host+"/mis/index.php?c=passenger&m=set",{user_id:obj,status:st},function(data){
            location.reload(true);
            return;
        });
        
        
    }
</script>



<script type="text/javascript">
        $("#go").click(function(){
                obj = $("#email").val();
                $.post("http://"+window.location.host+"/mis/index.php?c=passenger&m=batchset",{email:obj},function(data){
                    location.reload(true);
                    return;
                });            
               
        });

</script>



<div  class="button">
<?php if ($page != 0):?>
             
    <a href="<?php echo base_url();?>mis/index.php?c=passenger&m=index&page=<?php echo $page-1;?>"   class="btn btn-success" >上一页</a>

<?php endif;?> 
             
    <a href="<?php echo base_url();?>mis/index.php?c=passenger&m=index&page=<?php echo $page+1;?>"   class="btn btn-success" >下一页</a>
</div>
<?php if (!empty($list)):?>
    <?php foreach ($list as $item): ?>
        <div  class="list-group-item">
            <div class="span5">
               <p><i class="icon-time">创建时间： <?php echo date("Y-m-d",$item['ctime']);?></i></p>
                <p><i class="icon-star">手机号： <?php echo $item['phone'];?></i></p>
                <p><i class="icon-star">邮箱： <?php echo $item['email']."\t";?></i></p>
                
            </div>   
            <div class="button" style="margin-bottom: 0px;">               
                <input type="button" id=<?php echo "go_suc".$item['user_id'];?> onclick="Click(<?php echo $item['user_id'];?>,2)" value="通过" class="btn btn-success">
                <input type="button" id=<?php echo "go_fail".$item['user_id'];?> onclick="Click(<?php echo $item['user_id'];?>,3)" value="拒绝" class="btn btn-success">  
            </div>             
        </div>  
    <?php endforeach;?>
<?php else:?>
    没有数据了    
<?php endif;?>


