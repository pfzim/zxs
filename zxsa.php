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

function update_link($db, $lid)
{
	$db->put(rpv("DELETE FROM `zxs_link_files` WHERE `pid` <> 0 AND `lid` = #", $lid));

	if($db->select(rpv("SELECT m.`fid`, j1.`uid` FROM `zxs_link_files` AS m LEFT JOIN `zxs_links` AS j1 ON j1.`id` = m.`lid` LEFT JOIN `zxs_files` AS j2 ON j2.`id` = m.`fid` WHERE m.`lid` = # AND m.`pid` = 0 AND j2.`type` = 1 AND j1.`deleted` = 0 AND j2.`deleted` = 0", $lid)))
	{
		foreach($db->data as $row)
		{
			share_subdir($row[1], $lid, $row[0]);
		}
	}
}

function share_subdir($db, $uid, $lid, $id)
{
	if($db->select(rpv("SELECT m.`id`, m.`type` FROM `zxs_files` AS m WHERE m.`uid` = # AND m.`pid` = # AND m.`deleted` = 0", $uid, $id)))
	{
		foreach($db->data as $row)
		{
			$db->put(rpv("INSERT INTO `zxs_link_files` (`lid`, `fid`, `pid`) VALUES (#, #, #)", $lid, $row[0], $id));
			if($row[1])
			{
				share_subdir($db, $uid, $lid, $row[0]);
			}
		}
	}
}

