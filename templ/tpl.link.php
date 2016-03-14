<?php include("tpl.header.php"); ?>
		<h3 align="center">Shared files</h3>
		<table>
			<thead>
			<tr>
				<th width="35%">Name</th>
				<th width="10%">Size</th>
				<th width="35%">Description</th>
				<th width="10%">Created</th>
				<th width="10%">Expired</th>
			</tr>
			</thead>
			<tbody>
		<?php if($uplevel || $fid) { ?>
			<tr>
				<td colspan="5"><a href="<?php eh("/link/$id/$uplevel/"); ?>"><b>Up level</b></a></td>
			</tr>
		<?php } ?>
		<?php if($res !== FALSE) foreach($res as $row) { ?>
			<tr>
			<?php if($row[2]) { ?>
				<td><a href="<?php eh("/link/$id/$row[1]/"); ?>"><b><?php eh($row[3]); ?></b></a></td>
				<td>[DIR]</td>
			<?php } else { ?>
				<td><a href="<?php eh("/dl/$id/$row[1]/$row[3]"); ?>"><?php eh($row[3]); ?></a></td>
				<td><?php eh(formatBytes($row[4], 2)); ?></td>
			<?php } ?>
				<td><?php eh($row[9]); ?></td>
				<td><?php eh($row[5]); ?></td>
				<td><?php eh($row[6]); ?></td>
			</tr>
		<?php } ?>
			</tbody>
		</table>
<?php include("tpl.footer.php"); ?>
