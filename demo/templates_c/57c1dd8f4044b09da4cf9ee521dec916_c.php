<?php /* -- * -- RadishPHP Template Engine (v1.0) -- * -- */ ?>
<?php include ("E:/Developer/PHP/RadishPHP/demo/templates_c/dc7d79a4bc0a410e287e8c0f3832849b_c.php"); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> Demo for RadishPHP v1.0 </title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
<?php echo $sys['doValue']; ?>
<?php echo date('Y-m-d H:i:s', time()); ?>
	<h1><?php echo $site['dirs']['attachments']['file']; ?></h1>
	<div><?php echo $title; ?></div>
	<ul>
<?php if (is_array($data)) { foreach ($data as $key => $value) { ?>
		<li><a href=""><?php echo $value['a']; ?></a></li>
		<ul>
<?php if (is_array($value['c'])) { foreach ($value['c'] as $k => $v) { ?>
			<li><?php echo $v['cv']; ?></li>
<?php }} ?>
		</ul>
<?php }} ?>
	</ul>
	<ul>
<?php if (is_array($data)) { foreach ($data as $key => $value) { ?>
		<li><a href=""><?php echo $value['b']; ?></a></li>
<?php }} ?>
	</ul>
	<ul>
<?php if (is_array($data)) { foreach ($data as $key => $value) { ?>
		<li><a href=""><?php echo $value['c']; ?></a></li>
<?php }} ?>
	</ul>
<?php if ($view == 1) { ?>
	A
<?php } else { ?>
	B
<?php } ?>
</body>
</html>
<?php include ("E:/Developer/PHP/RadishPHP/demo/templates_c/b686b87b53fd0f7a29f622a76f850c6e_c.php"); ?>