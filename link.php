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
	if($res !== FALSE) foreach($res as $row)
	{
		share_subdir($row[1], $lid, $row[0]);
	}
}

function share_subdir($uid, $lid, $id)
{
	$query = rpv_v2("SELECT m.`id`, m.`type` FROM `zxs_files` AS m WHERE m.`uid` = # AND m.`pid` = # AND m.`deleted` = 0", array($uid, $id));
	$res = db_select($query);
	if($res !== FALSE) foreach($res as $row)
	{
		$query = rpv_v2("INSERT INTO `zxs_link_files` (`lid`, `fid`, `pid`) VALUES (#, #, #)", array($lid, $row[0], $id));
		db_put($query);
		
		if($row[1])
		{
			share_subdir($uid, $lid, $row[0]);
		}
	}
}

function tar_checksum($first, $last)
{
	$v_checksum = 0;
	for ($i=0; $i<148; $i++)
	{
	  $v_checksum += ord(substr($first,$i,1));
	}
	for ($i=148; $i<156; $i++)
	{
	  $v_checksum += ord(' ');
	}
	for ($i=156, $j=0; $i<512; $i++, $j++)
	{
	  $v_checksum += ord(substr($last,$j,1));
	}
	
	return pack("a8", sprintf("%6s ", DecOct($v_checksum)));
}

function tar_fill($fs)
{
	if($fs % 512 > 0)
	{
		$i = 512 - ($fs % 512);
		while($i > 0)
		{
			echo chr(0);
			$i--;
		}
	}
}

function tar_header($name, $flag, $perm, $size, $date)
{
	$fs = strlen($name);
	if($fs > 99)
	{
		$first = pack("a100a8a8a8a12A12", '././@LongLink', $perm, "0000000 ", "0000000 ", sprintf("%11s ", DecOct($fs)), sprintf("%11s", DecOct(strtotime($date))));
		$last = pack("a1a100a6a2a32a32a8a8a155a12", 'L', "", "ustar", "00", "", "", "", "", "", "");

		echo $first;
		echo tar_checksum($first, $last);
		echo $last;
		echo $name;
		tar_fill($fs);
		$name = substr($name, 0, 99);
	}

	$first = pack("a100a8a8a8a12A12", $name, $perm, "0000000 ", "0000000 ", sprintf("%11s ", DecOct($size)), sprintf("%11s", DecOct(strtotime($date))));
	$last = pack("a1a100a6a2a32a32a8a8a155a12", $flag, "", "ustar", "00", "", "", "", "", "", "");

	echo $first;
	echo tar_checksum($first, $last);
	echo $last;
}

