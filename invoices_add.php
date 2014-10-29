<?php
/*Лимит не синхронизируется при вводе каждой накладной. Предположение - несколько экономистов не могут одновременно заносить накладные для одной пары клиент-дебитор*/
session_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html><head>
	<meta http-equiv="CONTENT-TYPE" content="text/html; charset=UTF-8">
	<title>Создание нового реестра</title>
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
include "financial.php";
?>	
<script src="financial.js"></script>
<script src="dates.js"></script>
<script>
function processInvSum(sum, deb){	
	var x=document.getElementById("debitor");
	for(var i=0; i<x.length;i++){
		if(x[i].value.split("_")[0]==deb){
			var new_lim_rem = parseInt(x[i].value.split("_")[1])-sum;
			if(new_lim_rem<0){
				alert('Лимит превышен на ' + financial2str(-new_lim_rem));
				return false;
			}
			x[i].value = deb+"_"+new_lim_rem;
			break;
		}
	}
	if(x[x.selectedIndex].text!='Выберите дебитора')
		document.getElementById('pair_lim').value = financial2str(x.options[x.selectedIndex].value.split('_')[1]);

	var arr_err = []
    document.getElementById("all_invoices_sum").value=financial2str(getFinancial(document.getElementById("all_invoices_sum").value, arr_err) + sum);	
	return true;
}
function deleteRow(el) {
  // while there are parents, keep going until reach TR  		
  while (el.parentNode && el.tagName.toLowerCase() != 'tr') {
    el = el.parentNode;
  }

  // If el has a parentNode it must be a TR, so delete it
  if (el.parentNode) {
    el.parentNode.removeChild(el); 
    var arr_err = []   
    processInvSum(-getFinancial(el.cells[3].innerHTML, arr_err),parseInt(el.cells[2].innerHTML.split(" ")[0]));
  }
}

function checkAndAddInvoice(num, datestr, deb, invoice_sum, nds)
{
	check_dat(datestr,arr_err)
	if(arr_err.length>0){
		alert("Ошибка в дате: "+arr_err[0])
		document.getElementById("invoice_date").focus()
		document.getElementById("invoice_date").select()
		return false
	}	

	var v_invoice_date = datestr.substring(0,2)+'-'+datestr.substring(2,4)+'-'+datestr.substring(4);	
	
	var table=document.getElementById("tblNewInvoices");
	for(var i=1;i<table.rows.length;i++){
		if((table.rows[i].cells[0].innerHTML == num)
			&&(table.rows[i].cells[1].innerHTML == v_invoice_date)
			&&(table.rows[i].cells[2].innerHTML == deb)
		){
			alert('Попытка ввести накладную №'+num+' повторно! Номер, дата и дебитор не могут совпадать.'); 
			return false;
		}
	}
	if(processInvSum(invoice_sum, deb.split(" ")[0])==false)
		return false;
	
	var row=table.insertRow(table.rows.length);
	var cell=row.insertCell(0); cell.innerHTML=num;
	var cell=row.insertCell(1); cell.innerHTML=v_invoice_date;
	var cell=row.insertCell(2); cell.innerHTML=deb;
	var cell=row.insertCell(3);	cell.align="right"; cell.innerHTML=financial2str(invoice_sum);
	var cell=row.insertCell(4);	cell.align="right"; cell.innerHTML=financial2str(nds);	
	var cell=row.insertCell(5);	cell.innerHTML="<p padding=0><input value=\"Удалить\" type=\"button\" onclick=\"deleteRow(this);\">";
	return true;
}

