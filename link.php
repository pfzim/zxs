<?php
/*
	ZXS - simple web service for sharing files
    Copyright (C) 2016 Dmitry V. Zimin

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

function update_link($lid)
{
	$query = rpv_v2("DELETE FROM `zxs_link_files` WHERE `pid` <> 0 AND `lid` = #", array($lid));
	db_put($query);
	
	$query = rpv_v2("SELECT m.`fid`, j1.`uid` FROM `zxs_link_files` AS m LEFT JOIN `zxs_links` AS j1 ON j1.`id` = m.`lid` LEFT JOIN `zxs_files` AS j2 ON j2.`id` = m.`fid` WHERE m.`lid` = # AND m.`pid` = 0 AND j2.`type` = 1 AND j1.`deleted` = 0 AND j2.`deleted` = 0", array($lid));
	$res = db_select($query);
	foreach(@$res as $row)
	{
		share_subdir($row[1], $lid, $row[0]);
	}
}

function share_subdir($uid, $lid, $id)
{
	$query = rpv_v2("SELECT m.`id`, m.`type` FROM `zxs_files` AS m WHERE m.`uid` = # AND m.`pid` = # AND m.`deleted` = 0", array($uid, $id));
	$res = db_select($query);
	foreach(@$res as $row)
	{
		$query = rpv_v2("INSERT INTO `zxs_link_files` (`lid`, `fid`, `pid`) VALUES (#, #, #)", array($lid, $row[0], $id));
		db_put($query);
		
		if($row[1])
		{
			share_subdir($uid, $lid, $row[0]);
		}
	}
}

	session_name("ZXSID");
	session_start();
	error_reporting(E_ALL);
	define("ZXS_PROTECTED", "YES");

	header("Content-Type: text/html; charset=utf-8");
	
	$self = $_SERVER['PHP_SELF'];
	
	$uid = 0;
	$pin = 0;
	if(isset($_SESSION['pin']))
	{
		$pin = $_SESSION['pin'];
	}

	if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = @$_SERVER['REMOTE_ADDR'];
	}

	include('inc.dbfunc.php');
	include('inc.utils.php');

	$action = "";
	if(isset($_GET['action']))
	{
		$action = $_GET['action'];
	}

	$id = 0;
	if(isset($_GET['id']))
	{
		$id = intval($_GET['id']);
	}

	$fid = 0;
	if(isset($_GET['fid']))
	{
		$fid = intval($_GET['fid']);
	}

	switch($action)
	{
		case 'download':
		{
			if($fid && $id)
			{
				db_connect();
				$query = rpv_v2("SELECT j1.`pin`, j2.`id`, j2.`name`, j2.`size` FROM `zxs_link_files` AS m LEFT JOIN `zxs_links` AS j1 ON j1.`id` = m.`lid` LEFT JOIN `zxs_files` AS j2 ON j2.`id` = m.`fid` WHERE m.`lid` = # AND m.`fid` = # AND j2.`type` = 0 AND j1.`deleted` = 0 AND j2.`deleted` = 0 LIMIT 1", array($id, $fid));
				$res = db_select($query);
				$query = rpv_v2("INSERT INTO `zxs_log` (`date`, `uid`, `oid`, `fid`, `ip`) VALUES (NOW(), #, #, #, !)", array(0, 2, $fid, $ip));
				db_put($query);
				db_disconnect();
				if($res !== FALSE)
				{
					if(empty($res[0][0]) || (strcmp($res[0][0], $pin) == 0))
					{
						$fs = filesize(UPLOAD_DIR."/f".$res[0][1]);
						if($fs != intval($res[0][3]))
						{
							$error_msg = "File corrupted";
							include('templ/tpl.error.php');
							exit;
						}
						if(isset($_SERVER['HTTP_RANGE']))
						{
							if(!preg_match('/^bytes=\d+-\d*$/i', $_SERVER['HTTP_RANGE']))
							{
								header('HTTP/1.1 416 Requested Range Not Satisfiable');
								header('Content-Range: bytes */'.$fs);
								exit;
							}
							list($pos_s, $pos_e) = explode('-', substr($_SERVER['HTTP_RANGE'], 6));
							$pos_s = intval($pos_s);
							$pos_e = intval($pos_e);
							if(!$pos_e)
							{
								$pos_e = $fs - 1;
							}
							if($pos_s > $pos_e)
							{
								header('HTTP/1.1 416 Requested Range Not Satisfiable');
								header('Content-Range: bytes */'.$fs);
								exit;
							}
							
							header('HTTP/1.1 206 Partial Content');
							header('Content-Range: bytes '.$pos_s.'-'.$pos_e.'/'.$fs);
							$fh = fopen(UPLOAD_DIR."/f".$res[0][1], 'rb');
							if(fseek($fh, $pos_s, SEEK_SET) == 0)
							{
								while($pos_s < $pos_e)
								{
									//echo fgetc($fh);
									//$pos_s++;
									if(($pos_s + 1048576) > $pos_e)
									{
										echo fread($fh, $pos_e - $pos_s);
										break;
									}

									echo fread($fh, 1048576);
									$pos_s += 1048576;
								}
							}
							fclose($fh);
						}
						else
						{
							header("Content-Type: application/octet-stream");
							header("Content-Length: ".$fs);
							//header("Content-Disposition: attachment; filename=\"".rawurlencode($res[0][1])."\"; filename*=\"utf-8''".rawurlencode($res[0][2]));
							readfile(UPLOAD_DIR."/f".$res[0][1]);
						}
						exit;
					}
					else
					{
						include('templ/tpl.pin.php');
						exit;
					}
				}
			}
			$error_msg = "File not found";
			include('templ/tpl.error.php');
			exit;
		}
		case 'pin':
		{
			$pin = @$_POST['pin'];
			$_SESSION['pin'] = $pin;
			
			header("Location: /link/$id/");
			exit;
		}
	}

	db_connect();
	update_link($id);
	#$query = rpv_v2("SELECT j1.`pin`, j2.`id`, j2.`type`, j2.`name`, j2.`size`, j2.`date`, j2.`expire`, j2.`deleted` FROM `zxs_link_files` AS m LEFT JOIN `zxs_links` AS j1 ON j1.`id` = m.`lid` LEFT JOIN `zxs_files` AS j2 ON j2.`id` = m.`fid` WHERE m.`lid` = # AND m.`pid` = # AND j2.`type` = 0 AND j2.`deleted` = 0", array($id, $fid));
	$uplevel = 0;
	if($fid)
	{	
		$query = rpv_v2("SELECT m.`pid` FROM `zxs_link_files` AS m WHERE m.`lid` = # AND m.`fid` = # LIMIT 1", array($id, $fid));
		$res = db_select($query);
		if($res !== FALSE)
		{
			$uplevel = $res[0][0];
		}
	}
	
	$query = rpv_v2("SELECT j1.`pin`, j2.`id`, j2.`type`, j2.`name`, j2.`size`, DATE_FORMAT(j2.`date`, '%d.%m.%Y'), DATE_FORMAT(j2.`expire`, '%d.%m.%Y'), j2.`deleted`, m.`pid`, j2.`desc` FROM `zxs_link_files` AS m LEFT JOIN `zxs_links` AS j1 ON j1.`id` = m.`lid` LEFT JOIN `zxs_files` AS j2 ON j2.`id` = m.`fid` WHERE m.`lid` = # AND m.`pid` = # AND j2.`deleted` = 0 AND j1.`deleted` = 0 ORDER BY j2.`type` DESC, j2.`name`", array($id, $fid));
	$res = db_select($query);
	db_disconnect();
	if($res !== FALSE)
	{
		if(empty($res[0][0]) || (strcmp($res[0][0], $pin) == 0))
		{
			include('templ/tpl.link.php');
			exit;
		}
		else
		{
			include('templ/tpl.pin.php');
			exit;
		}
	}
	
	//$error_msg = "File not found";
	include('templ/tpl.link.php');
	exit;
