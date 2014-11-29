<div class="container">
<div class="row">
        <?php $this->load->view('sidebar/ptagsebar');?>
        <div class="span9" >
        <section >
        	<p class="text-info">
				<span class="label label-info">ptagse对比</span>
			</p>
			<div class="input-prepend input-append">
			
				<span class="add-on">uid</span>
				<input class="span2" id="uid" type="text">
				<button class="btn" type="button" id="go">走你</button>
			</div>
			<hr>
        </section>
        <section>
        	<table class="table table-bordered table-striped result1 hide" style="table-layout:fixed ;">
	            <tbody>
	             <tr>
	              	<td>lt_flag</td>
	              	<td id="lt_flag"></td>
	                
	              </tr>
	              <tr>
	              	<td>lt_ex_flag</td>
	                <td id="lt_ex_flag"></td>
	              </tr>
	              <tr>
	              	<td>diff_count</td>
	                <td id="diff_count"></td>
	              </tr>
	              <tr>
	              	<td>diff_fsids</td>
	                <td style="word-wrap:break-word;" id="diff_fsids"></td>
	              </tr>
	             
	     	</tbody>
	    	</table>
        </section>
        
         <section >
         	<p class="text-info">
				<span class="label label-info">ptagse查找</span>多个fsids使用逗号(,)号分割
			</p>
			<div class="input-prepend input-append">
			
				<span class="add-on">uid</span>
				<input class="span2" id="uid2" type="text">
				<span class="add-on">fsids</span>
				<input class="span3" id="fsids" type="text">			  	
				<button class="btn" type="button" id="go1">走你</button>
				
			</div>
			<hr>
        </section>
        <section class="result2 hide">
        	<table class="table table-bordered table-striped">
	            <tbody>
	             <tr>
	              	<td>select_fsids</td>
	              	<td id="select_fsids"></td>
	                
	              </tr>
	              <tr>
	              	<td>nofound</td>
	                <td id="nofound"></td>
	              </tr>
	             
	     	</tbody>
	    	</table>
	    	<textarea id="metas" style="width:860px;height:300px;"></textarea>
        </section>
        </div><!--/span-->
      </div><!--/row-->
 </div>
 
<script type="text/javascript">
	$("#go").click(function(){
		uid = $("#uid").val();
		if(uid.trim() == ""){
			$('#message').html("uid不能为空...");
			$('#messagebox').modal('show');
			return;
		}
		
		$.post(base_url+"?c=ptagse&m=diff",{uid:uid},function(data){
			if(data.errno == 1){
				$('#message').html(data.errmsg);
				$('#messagebox').modal('show');
				$(".result1").hide();
				return;
			}
			$("#lt_flag").html(data.logic.lt_flag);
			$("#lt_ex_flag").html(data.logic.lt_ex_flag);
			$("#diff_count").html(data.diff_count);
			$("#diff_fsids").html(data.diff_fsids);

			$(".result1").show();
		})
	});

	$("#go1").click(function(){
		uid = $("#uid2").val();
		if(uid.trim() == ""){
			$('#message').html("uid不能为空...");
			$('#messagebox').modal('show');
			return;
		}

		fsids = $("#fsids").val();
		if(fsids.trim() == ""){
			$('#message').html("fsids不能为空...");
			$('#messagebox').modal('show');
			return;
		}
		
		$.post(base_url+"?c=ptagse&m=info",{uid:uid,fsids:fsids},function(data){
			if(data.errno == 1){
				$('#message').html(data.errmsg);
				$('#messagebox').modal('show');
				$(".result2").hide();
				return;
			}
			$("#select_fsids").html(data.select_fsids);
			$("#nofound").html(data.nofound);
			metas = format_json((data.metas));
			$("#metas").html(metas);

			$(".result2").show();
		})
	});
</script>