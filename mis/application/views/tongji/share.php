<div class="container">
<div class="row">
        <?php $this->load->view('sidebar/apisbar');?>
        <div class="span9" >
        <section >
			<div class="input-prepend input-append">
			
				<span class="add-on">url</span>
				<input class="span4" id="url" type="text">
				<button class="btn" type="button" id="go">走你</button>
				
			</div>
			<p class="text-info">
				<span class="label label-info">说明！</span>
				url可以为长链接或者短链接
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
		url = $("#url").val();
		if(url.trim() == ""){
			$('#message').html("url不能为空...");
			$('#messagebox').modal('show');
			return;
		}
		

		$.post(base_url+"?c=apis&m=share",{url:url},function(data){
			info = format_json(data);
			$("#fileinfo").html(info);
			$("#fileinfo").show();
		})
	});
</script>