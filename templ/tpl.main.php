<?php include("tpl.header.php"); ?>
		<h3 align="center">My Files</h3>
		<table id="table" class="main-table">
			<thead>
			<tr>
				<th width="3%"><input type="checkbox" onclick="f_select_all(this)"/></th>
				<th width="34%">Name</th>
				<th width="10%">Size</th>
				<th width="10%">Operations</th>
				<th width="25%">Description</th>
				<th width="9%">Created</th>
				<th width="9%">Expired</th>
			</tr>
			</thead>
			<tbody>
		<?php if($uplevel || $id) { ?>
			<tr>
				<td colspan="7"><a href="<?php eh("$self?id=$uplevel"); ?>"><b>Up level</b></a></td>
			</tr>
		<?php } ?>
		<?php $i = 0; if($res !== FALSE) foreach($res as $row) { $i++; ?>
			<tr id="<?php eh("row".$row[0]);?>">
				<td><input type="checkbox" name="check" value="<?php eh($row[0]); ?>"/></td>
			<?php if(intval($row[5]) == 1) { ?>
				<td id="<?php eh("fname".$row[0]); ?>"><a id="<?php eh("dir".$row[0]); ?>" class="boldtext" href="<?php eh("$self?id=$row[0]"); ?>"><?php eh($row[1]); ?></a></td>
				<td class="command" onclick="f_rename_dir(<?php eh($row[0]); ?>);">[Rename]</td>
				<td>
					<a href="<?php eh("$self?action=share&id=$row[0]"); ?>" onclick="return f_share(<?php eh($row[0]); ?>);">Share</a>
					<a href="<?php eh("$self"); ?>" onclick="return f_delete(<?php eh($row[0]); ?>);">Delete</a>
				</td>
				<td colspan="3" class="command" id="<?php eh("desc".$row[0]); ?>" onclick="f_desc(this, <?php eh($row[0]); ?>);"><?php eh($row[6]); ?></td>
			<?php } else { ?>
				<td class="command" id="<?php eh("fname".$row[0]); ?>" onclick="f_rename(this, <?php eh($row[0]); ?>);"><?php eh($row[1]); ?></td>
				<td><a href="<?php eh("$self?action=download&id=$row[0]"); ?>"><?php eh(formatBytes($row[2], 2));?></a></td>
				<td>
					<a href="<?php eh("$self?action=share&id=$row[0]"); ?>" onclick="return f_share(<?php eh($row[0]); ?>);">Share</a>
					<a href="<?php eh("$self"); ?>" onclick="return f_delete(<?php eh($row[0]); ?>);">Delete</a>
				</td>
				<td class="command" id="<?php eh("desc".$row[0]); ?>" onclick="f_desc(this, <?php eh($row[0]); ?>);"><?php eh($row[6]); ?></td>
				<td title="<?php eh($row[7]); ?>"><?php eh($row[3]); ?></td>
				<!--
				<td><?php eh($row[4]); ?></td>
				<td id="<?php eh("expire".$row[0]); ?>"><?php eh($row[4]); ?></td>
				-->
				<td class="command" onclick="f_expire_cal(this, <?php eh($row[0]); ?>);"><span id="<?php eh("expire".$row[0]); ?>"><?php eh($row[4]); ?></span></td>
			<?php } ?>
			</tr>
		<?php } ?>
			</tbody>
		</table>		

		<p>
			Selected: 
			<a href="<?php eh("$self"); ?>" onclick="return f_share_selected();">Share</a>
			<a href="<?php eh("$self"); ?>" onclick="return f_delete_selected();">Delete</a>
			This folder:
			<a href="<?php eh("$self"); ?>" onclick="return f_mkdir(<?php eh($id); ?>);">Create folder</a>
		<?php if($id) { ?>
			<a href="<?php eh("$self?action=share&id=$id"); ?>" onclick="return f_share(<?php eh($id); ?>);">Share</a>
		<?php } ?>
		</p>

		<input id="upload" type="file" name="myfile" size="100" multiple="multiple" style="display: none">
		<div id="dropzone">Drop files here or <a href="#" onclick="gi('upload').click(); return false;">select</a> for upload</div>

		<div class="server-info">Free space: <?php eh(formatBytes($free_space, 1));?>, Used space: <?php eh(formatBytes($disk_usage, 1));?> (<?php eh($files_on_server);?> files)</div>
   
		<script type="text/javascript">
			zxs_init(<?php eh($uid); ?>, <?php eh($id); ?>);
		</script>
<?php include("tpl.footer.php"); ?>
