<?php if(!defined("ZXS_PROTECTED")) exit; ?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>ZXS</title>
		<link type="text/css" href="/templ/style.css" rel="stylesheet" />
		<?php if($uid) { ?>
		<script type="text/javascript" src="/zxs.js"></script>
		<?php } ?>
	</head>
	<body>
		<?php if($uid) { ?>
		<ul class="menu-bar">
			<li><a href="/">My files</a></li>
			<li><a href="/links/">My shares</a></li>
			<?php if($uid == 1) { ?>
			<li><a href="/all/">All files</a></li>
			<li><a href="/all-links/">All links</a></li>
			<?php } ?>
			<ul style="float:right;list-style-type:none;">
				<li><a href="/stats/">Top users</a></li>
				<li><a href="/info/">About</a></li>
				<li><a href="/logoff/">Logout</a></li>
			</ul>
		</ul>
		<?php } ?>
