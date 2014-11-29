<div class="container">

<div class="row">
        <?php $this->load->view('sidebar/ptagsebar');?>
       
        <div class="span9" >
        <section >
			<table class="table table-bordered table-striped">
	            <tbody>
	              <tr>
	                <td colspan=3>
	                	<div class="input-prepend input-append">
	                		<select class="span1" id="cate">
	                			<option value="3">照片</option>
	                			<option value="1">视频</option>
							</select>
							<span class="add-on">uid</span>
							<input class="span2" id="keyword" type="text" value="<?php echo $uid;?>">
							<span class="add-on">页码</span>
							<input class="span1" id="page" type="text" value="0">
							<button class="btn" type="button" id="go">走你</button>
							<button class="btn" type="button" id="refresh">刷新</button>
							<button class="btn" type="button" id="prev">上一页</button>
							<button class="btn" type="button" id="next">下一页</button>
						</div>
	                </td>
	              </tr>
	            </tbody>
          </table>
        </section>      
       	<hr>
       	<section id="result">
       
       	</section>
        </div><!--/span-->
      </div><!--/row-->
 </div>
<script type="text/javascript">

function updatepages(start){
	cate = $('#cate').val();
	keyword = $("#keyword").val();
	if(keyword.trim() == ""){
		$('#message').html("请输入UID");
		$('#messagebox').modal('show');
		return;
	}

	
	$("#result").html("")
	$.post(base_url+"?c=apis&m=imgview",{cate:cate,uid:keyword,page:start},function(data){
		$("#result").html(data)
		$('.fancybox').fancybox();
		//$("#page").val(start+1)
		return;

	})
	
}

$("#prev").click(function(){
	start = parseInt($("#page").val())
	$("#page").val(start-1)
	updatepages(start-1)
});

$("#next").click(function(){
	start = parseInt($("#page").val())
	$("#page").val(start+1)
	updatepages(start+1)
});

$("#go").click(function(){
	start = parseInt($("#page").val())
	updatepages(start)
});

$("#refresh").click(function(){
	
	$.post(base_url+"?c=apis&m=updateuid",{},function(data){
		$("#keyword").val(data.uid)
		return;

	})
	
});
</script>
