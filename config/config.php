<?php
return array(
	'snippets' => array(
		'example' => function($params){
			return print_r($params, 1);
		},
		'user' => function($params){
			$id = isset($params['id']) ? (int)$params['id'] : 0;
			$field = (isset($params['field']) && in_array($params['field'], array('name', 'email'))) ? (string)$params['field'] : 'name';
			$userObj = App\Models\User::findOrNew($id);
			return $userObj->{$field};
		}
	)
);