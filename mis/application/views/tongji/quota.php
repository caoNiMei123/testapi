<div class="container">
<div class="row">
        <?php $this->load->view('sidebar/apisbar');?>
        <div class="span9" >
        <section >
        	<div class="input-prepend input-append" style="margin-bottom: 0px;">
				<span class="add-on">uid</span>
				<input class="span2" id="uid" type="text">
				<button class="btn" type="button" id="go">走你</button>
			</div>
			
			<hr>
			<table class="table table-bordered table-striped result hide">
				<thead>
	          	<tr>
	          		<th>免费容量(G)</th>
	          		<th>付费容量(G)</th>
	          		<th>扩展容量(G)</th>
	          		<th>已使用(G)</th>
	          		<th>总容量(G)</th>
	          	</tr>
	          </thead>
	            <tbody id="info">
	             
	            </tbody>
          </table>
          
          <table class="table table-bordered table-striped result hide">
	          <thead>
	          	<tr>
	          		<th>trans_id</th>
	          		<th>size(G)</th>
	          		<th>start</th>
	          		<th>end</th>
	          		<th>operation</th>
	          		<th>reason code</th>
	          	</tr>
	          </thead>
	            <tbody id="list">
	             
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
		uid = $("#uid").val();
		
		if(uid.trim() == ""){
			$('#message').html("uid不能为空");
			$('#messagebox').modal('show');
			return;
		}

		$.post(base_url+"?c=apis&m=quota",{uid:uid},function(data){
			if(data.errno == 0){				
				info = "<tr>"+
				"<td>"+data.info.quota_free+"</td>"+
				"<td>"+data.info.quota_paid+"</td>"+
				"<td>"+data.info.quota_extend+"</td>"+
				"<td>"+data.info.quota_used+"</td>"+
				"<td>"+data.info.total+"</td>"+
				"</tr>";

				$("#info").html(info);
				
				records = "";
				$(data.list).each(function(){ 
					records += "<tr>"+
					"<td>"+this.trans_id+"</td>"+
					"<td>"+this.size+"</td>"+
					"<td>"+this.start_time+"</td>"+
					"<td>"+this.end_time+"</td>"+
					"<td>"+this.operation_time+"</td>"+
					"<td>"+this.reason_code+"</td>"+
					"</tr>";
				})
				$("#list").html(records);
				$(".result").show();
				return;
			}else{
				$('#message').html(data.errmsg);
				$('#messagebox').modal('show');
				return;
			}
			
		});
	});

</script>