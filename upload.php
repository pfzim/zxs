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

	if(empty($_SERVER['HTTP_X_UPLOAD_FILENAME']) || !isset($_SERVER['HTTP_X_UPLOAD_FILESIZE']) || !isset($_SERVER['HTTP_X_UPLOAD_ID']) || !isset($_SERVER['HTTP_X_UPLOAD_PID']))
	{
		echo '{"code": 1, "status": "HTTP headers undefined"}';
		exit;
	}

	$filename = urldecode($_SERVER['HTTP_X_UPLOAD_FILENAME']);
	$fsz = intval($_SERVER['HTTP_X_UPLOAD_FILESIZE']);
	//$uid = intval($_SERVER['HTTP_X_UPLOAD_UID']);
	$pid = intval($_SERVER['HTTP_X_UPLOAD_PID']);
	$id = intval($_SERVER['HTTP_X_UPLOAD_ID']);

	if(!$uid)
	{
		echo '{"code": 1, "status": "UID undefined"}';
		exit; 
	}
	
	//if(!$id) { echo '{"code": 1, "status": "ID undefined"}'; exit; }
	//if(!$pid) { echo '{"code": 1, "status": "PID undefined"}'; exit; }
	//if(!$fsz) { echo '{"code": 1, "status": "FileSize undefined"}'; exit; }

	$db = new MySQLDB();
	
	if(!$db->connect())
	{
		echo '{"code": 1, "status": "SQL: Error connect DB"}';
		exit;
	}

	if(!$id)
	{
		$fn = basename($filename);

		if(!$db->put(rpv("INSERT INTO zxs_files (uid, pid, name, size, date, expire, type, deleted) VALUES (#, #, !, 0, NOW(), DATE_ADD(CURDATE(), INTERVAL 3 MONTH), 3, 1)", $uid, $pid, $fn)))
		{
			echo'{"code": 1, "status": "SQL: Error insert file"}';
			exit;
		}
		$id = $db->last_id();
	}
	else
	{
		if(!$db->select(rpv("SELECT m.`id` FROM `zxs_files` AS m WHERE m.`id` = # AND m.`uid` = # AND m.`pid` = # AND m.`type` = 3 AND m.`deleted` = 1 LIMIT 1", $id, $uid, $pid)))
		{
			echo'{"code": 1, "status": "SQL: Error get file data"}';
			exit;
		}
	}

	$fh = fopen(UPLOAD_DIR."/f$id", 'a+b');
	if(!$fh)
	{
		echo '{"code": 1, "status": "Error create file"}';
		exit;
	}

	$fi = fopen('php://input', 'rb');
	if(!$fi)
	{
		fclose($fh);
		echo '{"code": 1, "status": "Error open php://input"}';
		exit;
	}

	while(!feof($fi))
	{
		$data = fread($fi, 4096);
		fwrite($fh, $data);
	}

	fclose($fh);
	fclose($fi);

	$fs = filesize(UPLOAD_DIR."/f$id");
	if($fs == $fsz)
	{
		if(!$db->put(rpv("UPDATE zxs_files SET size = #, type=0, deleted=0 WHERE id = # AND type=3 AND deleted=1 LIMIT 1", $fs, $id)))
		{
			echo '{"code": 1, "status": "SQL: Error update file size"}';
			exit;
		}
	}

	if(!$db->select(rpv("SELECT m.`id`, m.`name`, m.`size`, DATE_FORMAT(m.`date`, '%d.%m.%Y'), DATE_FORMAT(m.`expire`, '%d.%m.%Y'), m.`type`, m.`desc` FROM `zxs_files` AS m WHERE m.`id` = # AND m.`uid` = # LIMIT 1", $id, $uid)))
	{
		echo '{"code": 1, "status": "SQL: Error get file data"}';
		exit;
	}

	print '{"code": 0, "id": '.$id.', "name": "'.json_escape($db->data[0][1]).'", "size": '.$db->data[0][2].', "desc": "'.json_escape($db->data[0][6]).'", "date": "'.$db->data[0][3].'", "expire": "'.$db->data[0][4].'"}';
