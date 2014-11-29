<div class="container">
<div class="row">
        <?php $this->load->view('sidebar/apisbar');?>
        <div class="span9" >
        <section >
			<table class="table table-bordered table-striped">
	            <tbody>
	              <tr>
	              	<td>清理用户状态缓存</td>
	                <td colspan=2>
	                	<div class="input-prepend input-append" style="margin-bottom: 0px;">
	                		<span class="add-on">uid</span>
							<input class="span2" id="clearcacheUser" type="text">
							<button class="btn" type="button" id="clearcacheUser_GO">走你</button>
						</div>
	                </td>
	              </tr>
	               <tr>
	              	<td>清理用户中奖列表缓存</td>
	                <td colspan=2>
	                	<div class="input-prepend input-append" style="margin-bottom: 0px;">
	                		<span class="add-on">uid</span>
							<input class="span2" id="clearcacheMylist" type="text">
							<button class="btn" type="button" id="clearcacheMylist_GO">走你</button>
						</div>
	                </td>
	              </tr>
	               <tr>
	              	<td>清理中奖列表缓存缓存</td>
	                <td colspan=2>
	                	<div class="input-prepend input-append" style="margin-bottom: 0px;">
							<button class="btn" type="button" id="clearcacheLuckylist_GO">走你</button>
						</div>
	                </td>
	              </tr>
	              <tr style="display:none">
	              	<td>清理uv缓存</td>
	                <td colspan=2>
	                	<div class="input-prepend input-append" style="margin-bottom: 0px;">
							<button class="btn" type="button" id="clearcacheUV_GO">走你</button>
						</div>
	                </td>
	              </tr>
	            </tbody>
          </table>
          
          <table class="table table-bordered table-striped">
	            <tbody>
	              <tr>
	              	<td class="span2">查询用户状态</td>
	                <td colspan=9>
	                	<div class="input-prepend input-append" style="margin-bottom: 0px;">
	                		<span class="add-on">uid</span>
							<input class="span2" id="searchUserStatus" type="text">
							<button class="btn" type="button" id="searchUserStatus_GO">走你</button>
						</div>
	                </td>
	              </tr>
	               <tr class="searchUserStatus" style="display:none">
	              	<td>用户ID</td>
	                <td>用户名</td>
	                <td>端登陆态</td>
	                <td>大礼包态</td>
	                <td>抽奖次数</td>
	                <td>邀请次数</td>
	                <td>邀请者ID</td>
	                <td>邀请者名字</td>
	                <td>是否提交收货信息</td>
	                <td>是否中奖过</td>
	              </tr>
	              <tr class="searchUserStatus" style="display:none">
	              	<td id="searchUserStatus_user_id"></td>
	                <td id="searchUserStatus_user_name"></td>
	                <td id="searchUserStatus_status_login"></td>
	                <td id="searchUserStatus_status_gift"></td>
	                <td id="searchUserStatus_lucky_num"></td>
	                <td id="searchUserStatus_invite_num"></td>
	                <td id="searchUserStatus_inviter_user_id"></td>
	                <td id="searchUserStatus_inviter_user_name"></td>
	                <td id="searchUserStatus_is_init_uinfo"></td>
	                <td id="searchUserStatus_is_hit_gift"></td>
	              </tr>
	             
	            </tbody>
          </table>
          
          <table class="table table-bordered table-striped">
	            <tbody>
	              <tr>
	              	<td class="span2">查询用户收货地址</td>
	                <td colspan=4>
	                	<div class="input-prepend input-append" style="margin-bottom: 0px;">
	                		<span class="add-on">uid</span>
							<input class="span2" id="searchUserInfo" type="text">
							<button class="btn" type="button" id="searchUserInfo_GO">走你</button>
						</div>
	                </td>
	              </tr>
	               <tr class="searchUserInfo" style="display:none">
	              	<td>用户ID</td>
	                <td>用户名</td>
	                <td>真实名字</td>
	                <td>地址</td>
	                <td>手机号</td>
	              </tr>
	              <tr class="searchUserInfo" style="display:none">
	              	<td id="searchUserInfo_user_id"></td>
	                <td id="searchUserInfo_user_name"></td>
	                <td id="searchUserInfo_real_name"></td>
	                <td id="searchUserInfo_address"></td>
	                <td id="searchUserInfo_phone"></td>
	              </tr>
	             
	            </tbody>
          </table>
          
           <table class="table table-bordered table-striped">
	            <tbody>
	              <tr>
	              	<td class="span2">查询奖池中奖状态</td>
	                <td colspan=8>
							<button class="btn" type="button" id="searchPrizeStatus_GO">走你</button>
	                </td>
	              </tr>
	               <tr class="searchPrizeStatus" style="display:none" id="prizelist">
	               	<td>奖品名</td>
	                <td>激活时间</td>
	                <td>激活UV</td>
	              	<td>用户ID</td>
	                <td>用户名</td>
	                <td>当前UV</td>
	                <td>邀请者ID</td>
	                <td>邀请者名字</td>
	                <td>中奖时间</td>
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
	$("#clearcacheUser_GO").click(function(){
		uid = $("#clearcacheUser").val();
		
		if(uid.trim() == ""){
			$('#message').html("uid不能为空");
			$('#messagebox').modal('show');
			return;
		}

		$.post(base_url+"?c=spring&m=clearuser",{uid:uid},function(data){
			if(data.errno == 0){
				$('#message').html("操作成功...");
				$('#messagebox').modal('show');
				return;
			}else{
				$('#message').html("操作失败...");
				$('#messagebox').modal('show');
				return;
			}
			
		});
	});

	$("#clearcacheMylist_GO").click(function(){
		uid = $("#clearcacheMylist").val();
		
		if(uid.trim() == ""){
			$('#message').html("uid不能为空");
			$('#messagebox').modal('show');
			return;
		}

		$.post(base_url+"?c=spring&m=clearmylist",{uid:uid},function(data){
			if(data.errno == 0){
				$('#message').html("操作成功...");
				$('#messagebox').modal('show');
				return;
			}else{
				$('#message').html("操作失败...");
				$('#messagebox').modal('show');
				return;
			}
			
		});
	});

	$("#clearcacheLuckylist_GO").click(function(){
		$.post(base_url+"?c=spring&m=clearluckylist",{},function(data){
			if(data.errno == 0){
				$('#message').html("操作成功...");
				$('#messagebox').modal('show');
				return;
			}else{
				$('#message').html("操作失败...");
				$('#messagebox').modal('show');
				return;
			}
			
		});
	});

	$("#searchUserStatus_GO").click(function(){
		uid = $("#searchUserStatus").val();
		
		if(uid.trim() == ""){
			$('#message').html("uid不能为空");
			$('#messagebox').modal('show');
			return;
		}

		$.post(base_url+"?c=spring&m=getuserstatus",{uid:uid},function(data){
			if(data.errno == 0){
				data = data.status
				$("#searchUserStatus_user_id").html(data.user_id);
				$("#searchUserStatus_user_name").html(data.user_name);
				$("#searchUserStatus_status_login").html(data.status_login);
				$("#searchUserStatus_status_gift").html(data.status_gift);
				$("#searchUserStatus_lucky_num").html(data.lucky_num);
				$("#searchUserStatus_invite_num").html(data.invite_num);
				$("#searchUserStatus_inviter_user_id").html(data.inviter_user_id);
				$("#searchUserStatus_inviter_user_name").html(data.inviter_user_name);
				$("#searchUserStatus_is_init_uinfo").html(data.is_init_uinfo);
				$("#searchUserStatus_is_hit_gift").html(data.is_hit_gift);
				$(".searchUserStatus").show();
				return;
			}else{
				$('#message').html("查询失败...");
				$('#messagebox').modal('show');
				return;
			}
			
		});
	});

	$("#searchUserInfo_GO").click(function(){
		uid = $("#searchUserInfo").val();
		
		if(uid.trim() == ""){
			$('#message').html("uid不能为空");
			$('#messagebox').modal('show');
			return;
		}

		$.post(base_url+"?c=spring&m=getuserinfo",{uid:uid},function(data){
			if(data.errno == 0){
				data = data.info
				$("#searchUserInfo_user_id").html(data.user_id);
				$("#searchUserInfo_user_name").html(data.user_name);
				$("#searchUserInfo_real_name").html(data.real_name);
				$("#searchUserInfo_address").html(data.address);
				$("#searchUserInfo_phone").html(data.phone);
				$(".searchUserInfo").show();
				return;
			}else{
				$('#message').html("查询失败...");
				$('#messagebox').modal('show');
				return;
			}
			
		});
	});

	$("#searchPrizeStatus_GO").click(function(){
		
		$.post(base_url+"?c=spring&m=getprizes",{},function(data){
			if(data.errno == 0 && data.prizes.length > 0){
				tds = "";
				$(data.prizes).each(function(){ 
					tds += "<tr><td>"+this.prize_name+"</td>"+
		                "<td>"+this.active_time+"</td>"+
		                "<td>"+this.active_uv+"</td>"+
		              	"<td>"+this.lucky_user_id+"</td>"+
		                "<td>"+this.lucky_user_name+"</td>"+
		                "<td>"+this.lucky_uv+"</td>"+
		                "<td>"+this.inviter_user_id+"</td>"+
		                "<td>"+this.inviter_user_name+"</td>"+
		                "<td>"+this.mtime+"</td></tr>";
					}); 
				$("#prizelist").after(tds);
				$(".searchPrizeStatus").show();
				return;
			}else{
				$('#message').html("查询失败...");
				$('#messagebox').modal('show');
				return;
			}
			
		});
	});
</script>