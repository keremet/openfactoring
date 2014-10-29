<?php
session_start();

$agr_id = $_GET['agr_id'];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html><head>
	<meta http-equiv="CONTENT-TYPE" content="text/html; charset=UTF-8">
	<title>Списания по договору</title>
	
<?php
include "user_styles.php";
include "localdb.php";
try{
	$db = new localdb();
}catch (Exception $e) {
	die($e->getMessage());		
}
if($agr_id!=null){	
	$agr = $db->getAgr($agr_id);
}
?>

<script>
function deleteRepayJS(id) {
  if(confirm('Действительно отменить данное списание?')){
	document.getElementById('r_id').value = id;	
	document.getElementById('main_form').submit();
	return true;
  }
  return false;
}	
</script>
</head><body>
<?php
if(isset($_POST['r_id'])&&($_POST['r_id']>0)){
	echo $db->deleteRepay($_POST['r_id']);
}
	echo '<form  id="main_form" method="post" action="repays.php?agr_id='.$agr_id.'">'
?>

<input type="hidden" id="r_id" name="r_id" value="0">
<table style="page-break-before: always;" width="262" border="0" cellpadding="0" cellspacing="0">
<tr valign="TOP">
		<td>
			<pre style="text-align: left;"><a href="index.php"><font face="Liberation Mono, monospace"><font size="2">Договоры</font></font></a></pre>
		</td>		
		<td>
			<pre style="text-align: left;"><a href="exit.php"><font face="Liberation Mono, monospace"><font size="2">Выход</font></font></a></pre>
		</td>		
	</tr>
</table>

<?php	

if($agr_id!=null){
	include "oft_table.php";
	oftTable::init('Списания договора '.$agr['urid_id'],'tblDebitors');
	oftTable::header(array('Внутренний номер','Дата опердня','Фактическая дата создания','Действия'));
	foreach ($db->getRepays($agr_id) as $i => $value) {
		oftTable::row(array(
			 '<p align="RIGHT">'.$value['id'].' <a href="reports.php?rep=rasp_repay_agr&header_id='.$value['id'].'">Распоряжение</a>',
			 '<p align="RIGHT">'.$value['oper_date'],
			 '<p align="RIGHT">'.$value['created'],
			 '<input type="submit" value="Отменить"  onclick="return deleteRepayJS('.$value['id'].')">',
		)
		);
	}
	oftTable::end();
}
?>	
</form>
</body></html>
