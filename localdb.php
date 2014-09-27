<?php
class localdb{
	const acc60311 = '60311810000000000000';
	const acc70601 = '70601810000000000000';
	const acc60309 = '60309810000000000000';
	const acc99999 = '99999810500000000000';
	
	function connect(){
/*		if (($_SESSION['login']=='test') &&($_SESSION['passwd']=='1'))
			return 1;
		return null;*/
		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, "select styles
				from users
				where login='".$_SESSION['login']."' and passwd='".$_SESSION['passwd']."'");
		if ($entry = sqlite_fetch_array($result, SQLITE_ASSOC)) {
			$_SESSION['user_styles'] = $entry['styles'];
			return 1;
		}
		return	null;			
	}
	function init_connection(){

	}
	function disconnect(){
		
	}
	function getAgrUridId($agr_id){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, "select urid_id
				from agr
				where id=$agr_id");
		if ($entry = sqlite_fetch_array($result, SQLITE_ASSOC)) {	
			return $entry['urid_id'];
		}
		return	null;		
/*
		$ret="";
		$stmt = ociparse($_SESSION['orcl_con'],"
			select AGR_ID
				from FCT_AGREEMENT
				where AGR_NO=$agr_no
		");			
		ociexecute($stmt,OCI_DEFAULT);			
		if (ocifetch($stmt)){
			$ret = ociresult($stmt,"AGR_ID");
		}
		return $ret;
*/
//		return '11-2013ф';
	}
	function getOperDay(){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, "select strftime('%d-%m-%Y', oper_day) as OD
				from users
				where login='".$_SESSION['login']."' and passwd='".$_SESSION['passwd']."'"
		);
		if ($entry = sqlite_fetch_array($result, SQLITE_ASSOC)) {	
			return $entry['OD'];
		}
		return	null;		
	}
	function getOperDayJ(){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, "select oper_day
				from users
				where login='".$_SESSION['login']."' and passwd='".$_SESSION['passwd']."'"
		);
		if ($entry = sqlite_fetch_array($result, SQLITE_ASSOC)) {	
			return $entry['oper_day'];
		}
		return	null;		
	}
	function setOperDay($str_dat){
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, "update users set oper_day=julianday('$str_dat') where login='".$_SESSION['login']."' and passwd='".$_SESSION['passwd']."'");
		//~ $result = sqlite_query($db, "update settings set oper_day=julianday('2015-01-08')");
	}
	function insertRepayHeader($agr_id){
		$operDayJ = localdb::getOperDayJ();
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$error="1";
		$result = sqlite_exec($db, "insert into repay_header(agr_id, oper_date, created)values($agr_id, $operDayJ, julianday(date('now')))", $error);
		if (!$result) {
			exit("Ошибка в запросе: '$error'");
		}	
		return sqlite_last_insert_rowid($db);
	}
	function insertRepayEntry($header_id, $invoice_id, $doc_47803, $doc_70601, $doc_60309, $doc_rs, $doc_91418){
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$error="1";
		$result = sqlite_exec($db, "insert into repay_entry(header_id, invoice_id, doc_47803, doc_70601, doc_60309, doc_rs, doc_91418)".
		"values($header_id, $invoice_id, $doc_47803, $doc_70601, $doc_60309, $doc_rs, $doc_91418)", $error);
		if (!$result) {
			exit("Ошибка в запросе: '$error'");
		}
	}	
	function createAccount($balans, $cust_id, $name){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$error="1";
		$result = sqlite_query($db, "select ifnull(substr(max(id),9,12), 0)+1 as ACC from accounts where id like '$balans%'");
		if ($entry = sqlite_fetch_array($result, SQLITE_ASSOC)) {	
			$num=$entry['ACC'];
			while(strlen($balans.'810'.$num)<20)
				$num='0'.$num;
		}		
		
		$result = sqlite_exec($db, "insert into accounts(id, opened, cust_id, name)
		values('".$balans.'810'.$num."', julianday(date('now')), ".$cust_id.", '$name')", $error);
		if (!$result) {
			exit("Ошибка в запросе: '$error'");
		}
		return $balans.'810'.$num;
	}	
	function getAccounts(){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, "select accounts.id as id, strftime('%d-%m-%Y', opened) as opened, strftime('%d-%m-%Y', closed) as closed, name_cyr, name 
		from accounts
		left join customers on customers.id=accounts.cust_id");
		return sqlite_fetch_all($result, SQLITE_ASSOC);
	}
	function getAccountAmount($id){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, "select sum(a.crob)-sum(a.debob) as r from (
			select sum(amount) as debob, 0 as crob from documents where deb_acc_id='$id'
			union
			select 0 as debob, sum(amount) as crob from documents where cr_acc_id='$id'
		)a");
		if ($entry = sqlite_fetch_array($result, SQLITE_ASSOC)) {	
			return $entry['r'];
		}
		return	null;	
	}
	function getDebUsedLimit($agr_id, $debitor_cust_id){
		$r = 0;		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, "select acc91418 from invoice where payed is null and debitor_cust_id=$debitor_cust_id and agr_id=$agr_id");
		$inv = sqlite_fetch_all($result, SQLITE_ASSOC);
		foreach($inv as $v){
			$r += localdb::getAccountAmount($v['acc91418']);
		}
		return -$r; //так как 91418 - активный
	}	
	function getDebOb($acc_id){
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, "select sum(amount) as debob from documents where deb_acc_id='$acc_id'");
		if ($entry = sqlite_fetch_array($result, SQLITE_ASSOC)) {	
			return $entry['debob'];
		}
		return	null;			
	}
	function insertDocument($agr_id, $class_op, $doc_type, $amount, $deb_acc_id, $cr_acc_id, $nazn_pl){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$error="1";
		$result = sqlite_exec($db, "insert into documents(agr_id, class_op, doc_type, value_date, amount, deb_acc_id, cr_acc_id, nazn_pl)
		select '$agr_id', '$class_op', '$doc_type', oper_day, $amount, '$deb_acc_id', '$cr_acc_id', '$nazn_pl' from users where login='".$_SESSION['login']."' and passwd='".$_SESSION['passwd']."'", $error);
		if (!$result) {
			exit("Ошибка в запросе: '$error'");
		}	
		return sqlite_last_insert_rowid($db);		
	}
	function getDocuments(){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, "select id, amount, deb_acc_id, cr_acc_id, nazn_pl, strftime('%d-%m-%Y', value_date) as value_date	from documents");
		return sqlite_fetch_all($result, SQLITE_ASSOC);
	}
	function getDebInv4Repay($agr_id, $debitor_cust_id){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		//Накладные по дебитору договора, которые поставлены и не оплачены (остаток по счетам)
		$result = sqlite_query($db, 
		"select invoice.id as id, invoice.urid_id as urid_id,  invoice.register_id , invoice.inv_date, invoice.date_otsr_agr, invoice.acc47803 as acc47803, invoice.acc91418 as acc91418, register.d register_d, count(documents.id) as cnt_doc from invoice 
left join documents on documents.cr_acc_id=invoice.acc91418
left join register on register.id=invoice.register_id
where invoice.payed is null and invoice.date_otsr_agr is not null and invoice.agr_id=$agr_id and invoice.debitor_cust_id=$debitor_cust_id
group by invoice.id, invoice.urid_id, invoice.register_id, invoice.inv_date, invoice.date_otsr_agr, invoice.acc47803, invoice.acc91418, register.d
order by cnt_doc desc, invoice.date_otsr_agr");
		return sqlite_fetch_all($result, SQLITE_ASSOC);
	}
	function getDebitors($agr_id){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, 
		"select cust_id, customers.NAME_CYR as NAME_CYR, lim, acc61212, deliv_agr_id
			, strftime('%d-%m-%Y', deliv_agr_date) as deliv_agr_date_h
			, strftime('%d%m', deliv_agr_date)||substr(strftime('%Y', deliv_agr_date),3,2) as deliv_agr_date
			, com1, srok_otsr, penya, fct_type
		from debitor
		left join customers on debitor.cust_id=customers.id
		where agr_id=$agr_id"
		);
		return sqlite_fetch_all($result, SQLITE_ASSOC);


/*
		$stmt = ociparse($_SESSION['orcl_con'],"select fct_payer_limit.PAYER_NO, customers.NAME_CYR from fct_agreement 
		left join fct_payer_limit on fct_agreement.cust_no=fct_payer_limit.cust_no
		left join customers on fct_payer_limit.payer_no=customers.no
		where fct_agreement.agr_no=$agr_no
		");	
		ociexecute($stmt,OCI_DEFAULT);
		while (ocifetch($stmt)){
			$arr[] = array(
			'payer_no' => ociresult($stmt,"PAYER_NO"),
			'payer_name' => ociresult($stmt,"NAME_CYR"),
			);
			//echo $conn." <".ociresult($stmt,"TEST")			
		}
		return $arr;
*/
		
		//~ return array(
			//~ array('payer_no' => 10011, 'payer_name' => 'Дебитор №1'),
			//~ array('payer_no' => 11011, 'payer_name' => 'Дебитор №2'),
			//~ array('payer_no' => 10211, 'payer_name' => 'Дебитор №3'),
			//~ array('payer_no' => 10051, 'payer_name' => 'Дебитор №4'),
		//~ ); 
	}
	function insertDebitor($agr_id, $cust_id, $lim, $acc61212, $deliv_agr_id, $deliv_agr_date, $com1, $srok_otsr, $penya, $fct_type){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$error="1";
		$result = sqlite_exec($db, "insert into debitor(
			agr_id, cust_id, lim, acc61212, deliv_agr_id, deliv_agr_date, com1, srok_otsr,penya,fct_type)
			values($agr_id, $cust_id, $lim, $acc61212, '$deliv_agr_id', julianday('$deliv_agr_date'), 
			$com1, $srok_otsr, '$penya', $fct_type)", $error);
		if (!$result) {
			return $error;
		}
		return false;
	}		
	function updateDebitor($agr_id, $cust_id, $lim, $acc61212, $deliv_agr_id, $deliv_agr_date, $com1, $srok_otsr, $penya, $fct_type){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$error="1";
		$result = sqlite_exec($db, "update debitor set 
				lim=$lim, acc61212=$acc61212, deliv_agr_id='$deliv_agr_id', deliv_agr_date=julianday('$deliv_agr_date'),
				com1=$com1, srok_otsr=$srok_otsr, penya='$penya', fct_type=$fct_type
				where agr_id=$agr_id and cust_id=$cust_id", $error);
		if (!$result) {
			return $error;
		}
		return false;
	}		
	function deleteDebitor($agr_id, $cust_id){
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$error="1";
		$result = sqlite_exec($db, "delete from debitor where agr_id=$agr_id and cust_id=$cust_id", $error);
		if (!$result) {
			exit("Ошибка в запросе: '$error'");
		}	
	}	
	function getRegisters($agr_id){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, "select id, num, strftime('%d-%m-%Y', d) as d from register where agr_id=$agr_id");
		return sqlite_fetch_all($result, SQLITE_ASSOC);
	}
	function getRepays($agr_id){
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) {
			die($sqliteerror);
		}
		$result = sqlite_query($db, "select id, strftime('%d-%m-%Y', oper_date) as oper_date, strftime('%d-%m-%Y', created) as created from repay_header where agr_id=$agr_id");
		return sqlite_fetch_all($result, SQLITE_ASSOC);
	}
	function getRegister($id){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, 
			"select register.agr_id as agr_id, num, strftime('%d-%m-%Y', d) as d, d1.amount as d_rs_60311, d2.amount as d60311_70601, d3.amount as d60311_60309
			from register
			left join documents d1 on d1.id=doc_rs_60311
			left join documents d2 on d2.id=doc_60311_70601
			left join documents d3 on d3.id=doc_60311_60309
			where register.id=$id");
		if ($entry = sqlite_fetch_array($result, SQLITE_ASSOC)) {	
			return $entry;
		}
		return	null;	
	}
	function deleteRepay($id){
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) {
			die($sqliteerror);
		}
		//В рамках транзакции
		$error="1";
		$result = sqlite_exec($db, "BEGIN TRANSACTION;", $error);
		if (!$result) {
			return $error;
		}
		//Удалить документы
		$result = sqlite_exec($db, "delete from documents where id in (
			select doc_47803 from repay_entry where header_id=$id
			union
			select doc_70601 from repay_entry where header_id=$id
			union
			select doc_60309 from repay_entry where header_id=$id
			union
			select doc_rs from repay_entry where header_id=$id
			union
			select doc_91418 from repay_entry where header_id=$id
		)", $error);
		if (!$result) {
			sqlite_exec($db, "ROLLBACK");
			return $error;
		}
		$result = sqlite_exec($db, "delete from repay_entry where header_id=$id", $error);
		if (!$result) {
			sqlite_exec($db, "ROLLBACK");
			return $error;
		}
		$result = sqlite_exec($db, "delete from repay_header where id=$id", $error);
		if (!$result) {
			sqlite_exec($db, "ROLLBACK");
			return $error;
		}
		sqlite_exec($db, "COMMIT");
		return "Было отменено списание №$id";
	}
	function deleteRegister($id){		
		$operDayJ = localdb::getOperDayJ();
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		//Если были списания хотя бы по одной накладной из реестра, вывести номер этой накладной и выход.
		$result = sqlite_query($db, 
		"select invoice.id as id, invoice.urid_id as urid_id
		from invoice, repay_entry
		where invoice.register_id=$id and repay_entry.invoice_id=invoice.id");
		if ($entry = sqlite_fetch_array($result, SQLITE_ASSOC)) {
			return "Были списания по накладной №".$entry['urid_id']." (".$entry['id']."). Для отмены реестра сначала отмените все списания по накладным реестра.";
		}
		
		//Все в рамках транзакции:
		$error="1";
		$result = sqlite_exec($db, "BEGIN TRANSACTION;", $error);
		if (!$result) {
			return $error;
		}
		//Удалить документы, общие для всего реестра 
		$result = sqlite_exec($db, "delete from documents where id in (
			select doc_60311_60309 from register where id=$id
			union
			select doc_60311_70601 from register where id=$id
			union
			select doc_rs_60311 from register where id=$id
			union
			select doc_47401_rs from register where id=$id
		)", $error);
		if (!$result) {
			sqlite_exec($db, "ROLLBACK");
			return $error;
		}		
		//Удалить документы на постановку каждой накладной реестра
		$result = sqlite_exec($db, "delete from documents where deb_acc_id in (
			select acc47803 from invoice where register_id = $id 
			union 
			select acc91418 from invoice where register_id = $id
		)", $error);
		if (!$result) {
			sqlite_exec($db, "ROLLBACK");
			return $error;
		}
		//Закрыть счета по каждой накладной
		$result = sqlite_exec($db, "update accounts set closed=$operDayJ where id in (
			select acc47803 from invoice where register_id = $id 
			union 
			select acc91418 from invoice where register_id = $id
		)", $error);
		if (!$result) {
			sqlite_exec($db, "ROLLBACK");
			return $error;
		}		
		//Очистить запись о каждой накладной
		$result = sqlite_exec($db, "update invoice 
			set register_id=null, date_otsr_agr=null, payed=null, acc47803=null, acc91418=null
		where register_id = $id", $error);
		if (!$result) {
			sqlite_exec($db, "ROLLBACK");
			return $error;
		}
		//Удалить запись о реестре
		$result = sqlite_exec($db, "delete from register where id = $id", $error);
		if (!$result) {
			sqlite_exec($db, "ROLLBACK");
			return $error;
		}
		sqlite_exec($db, "COMMIT");
		return "Была отменена постановка накладных из реестра $id";
	}
	function getCom1($agr_id, $debitor_cust_id){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, "select com1 from debitor where agr_id=$agr_id and cust_id=$debitor_cust_id");
		if ($entry = sqlite_fetch_array($result, SQLITE_ASSOC)) {	
			return $entry['com1'];
		}
		return	null;		
	}
	function insertInvoice($agr_id, $num, $dat, $deb, $sum, $nds){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$dat4jd=substr($dat,6,4).'-'.substr($dat,3,2).'-'.substr($dat,0,2);
		$error="1";
		//~ echo "insert into invoice(urid_id, agr_id, debitor_cust_id, inv_date, nds, sum)
			//~ values('$num', $agr_id, $deb, julianday('$dat4jd'),$nds, $sum)";
		$result = sqlite_exec($db, "insert into invoice(urid_id, agr_id, debitor_cust_id, inv_date, nds, sum)
			values('$num', $agr_id, $deb, julianday('$dat4jd'),$nds, $sum)", $error);
		if (!$result) {
			return $error;
		}
		return false;
	}	
	function deleteNewInvoices($agr_id){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_exec($db, "delete from invoice where agr_id=$agr_id and register_id is null", $error);
		if (!$result) {
			return $error;
		}
		return false;
	}	
	function getNewInvoices($agr_id){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, 
			"select invoice.id as id, customers.id deb_id, customers.NAME_CYR as deb_name, urid_id, strftime('%d%m%Y', inv_date) as dat, nds, sum, debitor.srok_otsr as srok_otsr from invoice 
			left join customers on invoice.debitor_cust_id=customers.id
			left join debitor on debitor.agr_id=invoice.agr_id and debitor.cust_id=invoice.debitor_cust_id
			where invoice.agr_id=$agr_id and invoice.register_id is null"
		);
		return sqlite_fetch_all($result, SQLITE_ASSOC);
	}	
	function getRegisterInvoices($reg_id){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		//~ echo "select invoice.id as id, customers.id deb_id, customers.NAME_CYR as deb_name, urid_id, strftime('%d%m%Y', inv_date) as dat, nds, sum from invoice 
			//~ left join customers on invoice.debitor_cust_id=customers.id
			//~ where agr_id=$agr_id and register_id=$reg_id";
		$result = sqlite_query($db, 
			"select invoice.id as id, customers.id deb_id, customers.NAME_CYR as deb_name, urid_id, strftime('%d-%m-%Y', inv_date) as dat, nds, sum, acc47803, acc91418 from invoice 
			left join customers on invoice.debitor_cust_id=customers.id
			where register_id=$reg_id"
		);
		return sqlite_fetch_all($result, SQLITE_ASSOC);
	}	
	function updateInvoice($id, $acc47803, $acc91418, $reg_id, $srok_otsr){
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_exec($db, "update invoice set acc47803='$acc47803', acc91418='$acc91418',register_id=$reg_id, date_otsr_agr=$srok_otsr+".localdb::getOperDayJ()." where id=$id", $error);		
	}
	function insertAgr($urid_id, $signed, $cust_id, $next_reg_num, $acc_cur, $acc47401, $comfin, $penya_cl, $fct_type){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$error="1";
		if($penya_cl=='') $penya_cl="0";
		$result = sqlite_exec($db, "insert into agr(urid_id,signed,cust_id,acc_cur,acc47401, comfin, next_reg_num, penya_cl, fct_type)
			values('$urid_id',julianday('$signed'),$cust_id,'$acc_cur', '$acc47401', $comfin, $next_reg_num, $penya_cl, $fct_type)", $error);
		if (!$result) {
			exit("Ошибка в запросе: '$error'");
		}	
	}
	function incRegNum($agr_id)	{
		$operDayJ = localdb::getOperDayJ();
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$error="1";
		$result = sqlite_exec($db, "insert into register(agr_id, num, d) select $agr_id, next_reg_num, $operDayJ from agr where id=$agr_id", $error);
		if (!$result) {
			exit("Ошибка в запросе: '$error'");
		}	
		$r = sqlite_last_insert_rowid($db);
		$error="1";
		$result = sqlite_exec($db, "update agr set next_reg_num=next_reg_num+1 where id=$agr_id", $error);
		if (!$result) {
			exit("Ошибка в запросе: '$error'");
		}
		return $r;
	}
	function setRegComDocs($reg_id, $doc_47401_rs, $doc_rs_60311, $doc_60311_70601, $doc_60311_60309)	{
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$error="1";
		$result = sqlite_exec($db, "update register set doc_47401_rs=$doc_47401_rs, doc_rs_60311=$doc_rs_60311, doc_60311_70601=$doc_60311_70601,doc_60311_60309=$doc_60311_60309   where id=$reg_id", $error);
		if (!$result) {
			exit("Ошибка в запросе: '$error'");
		}			
	}
	function updateAgr($agr_id, $urid_id, $signed, $cust_id, $next_reg_num, $acc_cur, $acc47401, $comfin, $penya_cl){		
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$error="1";

		//~ echo "update agr set urid_id = '$urid_id',
			//~ signed=julianday('$signed4jd'),
			//~ cust_id=$cust_id,
			//~ acc_cur='$acc_cur',
			//~ acc47401='$acc47401',
			//~ comfin=$comfin, 
			//~ next_reg_num=$next_reg_num
			//~ where id=$agr_id";
		if($penya_cl=='') $penya_cl="0";
		$result = sqlite_exec($db, "update agr set urid_id = '$urid_id',
			signed=julianday('$signed'),
			cust_id=$cust_id,
			acc_cur='$acc_cur',
			acc47401='$acc47401',
			comfin=$comfin,
			penya_cl=$penya_cl,
			next_reg_num=$next_reg_num
			where id=$agr_id", $error);
		if (!$result) {
			exit("Ошибка в запросе: '$error'");
		}	
	}
	function deleteAgr($agr_id){
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$error="1";
		echo "delete from agr where id=$agr_id";
		$result = sqlite_exec($db, "delete from agr where id=$agr_id", $error);
		if (!$result) {
			exit("Ошибка в запросе: '$error'");
		}	
	}
	function getAgr($agr_id){
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, "select urid_id
		, strftime('%d%m', signed)||substr(strftime('%Y', signed),3,2) as signed
		, strftime('%d-%m-%Y', signed) as signed_h
		,cust_id
		,acc_cur
		,acc47401
		,comfin
		,next_reg_num
		,customers.name_cyr as name_cyr
		,customers.inn as inn
		,penya_cl
		,fct_type
		from agr 
		left join customers on customers.id=agr.cust_id 
		where agr.id=$agr_id");
		return sqlite_fetch_array($result, SQLITE_ASSOC);
	}	
	function getAgrs(){
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, "select agr.id as id, urid_id
				, strftime('%d-%m-%Y', signed) as signed
				, customers.name_cyr ||'('||agr.cust_id||')' as CLIENT
				from agr
				left join customers on customers.id=agr.cust_id");
		while ($entry = sqlite_fetch_array($result, SQLITE_ASSOC)) {	
			$arr[] = array(
				'id' => $entry['id'],
				'urid_id' => $entry['urid_id'],
				'client' => $entry['CLIENT'],
				'signed' => $entry['signed'],
			);
		}
		return	 $arr;
/*
		$stmt = ociparse($_SESSION['orcl_con'],"
			select FCT_AGREEMENT.branch, AGR_NO, AGR_ID
				, to_char(signed, 'dd-mm-yyyy') as S
				, customers.name_short ||'('||to_char(FCT_AGREEMENT.cust_no)||')' as CLIENT
				,(case when FCT_AGREEMENT.signed is not null then to_char(P_FCT.GET_ACC_ID (FCT_AGREEMENT.BRANCH,DIVISION,agr_no,null,'РАСЧ_КЛ',sysdate)) else '' end)  as ACC47401
				,(case when FCT_AGREEMENT.signed is not null then to_char(P_FCT.GET_ACC_ID (FCT_AGREEMENT.BRANCH,DIVISION,agr_no,null,'СЧЕТ_КЛ',sysdate)) else '' end)  as RS
				from FCT_AGREEMENT
				left join customers on customers.no=FCT_AGREEMENT.cust_no
		");			
		ociexecute($stmt,OCI_DEFAULT);			
		while (ocifetch($stmt)){
			$arr[] = array(
			'agr_no' => ociresult($stmt,"AGR_NO"),
			'num' => ociresult($stmt,"AGR_ID"),
			'client' => ociresult($stmt,"CLIENT"),
			'signed' => ociresult($stmt,"S"),
			'40817' => ociresult($stmt,"RS"),
			'47401' => ociresult($stmt,"ACC47401"),
			);
			//echo $conn." <".ociresult($stmt,"TEST")			
		}
		return	 $arr;
*/
	/*	

		return array(
			array(
			'num' => '02Ф-2012',
			'client' => 'Клиент1 (12345)',
			'signed' => '12-02-2012',
			'40817' => '40702810000000000001',
			'47401' => '47401810000000000001',
			'comfin' => '0,5'
			),
			array(
			'num' => '03Ф-2012',
			'client' => 'Клиент2 (212345)',
			'signed' => '12-02-2012',
			'40817' => '40702810000000000001',
			'47401' => '47401810000000000001',
			'comfin' => '0,5'
			)
		);
*/
	}
	function getClosedAgrs(){
		return array(
			array(
			'num' => '01Ф-2012',
			'client' => 'Клиент закрытый (65412)',
			'signed' => '12-03-2012',
			'40817' => '40702810000000000001',
			'47401' => '47401810000000000001',
			'closed' => '12-01-2013'
			)
		);
	}

    function getUserInfo($login){
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		//~ echo "select * from users where login='$login'";
		$result = sqlite_query($db, "select * from users where login='$login'");
		return sqlite_fetch_array($result, SQLITE_ASSOC);
	}


//Функции пользователя	
	function getNextAgrNumber(){
		if (!($db = sqlite_open('fct.db', 0666, $sqliteerror))) { 
			die($sqliteerror);
		}
		$result = sqlite_query($db, "select ifnull(max(urid_id)+1,1)||'-'||strftime('%Y','now')||'ф' as urid_id from agr");
		if ($entry = sqlite_fetch_array($result, SQLITE_ASSOC)) {	
			return $entry['urid_id'];
		}
		return	null;		
	}	
}
?>

