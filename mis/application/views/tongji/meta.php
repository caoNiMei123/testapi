<div class="container">
<div class="row">
        <?php $this->load->view('sidebar/apisbar');?>
        <div class="span9" >
        <section >
			<div class="input-prepend input-append">
			
				<span class="add-on">uid</span>
				<input class="span2" id="uid" type="text">
			  	<select class="span1" id="type">
                			<option value="fsid">fsid</option>
						  	<option value="path">path</option>
						</select>
			  	<input class="span2" id="file" type="text">
			  	
				<button class="btn" type="button" id="go">走你</button>
				
			</div>
			<p class="text-info">
				<span class="label label-info">注意！</span>
				当选择path的时候，请不要进行urlencode
			</p>
			<hr>
        </section>
        <section>
        	<textarea id="fileinfo" style="width:860px;height:300px;display:none"></textarea>
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
		type = $("#type").val();
		file = $("#file").val();
		if(file.trim() == ""){
			$('#message').html("fsid或者path不能为空...");
			$('#messagebox').modal('show');
			return;
		}

		$.post(base_url+"?c=apis&m=meta",{uid:uid,type:type,file:file},function(data){
			info = format_json(data);
			$("#fileinfo").html(info);
			$("#fileinfo").show();
		})
	});
</script>