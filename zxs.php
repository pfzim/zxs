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

function delete_expired($db)
{
	if($db->select("SELECT m.`id` FROM `zxs_files` AS m WHERE (m.`deleted` = 0 AND m.`type` = 0 AND m.`expire` IS NOT NULL AND m.`expire` < CURDATE()) OR (m.`deleted` = 1 AND m.`type` = 3 AND m.`date` IS NOT NULL AND DATE_ADD(m.`date`, INTERVAL 3 DAY) < CURDATE())"))
	{
		foreach($db->data as $row)
		{
			$db->put(rpv("UPDATE `zxs_files` SET `type` = 0, `deleted` = 1 WHERE `id` = # LIMIT 1", $row[0]));
			unlink(UPLOAD_DIR."/f".$row[0]);
		}
	}
}

function php_mailer($to, $name, $subject, $html, $plain)
{
	require_once 'libs/PHPMailer/PHPMailerAutoload.php';

	$mail = new PHPMailer;

	$mail->isSMTP();
	$mail->Host = MAIL_HOST;
	$mail->SMTPAuth = MAIL_AUTH;
	if(MAIL_AUTH)
	{
		$mail->Username = MAIL_USER;
		$mail->Password = MAIL_PASSWD;
	}

	$mail->SMTPSecure = MAIL_SECURE;
	$mail->Port = MAIL_PORT;

	$mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
	$mail->addAddress($to, $name);
	//$mail->addReplyTo('helpdesk@example.com', 'Information');

	$mail->isHTML(true);

	$mail->Subject = $subject;
	$mail->Body    = $html;
	$mail->AltBody = $plain;

	return $mail->send();
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

	require_once('inc.db.php');
	require_once('inc.utils.php');

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
	
	if($action == "message")
	{
		switch($id)
		{
			case 1:
				$error_msg = "Registration complete. Wait for activation account by administrator.";
				break;
			default:
				$error_msg = "Unknown error";
				break;
		}
		
		include('templ/tpl.message.php');
		exit;
	}

	$db = new MySQLDB();
	if(!$db->connect())
	{
		$error_msg = $db->get_last_error();
		include('templ/tpl.error.php');
		exit;
	}

	if(empty($uid))
	{
		if(!empty($_COOKIE['zxsh']) && !empty($_COOKIE['zxsl']))
		{
			if($db->select(rpv("SELECT m.`id` FROM zxs_users AS m WHERE m.`login` = ! AND m.`sid` IS NOT NULL AND m.`sid` = ! AND m.`deleted` = 0 LIMIT 1", $_COOKIE['zxsl'], $_COOKIE['zxsh'])))
			{
				$_SESSION['uid'] = $db->data[0][0];
				$uid = $_SESSION['uid'];
				setcookie("zxsh", $_COOKIE['zxsh'], time()+2592000, '/');
				setcookie("zxsl", $_COOKIE['zxsl'], time()+2592000, '/');
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

				delete_expired($db);

				if(!$db->select(rpv("SELECT m.`id` FROM zxs_users AS m WHERE m.`login` = ! AND m.`passwd` = PASSWORD(!) AND m.`deleted` = 0 LIMIT 1", @$_POST['login'], @$_POST['passwd'])))
				{
					$db->put(rpv("INSERT INTO `zxs_log` (`date`, `uid`, `type`, `p1`, `ip`) VALUES (NOW(), #, #, #, !)", 0, LOG_LOGIN_FAILED, 0, $ip));
					$error_msg = "Неверное имя пользователя или пароль!";
					include('templ/tpl.login.php');
					exit;
				}

				$_SESSION['uid'] = $db->data[0][0];
				$uid = $_SESSION['uid'];

				$sid = uniqid();
				setcookie("zxsh", $sid, time()+2592000, '/');
				setcookie("zxsl", @$_POST['login'], time()+2592000, '/');

				$db->put(rpv("UPDATE zxs_users SET `sid` = ! WHERE `id` = # LIMIT 1", $sid, $uid));
				$db->put(rpv("INSERT INTO `zxs_log` (`date`, `uid`, `type`, `p1`, `ip`) VALUES (NOW(), #, #, #, !)", $uid, LOG_LOGIN, 0, $ip));

				header('Location: /');
				exit;
			}
			case 'register': // show registartion form
			{
				include('templ/tpl.register.php');
				exit;
			}
			case 'reg': // register new account
			{
				if(empty($_POST['login']) || empty($_POST['passwd']) || empty($_POST['mail']) || !preg_match('/'.ALLOW_MAILS.'/i', $_POST['mail']))
				{
					$error_msg = "Указаны неверные данные!";
					include('templ/tpl.register.php');
					exit;
				}

				if($db->select(rpv("SELECT m.`id` FROM zxs_users AS m WHERE m.`login`= ! OR m.`mail` = ! LIMIT 1", @$_POST['login'], @$_POST['mail'])))
				{
					$res = $db->data;
					$error_msg = "Пользователь существует!";
					include('templ/tpl.register.php');
					exit;
				}
				$db->put(rpv("INSERT INTO zxs_users (login, passwd, mail, deleted) VALUES (!, PASSWORD(!), !, 1)", @$_POST['login'], @$_POST['passwd'], @$_POST['mail']));
				$uid = $db->last_id();

				// send mail to admin for accept registration
				if(!php_mailer(
					MAIL_ADMIN, MAIL_ADMIN_NAME,
					'Accept new registration',
					'Hello, Admin!<br /><br />New user wish to register.<br />Login: <b>'.@$_POST['login'].'</b><br />E-Mail: <b>'.@$_POST['mail'].'</b><br/><br/>Accept registration: <a href="'.$self.'?action=activate&amp;login='.@$_POST['login'].'&amp;id='.$uid.'">Accept</a>',
					'Hello, Admin! New user wish to register. Accept registration: '.$self.'?action=activate&amp;login='.@$_POST['login'].'&amp;id='.$uid
				))
				{
					$error_msg = 'Mailer Error: ' . $mail->ErrorInfo;
					include('templ/tpl.register.php');
					exit;
				}

				header("Location: $self?action=message&id=1");
				exit;
			}
			case 'activate': // activate account after registartion
			{
				if(empty($_GET['login']) || empty($id))
				{
					$error_msg = "Неверные данные активации!";
					include('templ/tpl.error.php');
					exit;
				}

				$db->put(rpv("UPDATE zxs_users SET `deleted` = 0 WHERE `login` = ! AND `id` = #", @$_GET['login'], $id));
				$db->put(rpv("INSERT INTO `zxs_log` (`date`, `uid`, `type`, `p1`, `ip`) VALUES (NOW(), #, #, #, !)", 0, LOG_LOGIN_ACTIVATE, $id, $ip));

				if($db->select(rpv("SELECT m.`id`, m.`mail` FROM zxs_users AS m WHERE m.`login`= ! AND m.`id` = # LIMIT 1", @$_GET['login'], $id)))
				{
					if(!php_mailer(
						$db->data[0][1], @$_GET['login'],
						'Registration accepted',
						'Hello!<br /><br />You account activated.<br /><br/><a href="'.$self.'">Login</a>',
						'Hello! You account activated.'
					))
					{
						$error_msg = 'Mailer Error: ' . $mail->ErrorInfo;
						include('templ/tpl.error.php');
						exit;
					}
				}
			}
		}

		include('templ/tpl.login.php'); // show login form
		exit;
	}

	switch($action)
	{
		case 'download':
		{
			if($db->select(rpv("SELECT m.`name` FROM `zxs_files` AS m WHERE m.`uid` = # AND m.`id` = # AND m.`type` = 0 AND m.`deleted` = 0 LIMIT 1", $uid, $id)))
			{
				$db->disconnect(); // release database connection
				header("Content-Type: application/octet-stream");
				header("Content-Length: ".filesize(UPLOAD_DIR."/f".$id));
				header("Content-Disposition: attachment; filename=\"".rawurlencode($db->data[0][0])."\"; filename*=utf-8''".rawurlencode($db->data[0][0]));
				readfile(UPLOAD_DIR."/f".$id);
			}
			exit;
		}
		case 'logoff':
		{
			$db->put(rpv("UPDATE zxs_users SET `sid` = NULL WHERE `id` = # LIMIT 1", $uid));
			$_SESSION['uid'] = 0;
			$uid = $_SESSION['uid'];
			setcookie("zxsh", NULL, time()-60, '/');
			setcookie("zxsl", NULL, time()-60, '/');

			include('templ/tpl.login.php');
			exit;
		}
		case 'links':
		{
			//$query = rpv_v2("SELECT j1.`id`, j1.`pin`, j2.`id`, j2.`name`, j2.`size`, DATE_FORMAT(j2.`date`, '%d.%m.%Y'), DATE_FORMAT(j2.`expire`, '%d.%m.%Y'), j1.`desc` FROM `zxs_link_files` AS m LEFT JOIN zxs_links AS j1 ON j1.`id` = m.`lid` LEFT JOIN zxs_files AS j2 ON j2.`id` = m.`fid` WHERE m.`pid` = 0 AND j2.`uid` = # ORDER BY j2.`name`", array($uid));

			$db->select(rpv("SELECT m.`id`, m.`pin`, m.`desc`, DATE_FORMAT(m.`date`, '%d.%m.%Y'), DATE_FORMAT(m.`date`, '%d.%m.%Y %k:%i:%s') FROM `zxs_links` AS m WHERE m.`uid` = # AND m.`deleted` = 0 ORDER BY m.`id`", $uid));

			$res = $db->data;
			include('templ/tpl.links.php');
			exit;
		}
		case 'all-links':
		{
			if($uid != 1)
			{
				break;
			}

			$db->select("SELECT m.`id`, m.`pin`, m.`desc`, j1.`login`, DATE_FORMAT(m.`date`, '%d.%m.%Y'), DATE_FORMAT(m.`date`, '%d.%m.%Y %k:%i:%s') FROM `zxs_links` AS m LEFT JOIN zxs_users AS j1 ON j1.`id` = m.`uid` WHERE m.`deleted` = 0 ORDER BY m.`id` DESC");
			$res = $db->data;
			include('templ/tpl.all-links.php');
			exit;
		}
		case 'all':
		{
			if($uid != 1)
			{
				break;
			}

			$uplevel = 0;
			if($id)
			{
				if($db->select(rpv("SELECT m.`pid` FROM `zxs_files` AS m WHERE m.`id` = # AND m.`deleted` = 0 LIMIT 1", $id)))
				{
					$uplevel = $db->data[0][0];
				}
			}

			$db->select(rpv("SELECT m.`id`, m.`name`, m.`size`, DATE_FORMAT(m.`date`, '%d.%m.%Y'), DATE_FORMAT(m.`expire`, '%d.%m.%Y'), m.`type`, m.`desc`, j1.`login`, DATE_FORMAT(m.`date`, '%d.%m.%Y %k:%i:%s'), (SELECT COUNT(*) FROM `zxs_log` AS c WHERE c.`type` = # AND c.`p1` = m.`id`) FROM `zxs_files` AS m LEFT JOIN `zxs_users` AS j1 ON j1.`id` = m.`uid` WHERE m.`pid` = # AND m.`deleted` = 0 ORDER BY m.`type` DESC, m.`name`", LOG_DOWNLOAD, $id));

			$res = $db->data;
			include('templ/tpl.all.php');
			exit;
		}
		case 'stats':
		{
			$db->put(rpv("INSERT INTO `zxs_log` (`date`, `uid`, `type`, `p1`, `ip`) VALUES (NOW(), #, #, #, !)", $uid, LOG_VIEW_STATS, 0, $ip));
			$db->select("SELECT m.`login`, COUNT(j1.`id`) AS `uploads` FROM zxs_users AS m LEFT JOIN zxs_files AS j1 ON j1.`uid` = m.`id` AND j1.`type` = 0 GROUP BY m.`id` ORDER BY `uploads` DESC");

			$res = $db->data;
			include('templ/tpl.top-users.php');
			exit;
		}
		case 'info':
		{
			$db->put(rpv("INSERT INTO `zxs_log` (`date`, `uid`, `type`, `p1`, `ip`) VALUES (NOW(), #, #, #, !)", $uid, LOG_VIEW_ABOUT, 0, $ip));

			include('templ/tpl.info.php');
			exit;
		}
	}

	$uplevel = 0;
	$upname = 0;
	if($id)
	{
		if($db->select(rpv("SELECT m.`pid`, j1.`name` FROM `zxs_files` AS m LEFT JOIN `zxs_files` AS j1 ON j1.`id` = m.`id` WHERE m.`uid` = # AND m.`id` = # AND m.`deleted` = 0 LIMIT 1", $uid, $id)))
		{
			$uplevel = $db->data[0][0];
			$upname = $db->data[0][1];
		}
	}

	if($db->select("SELECT COUNT(*), SUM(m.`size`) FROM zxs_files m WHERE m.`type` = 0 AND m.`deleted` = 0"))
	{
		$files_on_server = $db->data[0][0];
		$disk_usage = $db->data[0][1];
	}
	$free_space = disk_free_space(UPLOAD_DIR."/");

	$db->select(rpv("SELECT m.`id`, m.`name`, m.`size`, DATE_FORMAT(m.`date`, '%d.%m.%Y'), DATE_FORMAT(m.`expire`, '%d.%m.%Y'), m.`type`, m.`desc`, DATE_FORMAT(m.`date`, '%d.%m.%Y %k:%i:%s'), (SELECT COUNT(*) FROM `zxs_log` AS c WHERE c.`type` = # AND c.`p1` = m.`id`) FROM `zxs_files` AS m WHERE m.`uid` = # AND m.`pid` = # AND m.`deleted` = 0 ORDER BY m.`type` DESC, m.`name`, m.`id`", LOG_DOWNLOAD, $uid, $id));
	$res = $db->data;

	include('templ/tpl.main.php');
	//include('templ/tpl.debug.php');
