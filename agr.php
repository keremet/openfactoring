<?php
session_start();
if(
	isset($_POST['urid_id']) && 
	isset($_POST['signed']) && 
	isset($_POST['cust_id']) && 
	isset($_POST['acc_cur']) && 
	isset($_POST['acc47401']) && 
	isset($_POST['comfin']) && 
	isset($_POST['penya_cl']) && 
	isset($_POST['next_reg_num'])
){	
	include "localdb.php";
	try{
		$db = new localdb();
	}catch (Exception $e) {
		die($e->getMessage());		
	}
	if($_POST['agr_id']=='')
		$db->insertAgr($_POST['urid_id'], $_POST['signed_cor'], $_POST['cust_id'], $_POST['next_reg_num'], $_POST['acc_cur'], $_POST['acc47401'], $_POST['comfin'], $_POST['penya_cl'], $_POST['fct_type']);		
	else{
		if($_POST['oper_type']=='delete')
			$db->deleteAgr($_POST['agr_id']);
		else if($_POST['oper_type']=='delete_deb')
			$db->deleteDebitor($_POST['agr_id'], $_POST['deb_cust_id']);
		else if($_POST['oper_type']=='update'){
			$db->updateAgr($_POST['agr_id'], $_POST['urid_id'], $_POST['signed_cor'], $_POST['cust_id'], $_POST['next_reg_num'], $_POST['acc_cur'], $_POST['acc47401'], $_POST['comfin'], $_POST['penya_cl']);
			if (isset($_POST['arr_debs'])){
				$debs = $db->getDebitors($_POST['agr_id']);
				foreach(json_decode($_POST['arr_debs'],true) as $i => $v){
					$f_found=0;
					foreach($debs as $i_debs =>$v_debs){
						if($v_debs['cust_id']==$v['cust_id']){
							if(
								($v_debs['lim']!=$v['lim'])||
								($v_debs['acc61212']!=$v['acc61212'])||
								($v_debs['deliv_agr_id']!=$v['deliv_agr_id'])||
								($v_debs['deliv_agr_date']!=substr($v['deliv_agr_date'],4,4).'-'.substr($v['deliv_agr_date'],2,2).'-'.substr($v['deliv_agr_date'],0,2))||
								($v_debs['com1']!=$v['com1'])||
								($v_debs['srok_otsr']!=$v['srok_otsr'])||
								($v_debs['penya']!=$v['penya'])||
								($v_debs['fct_type']!=$v['fct_type'])
							){
								$db->updateDebitor($_POST['agr_id'], $v['cust_id'], $v['lim'], $v['acc61212'], $v['deliv_agr_id'], $v['deliv_agr_date'], 
			$v['com1'], $v['srok_otsr'], $v['penya'], $v['fct_type']);
							}
							$f_found=1;
							break;							
						}
					}
					if($f_found==0){
						$db->insertDebitor($_POST['agr_id'], $v['cust_id'], $v['lim'], $v['acc61212'], $v['deliv_agr_id'], $v['deliv_agr_date'], 
			$v['com1'], $v['srok_otsr'], $v['penya'], $v['fct_type']);
					}
				}
			}
		}
	}

//Если все успешно, то переход. Вынесено в конец, чтобы отобразить ошибку в случае ее возникновения
	if(isset($_POST['oper_type'])&&(($_POST['oper_type']=='update') || ($_POST['oper_type']=='delete_deb'))){
		header('Location: agr.php?agr_id='.$_POST['agr_id']);
	}else{
		header('Location: index.php');
	}
	exit;
}
$agr_id = isset($_GET['agr_id'])?$_GET['agr_id']:null;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html><head>
	<meta http-equiv="CONTENT-TYPE" content="text/html; charset=UTF-8">
	<title>Параметры договора</title>
<script src="financial.js"></script>	
<script src="dates.js"></script>	
<script>
function deleteRow(el) {
  // while there are parents, keep going until reach TR  		
  while (el.parentNode && el.tagName.toLowerCase() != 'tr') {
    el = el.parentNode;
  }
  
  if(el.getElementsByTagName('td')[1].innerHTML==''){
	  // If el has a parentNode it must be a TR, so delete it
	  if (el.parentNode) {
		el.parentNode.removeChild(el);
	  }
	  return;
  }
  if(confirm('Действительно удалить?')){
	document.getElementById('oper_type').value='delete_deb';
	document.getElementById('deb_cust_id').value=el.getElementsByTagName('td')[0].getElementsByTagName('input')[0].value;
	document.getElementById('main_form').submit();
  }
}

