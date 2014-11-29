
<div class="container">
<div class="row">
        <?php $this->load->view('sidebar/apisbar');?>
        <div class="span9" >
        <section>
       		<canvas id="q" style="width:100%;height:100%;"></canvas>
        </section>
        
        
        </div><!--/span-->
      </div><!--/row-->
 </div>
 
<script  type="text/javascript" >
var s = window.screen;
var width = q.width = s.width;
var height = q.height = s.height;
var letters = Array(256).join(1).split('');

var draw = function () {
  q.getContext('2d').fillStyle='rgba(0,0,0,.05)';
  q.getContext('2d').fillRect(0,0,width,height);
  q.getContext('2d').fillStyle='#0F0';
  q.getContext('2d').font = "40pt Calibri"
  letters.map(function(y_pos, index){
	if(Math.random() < 0.5){
		text = "0"
	}else{
		text = "1"
	}
    x_pos = index * 40;
	
    q.getContext('2d').fillText(text, x_pos, y_pos);
    letters[index] = (y_pos > 758 + Math.random() * 1e4) ? 0 : y_pos + 40;
  });
};
setInterval(draw, 40);
</script>