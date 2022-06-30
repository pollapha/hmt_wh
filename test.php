<?php
$path = $_SERVER['PHP_SELF'];
ini_set('session.save_path',dirname($_SERVER['DOCUMENT_ROOT']). '/htdocs'.  substr($path, 0,strpos($path, '/', 1)) .'/sessions');
session_start();
$_SESSION['test'] = 'xxx';
echo $_SESSION['test'] ;
?>