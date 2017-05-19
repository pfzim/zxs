<?php

if(file_exists('inc.config.php'))
{
	header("Content-Type: text/plain; charset=utf-8");
	echo 'Configuration file already exist. Remove inc.config.php before running installation';
	exit;
}

class MySQLDB
{
	private $link = NULL;
	public $data = NULL;
	private $error_msg = "";
	function __construct()
	{
		$link = NULL;
		$data = FALSE;
		$error_msg = "";
	}
	function connect($db_host = "", $db_user = "", $db_passwd = "", $db_name = "", $db_cpage = "utf8")
	{
		$this->link = mysqli_connect($db_host, $db_user, $db_passwd, $db_name);
		if(!$this->link)
		{
			$this->error(mysqli_connect_error());
			return NULL;
		}
		if(!mysqli_set_charset($this->link, $db_cpage))
		{
			$this->error(mysqli_error($this->link));
			mysqli_close($this->link);
			$this->link = NULL;
			return NULL;
		}
		return $this->link;
	}
	public function __destruct()
	{
		$this->data = FALSE;
		$this->disconnect();
	}

	public function select_db($db_name)
	{
		return mysqli_select_db($this->link, $db_name);
	}

	public function select($query)
	{
		$this->data = FALSE;
		if(!$this->link)
		{
			return FALSE;
		}
		$res = mysqli_query($this->link, $query);
		if(!$res)
		{
			$this->error(mysqli_error($this->link));
			return FALSE;
		}
		if(mysqli_num_rows($res) <= 0)
		{
			return FALSE;
		}
		$this->data = array();
		while($row = mysqli_fetch_row($res))
		{
			$this->data[] = $row;
		}
		mysqli_free_result($res);
		return TRUE;
	}
	public function put($query)
	{
		if(!$this->link)
		{
			return FALSE;
		}
		$res = mysqli_query($this->link, $query);
		if(!$res)
		{
			$this->error(mysqli_error($this->link));
			return FALSE;
		}
		//return mysqli_affected_rows($this->link);
		return TRUE;
	}
	public function last_id()
	{
		return mysqli_insert_id($this->link);
	}
	public function disconnect()
	{
		//$this->data = FALSE;
		$this->error_msg = "";
		if($this->link)
		{
			mysqli_close($this->link);
			$this->link = NULL;
		}
	}
	public function get_last_error()
	{
		return $this->error_msg;
	}
	private function error($str)
	{
		//$this->error_msg = $str;
		throw new Exception($str); //__CLASS__.": ".$str
	}
}

function json_escape($value) //json_escape
{
    $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
    $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");
    $result = str_replace($escapers, $replacements, $value);
    return $result;
}

