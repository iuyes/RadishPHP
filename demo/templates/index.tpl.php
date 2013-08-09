<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> Demo for RadishPHP v1.0 </title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
	<?php
	print_r($this->data);
	?>
	Hello, World! <br />
	<?php
	echo $this->data['name'];
	echo '<br />', 'code: ', $this->data['code'];
	?>
	<br />
	<?php
	print_r($this->data['sys']['gets']);
	print_r($this->data['keywords_stat']);
	?>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>" enctype="multipart/form-data">
		<input type="file" name="file[]" /><br />
		<input type="file" name="file[]" /><br />
		<input type="submit" value="上传文件" />
	</form>
</body>
</html>