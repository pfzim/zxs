<?php include("tpl.header.php"); ?>
		<h3 align="center">All shared links</h3>
		<table>
			<thead>
			<tr>
				<th width="25%">Link</th>
				<th width="5%">PIN</th>
				<th width="20%">Owner</th>
				<th width="30%">Description</th>
				<th width="10%">Created</th>
			</tr>
			</thead>
			<tbody>
		<?php $i = 0; if($res !== FALSE) foreach($res as &$row) { $i++; ?>
		<tr id="<?php eh("row".$row[0]);?>">
			<td><a href="<?php eh("/link/$row[0]/"); ?>"><?php eh("http://{$_SERVER['HTTP_HOST']}/link/$row[0]/"); ?></a></td>
			<td><?php eh($row[1]);?></td>
			<td><?php eh($row[3]);?></td>
			<td><?php eh($row[2]); ?></td>
			<td title="<?php eh($row[5]);?>"><?php eh($row[4]);?></td>
		</tr>
		<?php } ?>
			</tbody>
		</table>
<?php include("tpl.footer.php"); ?>
