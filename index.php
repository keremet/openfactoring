<?php
include "auth.php";
?>

<html><head>
	<meta http-equiv="CONTENT-TYPE" content="text/html; charset=UTF-8">
	<title>Договоры</title>
	<meta name="GENERATOR" content="OpenOffice.org 3.1  (Solaris x86)">
	<meta name="CREATED" content="0;0">
	<meta name="CHANGED" content="20130311;21321100">
	<style type="text/css">
	<!--
		@page { size: 21cm 29.7cm; margin: 2cm }
		P { margin-bottom: 0.21cm }
		PRE { font-family: "Liberation Mono", monospace; font-size: 10pt }
		A:link { color: #000080; so-language: zxx; text-decoration: underline }
		A:visited { color: #800000; so-language: zxx; text-decoration: underline }
	-->
	</style>
<?php
include "user_styles.php";
?>
</head><body dir="LTR" lang="ru-RU" link="#000080" vlink="#800000">
<?php
	if(localdb::connect()==null) die('Ошибка подключения к БД');
?>
<table style="page-break-before: always;" width="650" border="0" cellpadding="0" cellspacing="0">
<tr valign="TOP">
		<td>
			<pre style="text-align: left;"><a href="exit.php"><font face="Liberation Mono, monospace"><font size="2">Выход</font></font></a></pre>
		</td>
		<td>
			<pre style="text-align: left;"><a href="agr.php"><font face="Liberation Mono, monospace"><font size="2">Добавить договор</font></font></a></pre>
		</td>
		<td>
			<pre style="text-align: left;"><a href="close_inv.php"><font face="Liberation Mono, monospace"><font size="2">Закрыть списанные накладные</font></font></a></pre>
		</td>
		<td>
			<pre style="text-align: left;"><a href="test.php"><font face="Liberation Mono, monospace"><font size="2">Для тестирования</font></font></a></pre>
		</td>
		<td>
			<pre style="text-align: left;"><font face="Liberation Mono, monospace"><font size="2"><?php echo localdb::getOperDay(); ?></font></font></pre>
		</td>
	</tr>
</table>
<?php
	include "oft_table.php";
    //include "localdb.php";

	oftTable::init(((!isset($_GET['closed']))?'Действующие':'Закрытые').' договоры');
	$header=array('Номер','Клиент','Дата подписания', 'Действия');
	if (isset($_GET['closed']))
		$header=oftTable::addCol($header,'3','Дата закрытия');
	oftTable::header($header);
	foreach (((isset($_GET['closed']))?localdb::getClosedAgrs():localdb::getAgrs()) as $i => $value) {
		$row=array(
			'<a href="agr.php?agr_id='.$value['id'].'"><p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">'.$value['urid_id'].'</a>'.
			'<a href="registers.php?agr_id='.$value['id'].'"><p align="CENTER">Реестры</a>'.
			'<a href="repays.php?agr_id='.$value['id'].'"><p align="CENTER">Списания</a>'
, '<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">'.$value['client'].'</font></font></p>'
, '<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">'.$value['signed'].'</font></font></p>'
, '<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2"><a href="invoices_add.php?agr_id='.$value['id'].'"><p align="CENTER">Новый реестр</a>'.
  '<a href="reports.php?rep=repay_agr&agr_id='.$value['id'].'"><p align="CENTER">Списать накладные</a>'
);
		if (isset($_GET['closed']))
			$row=oftTable::addCol($row,'3','<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">'.$value['closed'].'</font></font></p>');
		oftTable::row($row);
	}
	oftTable::end();	
	localdb::disconnect();
?>


</p>
</body></html>
