<?php include("tpl.header.php"); ?>
		<div class="login-block">
			<h1 align="center">Enter PIN code:</h1>
			<form action="<?php eh("$self?action=pin&id=$id"); ?>" method="post">
				PIN: <input name="pin" type="text" autofocus="autofocus"/>
				<?php if(!empty($error_msg)) { ?>
				<p><?php he($error_msg); ?></p>
				<?php } ?>
			<input type="submit" value="OK" />
		</div>
<?php include("tpl.footer.php"); ?>