function addDebitor()
{
	var table=document.getElementById("tblDebitors");
	var row=table.insertRow(table.rows.length);
	var cell=row.insertCell(0); cell.innerHTML='<p align="LEFT"><input type="text" onkeyup="return proverka_dat(this);" onchange="return proverka_dat(this);" size=10>';
	var cell=row.insertCell(1); cell.innerHTML='';
	var cell=row.insertCell(2); cell.innerHTML='<p align="RIGHT"><input type="text" size=10 onkeyup="return proverka_fin(this);" onchange="return proverka_fin(this);">';
	var cell=row.insertCell(3); cell.innerHTML= '<p align="RIGHT"><input type="text" size=20 maxlength="20" onkeyup="return proverka_dat(this);" onchange="return proverka_dat(this);">';
	var cell=row.insertCell(4); cell.innerHTML='<p align="LEFT"><input type="text"  size=10>';		
	var cell=row.insertCell(5); cell.innerHTML='<p align="RIGHT"><input type="text" size=6 maxlength="6" onkeyup="return proverka_dat(this);" onchange="return proverka_dat(this);">';		
	var cell=row.insertCell(6); cell.innerHTML='<p align="RIGHT"><input type="text" size=5 onkeyup="return proverka_fin(this);" onchange="return proverka_fin(this);">';		
	var cell=row.insertCell(7); cell.innerHTML='<p align="RIGHT"><input type="text" size=2 onkeyup="return proverka_dat(this);" onchange="return proverka_dat(this);">';	
	var cell=row.insertCell(8); cell.innerHTML='<p align="RIGHT"><input type="text" size=5 onkeyup="return proverka_fin(this);" onchange="return proverka_fin(this);">';	
	var cell=row.insertCell(9); cell.innerHTML='<select><option value="1">Открытый с регрессом (пени только с клиента)</option>;<option value="2">Открытый с регрессом</option>;<option value="3">Закрытый</option>; </select>';
	var cell=row.insertCell(10); cell.innerHTML='<p align="RIGHT"><input value="Удалить" type="button"  onclick="deleteRow(this);">';
}

function checkDelAgr(){
	if(confirm('Действительно удалить договор?')){
		document.getElementById('oper_type').value='delete';
		return true;
	}
	return false;
}

