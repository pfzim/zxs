<?php include("tpl.header.php"); ?>
		<h3 align="center">All Files</h3>
		<table id="table">
			<thead>
			<tr>
				<th width="34%">Name</th>
				<th width="10%">Size /DL</th>
				<th width="15%">Owner</th>
				<th width="23%">Description</th>
				<th width="9%">Created</th>
				<th width="9%">Expired</th>
			</tr>
			</thead>
			<tbody>
		<?php if($uplevel || $id) { ?>
			<tr>
				<td colspan="6"><a href="<?php eh("/all/$uplevel/"); ?>"><b>Up level</b></a></td>
			</tr>
		<?php } ?>
		<?php $i = 0; if($res !== FALSE) foreach($res as &$row) { $i++; ?>
			<tr id="<?php eh("row".$row[0]);?>">
			<?php if(intval($row[5]) == 1) { ?>
				<td><a href="<?php eh("/all/$row[0]/"); ?>"><b><?php eh($row[1]); ?></b></a></td>
				<td>[DIR]</td>
				<td><?php eh($row[7]); ?></td>
				<td><?php eh($row[6]); ?></td>
			<?php } else { ?>
				<td><?php eh($row[1]); ?></td>
				<td><?php eh(formatBytes($row[2], 2));?> /<?php eh($row[9]); ?></td>
				<td><?php eh($row[7]); ?></td>
				<td><?php eh($row[6]); ?></td>
				<td title="<?php eh($row[8]); ?>"><?php eh($row[3]); ?></td>
				<td><?php eh($row[4]); ?></td>
			<?php } ?>
			</tr>
		<?php } ?>
			</tbody>
		</table>		

<?php include("tpl.footer.php"); ?>