function addInvoice()
{
	var v_invoice_num = document.getElementById("invoice_num").value;
	if(v_invoice_num==''){
		alert('Введите номер накладной');
		document.getElementById("invoice_num").focus();
		document.getElementById("invoice_num").select();
		return false;
	}
	
	var arr_err = []
	
	var datestr = form_dat(document.getElementById("invoice_date").value,arr_err)
	if(arr_err.length>0){
		alert("Ошибка в дате: "+arr_err[0])
		document.getElementById("invoice_date").focus()
		document.getElementById("invoice_date").select()
		return false
	}	

	var v_invoice_sum = getFinancial(document.getElementById('invoice_sum').value, arr_err)
	if(arr_err.length>0){
		alert("Ошибка в сумме: "+arr_err[0])
		document.getElementById("invoice_sum").focus()
		document.getElementById("invoice_sum").select()
		return false
	}	

	var v_nds = getFinancial(document.getElementById('NDS').value, arr_err)
	if(arr_err.length>0){
		alert("Ошибка в НДС: "+arr_err[0])
		document.getElementById("NDS").focus()
		document.getElementById("NDS").select()
		return false
	}	

	var deblist = document.getElementById("debitor");
	if(deblist[deblist.selectedIndex].text=='Выберите дебитора'){
		alert('Выберите дебитора'); 
		deblist.focus();
		deblist.select();
		return false;
	}

	var v_debitor = deblist[deblist.selectedIndex].value.split("_")[0]+" "+deblist[deblist.selectedIndex].text;

	var r = checkAndAddInvoice(v_invoice_num, datestr, v_debitor, v_invoice_sum, v_nds);
	if(r){
		document.getElementById("invoice_num").focus();
		document.getElementById("invoice_num").select();
		document.getElementById('invoice_sum').value='';
		document.getElementById('NDS').value='';		
	}
	return r;
}
function calcNDS(){
	document.getElementById("NDS").value=Math.round(document.getElementById("invoice_sum").value*100*18/118)/100;
}

function saveInvoices(){
	var arr_invoices = [];
	
	var table=document.getElementById("tblNewInvoices");	
	for(var i=1;i<table.rows.length;i++){
		var v_invoice_num = table.rows[i].cells[0].innerHTML;
		var v_invoice_date = table.rows[i].cells[1].innerHTML;
		var v_deb_id = table.rows[i].cells[2].innerHTML.split(" ")[0];
		var arr_err = []
		var v_sum = getFinancial(table.rows[i].cells[3].innerHTML, arr_err);
		var v_nds = getFinancial(table.rows[i].cells[4].innerHTML, arr_err);
		arr_invoices.push({'num':v_invoice_num, 'dat':v_invoice_date, 'deb':v_deb_id, 'sum':v_sum, 'nds':v_nds});
	}	
	//alert('saveInvoices'+JSON.stringify(arr_invoices));
	document.getElementById("f_si_invoices").value = JSON.stringify(arr_invoices);
}
</script>	
</head><body dir="LTR" lang="ru-RU" onkeypress="if(event.which==13)addInvoice();" onload="document.getElementById('invoice_num').focus();">
<?php
	include "localdb.php";
	try{
		$db = new localdb();
	}catch (Exception $e) {
		die($e->getMessage());		
	}
?>

<table style="page-break-before: always;" width="262" border="0" cellpadding="0" cellspacing="0">
<tr valign="TOP">
		<td>
			<pre style="text-align: left;"><a href="index.php"><font face="Liberation Mono, monospace"><font size="2">Договоры</font></font></a></pre>
		</td>		
		<td>
			<pre style="text-align: left;"><a href="exit.php"><font face="Liberation Mono, monospace"><font size="2">Выход</font></font></a></pre>
		</td>
		<td>
			<pre style="text-align: left;"><font face="Liberation Mono, monospace"><font size="2"><?php echo $db->getOperDay(); ?></font></font></pre>
		</td>		
	</tr>
</table>
<p>Номер накладной<input id="invoice_num" size="16" type="text" maxlength="16"> Дата<input id="invoice_date" size="6" type="text" maxlength="6" onkeyup="return proverka_dat(this);" onchange="return proverka_dat(this);" >
Сумма накладной<input id="invoice_sum" size="10"  maxlength="10" type="text" onkeyup="return proverka_fin(this);" onchange="return proverka_fin(this);">  НДС<input id="NDS" size="10"  maxlength="10" type="text" onfocus="calcNDS();" onkeyup="return proverka_fin(this);" onchange="return proverka_fin(this);">
<p>Дебитор <select id="debitor" onchange="document.getElementById('pair_lim').value = financial2str(this.options[this.selectedIndex].value.split('_')[1])">
<?php
	$debs = $db->getDebitors($_GET['agr_id']);
	if(count($debs)>1)
		echo "<option selected disabled>Выберите дебитора</option>";
	foreach ($debs as $i => $value) {
		$debAvailLim = $value['lim'] - $db->getDebUsedLimit($_GET['agr_id'], $value['cust_id']);
		echo  "<option value=\"".$value['cust_id']."_".$debAvailLim."\">".$value['NAME_CYR']."</option>";
	}
?>
   </select> Лимит
<?php
	echo '<input id="pair_lim"  size="10" type="text" '.((count($debs)==1)?('value="'.financial2str($value['lim']).'" '):'').'readonly>';