function delete_subdir($db, $uid, $id)
{

	if($db->select(rpv("SELECT m.`id`, m.`type` FROM `zxs_files` AS m WHERE m.`uid` = # AND m.`pid` = # AND m.`deleted` = 0", $uid, $id)))
	{
		foreach($db->data as $row)
		{
			$db->put(rpv("UPDATE `zxs_files` SET `deleted` = 1 WHERE `uid` = # AND `id` = # LIMIT 1", $uid, $row[0]));
			if($row[1])
			{
				delete_subdir($db, $uid, $row[0]);
			}
			else
			{
				unlink(UPLOAD_DIR."/f".$row[0]);
			}
		}
	}
}

	session_name("ZXSID");
	session_start();
	error_reporting(E_ALL);
	define("ZXS_PROTECTED", "YES");

	header("Content-Type: text/plain; charset=utf-8");

	$self = $_SERVER['PHP_SELF'];

	$uid = 0;
	if(isset($_SESSION['uid']))
	{
		$uid = $_SESSION['uid'];
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

	$db = new MySQLDB();
	$db->connect();

	if(empty($uid))
	{
		if(!empty(@$_COOKIE['zxsh']) && !empty(@$_COOKIE['zxsl']))
		{
			if($db->select(rpv("SELECT m.`id` FROM zxs_users AS m WHERE m.`login` = ! AND m.`sid` IS NOT NULL AND m.`sid` = ! AND m.`deleted` = 0 LIMIT 1", $_COOKIE['zxsl'], $_COOKIE['zxsh'])))
			{
				$_SESSION['uid'] = $db->data[0][0];
				$uid = $_SESSION['uid'];
				setcookie("zxsh", @$_COOKIE['zxsh'], time()+2592000, '/');
				setcookie("zxsl", @$_COOKIE['zxsl'], time()+2592000, '/');
			}
		}
	}

	if(empty($uid))
	{
		echo '{"result": 1, "status": "Log in, please"}';
		exit;
	}

	switch($action)
	{
		case 'mkdir':
		{
			if(empty($_POST['name']))
			{
				echo '{"result": 1, "status": "name undefined"}';
				exit;
			}

			$db->put(rpv("INSERT INTO `zxs_files` (`uid`, `pid`, `type`, `name`, `date`) VALUES (#, #, 1, !, NOW())", $uid, $id, @$_POST['name']));
			$id = $db->last_id();

			echo '{"result": 0, "id": '.$id.', "name": "'.json_escape($_POST['name']).'", "desc": ""}';
			exit;
		}
		case 'delete':
		{
			if(!$id)
			{
				echo '{"result": 1, "status": "id undefined"}';
				exit;
			}

			if($db->select(rpv("SELECT m.`type` FROM `zxs_files` AS m WHERE m.`uid` = # AND m.`id` = # AND m.`deleted` = 0 LIMIT 1", $uid, $id)))
			{
				$type = intval($db->data[0][0]);
				$db->put(rpv("UPDATE `zxs_files` SET `deleted` = 1 WHERE `uid` = # AND `id` = # LIMIT 1", $uid, $id));
				if($type == 0)
				{
					unlink(UPLOAD_DIR."/f".$id);
				}
				else
				{
					delete_subdir($uid, $id);
				}
			}

			$id = 0;
			echo '{"result": 0}';
			exit;
		}
		case 'delete_selected':
		{
			if(empty($_POST['fid']))
			{
				echo '{"result": 1, "status": "fid undefined"}';
				exit;
			}

			foreach($_POST['fid'] as $id)
			{
				if($db->select(rpv("SELECT m.`type` FROM `zxs_files` AS m WHERE m.`uid` = # AND m.`id` = # AND m.`deleted` = 0 LIMIT 1", $uid, $id)))
				{
					$type = intval($db->data[0][0]);

					$db->put(rpv("UPDATE `zxs_files` SET `deleted` = 1 WHERE `uid` = # AND `id` = # LIMIT 1", $uid, $id));
					if($type == 0)
					{
						unlink(UPLOAD_DIR."/f".$id);
					}
					else
					{
						delete_subdir($uid, $id);
					}
				}
			}

			echo '{"result": 0}';
			exit;
		}
		case 'rename':
		{
			if(!$id)
			{
				echo '{"result": 1, "status": "id undefined"}';
				exit;
			}
			if(empty($_POST['name']))
			{
				echo '{"result": 1, "status": "name undefined"}';
				exit;
			}

			$db->put(rpv("UPDATE `zxs_files` SET `name` = ! WHERE `uid` = # AND `id` = # LIMIT 1", $_POST['name'], $uid, $id));
			echo '{"result": 0, "id": '.$id.', "name": "'.json_escape($_POST['name']).'"}';
			exit;
		}
		case 'expire':
		{
			if(!$id)
			{
				echo '{"result": 1, "status": "id undefined"}';
				exit;
			}
			if(empty($_POST['date']))
			{
				$db->put(rpv("UPDATE `zxs_files` SET `expire` = NULL WHERE `uid` = # AND `id` = # LIMIT 1", $uid, $id));

				echo '{"result": 0, "id": '.$id.', "date": ""}';
			}
			else
			{
				$td = getdate();
				$dd = $td['mday'];
				$dm = $td['mon'];
				$dy = $td['year'];
				$d = explode('.', $_POST['date'], 3);
				$nd = intval(@$d[0]);
				$nm = intval(@$d[1]);
				$ny = intval(@$d[2]);
				if(!datecheck($nd, $nm, $ny) || (datecmp($nd, $nm, $ny, $dd, $dm, $dy) < 0))
				{
					echo '{"result": 1, "status": "date invalid value"}';
					exit;
				}

				$db->put(rpv("UPDATE `zxs_files` SET `expire` = ! WHERE `uid` = # AND `id` = # LIMIT 1", sprintf("%04d-%02d-%02d", $ny, $nm, $nd), $uid, $id));

				echo '{"result": 0, "id": '.$id.', "date": "'.json_escape(sprintf("%02d.%02d.%04d", $nd, $nm, $ny)).'"}';
			}
			exit;
		}
		case 'desc':
		{
			if(!$id)
			{
				echo '{"result": 1, "status": "id undefined"}';
				exit;
			}

			$db->put(rpv("UPDATE `zxs_files` SET `desc` = ! WHERE `uid` = # AND `id` = # LIMIT 1", @$_POST['name'], $uid, $id));

			echo '{"result": 0, "id": '.$id.', "desc": "'.json_escape(@$_POST['name']).'"}';
			exit;
		}
		case 'desc_link':
		{
			if(!$id)
			{
				echo '{"result": 1, "status": "id undefined"}';
				exit;
			}

			$db->put(rpv("UPDATE `zxs_links` SET `desc` = ! WHERE `uid` = # AND `id` = # LIMIT 1", @$_POST['name'], $uid, $id));

			echo '{"result": 0, "id": '.$id.', "desc": "'.json_escape(@$_POST['name']).'"}';
			exit;
		}
		case 'pinon':
		{
			if(!$id)
			{
				echo '{"result": 1, "status": "id undefined"}';
				exit;
			}

			$pin = rand(0, 9).rand(0, 9).rand(0, 9).rand(0, 9);

			$db->put(rpv("UPDATE `zxs_links` SET `pin` = ! WHERE `id` = # LIMIT 1", $pin, $id));

			echo '{"result": 0, "id": '.$id.', "pin": "'.$pin.'"}';
			exit;
		}
		case 'pinoff':
		{
			if(!$id)
			{
				echo '{"result": 1, "status": "id undefined"}';
				exit;
			}

			$db->put(rpv("UPDATE `zxs_links` SET `pin` = '' WHERE `id` = # LIMIT 1", $id));

			echo '{"result": 0, "id": '.$id.', "pin": ""}';
			exit;
		}
		case 'share':
		{
			// check file uid!
			if(!$id)
			{
				echo '{"result": 1, "status": "id undefined"}';
				exit;
			}
			$lid = 0;

			if($db->select(rpv("SELECT m.`type`, m.`name` FROM `zxs_files` AS m WHERE m.`uid` = # AND m.`id` = # AND m.`deleted` = 0 LIMIT 1", $uid, $id)))
			{
				$pin = rand(0, 9).rand(0, 9).rand(0, 9).rand(0, 9);

				$db->put(rpv("INSERT INTO `zxs_links` (`uid`, `pin`, `desc`, `date`) VALUES (#, !, !, NOW())", $uid, $pin, $db->data[0][1]));
				$lid = $db->last_id();

				$db->put(rpv("INSERT INTO `zxs_link_files` (`lid`, `fid`, `pid`) VALUES (#, #, 0)", $lid, $id));
			}

			echo '{"result": 0, "id": '.$lid.', "pin": "'.$pin.'"}';
			exit;
		}
		case 'share_selected':
		{
			// check file uid!
			if(empty($_POST['fid']))
			{
				echo '{"result": 1, "status": "fid undefined"}';
				exit;
			}

			$pin = rand(0, 9).rand(0, 9).rand(0, 9).rand(0, 9);

			$db->put(rpv("INSERT INTO `zxs_links` (`uid`, `pin`, `date`) VALUES (#, !, NOW())", $uid, $pin));
			$lid = $db->last_id();

			$i = 0;
			$desc = '';
			foreach($_POST['fid'] as $id)
			{
				if($db->select(rpv("SELECT m.`type`, m.`name` FROM `zxs_files` AS m WHERE m.`uid` = # AND m.`id` = # AND m.`deleted` = 0 LIMIT 1", $uid, $id)))
				{
					$i++;
					if($i == 1)
					{
						$desc = $db->data[0][1];
					}
					else if($i < 4)
					{
						$desc .= ', '.$db->data[0][1];
					}
					else if($i == 4)
					{
						$desc .= ', ...';
					}

					$db->put(rpv("INSERT INTO `zxs_link_files` (`lid`, `fid`, `pid`) VALUES (#, #, 0)", $lid, $id));
				}
			}

			$db->put(rpv("UPDATE `zxs_links` SET `desc` = ! WHERE `id` = # LIMIT 1", $desc, $lid));

			echo '{"result": 0, "id": '.$lid.', "pin": "'.$pin.'"}';
			exit;
		}
		case 'unlink':
		{
			if(!$id)
			{
				echo '{"result": 1, "status": "id undefined"}';
				exit;
			}
			//--db_connect();
			//rpv("DELETE FROM `zxs_links` WHERE `uid` = # AND `id` = # LIMIT 1", array($uid, $id));
			//$res = $db->put($query);
			//rpv("DELETE FROM `zxs_link_files` WHERE `lid` = #", array($id));
			//$res = $db->put($query);

			$db->put(rpv("UPDATE `zxs_links` SET `deleted` = 1 WHERE `uid` = # AND `id` = # LIMIT 1", $uid, $id));
			$db->put(rpv("DELETE FROM `zxs_link_files` WHERE `pid` <> 0 AND `lid` = #", $id));
			$id = 0;

			echo '{"result": 0}';
			exit;
		}
		case 'expand':
		{
			if(!$id)
			{
				echo '{"result": 1, "status": "id undefined"}';
				exit;
			}
			$pid = 0;
			if(isset($_GET['pid']))
			{
				$pid = $_GET['pid'];
			}

			update_link($id);
			$list = '';

			if($db->select(rpv("SELECT m.`fid`, j1.`name`, j1.`type`, j1.`size`, j1.`desc`, DATE_FORMAT(j1.`date`, '%d.%m.%Y'), DATE_FORMAT(j1.`expire`, '%d.%m.%Y') FROM zxs_link_files AS m LEFT JOIN zxs_files AS j1 ON j1.`id` = m.`fid` WHERE j1.`uid` = # AND m.`lid` = # AND m.`pid` = # AND j1.`deleted` = 0 ORDER BY j1.`type` DESC, j1.`name`", $uid, $id, $pid)))
			{
				foreach($db->data as $row)
				{
					if(!empty($list))
					{
						$list .= ', ';
					}
					$list .= '{"id": '.$row[0].', "name": "'.json_escape($row[1]).'", "type": '.$row[2].', "size": '.$row[3].', "desc": "'.json_escape($row[4]).'", "date": "'.$row[5].'", "expire": "'.$row[6].'"}';
				}
			}

			echo '{"result": 0, "list": ['.$list.']}';
			exit;
		}
	}

	echo '{"result": 1, "status": "action undefined"}';
