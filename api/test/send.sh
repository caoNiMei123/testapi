#reg
curl 'http://127.0.0.1:8089/rest/2.0/carpool/user?method=reg&devuid=1&ctype=1' -d 'account=18601165872' -d 'type=1' -d 'detail={"car_num":"NGW036","car_engine_num":"103210677","car_type":"cruze"}' -d 'secstr=1234'
curl 'http://127.0.0.1:8089/rest/2.0/carpool/user?method=reg&devuid=1&ctype=1' -d 'account=18601165873' -d 'type=2' -d 'secstr=1234'
#login
curl 'http://127.0.0.1:8089/rest/2.0/carpool/user?method=login&devuid=1&ctype=1' -d 'account=18601165873' -d 'secstr=1234' -d 'type=2'
#carpool create 
curl 'http://127.0.0.1:8089/rest/2.0/carpool/order?method=create&devuid=1&ctype=1' -d 'type=2' -d 'src=abcd' -d 'src_gps=117.131692,34.211964' -d 'dest=efgh' -d 'dest_gps=117.131692,34.211964' -d 'user_name=18601165873' -d 'user_type=2' -d 'user_id=10045'
#carpool cancel
curl 'http://127.0.0.1:8089/rest/2.0/carpool/order?method=cancel&devuid=1&ctype=1' -d 'user_name=18601165873' -d 'user_type=2' -d 'user_id=10045' -d 'pid=14937130105680741056'
#driver report
curl 'http://127.0.0.1:8089/rest/2.0/carpool/driver?method=report&devuid=1&ctype=1' -d 'user_name=18601165872' -d 'user_type=1' -d 'user_id=10000' -d 'gps=34.211964,117.131692'
#user query 
curl 'http://127.0.0.1:8089/rest/2.0/carpool/user?method=query&devuid=1&ctype=1' -d 'user_name=18601165872' -d 'user_type=1' -d 'user_id=10002' 
#user modify
curl 'http://127.0.0.1:8089/rest/2.0/carpool/user?method=modify&devuid=1&ctype=1' -d 'user_name=18601165872' -d 'user_type=1' -d 'user_id=10002' -d 'name=abcdefg'

#carpool accept 
curl 'http://127.0.0.1:8089/rest/2.0/carpool/order?method=accept&devuid=1&ctype=1' -d 'user_name=18601165872' -d 'user_type=1' -d 'user_id=10000' -d 'pid=14937130105680741056'
#carpool finish 
curl 'http://127.0.0.1:8089/rest/2.0/carpool/order?method=finish&devuid=1&ctype=1' -d 'user_name=18601165872' -d 'user_type=1' -d 'user_id=10000' -d 'pid=14937130105680741056'
#carpool batch_query
curl 'http://127.0.0.1:8089/rest/2.0/carpool/order?method=batch_query&devuid=1&ctype=1' -d 'user_name=18601165872' -d 'user_type=1' -d 'user_id=10002' -d 'list=[3287598590273545518]'
#carpool list 
curl 'http://127.0.0.1:8089/rest/2.0/carpool/order?method=list&devuid=1&ctype=1' -d 'user_name=18601165872' -d 'user_type=1' -d 'user_id=10000' 
curl 'http://127.0.0.1:8089/rest/2.0/carpool/order?method=list&devuid=1&ctype=1' -d 'user_name=18601165873' -d 'user_type=2' -d 'user_id=10045' 
#user report
curl 'http://127.0.0.1:8089/rest/2.0/carpool/user?method=report&devuid=1&ctype=1' -d 'user_name=18601165873' -d 'user_type=2' -d 'user_id=10045' -d 'client_id=1'
#driver report
curl 'http://127.0.0.1:8089/rest/2.0/carpool/driver?method=report&devuid=1&ctype=1' -d 'user_name=18601165872' -d 'user_type=1' -d 'user_id=10000' -d 'gps=117.131692,34.211964' 
#gettoken
curl 'http://127.0.0.1:8089/rest/2.0/carpool/user?method=gettoken&devuid=1&ctype=1' -d 'account=18601165873' -d 'type=1' -d 'reason=1'
curl 'http://127.0.0.1:8089/rest/2.0/carpool/user?method=gettoken&devuid=1&ctype=1' -d 'account=gaowei@baidu.com' -d 'user_name=18601165873' -d 'user_type=2' -d 'user_id=10003' -d 'type=2' -d 'reason=2'
#feedback
curl 'http://127.0.0.1:8089/rest/2.0/carpool/feedback?method=create&devuid=1&ctype=1' -d 'user_name=18601165872' -d 'user_type=1' -d 'user_id=10002' -d 'type=1' -d 'detail=abcdefg'
#image upload
curl 'http://127.0.0.1:8089/rest/2.0/carpool/image?method=upload&devuid=1&ctype=1' -d 'user_name=18601165872' -d 'user_type=1' -d 'user_id=10002' -T '111111111111111111111111111111111111111111111111111111111111'