?>  
    Сумма реестра<input id="all_invoices_sum"  size="10" type="text" value="0" readonly> <input value="Добавить накладную" type="submit"  onclick="addInvoice()"></p>
<?php  
echo "<form enctype=\"multipart/form-data\" action=\"".$_SERVER['REQUEST_URI']."\" method=\"POST\">";
 ?>
    <!-- Поле MAX_FILE_SIZE должно быть указано до поля загрузки файла -->
    <input type="hidden" name="MAX_FILE_SIZE" value="300000" />
    <!-- Название элемента input определяет имя в массиве $_FILES -->
    Файл с реестром: <input name="userfile" type="file" />
    <input type="submit" value="Загрузить" />
</form>
<p align="center"><font size="4"><b>	
<?php
	echo "Новые накладные договорa ".$db->getAgrUridId($_GET['agr_id']);
?>
</b></font></p></pre>
<table id="tblNewInvoices" width="100%" border="1" bordercolor="#000000" cellpadding="4" cellspacing="0">
	<tr valign="TOP">
		<td width="11%">
			<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">Номер</font></font></p>
		</td>
		<td width="11%">
			<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">Дата</font></font></p>
		</td>
		<td width="55%">
			<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">Дебитор</font></font></p>
		</td>
		<td width="11%">
			<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">Сумма</font></font></p>
		</td>
		<td width="11%">
			<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">НДС</font></font></p>
		</td>
		<td width="11%">
			<p align="CENTER"><font face="Liberation Mono, monospace"><font size="2">Действия</font></font></p>
		</td>
	</tr>		
</table>
<script>
	var arr_err = [];
	var inv_sum;
	var inv_nds;
<?php
if($_GET['agr_id']=='1')
 if (isset($_FILES['userfile']))	
	if($_FILES['userfile']['tmp_name']!==null){
		$fp = fopen($_FILES['userfile']['tmp_name'], 'r');
		if ($fp) {
			while (!feof($fp))
			{
				$line = fgets($fp, 9999);				
				$line = iconv ( "CP1251" , "UTF-8" , substr($line,0,-1)); // Удалить последний символ - перевод строки
				$line_arr = explode(';', $line, 9);
				if(count($line_arr)!=9)
					continue;
				$deb_num = "";
				if($line_arr[1]=="Второй дебитор первого клиента"){
					$deb_num = "5 Второй дебитор первого клиента";
				}else
					continue;
				$date_str = substr($line_arr[5],0,2).substr($line_arr[5],3,2).'20'.substr($line_arr[5],6,2);
				$inv_sum = str_replace(',','.',$line_arr[6]);
				$inv_nds = str_replace(',','.',$line_arr[7]);
				
				//oftTable::row(array($line_arr[0],$line_arr[1],$line_arr[2],$line_arr[3],$line_arr[4], '<p padding=0><input value="Удалить" type="button" onclick="deleteRow(this);">'));
				echo "
				inv_sum = getFinancial('$inv_sum',arr_err);
				inv_nds = getFinancial('$inv_nds',arr_err);
				checkAndAddInvoice('".$line_arr[4]."', '$date_str', '$deb_num', inv_sum, inv_nds);";
			}
		}
		else 
			echo "Ошибка при открытии файла";	
		fclose($fp);
	}
	if (isset($_POST['f_si_invoices'])){
		$db->deleteNewInvoices($_GET['agr_id']);
		foreach(json_decode($_POST['f_si_invoices'],true) as $i => $v){
			$err = $db->insertInvoice($_GET['agr_id'], $v['num'], $v['dat'], $v['deb'],$v['sum'], $v['nds']);
			if($err!==null){
				echo 'alert("Не была сохранена накладная '.$v['num'].': '.$err.'");';
			}		
		}
	}
	foreach($db->getNewInvoices($_GET['agr_id']) as $i => $v){
		echo "checkAndAddInvoice('".$v['urid_id']."', '".$v['dat']."', '".$v['deb_id'].' '.$v['deb_name']."', ".$v['sum'].", ".$v['nds'].");";
	}
	
?>
</script>
<?php
echo '<form action="invoices_add.php?agr_id='.$_GET['agr_id'].'" method="post">';
?>
<p align="RIGHT"><input value="Сохранить накладные" type="submit"  onclick="saveInvoices()"></p>
<input type="hidden" id="f_si_invoices" name="f_si_invoices">
</form>
<?php
echo '<a href="reports.php?rep=new_registry&agr_id='.$_GET['agr_id'].'">Постановка накладных</a>';
?>
</body></html>
