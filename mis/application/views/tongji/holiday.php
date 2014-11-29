<div class="container">
<div class="row">
        <?php $this->load->view('sidebar/apisbar');?>
        <div class="span9" >
        <section >
        	<h2>休假计划</h2>
        	<p class="text-info">
				<span class="label label-important">说明</span>
				重复提交，不更改名字，即可修改。休假时间为不在公司上班的时间段，包括法定假日和休假时间
			</p>
			<hr>
			<table class="table table-bordered table-striped">
	            <tbody id="info">
	            	<tr>
	              	<td class="span2">分类</td>
	                <td colspan=9>
	                	<div class="input-prepend input-append" style="margin-bottom: 0px;">
							<select class="span2" id="cate">
	                			<option value="3">网盘RD</option>
	                			<option value="1">UE</option>
							</select>
							<span class="add-on">必填</span>
						</div>
	                </td>
	              </tr>
	              <tr>
	              	<td class="span2">名字</td>
	                <td colspan=9>
	                	<div class="input-prepend input-append" style="margin-bottom: 0px;">
							<input class="span2" id="uname" type="text">
							<span class="add-on">必填</span>
						</div>
	                </td>
	              </tr>
	              
	               <tr>
	              	<td class="span2">休假时间</td>
	                <td colspan=9>
	                	<div class="input-prepend input-append" style="margin-bottom: 0px;">
							<input class="span4" id="htime" type="text">
							<span class="add-on">必填 例如：2014-04-04至2014-04-04，不额外休请填 “无”</span>
						</div>
	                </td>
	              </tr>
	              
	               <tr>
	              	<td class="span2">联系电话</td>
	                <td colspan=9>
	                	<div class="input-prepend input-append" style="margin-bottom: 0px;">
							<input class="span2" id="iphone" type="text">
							<span class="add-on">必填</span>
						</div>
	                </td>
	              </tr>
	              
	              <tr>
	              	<td class="span2">紧急联系电话</td>
	                <td colspan=9>
	                	<div class="input-prepend input-append" style="margin-bottom: 0px;">
							<input class="span2" id="ophone" type="text">
							<span class="add-on">必填（不额外休假可填“无”）</span>
						</div>
	                </td>
	              </tr>
	              
	              <tr>
	              	<td class="span2">备注</td>
	                <td colspan=9>
	                	<div class="input-prepend input-append" style="margin-bottom: 0px;">
							<input class="span5" id="other" type="text">
							<span class="add-on">必填(网络情况等)</span>
						</div>
	                </td>
	              </tr>
	              
	              <tr>
	                <td colspan=10>
	                	<div class="input-prepend input-append" style="margin-bottom: 0px;">
							<button class="btn" type="button" id="go">提交</button>
							
						</div>
						
	                </td>
	              </tr>
	              <tr>
	              <td colspan=10>
	              	<img alt="" src="<?php echo base_url('assets/img/qingming.jpg') ?>">
	              </td>
	              </tr>
	            </tbody>
          </table>
         
        </section>
        </div><!--/span-->
      </div><!--/row-->
 </div>
<!-- Button to trigger modal -->
<!-- Modal -->

 
<script type="text/javascript">
	$("#go").click(function(){
		uname = $("#uname").val();
		htime = $("#htime").val();
		iphone = $("#iphone").val();
		ophone = $("#ophone").val();
		other = $("#other").val();
		cate = $("#cate").val();
		if(uname.trim() == "" || htime.trim() == "" || iphone.trim() == "" || ophone.trim()=="" || other.trim() == ""){
			$('#message').html("您有内容没有填哟");
			$('#messagebox').modal('show');
			return;
		}

		
		$.post(base_url+"?c=apis&m=holiday",
				{uname:uname,htime:htime,iphone:iphone,ophone:ophone,other:other,cate:cate},function(data){
			if(data.errno == 0){				
				$('#message').html("感谢提交");
				$('#messagebox').modal('show');
				return;
			}else{
				$('#message').html(data.errmsg);
				$('#messagebox').modal('show');
				return;
			}
			
		});
	});

</script>