function tar_subdir($lid, $id, $path)
{
	global $ip;
	
	$query = rpv_v2("SELECT j2.`id`, j2.`type`, j2.`name`, j2.`size`, j2.`date`, m.`pid` FROM `zxs_link_files` AS m LEFT JOIN `zxs_links` AS j1 ON j1.`id` = m.`lid` LEFT JOIN `zxs_files` AS j2 ON j2.`id` = m.`fid` WHERE m.`lid` = # AND m.`pid` = # AND j2.`deleted` = 0 AND j1.`deleted` = 0 ORDER BY j2.`type` DESC, j2.`name`", array($lid, $id));
	$res = db_select($query);
	if($res !== FALSE)
	{
		foreach($res as $row)
		{
			if(intval($row[1]) == 1)
			{
				tar_header($path.$row[2], '5', '0040777 ', 0, $row[4]);
				tar_subdir($lid, $row[0], $path.$row[2].'/');
			}
			else
			{
				$fs = filesize(UPLOAD_DIR."/f".$row[0]);
				tar_header($path.$row[2], '0', '0100777 ', $fs, $row[4]);
				readfile(UPLOAD_DIR."/f".$row[0]);
				tar_fill($fs);
				$query = rpv_v2("INSERT INTO `zxs_log` (`date`, `uid`, `type`, `p1`, `ip`) VALUES (NOW(), #, #, #, !)", array(0, LOG_DOWNLOAD, $row[0], $ip));
				db_put($query);
			}
		}
	}
}

	//session_name("ZXSID");
	//session_start();
	error_reporting(E_ALL);
	define("ZXS_PROTECTED", "YES");

	header("Content-Type: text/html; charset=utf-8");
	
	$self = $_SERVER['PHP_SELF'];
	
	$uid = 0;
	$pin = 0;
	/*
	if(isset($_SESSION['pin']))
	{
		$pin = $_SESSION['pin'];
	}
	*/

	if(!empty(@$_COOKIE['zxsp']))
	{
		$pin = $_COOKIE['zxsp'];
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

	$mime = '';
	
	switch($action)
	{
		case 'download':
			$mime = 'application/octet-stream';
		case 'open':
		{
			if($fid && $id)
			{
				db_connect();
				$query = rpv_v2("SELECT j1.`pin`, j2.`id`, j2.`name`, j2.`size` FROM `zxs_link_files` AS m LEFT JOIN `zxs_links` AS j1 ON j1.`id` = m.`lid` LEFT JOIN `zxs_files` AS j2 ON j2.`id` = m.`fid` WHERE m.`lid` = # AND m.`fid` = # AND j2.`type` = 0 AND j1.`deleted` = 0 AND j2.`deleted` = 0 LIMIT 1", array($id, $fid));
				$res = db_select($query);
				$query = rpv_v2("INSERT INTO `zxs_log` (`date`, `uid`, `type`, `p1`, `ip`, `desc`) VALUES (NOW(), #, #, #, !, !)", array(0, LOG_DOWNLOAD, $fid, $ip, @$_SERVER['HTTP_RANGE']));
				db_put($query);
				db_disconnect();
				if($res !== FALSE)
				{
					if(empty($res[0][0]) || (strcmp($res[0][0], $pin) == 0))
					{
						if(empty($mime))
						{
							$finfo = finfo_open(FILEINFO_MIME_TYPE);
							$mime = finfo_file($finfo, UPLOAD_DIR.'/f'.$res[0][1]);
							finfo_close($finfo);
							
							if($mime === FALSE)
							{
								$mime = 'application/octet-stream';
							}
						}
						
						$fs = filesize(UPLOAD_DIR.'/f'.$res[0][1]);
						if($fs != intval($res[0][3]))
						{
							$error_msg = 'File corrupted';
							include('templ/tpl.error.php');
							exit;
						}
						if(isset($_SERVER['HTTP_RANGE']))
						{
							//error_log($_SERVER['HTTP_RANGE']);
							if(!preg_match('/^bytes=\d*-\d*$/i', $_SERVER['HTTP_RANGE']))
							{
								header('HTTP/1.1 416 Requested Range Not Satisfiable');
								header('Content-Range: bytes */'.$fs);
								exit;
							}
							list($pos_s, $pos_e) = explode('-', substr($_SERVER['HTTP_RANGE'], 6));
							if(($pos_s == '') && ($pos_e == ''))
							{
								header('HTTP/1.1 416 Requested Range Not Satisfiable');
								header('Content-Range: bytes */'.$fs);
								exit;
							}

							if($pos_e == '')
							{
								$pos_e = $fs - 1;
							}
							else if($pos_s == '')
							{
								$pos_s = $fs - intval($pos_e);
								$pos_e = $fs - 1;
							}

							$pos_s = intval($pos_s);
							$pos_e = intval($pos_e);

							if($pos_s > $pos_e)
							{
								header('HTTP/1.1 416 Requested Range Not Satisfiable');
								header('Content-Range: bytes */'.$fs);
								exit;
							}

							//error_log('Content-Range: bytes '.$pos_s.'-'.$pos_e.'/'.$fs);
							header('HTTP/1.1 206 Partial Content');
							header('Accept-Ranges: bytes');
							header('Content-Length: '.$pos_e - $pos_s + 1);
							header('Content-Range: bytes '.$pos_s.'-'.$pos_e.'/'.$fs);
							header('Content-Type: '.$mime);
							$fh = fopen(UPLOAD_DIR."/f".$res[0][1], 'rb');
							if(fseek($fh, $pos_s, SEEK_SET) == 0)
							{
								while($pos_s <= $pos_e)
								{
									//error_log('pos_s = '.$pos_s);
									//echo fgetc($fh);
									//$pos_s++;
									if(($pos_s + 10485760) > $pos_e)
									{
										echo fread($fh, $pos_e - $pos_s + 1);
										break;
									}

									echo fread($fh, 10485760);
									$pos_s += 10485760;
									flush();
								}
							}
							fclose($fh);
						}
						else
						{
							header('Content-Type: '.$mime);
							header('Accept-Ranges: bytes');
							header('Content-Length: '.$fs);
							//header("Content-Disposition: attachment; filename=\"".rawurlencode($res[0][1])."\"; filename*=\"utf-8''".rawurlencode($res[0][2]));
							readfile(UPLOAD_DIR.'/f'.$res[0][1]);
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
		case 'tar':
		{
			if($id)
			{
				db_connect();
				$query = rpv_v2("SELECT m.`pin` FROM `zxs_links` AS m WHERE m.`id` = # AND m.`deleted` = 0 LIMIT 1", array($id));
				$res = db_select($query);
				$query = rpv_v2("INSERT INTO `zxs_log` (`date`, `uid`, `type`, `p1`, `p2`, `ip`) VALUES (NOW(), #, #, #, !)", array(0, LOG_TAR_CREATE, $id, $fid, $ip));
				db_put($query);
				if($res !== FALSE)
				{
					if(empty($res[0][0]) || (strcmp($res[0][0], $pin) == 0))
					{
						header("Content-Type: application/octet-stream");
						//header("Content-Disposition: attachment; filename=\"zxs-link-archive-".$id.".tar\";");

						tar_subdir($id, $fid, '');
						echo pack("a1024", "");
					}
				}
				db_disconnect();
			}
			exit;
		}
		case 'pin':
		{
			$pin = @$_POST['pin'];
			//$_SESSION['pin'] = $pin;
			setcookie("zxsp", $pin, time()+2592000, '/');
			
			header("Location: /link/$id/");
			exit;
		}
	}

	db_connect();
	update_link($id);
	#$query = rpv_v2("SELECT j1.`pin`, j2.`id`, j2.`type`, j2.`name`, j2.`size`, j2.`date`, j2.`expire`, j2.`deleted` FROM `zxs_link_files` AS m LEFT JOIN `zxs_links` AS j1 ON j1.`id` = m.`lid` LEFT JOIN `zxs_files` AS j2 ON j2.`id` = m.`fid` WHERE m.`lid` = # AND m.`pid` = # AND j2.`type` = 0 AND j2.`deleted` = 0", array($id, $fid));
	$uplevel = 0;
	$upname = 'root';
	if($fid)
	{	
		$query = rpv_v2("SELECT m.`pid`, j1.`name` FROM `zxs_link_files` AS m LEFT JOIN `zxs_files` AS j1 ON j1.`id` = m.`fid` WHERE m.`lid` = # AND m.`fid` = # LIMIT 1", array($id, $fid));
		$res = db_select($query);
		if($res !== FALSE)
		{
			$uplevel = $res[0][0];
			$upname = $res[0][1];
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
