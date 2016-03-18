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

function delete_expired()
{
	$res = db_select("SELECT m.`id` FROM `zxs_files` AS m WHERE (m.`deleted` = 0 AND m.`type` = 0 AND m.`expire` IS NOT NULL AND m.`expire` < CURDATE()) OR (m.`deleted` = 1 AND m.`type` = 3 AND m.`date` IS NOT NULL AND DATE_ADD(m.`date`, INTERVAL 3 DAY) < CURDATE())");
	if($res !== FALSE) foreach($res as $row)
	{
		$query = rpv_v2("UPDATE `zxs_files` SET `type` = 0, `deleted` = 1 WHERE `id` = # LIMIT 1", array($row[0]));
		db_put($query);
		unlink(UPLOAD_DIR."/f".$row[0]);
	}
}

	session_name("ZXSID");
	session_start();
	error_reporting(E_ALL);
	define("ZXS_PROTECTED", "YES");

	header("Content-Type: text/html; charset=utf-8");

	$self = $_SERVER['PHP_SELF'];
	$files_on_server = 0;
	$disk_usage = 0;
	$free_space = 0;

	$uid = 0;
	if(isset($_SESSION['uid']))
	{
		$uid = $_SESSION['uid'];
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
		$id = $_GET['id'];
	}

	if(empty($uid))
	{
		if(!empty(@$_COOKIE['zxsh']) && !empty(@$_COOKIE['zxsl']))
		{
			db_connect();
			$query = rpv_v2("SELECT m.`id` FROM zxs_users AS m WHERE m.`login` = ! AND m.`sid` IS NOT NULL AND m.`sid` = ! AND m.`deleted` = 0 LIMIT 1", array($_COOKIE['zxsl'], $_COOKIE['zxsh']));
			$res = db_select($query);
			db_disconnect();
			if($res !== FALSE)
			{
				$_SESSION['uid'] = $res[0][0];
				$uid = $_SESSION['uid'];
				setcookie("zxsh", @$_COOKIE['zxsh'], time()+2592000);
				setcookie("zxsl", @$_COOKIE['zxsl'], time()+2592000);
			}
		}
	}
	
	if(empty($uid))
	{
		switch($action)
		{
			case 'logon':
			{
				if(empty($_POST['login']) || empty($_POST['passwd']))
				{
					$error_msg = "Неверное имя пользователя или пароль!";
					include('templ/tpl.login.php');
					exit;
				}
				db_connect();
				delete_expired();
				$query = rpv_v2("SELECT m.`id` FROM zxs_users AS m WHERE m.`login` = ! AND m.`passwd` = ! AND m.`deleted` = 0 LIMIT 1", array(@$_POST['login'], @$_POST['passwd']));
				$res = db_select($query);
				if($res === FALSE)
				{
					$query = rpv_v2("INSERT INTO `zxs_log` (`date`, `uid`, `type`, `p1`, `ip`) VALUES (NOW(), #, #, #, !)", array(0, LOG_LOGIN_FAILED, 0, $ip));
					db_put($query);
					db_disconnect();
					$error_msg = "Неверное имя пользователя или пароль!";
					include('templ/tpl.login.php');
					exit;
				}
				
				$_SESSION['uid'] = $res[0][0];
				$uid = $_SESSION['uid'];
				
				$sid = uniqid ();
				setcookie("zxsh", $sid, time()+2592000);
				setcookie("zxsl", @$_POST['login'], time()+2592000);
				$query = rpv_v2("UPDATE zxs_users SET `sid` = ! WHERE `id` = # LIMIT 1", array($sid, $uid));
				db_put($query);

				$query = rpv_v2("INSERT INTO `zxs_log` (`date`, `uid`, `type`, `p1`, `ip`) VALUES (NOW(), #, #, #, !)", array($uid, LOG_LOGIN, 0, $ip));
				db_put($query);
				db_disconnect();
				header("Location: /");
				exit;
			}
			case 'register':
			{
				include('templ/tpl.register.php');
				exit;
			}
			case 'reg':
			{
				if(empty($_POST['login']) || empty($_POST['passwd']) || empty($_POST['mail']) || !preg_match('/'.ALLOW_MAILS.'/i', $_POST['mail']))
				{
					$error_msg = "Указаны неверные данные!";
					include('templ/tpl.register.php');
					exit;
				}
				db_connect();
				$query = rpv_v2("SELECT m.`id` FROM zxs_users AS m WHERE m.`login`= ! OR m.`mail` = ! LIMIT 1", array(@$_POST['login'], @$_POST['mail']));
				$res = db_select($query);
				if($res !== FALSE)
				{
					db_disconnect();
					$error_msg = "Пользователь существует!";
					include('templ/tpl.register.php');
					exit;
				}
				$query = rpv_v2("INSERT INTO zxs_users (login, passwd, mail, deleted) VALUES (!, !, !, 0)", array(@$_POST['login'], @$_POST['passwd'], @$_POST['mail']));
				$res = db_put($query);
				//mail();
				db_disconnect();
				
				header("Location: $self");
				exit;
			}
			case 'activate':
			{
				if(empty($_GET['login']) || empty($id))
				{
					$error_msg = "Неверные данные активации!";
					include('templ/tpl.login.php');
					exit;
				}
				db_connect();
				$query = rpv_v2("UPDATE zxs_users SET deleted = 0 WHERE login = ! AND id = #", array(@$_GET['login'], $id));
				$res = db_put($query);
				$query = rpv_v2("INSERT INTO `zxs_log` (`date`, `uid`, `type`, `p1`, `ip`) VALUES (NOW(), #, #, #, !)", array(0, LOG_LOGIN_ACTIVATE, $id, $ip));
				db_put($query);
				db_disconnect();
			}
		}
		include('templ/tpl.login.php');
		exit;
	}

	switch($action)
	{
		case 'download':
		{
			db_connect();
			$query = rpv_v2("SELECT m.`name` FROM `zxs_files` AS m WHERE m.`uid` = # AND m.`id` = # AND m.`type` = 0 AND m.`deleted` = 0 LIMIT 1", array($uid, $id));
			$res = db_select($query);
			db_disconnect();
			if($res !== FALSE)
			{
				header("Content-Type: application/octet-stream");
				header("Content-Length: ".filesize(UPLOAD_DIR."/f".$id));
				header("Content-Disposition: attachment; filename=\"".rawurlencode($res[0][0])."\"; filename*=utf-8''".rawurlencode($res[0][0]));
				readfile(UPLOAD_DIR."/f".$id);
			}
			exit;
		}
		case 'logoff':
		{
			$_SESSION['uid'] = 0;
			$uid = $_SESSION['uid'];
			include('templ/tpl.login.php');
			exit;
		}
		case 'links':
		{
			db_connect();
			//$query = rpv_v2("SELECT j1.`id`, j1.`pin`, j2.`id`, j2.`name`, j2.`size`, DATE_FORMAT(j2.`date`, '%d.%m.%Y'), DATE_FORMAT(j2.`expire`, '%d.%m.%Y'), j1.`desc` FROM `zxs_link_files` AS m LEFT JOIN zxs_links AS j1 ON j1.`id` = m.`lid` LEFT JOIN zxs_files AS j2 ON j2.`id` = m.`fid` WHERE m.`pid` = 0 AND j2.`uid` = # ORDER BY j2.`name`", array($uid));
			$query = rpv_v2("SELECT m.`id`, m.`pin`, m.`desc`, DATE_FORMAT(m.`date`, '%d.%m.%Y'), DATE_FORMAT(m.`date`, '%d.%m.%Y %k:%i:%s') FROM `zxs_links` AS m WHERE m.`uid` = # AND m.`deleted` = 0 ORDER BY m.`id`", array($uid));
			$res = db_select($query);
			db_disconnect();
			if($res === FALSE)
			{
			}
			include('templ/tpl.links.php');
			exit;
		}
		case 'all-links':
		{
			if($uid != 1)
			{
				break;
			}
			
			db_connect();
			$query = rpv_v2("SELECT m.`id`, m.`pin`, m.`desc`, j1.`login`, DATE_FORMAT(m.`date`, '%d.%m.%Y'), DATE_FORMAT(m.`date`, '%d.%m.%Y %k:%i:%s') FROM `zxs_links` AS m LEFT JOIN zxs_users AS j1 ON j1.`id` = m.`uid` WHERE m.`deleted` = 0 ORDER BY m.`id` DESC", array($uid));
			$res = db_select($query);
			db_disconnect();
			if($res === FALSE)
			{
			}
			include('templ/tpl.all-links.php');
			exit;
		}
		case 'all':
		{
			if($uid != 1)
			{
				break;
			}
			
			db_connect();
			$uplevel = 0;
			if($id)
			{	
				$query = rpv_v2("SELECT m.`pid` FROM `zxs_files` AS m WHERE m.`id` = # AND m.`deleted` = 0 LIMIT 1", array($id));
				$res = db_select($query);
				if($res !== FALSE)
				{
					$uplevel = $res[0][0];
				}
			}
			
			$query = rpv_v2("SELECT m.`id`, m.`name`, m.`size`, DATE_FORMAT(m.`date`, '%d.%m.%Y'), DATE_FORMAT(m.`expire`, '%d.%m.%Y'), m.`type`, m.`desc`, j1.`login`, DATE_FORMAT(m.`date`, '%d.%m.%Y %k:%i:%s'), (SELECT COUNT(*) FROM `zxs_log` AS c WHERE c.`type` = # AND c.`p1` = m.`id`) FROM `zxs_files` AS m LEFT JOIN `zxs_users` AS j1 ON j1.`id` = m.`uid` WHERE m.`pid` = # AND m.`deleted` = 0 ORDER BY m.`type` DESC, m.`name`", array(LOG_DOWNLOAD, $id));
			$res = db_select($query);
			db_disconnect();
			if($res === FALSE)
			{
			}
			include('templ/tpl.all.php');
			exit;
		}
		case 'info':
		{
			db_connect();
			$query = rpv_v2("INSERT INTO `zxs_log` (`date`, `uid`, `type`, `p1`, `ip`) VALUES (NOW(), #, #, #, !)", array($uid, LOG_VIEW_ABOUT, 0, $ip));
			db_put($query);
			db_disconnect();
			include('templ/tpl.info.php');
			exit;
		}
	}

	db_connect();
	$uplevel = 0;
	$upname = 0;
	if($id)
	{	
		$query = rpv_v2("SELECT m.`pid`, j1.`name` FROM `zxs_files` AS m LEFT JOIN `zxs_files` AS j1 ON j1.`id` = m.`id` WHERE m.`uid` = # AND m.`id` = # AND m.`deleted` = 0 LIMIT 1", array($uid, $id));
		$res = db_select($query);
		if($res !== FALSE)
		{
			$uplevel = $res[0][0];
			$upname = $res[0][1];
		}
	}
	
	$res = db_select("SELECT COUNT(*), SUM(m.`size`) FROM zxs_files m WHERE m.`type` = 0 AND m.`deleted` = 0");
	if($res !== FALSE)
	{
		$files_on_server = $res[0][0];
		$disk_usage = $res[0][1];
	}
	$free_space = disk_free_space(UPLOAD_DIR."/");
	
	$query = rpv_v2("SELECT m.`id`, m.`name`, m.`size`, DATE_FORMAT(m.`date`, '%d.%m.%Y'), DATE_FORMAT(m.`expire`, '%d.%m.%Y'), m.`type`, m.`desc`, DATE_FORMAT(m.`date`, '%d.%m.%Y %k:%i:%s'), (SELECT COUNT(*) FROM `zxs_log` AS c WHERE c.`type` = # AND c.`p1` = m.`id`) FROM `zxs_files` AS m WHERE m.`uid` = # AND m.`pid` = # AND m.`deleted` = 0 ORDER BY m.`type` DESC, m.`name`, m.`id`", array(LOG_DOWNLOAD, $uid, $id));
	$res = db_select($query);
	db_disconnect();
	if($res === FALSE)
	{
	}
	include('templ/tpl.main.php');
	//include('templ/tpl.debug.php');
