<?php

class DepartmentForm extends ModelForm
{
	public function fields()
	{
		return [
			'did'	=>	null,
			'pid'	=>	[
				'label'	=>	'父级部门',
				'type'	=>	'select',
				'options'	=>	[
					['did'=>null, 'pid'=>0, 'name'=>'顶级']
				],
				'default'	=>	0,
				'validator'	=> [
					'required'	=> true,
				]	
			],
			'name'	=>	[
				'label'	=>	'部门名称',
				'type'	=>	'text',
				'inputOptions'	=>	[
					'class'	=>	'col-lg-3'
				],	
				'validator'	=>	[
					'required'	=>	true,
					'minlength'	=>	2,
					'maxlength'	=>	10
				],
				'remark'	=>	'名称在2-10个字符之间'
			]
		];
	}
}
