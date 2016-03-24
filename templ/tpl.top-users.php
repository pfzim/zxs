<?php include("tpl.header.php"); ?>
		<h3 align="center">Top users</h3>
		<table>
			<thead>
			<tr>
				<th>User</th>
				<th>Uploads</th>
			</tr>
			</thead>
			<tbody>
		<?php $i = 0; if($res !== FALSE) foreach($res as $row) { $i++; ?>
		<tr>
			<td><?php eh($row[0]);?></td>
			<td><?php eh($row[1]);?></td>
		</tr>
		<?php } ?>
			</tbody>
		</table>
<?php include("tpl.footer.php"); ?>
