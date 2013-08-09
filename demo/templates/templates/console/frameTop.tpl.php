<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=gb2312" />
	<title></title>
	<base target="wMainFrame" />
	<link type="text/css" rel="stylesheet" href="<?php echo $this->data['sys']['cfgs']['envs']['css'];?>/global_console.css" />
	<script type="text/javascript" src="<?php echo $this->data['sys']['cfgs']['envs']['script'];?>/jquery.js"></script>
	<script type="text/javascript">
		//<![CDATA[
		function collapse() {
			if (top.collapseWin()) {
				$('#collapseLink').html('显示左侧窗口 &gt;');
			} else {
				$('#collapseLink').html('&lt; 隐藏左侧窗口');
			}
		}

		function refresh() {
			top.RefreshMainFrame();
		}
		//]]>
	</script>
</head>
<body>
	<div id="header">
		<ul class="clearfix">
			<!--li><span><a href="javascript:void(0);" id="collapseLink" onclick="collapse();" title="折叠/展开">&lt; 隐藏左侧窗口</a></span></li>
			<li><span><a href="javascript:void(0);" id="A1" onclick="refresh();" title="刷新主帧窗口">刷新主窗口</a></span></li-->
			<li class="css-bgi-1 spec"><span class="css-bgi-1"><a class="css-bgi-1" href="?r=console.index.default">睿泰网站管理后台</a></span></li>
			<li class="css-bgi-1"><span class="css-bgi-1"><a class="css-bgi-1" href="?r=console.settings.index">系统设置</a></span></li>
			<li class="css-bgi-1"><span class="css-bgi-1"><a class="css-bgi-1" href="?r=console.news.index">新闻中心</a></span></li>
			<li class="css-bgi-1"><span class="css-bgi-1"><a class="css-bgi-1" href="?r=console.product.index">产品展示</a></span></li>
			<li class="css-bgi-1"><span class="css-bgi-1"><a class="css-bgi-1" href="?r=console.order.index">订单管理</a></span></li>
			<li class="css-bgi-1"><span class="css-bgi-1"><a class="css-bgi-1" href="?r=console.guestbook.index">留言管理</a></span></li>
		</ul>
	</div>
	<div id="header-bottom-vline"></div>
	<script type="text/javascript">
	//<![CDATA[
	$('a').focus(function(){
		$(this).blur();
	});
	//]]>
	</script>
</body>
</html>