function saveAgr()
{
	var v_urid_id = document.getElementById("urid_id").value;
	if(v_urid_id==''){
		alert('Введите юридический номер договора');
		document.getElementById("urid_id").focus();
		document.getElementById("urid_id").select();
		return false;
	}
	
	var arr_err = []
	
	document.getElementById("signed_cor").value = form_and_check_std_dat(document.getElementById("signed").value, arr_err);
	if(arr_err.length>0){
		alert("Ошибка в дате подписания: "+arr_err[0])
		document.getElementById("signed").focus();
		document.getElementById("signed").select();
		return false;
	}
	
	var v_cust_id = document.getElementById("cust_id").value;
	if(v_cust_id==''){
		alert('Введите номер клиента');
		document.getElementById("cust_id").focus();
		document.getElementById("cust_id").select();
		return false;
	}

	var v_next_reg_num = document.getElementById("next_reg_num").value;
	if(v_next_reg_num==''){
		alert('Введите следующий номер реестра');
		document.getElementById("next_reg_num").focus();
		document.getElementById("next_reg_num").select();
		return false;
	}
	
	
	var v_acc_cur = document.getElementById("acc_cur").value;
	if(v_acc_cur.length!=20){
		alert('Номер расчетного счета должен содержать 20 цифр');
		document.getElementById("acc_cur").focus();
		document.getElementById("acc_cur").select();
		return false;
	}	

	var v_acc47401 = document.getElementById("acc47401").value;
	if(v_acc47401.length!=20){
		alert('Номер счета 47401 должен содержать 20 цифр');
		document.getElementById("acc47401").focus();
		document.getElementById("acc47401").select();
		return false;
	}	

	var v_comfin = getFinancial(document.getElementById('comfin').value, arr_err)
	if(arr_err.length>0){
		alert("Ошибка в комиссии за финансирование: "+arr_err[0])
		document.getElementById("comfin").focus()
		document.getElementById("comfin").select()
		return false
	}	
	
	var arr_debs = [];
	var table=document.getElementById("tblDebitors");	
	for(var i=1;i<table.rows.length;i++){
		var cust_id = table.rows[i].cells[0].getElementsByTagName('input')[0].value;
		var arr_err = [];
		var lim_input = table.rows[i].cells[2].getElementsByTagName('input')[0];
		var lim = getFinancial(lim_input.value, arr_err);
		if(arr_err.length>0){
			alert("Ошибка в лимите: "+arr_err[0])
			lim_input.focus()
			lim_input.select()
			return false
		}
		var acc61212 = table.rows[i].cells[3].getElementsByTagName('input')[0].value;
		var deliv_agr_id = table.rows[i].cells[4].getElementsByTagName('input')[0].value;
		var deliv_agr_date_input = table.rows[i].cells[5].getElementsByTagName('input')[0];
		var deliv_agr_date = form_and_check_std_dat(deliv_agr_date_input.value, arr_err);
		if(arr_err.length>0){
			alert("Ошибка в дате договора поставки: "+arr_err[0])
			deliv_agr_date_input.focus();
			deliv_agr_date_input.select();
			return false;
		}		
		
		
		var com1 = table.rows[i].cells[6].getElementsByTagName('input')[0].value;
		var srok_otsr = table.rows[i].cells[7].getElementsByTagName('input')[0].value;
		var penya = table.rows[i].cells[8].getElementsByTagName('input')[0].value;
		var fct_type = table.rows[i].cells[9].getElementsByTagName('select')[0].value;
		
		arr_debs.push({
			'cust_id':cust_id,
			'lim':lim, 
			'acc61212':acc61212, 
			'deliv_agr_id':deliv_agr_id, 
			'deliv_agr_date':deliv_agr_date, 
			'com1':com1, 
			'srok_otsr':srok_otsr,
			'penya':penya,
			'fct_type':fct_type
		});
	}	
	//alert('debs='+JSON.stringify(arr_debs));
	document.getElementById("arr_debs").value = JSON.stringify(arr_debs);	
	return true;
}

</script>	
<?php
include "user_styles.php";
?>
</head><body>


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
<form id="main_form" action="agr.php" method="post">
<table border="0" cellpadding="0" cellspacing="2">
<?php	
include "localdb.php";
try{
	$db = new localdb();
}catch (Exception $e) {
	die($e->getMessage());		
}
if($agr_id!=null){	
	$agr = $db->getAgr($agr_id);
}
echo '<b>'.(($agr['fct_type']==1)?"Реверсивный":"Обычный").'<b><tr><td>Юридический номер<td><input id="urid_id" name="urid_id" size="16" type="text" maxlength="16" value="'.(isset($agr['urid_id'])?$agr['urid_id']:($db->getNextAgrNumber())).'">
<tr><td>Дата подписания<td><input id="signed" name="signed"  size="6" type="text" maxlength="6" onkeyup="return proverka_dat(this);" onchange="return proverka_dat(this);"'.(isset($agr['signed'])?' value="'.$agr['signed'].'"':'').'> '.(isset($agr['signed_h'])?$agr['signed_h']:'').'
<tr><td>Номер клиента<td><input id="cust_id"  name="cust_id" size="10" type="text" maxlength="10" onkeyup="return proverka_dat(this);" onchange="return proverka_dat(this);"'.(isset($agr['cust_id'])?' value="'.$agr['cust_id'].'"':'').'>
<tr><td>Следующий номер реестра<td><input id="next_reg_num"  name="next_reg_num" size="10" type="text" maxlength="10" onkeyup="return proverka_dat(this);" onchange="return proverka_dat(this);" value="'.(isset($agr['next_reg_num'])?$agr['next_reg_num']:'1').'">
<tr><td>Расчетный счет<td><input id="acc_cur"  name="acc_cur" size="20" type="text" maxlength="20" onkeyup="return proverka_dat(this);" onchange="return proverka_dat(this);"'.(isset($agr['acc_cur'])?' value="'.$agr['acc_cur'].'"':'').'>
<tr><td>Счет 47401<td><input id="acc47401"  name="acc47401" size="20" type="text" maxlength="20" onkeyup="return proverka_dat(this);" onchange="return proverka_dat(this);"'.(isset($agr['acc47401'])?' value="'.$agr['acc47401'].'"':'').'>
<tr><td>Комиссия за финансирование<td><input id="comfin"  name="comfin" size="10"  maxlength="10" type="text" onkeyup="return proverka_fin(this);" onchange="return proverka_fin(this);"'.(isset($agr['comfin'])?' value="'.$agr['comfin'].'"':'').'>
<tr><td>Пеня с клиента<td><input id="penya_cl"  name="penya_cl" size="10"  maxlength="10" type="text" onkeyup="return proverka_fin(this);" onchange="return proverka_fin(this);"'.(isset($agr['penya_cl'])?' value="'.$agr['penya_cl'].'"':'').'>';
if($agr_id==null)
	echo '<tr><td>Тип договора<td><select name="fct_type" id="fct_type"><option value="0">Обычный</option>;<option value="1">Реверсивный</option>; </select>';
