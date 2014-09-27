<?php
session_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html><head>


	<meta http-equiv="CONTENT-TYPE" content="text/html; charset=UTF-8">
	<title>Счета</title>
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
	if(localdb::connect()==null) die('Ошибка подключения к БД');	
	include "oft_table.php";

	oftTable::init('Счета');	
	oftTable::header(array('Номер','Остаток','Дата открытия','Дата закрытия','Клиент','Название'));
	foreach (localdb::getAccounts() as $i => $value) {
		oftTable::row(array(
			'<p align="CENTER">'.$value['id']
, '<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">'.(localdb::getAccountAmount($value['id'])/100).'</font></font></p>'
, '<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">'.$value['opened'].'</font></font></p>'
, '<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">'.$value['closed'].'</font></font></p>'
, '<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">'.$value['name_cyr'].'</font></font></p>'
, '<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">'.$value['name'].'</font></font></p>'
));
	}
	oftTable::end();	
	localdb::disconnect();
?>

</body></html>