function sql_escape($value)
{
    $escapers = array("\\", "\"", "\n", "\r", "\t", "\x08", "\x0c", "'", "\x1A", "\x00"); // "%", "_"
    $replacements = array("\\\\", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b", "\\'", "\\Z", "\\0");
    $result = str_replace($escapers, $replacements, $value);
    return $result;
}


$sql = array(
<<<'EOT'
CREATE TABLE `#DB_NAME#`.`zxs_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `type` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(2048) NOT NULL,
  `size` bigint(20) unsigned NOT NULL DEFAULT '0',
  `date` datetime DEFAULT NULL,
  `expire` date DEFAULT NULL,
  `desc` varchar(4096) NOT NULL,
  `deleted` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOT
,
<<<'EOT'
CREATE TABLE `#DB_NAME#`.`zxs_link_files` (
  `lid` int(10) unsigned NOT NULL,
  `fid` int(10) unsigned NOT NULL,
  `pid` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOT
,
<<<'EOT'
CREATE TABLE `#DB_NAME#`.`zxs_links` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `pin` varchar(4) CHARACTER SET latin1 NOT NULL,
  `desc` varchar(4096) NOT NULL,
  `date` datetime NOT NULL,
  `deleted` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOT
,
<<<'EOT'
CREATE TABLE `#DB_NAME#`.`zxs_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `type` int(10) unsigned NOT NULL DEFAULT '0',
  `p1` int(10) unsigned NOT NULL DEFAULT '0',
  `p2` int(10) unsigned NOT NULL DEFAULT '0',
  `p3` int(10) unsigned NOT NULL DEFAULT '0',
  `p4` int(10) unsigned NOT NULL DEFAULT '0',
  `ip` varchar(256) NOT NULL,
  `desc` varchar(1024) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOT
,
<<<'EOT'
CREATE TABLE `#DB_NAME#`.`zxs_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `passwd` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `mail` varchar(1024) CHARACTER SET latin1 NOT NULL,
  `sid` varchar(15) DEFAULT NULL,
  `deleted` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOT
);

$config = <<<'EOT'
<?php
	define("DB_HOST", "#host#");
	define("DB_USER", "#login#");
	define("DB_PASSWD", "#password#");
	define("DB_NAME", "#db#");
	define("DB_CPAGE", "utf8");

	define("MAIL_HOST", "#mail_host#");
	define("MAIL_FROM", "#mail_from#");
	define("MAIL_FROM_NAME", "#mail_from_name#");
	define("MAIL_ADMIN", "#mail_admin#");
	define("MAIL_ADMIN_NAME", "#mail_admin_name#");
	define("MAIL_AUTH", #mail_auth#);
	define("MAIL_LOGIN", "#mail_user#");
	define("MAIL_PASSWD", "#mail_password#");
	define("MAIL_SECURE", "#mail_secure#");
	define("MAIL_PORT", #mail_port#);

	define("UPLOAD_DIR", "#upload_dir#");
	define("ALLOW_MAILS", '#allow_mails#');

	define("LOG_LOGIN", 1);
	define("LOG_DOWNLOAD", 2);			// p1 - file_id
	define("LOG_LOGIN_FAILED", 3);
	define("LOG_VIEW_ABOUT", 4);
	define("LOG_TAR_CREATE", 5);		// p1 - link_id
	define("LOG_LOGIN_ACTIVATE", 6);
	define("LOG_VIEW_STATS", 7);

EOT;


	//error_reporting(0);

	if(isset($_GET['action']))
	{
		$action = $_GET['action'];
		try
		{
			header("Content-Type: text/plain; charset=utf-8");

			switch($action)
			{
				case 'check_db':
				{
					if(empty($_POST['host'])) throw new Exception('Host value not defined!');
					if(empty($_POST['user'])) throw new Exception('Login value not defined!');

					$db = new MySQLDB();
					$db->connect(@$_POST['host'], @$_POST['user'], @$_POST['pwd']);
					echo '{"code": 0, "status": "OK"}';
				}
				exit;
				case 'create_db':
				{
					if(empty($_POST['host'])) throw new Exception('Host value not defined!');
					if(empty($_POST['user'])) throw new Exception('Login value not defined!');
					if(empty($_POST['db'])) throw new Exception('DB value not defined!');

					$db = new MySQLDB();
					$db->connect(@$_POST['host'], @$_POST['user'], @$_POST['pwd']);
					$db->put('CREATE DATABASE `'.@$_POST['db'].'` DEFAULT CHARACTER SET utf8');

					echo '{"code": 0, "status": "OK"}';
				}
				exit;
				case 'create_tables':
				{
					if(empty($_POST['host'])) throw new Exception('Host value not defined!');
					if(empty($_POST['user'])) throw new Exception('Login value not defined!');
					if(empty($_POST['db'])) throw new Exception('DB value not defined!');

					$db = new MySQLDB();
					$db->connect(@$_POST['host'], @$_POST['user'], @$_POST['pwd']);
					foreach($sql as $query)
					{
						$db->put(str_replace('#DB_NAME#', sql_escape(@$_POST['db']), $query));
					}
					//$db->put('CREATE DATABASE `'.@$_POST['db'].'` DEFAULT CHARACTER SET utf8');
					//$db->select_db(@$_POST['db']);
					//$db->put($db_table);

					echo '{"code": 0, "status": "OK"}';
				}
				exit;
				case 'create_db_user':
				{
					if(empty($_POST['host'])) throw new Exception('Host value not defined!');
					if(empty($_POST['user'])) throw new Exception('Login value not defined!');
					if(empty($_POST['dbuser'])) throw new Exception('Login value not defined!');

					$db = new MySQLDB();
					$db->connect(@$_POST['host'], @$_POST['user'], @$_POST['pwd']);
					$db->put("CREATE USER '".sql_escape(@$_POST['dbuser'])."'@'%' IDENTIFIED BY '".sql_escape(@$_POST['dbpwd'])."'");

					echo '{"code": 0, "status": "OK"}';
				}
				exit;
				case 'grant_access':
				{
					if(empty($_POST['host'])) throw new Exception('Host value not defined!');
					if(empty($_POST['user'])) throw new Exception('Login value not defined!');
					if(empty($_POST['db'])) throw new Exception('DB value not defined!');
					if(empty($_POST['dbuser'])) throw new Exception('Login value not defined!');

					$db = new MySQLDB();
					$db->connect(@$_POST['host'], @$_POST['user'], @$_POST['pwd']);
					//$db->put("GRANT USAGE ON mysql.* TO '".@$_POST['dbuser']."'@'%'");
					$db->put("GRANT ALL PRIVILEGES ON ".sql_escape(@$_POST['db']).".* TO '".sql_escape(@$_POST['dbuser'])."'@'%'");
					$db->put("FLUSH PRIVILEGES");

					echo '{"code": 0, "status": "OK"}';
				}
				exit;
				case 'check_mail':
				{
					if(empty($_POST['mailhost'])) throw new Exception('MAIL Host value not defined!');
					if(empty($_POST['mailport'])) throw new Exception('MAIL Port value not defined!');
					if(empty($_POST['mailfrom'])) throw new Exception('MAIL From value not defined!');
					if(empty($_POST['mailfromname'])) throw new Exception('MAIL From Name value not defined!');
					if(empty($_POST['mailadmin'])) throw new Exception('MAIL Admin value not defined!');
					if(empty($_POST['mailadminname'])) throw new Exception('MAIL Admin Name value not defined!');

					require_once 'libs/PHPMailer/PHPMailerAutoload.php';

					$mail = new PHPMailer;

					$mail->isSMTP();
					$mail->Host = @$_POST['mailhost'];
					$mail->SMTPAuth = !empty($_POST['mailuser']);
					if($mail->SMTPAuth)
					{
						$mail->Username = @$_POST['mailuser'];
						$mail->Password = @$_POST['mailpwd'];
					}

					$mail->SMTPSecure = @$_POST['mailsecure'];
					$mail->Port = @$_POST['mailport'];

					$mail->setFrom(@$_POST['mailfrom'], @$_POST['mailfromname']);
					$mail->addAddress(@$_POST['mailadmin'], @$_POST['mailadminname']);
					
					$mail->isHTML(true);

					$mail->Subject = 'Test message';
					$mail->Body    = 'This is a test message';
					$mail->AltBody = 'This is a test message';

					if($mail->send())
					{
						echo '{"code": 0, "status": "OK"}';
					}
					
					throw new Exception("FAILED");
				}
				exit;
				case 'add_user':
				{
					if(empty($_POST['host'])) throw new Exception('Host value not defined!');
					if(empty($_POST['dbuser'])) throw new Exception('Login value not defined!');
					if(empty($_POST['db'])) throw new Exception('DB value not defined!');
					if(empty($_POST['adminuser'])) throw new Exception('Login value not defined!');
					if(empty($_POST['mailadmin'])) throw new Exception('MAIL Admin value not defined!');

					$db = new MySQLDB();
					$db->connect(@$_POST['host'], @$_POST['dbuser'], @$_POST['dbpwd'], @$_POST['db']);
					$db->put("INSERT INTO zxs_users (login, passwd, mail, deleted) VALUES ('".sql_escape(@$_POST['adminuser'])."', PASSWORD('".sql_escape(@$_POST['adminpwd'])."'), '".sql_escape(@$_POST['mailadmin'])."', 0)");

					echo '{"code": 0, "status": "OK"}';
				}
				exit;
				case 'save_config':
				{
					if(empty($_POST['host'])) throw new Exception('Host value not defined!');
					if(empty($_POST['db'])) throw new Exception('DB value not defined!');
					if(empty($_POST['dbuser'])) throw new Exception('Login value not defined!');

					if(empty($_POST['mailhost'])) throw new Exception('MAIL Host value not defined!');
					if(empty($_POST['mailport'])) throw new Exception('MAIL Port value not defined!');
					if(empty($_POST['mailfrom'])) throw new Exception('MAIL From value not defined!');
					if(empty($_POST['mailfromname'])) throw new Exception('MAIL From Name value not defined!');
					if(empty($_POST['mailadmin'])) throw new Exception('MAIL Admin value not defined!');
					if(empty($_POST['mailadminname'])) throw new Exception('MAIL Admin Name value not defined!');

					if(empty($_POST['uploaddir'])) throw new Exception('MAIL Admin value not defined!');
					if(empty($_POST['allowmails'])) throw new Exception('MAIL Admin Name value not defined!');

					$config = str_replace(
						array('#host#', '#login#', '#password#', '#db#', '#mail_host#', '#mail_port#', '#mail_user#', '#mail_password#', '#mail_secure#', '#mail_admin#', '#mail_admin_name#', '#mail_from#', '#mail_from_name#', '#upload_dir#', '#allow_mails#', '#mail_auth#'),
						array(sql_escape(@$_POST['host']), sql_escape(@$_POST['dbuser']), sql_escape(@$_POST['dbpwd']), sql_escape(@$_POST['db']), sql_escape(@$_POST['mailhost']), sql_escape(@$_POST['mailport']), sql_escape(@$_POST['mailuser']), sql_escape(@$_POST['mailpwd']), sql_escape(@$_POST['mailsecure']), sql_escape(@$_POST['mailadmin']), sql_escape(@$_POST['mailadminname']), sql_escape(@$_POST['mailfrom']), sql_escape(@$_POST['mailfromname']), sql_escape(@$_POST['uploaddir']), sql_escape(@$_POST['allowmails']), empty(@$_POST['mailuser'])?'false':'true'),
						$config
					);

					if(file_put_contents('inc.config.php', $config) === FALSE)
					{
						throw new Exception("Save config error");
					}

					echo '{"code": 0, "status": "OK"}';
				}
				exit;
				case 'remove_self':
				{
					if(!unlink('install.php'))
					{
						throw new Exception("FAILED");
					}
					echo '{"code": 0, "status": "OK"}';
				}
				exit;
			}
		}
		catch(Exception $e)
		{
			echo '{"code": 1, "status": "'.json_escape($e->getMessage()).'"}';
			exit;
		}
	}

	header("Content-Type: text/html; charset=utf-8");
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Installation script</title>
		<meta charset="utf-8">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<style type="text/css">
			html {
				font-family: sans-serif;
				-webkit-text-size-adjust: 100%;
				-ms-text-size-adjust: 100%
			}
			body {
				margin: 0
			}
			button,
			input,
			select {
				margin: 0;
				font: inherit;
				color: inherit
			}
			button {
				overflow: visible
			}
			button,
			select {
				text-transform: none
			}
			button {
				-webkit-appearance: button;
				cursor: pointer
			}
			button::-moz-focus-inner,
			input::-moz-focus-inner {
				padding: 0;
				border: 0
			}
			input {
				line-height: normal
			}

			@media print {
				*,
				:after,
				:before {
					color: #000!important;
					text-shadow: none!important;
					background: 0 0!important;
					-webkit-box-shadow: none!important;
					box-shadow: none!important
				}
				h3 {
					orphans: 3;
					widows: 3
				}
				h3 {
					page-break-after: avoid
				}
			}
			@font-face {
				font-family: 'Glyphicons Halflings';
				src: url(../fonts/glyphicons-halflings-regular.eot);
				src: url(../fonts/glyphicons-halflings-regular.eot?#iefix) format('embedded-opentype'), url(../fonts/glyphicons-halflings-regular.woff2) format('woff2'), url(../fonts/glyphicons-halflings-regular.woff) format('woff'), url(../fonts/glyphicons-halflings-regular.ttf) format('truetype'), url(../fonts/glyphicons-halflings-regular.svg#glyphicons_halflingsregular) format('svg')
			}
			* {
				-webkit-box-sizing: border-box;
				-moz-box-sizing: border-box;
				box-sizing: border-box
			}
			:after,
			:before {
				-webkit-box-sizing: border-box;
				-moz-box-sizing: border-box;
				box-sizing: border-box
			}
			html {
				font-size: 10px;
				-webkit-tap-highlight-color: rgba(0, 0, 0, 0)
			}
			body {
				font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
				font-size: 14px;
				line-height: 1.42857143;
				color: #333;
				background-color: #fff
			}
			button,
			input,
			select {
				font-family: inherit;
				font-size: inherit;
				line-height: inherit
			}
			h3 {
				font-family: inherit;
				font-weight: 500;
				line-height: 1.1;
				color: inherit
			}
			h3 {
				margin-top: 20px;
				margin-bottom: 10px
			}
			h3 {
				font-size: 24px
			}
			.container {
				padding-right: 15px;
				padding-left: 15px;
				margin-right: auto;
				margin-left: auto
			}
			@media (min-width: 768px) {
				.container {
					width: 750px
				}
			}
			@media (min-width: 992px) {
				.container {
					width: 970px
				}
			}
			@media (min-width: 1200px) {
				.container {
					width: 1170px
				}
			}
			.col-sm-2,
			.col-sm-5 {
				position: relative;
				min-height: 1px;
				padding-right: 15px;
				padding-left: 15px
			}
			@media (min-width: 768px) {
				.col-sm-2,
				.col-sm-5 {
					float: left
				}
				.col-sm-5 {
					width: 41.66666667%
				}
				.col-sm-2 {
					width: 16.66666667%
				}
				.col-sm-offset-2 {
					margin-left: 16.66666667%
				}
			}
			label {
				display: inline-block;
				max-width: 100%;
				margin-bottom: 5px;
				font-weight: 700
			}
			.form-control {
				display: block;
				width: 100%;
				height: 34px;
				padding: 6px 12px;
				font-size: 14px;
				line-height: 1.42857143;
				color: #555;
				background-color: #fff;
				background-image: none;
				border: 1px solid #ccc;
				border-radius: 4px;
				-webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075);
				box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075);
				-webkit-transition: border-color ease-in-out .15s, -webkit-box-shadow ease-in-out .15s;
				-o-transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
				transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s
			}
			.form-control:focus {
				border-color: #66afe9;
				outline: 0;
				-webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 8px rgba(102, 175, 233, .6);
				box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 8px rgba(102, 175, 233, .6)
			}
			.form-control::-moz-placeholder {
				color: #999;
				opacity: 1
			}
			.form-control:-ms-input-placeholder {
				color: #999
			}
			.form-control::-webkit-input-placeholder {
				color: #999
			}
			.form-control::-ms-expand {
				background-color: transparent;
				border: 0
			}
			.form-group {
				margin-bottom: 15px
			}
			.form-horizontal .form-group {
				margin-right: -15px;
				margin-left: -15px
			}
			@media (min-width: 768px) {
				.form-horizontal .control-label {
					padding-top: 7px;
					margin-bottom: 0;
					text-align: right
				}
			}
			.btn {
				display: inline-block;
				padding: 6px 12px;
				margin-bottom: 0;
				font-size: 14px;
				font-weight: 400;
				line-height: 1.42857143;
				text-align: center;
				white-space: nowrap;
				vertical-align: middle;
				-ms-touch-action: manipulation;
				touch-action: manipulation;
				cursor: pointer;
				-webkit-user-select: none;
				-moz-user-select: none;
				-ms-user-select: none;
				user-select: none;
				background-image: none;
				border: 1px solid transparent;
				border-radius: 4px
			}
			.btn:active:focus,
			.btn:focus {
				outline: 5px auto -webkit-focus-ring-color;
				outline-offset: -2px
			}
			.btn:focus,
			.btn:hover {
				color: #333;
				text-decoration: none
			}
			.btn:active {
				background-image: none;
				outline: 0;
				-webkit-box-shadow: inset 0 3px 5px rgba(0, 0, 0, .125);
				box-shadow: inset 0 3px 5px rgba(0, 0, 0, .125)
			}
			.btn-primary {
				color: #fff;
				background-color: #337ab7;
				border-color: #2e6da4
			}
			.btn-primary:focus {
				color: #fff;
				background-color: #286090;
				border-color: #122b40
			}
			.btn-primary:hover {
				color: #fff;
				background-color: #286090;
				border-color: #204d74
			}
			.btn-primary:active {
				color: #fff;
				background-color: #286090;
				border-color: #204d74
			}
			.btn-primary:active:focus,
			.btn-primary:active:hover {
				color: #fff;
				background-color: #204d74;
				border-color: #122b40
			}
			.btn-primary:active {
				background-image: none
			}
			.alert {
				padding: 15px;
				margin-bottom: 20px;
				border: 1px solid transparent;
				border-radius: 4px
			}
			.alert-danger {
				color: #a94442;
				background-color: #f2dede;
				border-color: #ebccd1
			}
			.alert-success{
				color: #3c763d;
				background-color: #dff0d8;
				border-color: #d6e9c6
			}
			.container:after,
			.container:before,
			.form-horizontal .form-group:after,
			.form-horizontal .form-group:before {
				display: table;
				content: " "
			}
			.container:after,
			.form-horizontal .form-group:after {
				clear: both
			}
			@-ms-viewport {
				width: device-width
			}
		</style>
		<script type="text/javascript">
			function gi(name)
			{
				return document.getElementById(name);
			}

			if(!XMLHttpRequest.prototype.sendAsBinary) {
				XMLHttpRequest.prototype.sendAsBinary = function(datastr) {
					function byteValue(x)
					{
						return x.charCodeAt(0) & 0xff;
					}
					var ords = Array.prototype.map.call(datastr, byteValue);
					var ui8a = new Uint8Array(ords);
					try {
						this.send(ui8a);
					}
					catch(e) {
						this.send(ui8a.buffer);
					}
				};
			}

			function f_xhr()
			{
				try { return new XMLHttpRequest(); } catch(e) {}
				try { return new ActiveXObject("Msxml3.XMLHTTP"); } catch(e) {}
				try { return new ActiveXObject("Msxml2.XMLHTTP.6.0"); } catch(e) {}
				try { return new ActiveXObject("Msxml2.XMLHTTP.3.0"); } catch(e) {}
				try { return new ActiveXObject("Msxml2.XMLHTTP"); } catch(e) {}
				try { return new ActiveXObject("Microsoft.XMLHTTP"); } catch(e) {}
				console.log("ERROR: XMLHttpRequest undefined");
				return null;
			}

			function f_post(_id, action, data)
			{
				var xhr = f_xhr();
				var id = _id;
				if(xhr)
				{
					gi("result_"+id).textContent = 'Loading...';
					gi("result_"+id).style.display = 'block';
					
					xhr.open("post", "install.php?action="+action, true);
					xhr.onreadystatechange = function()
					{
						if(xhr.readyState == 4)
						{
							var result;
							if(xhr.status == 200)
							{
								try
								{
									result = JSON.parse(xhr.responseText);
								}
								catch(e)
								{
									result = {code: 1, status: "Response: "+xhr.responseText};
								}
							}
							else
							{
								result = {code: 1, status: "AJAX error code: "+xhr.status};
							}

							if(result.code)
							{
								gi("result_"+id).classList.remove('alert-success');
								gi("result_"+id).classList.add('alert-danger');
							}
							else
							{
								gi("result_"+id).classList.remove('alert-danger');
								gi("result_"+id).classList.add('alert-success');
							}
							gi("result_"+id).textContent = result.status;
						}
					};
					xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
					//xhr.send("name="+encodeURIComponent(el.value));
					xhr.send(data);
				}

				return false;
			}

			function f_check_db_conn(id)
			{
				f_post(id, 'check_db',
					'host='+encodeURIComponent(gi('host').value)+'&user='+encodeURIComponent(gi('user_root').value)+'&pwd='+encodeURIComponent(gi('pwd_root').value)
				);
			}

			function f_create_db(id)
			{
				f_post(id, 'create_db',
					'host='+encodeURIComponent(gi('host').value)+'&user='+encodeURIComponent(gi('user_root').value)+'&pwd='+encodeURIComponent(gi('pwd_root').value)
					+'&db='+encodeURIComponent(gi('db_scheme').value)
				);
			}

			function f_create_tables(id)
			{
				f_post(id, 'create_tables',
					'host='+encodeURIComponent(gi('host').value)+'&user='+encodeURIComponent(gi('user_root').value)+'&pwd='+encodeURIComponent(gi('pwd_root').value)
					+'&db='+encodeURIComponent(gi('db_scheme').value)
				);
			}

			function f_create_db_user(id)
			{
				f_post(id, 'create_db_user',
					'host='+encodeURIComponent(gi('host').value)+'&user='+encodeURIComponent(gi('user_root').value)+'&pwd='+encodeURIComponent(gi('pwd_root').value)
					+'&dbuser='+encodeURIComponent(gi('db_user').value)+'&dbpwd='+encodeURIComponent(gi('db_pwd').value)
				);
			}

			function f_grant_access(id)
			{
				f_post(id, 'grant_access',
					'host='+encodeURIComponent(gi('host').value)+'&user='+encodeURIComponent(gi('user_root').value)+'&pwd='+encodeURIComponent(gi('pwd_root').value)
					+'&db='+encodeURIComponent(gi('db_scheme').value)+'&dbuser='+encodeURIComponent(gi('db_user').value)+'&dbpwd='+encodeURIComponent(gi('db_pwd').value)
				);
			}

			function f_check_mail(id)
			{
				var ms = gi("mail_secure");
				f_post(id, "check_mail",
					'mailhost='+encodeURIComponent(gi('mail_host').value)+'&mailport='+encodeURIComponent(gi('mail_port').value)+'&mailuser='+encodeURIComponent(gi('mail_user').value)+'&mailpwd='+encodeURIComponent(gi('mail_pwd').value)
					+'&mailsecure='+encodeURIComponent(ms.options[ms.selectedIndex].value)+'&mailfrom='+encodeURIComponent(gi('mail_from').value)+'&mailfromname='+encodeURIComponent(gi('mail_from_name').value)
					+'&mailadmin='+encodeURIComponent(gi('mail_admin').value)+'&mailadminname='+encodeURIComponent(gi('mail_admin_name').value)
				);
			}

			function f_create_admin_account(id)
			{
				f_post(id, 'add_user',
					'host='+encodeURIComponent(gi('host').value)+'&dbuser='+encodeURIComponent(gi('db_user').value)+'&dbpwd='+encodeURIComponent(gi('db_pwd').value)
					+'&db='+encodeURIComponent(gi('db_scheme').value)+'&adminuser='+encodeURIComponent(gi('admin_user').value)+'&adminpwd='+encodeURIComponent(gi('admin_pwd').value)
					+'&mailadmin='+encodeURIComponent(gi('mail_admin').value)
				);
			}

			function f_save_config(id)
			{
				var ms = gi("mail_secure");
				f_post(id, "save_config",
					'host='+encodeURIComponent(gi('host').value)+'&db='+encodeURIComponent(gi('db_scheme').value)+'&dbuser='+encodeURIComponent(gi('db_user').value)+'&dbpwd='+encodeURIComponent(gi('db_pwd').value)
					+'&mailhost='+encodeURIComponent(gi('mail_host').value)+'&mailport='+encodeURIComponent(gi('mail_port').value)+'&mailuser='+encodeURIComponent(gi('mail_user').value)+'&mailpwd='+encodeURIComponent(gi('mail_pwd').value)
					+'&mailsecure='+encodeURIComponent(ms.options[ms.selectedIndex].value)+'&mailfrom='+encodeURIComponent(gi('mail_from').value)+'&mailfromname='+encodeURIComponent(gi('mail_from_name').value)
					+'&mailadmin='+encodeURIComponent(gi('mail_admin').value)+'&mailadminname='+encodeURIComponent(gi('mail_admin_name').value)
					+'&uploaddir='+encodeURIComponent(gi('upload_dir').value)+'&allowmails='+encodeURIComponent(gi('allow_mails').value)
				);
			}

			function f_remove_self(id)
			{
				f_post(id, "remove_self", 'goodbay=script');
			}
		</script>
	</head>
	<body>
		<div class="container">
		<div class="form-horizontal">
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-5">
					<h3>MySQL settings</h3>
				</div>
			</div>
			<div class="form-group">
				<label for="host" class="control-label col-sm-2">Host:</label>
				<div class="col-sm-5">
					<input id="host" class="form-control" type="text" value="localhost" />
				</div>
			</div>
			<div class="form-group">
				<label for="user_root" class="control-label col-sm-2">Login:</label>
				<div class="col-sm-5">
					<input id="user_root" class="form-control" type="text" value="root" />
				</div>
			</div>
			<div class="form-group">
				<label for="pwd_root" class="control-label col-sm-2">Password:</label>
				<div class="col-sm-5">
					<input id="pwd_root" class="form-control" type="password" value="" />
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-5">
					<button type="button" class="btn btn-primary" onclick='f_check_db_conn(1);'>1. Check DB connection</button><div id="result_1" class="alert alert-danger" style="display: none"></div>
				</div>
			</div>
			<div class="form-group">
				<label for="db_scheme" class="control-label col-sm-2">DB name:</label>
				<div class="col-sm-5">
					<input id="db_scheme" class="form-control" type="text" value="zxs" />
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-5">
					<button type="button" class="btn btn-primary" onclick='f_create_db(2);'>2. Create database</button><div id="result_2" class="alert alert-danger" style="display: none"></div>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-5">
					<button type="button" class="btn btn-primary" onclick='f_create_tables(3);'>3. Create tables</button><div id="result_3" class="alert alert-danger" style="display: none"></div>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-5">
					<h3>New MySQL DB user or enter existing</h3>
				</div>
			</div>
			<div class="form-group">
				<label for="db_user" class="control-label col-sm-2">Login:</label>
				<div class="col-sm-5">
					<input id="db_user" class="form-control" type="text" value="zxs" />
				</div>
			</div>
			<div class="form-group">
				<label for="db_pwd" class="control-label col-sm-2">Password:</label>
				<div class="col-sm-5">
					<input id="db_pwd" class="form-control" type="password" value="" />
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-5">
					<button type="button" class="btn btn-primary" onclick='f_create_db_user(4);'>4. Create DB user</button><div id="result_4" class="alert alert-danger" style="display: none"></div>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-5">
					<button type="button" class="btn btn-primary" onclick='f_grant_access(5);'>5. Grant access to database</button><div id="result_5" class="alert alert-danger" style="display: none"></div>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-5">
					<h3>Mail settings</h3>
				</div>
			</div>
			<div class="form-group">
				<label for="mail_host" class="control-label col-sm-2">Host:</label>
				<div class="col-sm-5">
					<input id="mail_host" class="form-control" type="text" value="smtp.example.com" />
				</div>
			</div>
			<div class="form-group">
				<label for="mail_port" class="control-label col-sm-2">Port:</label>
				<div class="col-sm-5">
					<input id="mail_port" class="form-control" type="text" value="25" />
				</div>
			</div>
			<div class="form-group">
				<label for="mail_user" class="control-label col-sm-2">User:</label>
				<div class="col-sm-5">
					<input id="mail_user" class="form-control" type="text" value="robot@example.com" />
				</div>
			</div>
			<div class="form-group">
				<label for="mail_pwd" class="control-label col-sm-2">Password:</label>
				<div class="col-sm-5">
					<input id="mail_pwd" class="form-control" type="password" value="" />
				</div>
			</div>
			<div class="form-group">
				<label for="mail_from" class="control-label col-sm-2">From address:</label>
				<div class="col-sm-5">
					<input id="mail_from" class="form-control" type="text" value="robot@example.com" />
				</div>
			</div>
			<div class="form-group">
				<label for="mail_from_name" class="control-label col-sm-2">From name:</label>
				<div class="col-sm-5">
					<input id="mail_from_name" class="form-control" type="text" value="Robot" />
				</div>
			</div>
			<div class="form-group">
				<label for="mail_admin" class="control-label col-sm-2">Admin address:</label>
				<div class="col-sm-5">
					<input id="mail_admin" class="form-control" type="text" value="admin@example.com" />
				</div>
			</div>
			<div class="form-group">
				<label for="mail_admin_name" class="control-label col-sm-2">Admin name:</label>
				<div class="col-sm-5">
					<input id="mail_admin_name" class="form-control" type="text" value="Admin" />
				</div>
			</div>
			<div class="form-group">
				<label for="mail_secure" class="control-label col-sm-2">Secure:</label>
				<div class="col-sm-5">
					<select id="mail_secure" class="form-control">
						<option value="" selected="selected">None</option>
						<option value="tls">TLS</option>
						<option value="ssl">SSL</option>
					</select>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-5">
					<button type="button" class="btn btn-primary" onclick='f_check_mail(6);'>6. Check mail connection</button><div id="result_6" class="alert alert-danger" style="display: none"></div>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-5">
					<h3>Admin account</h3>
				</div>
			</div>
			<div class="form-group">
				<label for="admin_user" class="control-label col-sm-2">Login:</label>
				<div class="col-sm-5">
					<input id="admin_user" class="form-control" type="text" value="admin" />
				</div>
			</div>
			<div class="form-group">
				<label for="admin_pwd" class="control-label col-sm-2">Password:</label>
				<div class="col-sm-5">
					<input id="admin_pwd" class="form-control" type="password" value="" />
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-5">
					<button type="button" class="btn btn-primary" onclick='f_create_admin_account(7);'>7. Create admin account</button><div id="result_7" class="alert alert-danger" style="display: none"></div>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-5">
					<h3>Misc settings</h3>
				</div>
			</div>
			<div class="form-group">
				<label for="upload_dir" class="control-label col-sm-2">Upload directory:</label>
				<div class="col-sm-5">
					<input id="upload_dir" class="form-control" type="text" value="<?php echo htmlspecialchars(dirname($_SERVER['SCRIPT_FILENAME'])); ?>/upload" />
				</div>
			</div>
			<div class="form-group">
				<label for="allow_mails" class="control-label col-sm-2">Allow mails (regexp match):</label>
				<div class="col-sm-5">
					<input id="allow_mails" class="form-control" type="text" value="^.+@.+$" />
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-5">
					<button type="button" class="btn btn-primary" onclick='f_save_config(8);'>8. Save config</button><div id="result_8" class="alert alert-danger" style="display: none"></div>
				</div>
			</div>
		</div>
		</div>
	</body>
</html>