echo '</table>
<br><input value="'.(($agr_id==null)?"Создать договор":"Сохранить").'" type="submit"  onclick="return saveAgr();">';
if($agr_id!=null){	
?>
	<input type="hidden" id="oper_type" name="oper_type" value="update">
	<input type="hidden" id="deb_cust_id" name="deb_cust_id" value="0">
	<input value="Удалить договор" type="submit"  onclick="return checkDelAgr();">
<?php	
}
?>

<input type="hidden" id="signed_cor" name="signed_cor">
<input type="hidden" id="arr_debs" name="arr_debs">
<?php echo '<input type="hidden" id="agr_id" name="agr_id" value="'.$agr_id.'">'; ?>
</form>
<?php 
include "financial.php";
if(($agr_id!=null)&&($agr['fct_type']!=1)){
	include "oft_table.php";
	oftTable::init('Дебиторы','tblDebitors');
	oftTable::header(array('Код клиента','Название клиента','Лимит','61212','Договор поставки','Дата договора поставки', 'Единовременная комиссия','Срок отсрочки','Пеня','Тип факторинга','Действия'));
	foreach ($db->getDebitors($agr_id) as $i => $value) {
		oftTable::row(array(
			 '<p align="LEFT"><input type="text" size=10  onkeyup="return proverka_dat(this);" onchange="return proverka_dat(this);" value="'.$value['cust_id'].'">'
			,'<p align="LEFT">'.$value['NAME_CYR']
, '<p align="RIGHT"><input type="text" size=10 onkeyup="return proverka_fin(this);" onchange="return proverka_fin(this);" value="'.financial2str($value['lim']).'">'
, '<p align="RIGHT"><input type="text" size=20 maxlength="20"  onkeyup="return proverka_dat(this);" onchange="return proverka_dat(this);" value="'.$value['acc61212'].'">'
, '<p align="LEFT"><input type="text"  size=10 value="'.$value['deliv_agr_id'].'">'
, '<p align="RIGHT"><input type="text" size=10 maxlength="10"  onkeyup="return proverka_dat(this);" onchange="return proverka_dat(this);" value="'.$value['deliv_agr_date'].'">'.$value['deliv_agr_date_h']
, '<p align="RIGHT"><input type="text" size=5 onkeyup="return proverka_fin(this);" onchange="return proverka_fin(this);" value="'.$value['com1'].'">'
, '<p align="RIGHT"><input type="text" size=2 onkeyup="return proverka_dat(this);" onchange="return proverka_dat(this);" value="'.$value['srok_otsr'].'">'
, '<p align="RIGHT"><input type="text" size=5 onkeyup="return proverka_fin(this);" onchange="return proverka_fin(this);" value="'.$value['penya'].'">'
, '<p align="RIGHT"><select><option value="1" '.(($value['fct_type']==1)?"selected":"").'>Открытый с регрессом (пени только с клиента)</option>;<option value="2" '.(($value['fct_type']==2)?"selected":"").'>Открытый с регрессом</option>;<option value="3" '.(($value['fct_type']==3)?"selected":"").'>Закрытый</option>; </select>'
, '<p align="RIGHT"><input value="Удалить" type="button"  onclick="deleteRow(this);">'
		)
		);
	}
	oftTable::end();	
?>
	<p align="LEFT"><input value="Добавить дебитора" type="button"  onclick="addDebitor();">
<?php
}
?>	
</body></html>
