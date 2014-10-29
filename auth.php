<?php
session_start();
$form_begin='
	<html><head>
		<meta http-equiv="CONTENT-TYPE" content="text/html; charset=UTF-8">
		<title>Вход</title>
	</head><body>	
	 <FORM action="index.php" method="post">
		Логин <input id="login" name="login" size="10" type="text"><br/> Пароль <input id="passwd" name="passwd" size="10" type="password">
		<br/>
		<INPUT type="submit" value="Вход">
	</form>	';
$form_end='</body></html>';

if(!isset($_SESSION['login']) || ($_SESSION['login']===null)){
	if(!isset($_POST['login']) || ($_POST['login']===null)){
		echo $form_begin.$form_end;		
	}else{
		header('Location: index.php'/*||$_SERVER[REQUEST_URI]*/);
		$_SESSION['login']=$_POST['login'];
		$_SESSION['passwd']=$_POST['passwd'];
		include "localdb.php";
		try{
			$db = new localdb();
		}catch (Exception $e) {
			die($form_begin.$e->getMessage().$form_end);		
		}
	}
	exit;
}else{	
	include "localdb.php";
	try{
		$db = new localdb();
	} catch (Exception $e) {
		session_destroy ();
		die($form_begin.$e->getMessage().$form_end);		
	}
}	
?>
