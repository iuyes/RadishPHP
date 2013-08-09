<!--{include file="header.tpl.php"}-->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> Demo for RadishPHP v1.0 </title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
	<!--{$sys.doValue}-->
	<!--{date time() 'Y-m-d H:i:s'}-->
	<h1><!--{$site.dirs.attachments.file}--></h1>
	<div><!--{$title}--></div>
	<ul>
		<!--{foreach $data $key $value}-->
		<li><a href=""><!--{$value.a}--></a></li>
		<ul>
			<!--{iterate $value.c $k $v}-->
			<li><!--{$v.cv}--></li>
			<!--{/iterate}-->
		</ul>
		<!--{/foreach}-->
	</ul>
	<ul>
		<!--{foreach $data $key $value}-->
		<li><a href=""><!--{$value.b}--></a></li>
		<!--{/foreach}-->
	</ul>
	<ul>
		<!--{foreach $data $key $value}-->
		<li><a href=""><!--{$value.c}--></a></li>
		<!--{/foreach}-->
	</ul>
	<!--{if $view == 1}-->
	A
	<!--{else}-->
	B
	<!--{/if}-->
</body>
</html>
<!--{include file="footer.tpl.php"}-->