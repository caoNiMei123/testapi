<div class="container">
    <div class="row">
        <div class="span2 bs-docs-sidebar">
            <?php $this->load->view('sidebar/tongjibar');?>
            <div class="span9" >
            <section>
                <canvas id="q" style="width:100%;height:100%;"></canvas>
            </section>        
        </div><!--/span-->
    </div><!--/row-->

<script type="text/javascript"> 
    function get_list(key_arr, value_arr) {    
        $('#container').highcharts({
            title: {
                text: '成功订单统计',
                x: -20 //center
            },
            subtitle: {
                text: 'Source: Mysql',
                x: -20
            },
            xAxis: {
                categories: key_arr
            },
            yAxis: {
                title: {
                    text: '个数'
                },
                plotLines: [{
                    value: 0,
                    width: 1,
                    color: '#808080'
                }]
            },
            tooltip: {
                valueSuffix: '个'
            },
            legend: {
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'middle',
                borderWidth: 0
            },
            series: [{
                name: '订单个数',
                data: value_arr
            }]
        });
    };

    var key_array=new Array();
    var value_array=new Array();
    </script> 
    <?php if (!empty($list)):?>
        <?php foreach ($list as $key => $item): ?>
            <script type="text/javascript"> 
                key_array.push(<?php echo $item['day'];?>);
                value_array.push(<?php echo $item['item_2'];?>);        
            </script> 
        <?php endforeach;?>
    <div id="container" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
    <script type="text/javascript"> 
        get_list(key_array, value_array);
    </script>



    <?php else:?>
        
    <?php endif;?>
</div>

