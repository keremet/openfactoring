<?php
session_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html><head>


	<meta http-equiv="CONTENT-TYPE" content="text/html; charset=UTF-8">
	<title>Настройки текущего пользователя</title>
	<style type="text/css">
	<!--
		@page { size: 21cm 29.7cm; margin: 2cm }
		P { margin-bottom: 0.21cm }
		PRE { font-family: "Liberation Mono", monospace; font-size: 10pt }
		A:link { color: #000080; so-language: zxx; text-decoration: underline }
		A:visited { color: #800000; so-language: zxx; text-decoration: underline }
	-->
	</style>
</head><body>
<?php
	include "localdb.php";
	try{
		$db = new localdb();
	}catch (Exception $e) {
		die($e->getMessage());		
	}	
?>
<form action="" method="post">
<p>Дата операционного дня
<?php
if($_POST['date']!==null){
	$d = $_POST['date'];	
	$db->setOperDay(substr($d,6,4).'-'.substr($d,3,2).'-'.substr($d,0,2));
}

echo '<input  id="date" name="date" size="10" value="'.$db->getOperDay().'" type="text"> ';
?>
</p><p><input value="Сохранить" type="submit"> 

</form></body></html>
