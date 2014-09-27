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

if($_SESSION['login']===null){
	if($_POST['login']===null){
		echo $form_begin.$form_end;		
	}else{
		header('Location: index.php'/*||$_SERVER[REQUEST_URI]*/);
		$_SESSION['login']=$_POST['login'];
		$_SESSION['passwd']=$_POST['passwd'];
		include "localdb.php";
		if(localdb::connect()!=null) 
			localdb::init_connection();
	}
	exit;
}else{	
	include "localdb.php";
	if(localdb::connect()==null){
		session_destroy ();
		die($form_begin.'Ошибка подключения к БД'.$form_end);
	}
}	
?>
