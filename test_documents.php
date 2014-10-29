<?php
session_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html><head>


	<meta http-equiv="CONTENT-TYPE" content="text/html; charset=UTF-8">
	<title>Документы</title>
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
	include "oft_table.php";

	oftTable::init('Документы');	
	oftTable::header(array('Номер','Дата','Сумма','Дт','Кт','Назначение платежа'));
	foreach ($db->getDocuments() as $i => $value) {
		oftTable::row(array(
			'<p align="CENTER">'.$value['id']
, '<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">'.$value['value_date'].'</font></font></p>'
, '<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">'.($value['amount']/100).'</font></font></p>'
, '<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">'.$value['deb_acc_id'].'</font></font></p>'
, '<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">'.$value['cr_acc_id'].'</font></font></p>'
, '<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">'.$value['nazn_pl'].'</font></font></p>'
));
	}
	oftTable::end();
?>

</body></html>
