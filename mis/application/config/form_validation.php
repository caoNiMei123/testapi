<?php 
/*
 * form_validation.php
 *
*/
$config = array(
	'register' => array(
		array(
			'field' => 'username',
			'label' => '用户名',
			'rules' => 'trim|required|min_length[4]|max_length[12]|xss_clean|callback_username_exists'
		),
		array(
			'field' => 'password',
			'label' => '密码',
			'rules' => 'trim|required'
		),
		array(
			'field' => 'repassword',
			'label' => '确认密码',
			'rules' => 'trim|matches[password]|required'
		),
		array(
			'field' => 'email',
			'label' => '邮箱',
			'rules' => 'trim|valid_email|required|callback_email_exists'
		),
		array(
			'field' => 'invitecode',
			'label' => '邀请码',
			'rules' => 'trim|required'
		)
	),
	
	'login' => array(
		array(
			'field' => 'login_username',
			'label' => '用户名',
			'rules' => 'trim|required|min_length[4]|max_length[12]|xss_clean|callback_username_check'
		),
		array(
			'field' => 'login_password',
			'label' => '密码',
			'rules' => 'trim|required|callback_password_check'
		)		
	),
	
	'add_experiment' => array(
	    array(
	        'field' => 'experiment_title',
	        'label' => '实验题目',
	        'rules' => 'trim|required|max_length[400]'
	    ),
	    array(
	        'field' => 'experiment_type',
	        'label' => '实验类型',
	        'rules' => 'trim|required'
	    ),
	    array(
	        'field' => 'experiment_describe',
	        'label' => '实验描述',
	        'rules' => 'trim|required|max_length[4000]'
	    ),
	    array(
	        'field' => 'experiment_status',
	        'label' => '实验状态',
	        'rules' => 'trim|required'
	    )
	),
	
	'update_experiment' => array(
	    array(
	        'field' => 'experiment_title',
	        'label' => '实验题目',
	        'rules' => 'trim|required|max_length[400]'
	    ),
	    array(
	        'field' => 'experiment_type',
	        'label' => '实验类型',
	        'rules' => 'trim|required'
	    ),
	    array(
	        'field' => 'experiment_describe',
	        'label' => '实验描述',
	        'rules' => 'trim|required|max_length[4000]'
	    ),
	    array(
	        'field' => 'experiment_status',
	        'label' => '实验状态',
	        'rules' => 'trim|required'
	    )
	),
	
	'create_key' => array(
	    array(
	        'field' => 'key_name',
	        'label' => '密钥名',
	        'rules' => 'trim|required|max_length[20]|min_length[3]'
	    )
	),
	
	'create_vm' => array(
	    array(
	        'field' => 'vm_count',
	        'label' => '虚拟机个数',
	        'rules' => 'trim|required|max_length[1]'
	    ),
		array(
				'field' => 'vm_eid',
				'label' => '实验题目',
				'rules' => 'trim|required'
		),
		array(
				'field' => 'vm_image',
				'label' => '虚拟机镜像',
				'rules' => 'trim|required'
		),
		array(
				'field' => 'vm_key',
				'label' => '虚拟机密钥',
				'rules' => 'trim|required'
		)
	),
		
	'add_cluster' => array(
		array(
			'field' => 'cluser_eid',
			'label' => '实验题目',
			'rules' => 'trim|required'	
		),
		array(
				'field' => 'cluster_template',
				'label' => 'HPC镜像',
				'rules' => 'trim|required'
		),
		array(
				'field' => 'cluster_slave_count',
				'label' => '从节点虚拟机数量',
				'rules' => 'trim|required'
		)		
	),
		
	'add_diary' => array(
		array(
				'field' => 'diary_eid',
				'label' => '实验标题',
				'rules' => 'trim|required'
		),
		array(
				'field' => 'diary_title',
				'label' => '日志标题',
				'rules' => 'trim|required|max_length[400]'
		),
		array(
				'field' => 'diary_content',
				'label' => '日志内容',
				'rules' => 'trim|required|max_length[4000]'
		)
	),

	'add_template' => array(
		array(
				'field' => 'exp_id',
				'label' => '实验标题',
				'rules' => 'trim|required'
				),
		array(
				'field' => 'node_count',
				'label' => '节点数量',
				'rules' => 'trim|required|max_length[1]'
				),
		array(
				'field' => 'command',
				'label' => '运行命令',
				'rules' => 'trim|required'
				)
	),
	
	'save_image' => array(
		array(
				'field' => 'image_name',
				'label' => '实验环境名称',
				'rules' => 'trim|required|alpha_dash'
				),
		array(
				'field' => 'image_describe',
				'label' => '环境描述',
				'rules' => 'trim|required|alpha_dash'
		)
			)
);
