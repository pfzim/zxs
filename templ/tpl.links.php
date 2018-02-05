<?php include("tpl.header.php"); ?>
		<h3 align="center">My shared links</h3>
		<table class="main-table">
			<thead>
			<tr>
				<th width="25%">Link</th>
				<th width="5%">PIN</th>
				<th width="30%">Operations</th>
				<th width="30%">Description</th>
				<th width="10%">Created</th>
			</tr>
			</thead>
			<tbody>
		<?php $i = 0; if($res !== FALSE) foreach($res as &$row) { $i++; ?>
		<tr id="<?php eh("row".$row[0]);?>">
			<td><a href="<?php eh("/link/$row[0]/"); ?>" onclick="return f_expand(this, <?php eh($row[0]);?>, 0);"><?php eh("http://{$_SERVER['HTTP_HOST']}/link/$row[0]/"); ?></a></td>
			<td id="<?php eh("pin".$row[0]);?>"><?php eh($row[1]);?></td>
			<td>
				<a href="<?php eh("/link/$row[0]/"); ?>" onclick="return f_copy(this.href, <?php eh($row[0]);?>);">Copy</a>
				<a href="<?php eh("$self?action=unlink&id=$row[0]"); ?>" onclick="return f_unlink(<?php eh($row[0]); ?>);">Remove link</a>
				<a href="<?php eh("$self?action=pinon&id=$row[0]"); ?>" onclick="return f_pinon(<?php eh($row[0]); ?>);">New PIN</a>
				<a href="<?php eh("$self?action=pinoff&id=$row[0]"); ?>" onclick="return f_pinoff(<?php eh($row[0]); ?>);">Remove PIN</a>
			</td>
			<td class="command" id="<?php eh("desc".$row[0]); ?>" onclick="f_desc_link(this, <?php eh($row[0]); ?>);"><?php eh($row[2]); ?></td>
			<td title="<?php eh($row[4]);?>"><?php eh($row[3]);?></td>
		</tr>
		<?php } ?>
			</tbody>
		</table>
<?php include("tpl.footer.php"); ?>
