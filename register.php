<?php
session_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html><head>
	<meta http-equiv="CONTENT-TYPE" content="text/html; charset=UTF-8">
	<title>Реестр</title>
	<style type="text/css">
	<!--
		@page { size: 21cm 29.7cm; margin: 2cm }
		p { margin: 0; padding: 5px; }
		PRE { font-family: "Liberation Mono", monospace; font-size: 10pt }
		A:link { color: #000080; so-language: zxx; text-decoration: underline }
		A:visited { color: #800000; so-language: zxx; text-decoration: underline }
	    td {	padding: 0px; /* Поля вокруг текста */   }
	-->
	</style>	
<?php
include "user_styles.php";
?>	
</head><body dir="LTR" lang="ru-RU" onkeypress="if(event.which==13)addInvoice();" onload="document.getElementById('invoice_num').focus();">
<?php
	include "oft_table.php";
	include "financial.php";
	include "localdb.php";
	try{
		$db = new localdb();
	}catch (Exception $e) {
		die($e->getMessage());		
	}	

	$reg = $db->getRegister($_GET['id']);	
?>

<table style="page-break-before: always;" width="462" border="0" cellpadding="0" cellspacing="0">
<tr valign="TOP">
		<td>
			<pre style="text-align: left;">
				<?php
				echo '<a href="registers.php?agr_id='.$reg['agr_id'].'">';
				?><font face="Liberation Mono, monospace"><font size="2">Реестры</font></font></a></pre>
		</td>		
		<td>
			<pre style="text-align: left;"><a href="exit.php"><font face="Liberation Mono, monospace"><font size="2">Выход</font></font></a></pre>
		</td>
		<td>
			<pre style="text-align: left;"><font face="Liberation Mono, monospace"><font size="2"><?php echo $db->getOperDay(); ?></font></font></pre>
		</td>		
	</tr>
</table>
<p align="center">
<?php	
	oftTable::init('Накладные реестра №'.$reg['num'].' от '.$reg['d'].' по договору '.$db->getAgrUridId($reg['agr_id']));
	oftTable::header(array('Номер','Дата','Дебитор','Сумма','НДС'));
	foreach($db->getRegisterInvoices($_GET['id']) as $i => $v){
		oftTable::row(array($v['urid_id'],$v['dat'],$v['deb_id'].' '.$v['deb_name'],'<p align="right">'.financial2str($v['sum']),'<p align="right">'.financial2str($v['nds'])));
	}	
	oftTable::end();	
?>

</body></html>
