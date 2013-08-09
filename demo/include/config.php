<?php
/* Global set the parameters collection. */
$cfgs = array(
	'db' => array(
		/* The default database connection parameters. */
		'default' => array(
			'type' => 'mysql', 
			'host' => 'localhost', 
			'name' => 'test', 
			'user' => 'root', 
			'pass' => '123456', 
			'port' => 3306, 
			'charset' => 'utf8'
		)
	), 
	'cache' => array(
		'db' => array(
			'table' => 'demo_cache'
		), 
		'io' => array()
	),
	'site' => array(
		'name' => 'RadishPHP Example', 
		'path' => array(
			'css' => 'styles', 
			'script' => 'scripts'
		)
	)
);