<?php
class localdb{
	const acc60311 = '60311810000000000000';
	const acc70601 = '70601810000000000000';
	const acc60309 = '60309810000000000000';
	const acc99999 = '99999810500000000000';
	
	private $db;
	
	function __construct(){
		try {
			$this->db = new PDO("sqlite:fct.sqlite");
		} catch (PDOException $e) {
			throw new Exception("Ошибка подключения к БД: " . $e->getMessage());
		}
		if ($row = $this->db->query("select styles
				from users
				where login='".(isset($_SESSION['login'])?$_SESSION['login']:'')."' and passwd='".(isset($_SESSION['passwd'])?$_SESSION['passwd']:'')."'")->fetch(PDO::FETCH_ASSOC)) {
			$_SESSION['user_styles'] = $row['styles'];
		}else throw new Exception("Неверный логин или пароль");	
		
		$this->execSql("PRAGMA foreign_keys = ON;");
	}

//Вспомогательные функции для работы с БД
	function select1value($sql){
		if (($r = $this->db->query($sql)->fetchColumn())!==FALSE) {
			return $r;
		}
		return	null;	
	}
	function select1row($sql){
		return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);	
	}
	function selectRows($sql){
		return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
	}
	function execSql($sql){
		if ($this->db->exec($sql)===FALSE){
			$tmp = $this->db->errorInfo();
			exit("Ошибка в запросе: ".$tmp[2]);			
		}		
	}
	function execSqlAndGetErr($sql){
		if ($this->db->exec($sql)===FALSE){
			$tmp = $this->db->errorInfo();
			return $tmp[2];
		}
		return null;
	}
	function execSqlAndGetId($sql){
		$this->execSql($sql);
		return $this->db->lastInsertId();	
	}
	
//Прикладные функции	
	function getAgrUridId($agr_id){		
		return $this->select1value("select urid_id from agr where id=$agr_id");		
	}
	function getOperDay(){		
		return $this->select1value("select strftime('%d-%m-%Y', oper_day)
				from users
				where login='".(isset($_SESSION['login'])?$_SESSION['login']:'')."' 
					and passwd='".(isset($_SESSION['passwd'])?$_SESSION['passwd']:'')."'");
	}
	function getOperDayJ(){		
		return $this->select1value("select oper_day
				from users
				where login='".(isset($_SESSION['login'])?$_SESSION['login']:'')."' 
					and passwd='".(isset($_SESSION['passwd'])?$_SESSION['passwd']:'')."'");
	}
	function setOperDay($str_dat){
		$this->execSql("update users set oper_day=julianday('$str_dat') 
			where login='".$_SESSION['login']."' and passwd='".$_SESSION['passwd']."'");
	}
	function insertRepayHeader($agr_id){
		return $this->execSqlAndGetId("insert into repay_header(agr_id, oper_date, created)
			values($agr_id, ".$this->getOperDayJ().", julianday(date('now')))");
	}
	function insertRepayEntry($header_id, $invoice_id, $doc_47803, $doc_70601, $doc_60309, $doc_rs, $doc_91418){
		$this->execSql("insert into repay_entry(header_id, invoice_id, doc_47803, doc_70601, doc_60309, doc_rs, doc_91418)".
			"values($header_id, $invoice_id, $doc_47803, $doc_70601, $doc_60309, $doc_rs, $doc_91418)");
	}	
	function createAccount($balans, $cust_id, $name){
		$num=$this->select1value("select ifnull(substr(max(id),9,12), 0)+1 from accounts where id like '$balans%'");
		while(strlen($balans.'810'.$num)<20)
			$num='0'.$num;
								
		$this->execSql("insert into accounts(id, opened, cust_id, name)
			values('".$balans.'810'.$num."', julianday(date('now')), ".$cust_id.", '$name')");
		return $balans.'810'.$num;
	}	
	function getAccounts(){		
		return $this->selectRows("select accounts.id as id, strftime('%d-%m-%Y', opened) as opened, strftime('%d-%m-%Y', closed) as closed, name_cyr, name 
		from accounts
		left join customers on customers.id=accounts.cust_id");
	}
	function getAccountAmount($id){		
		return $this->select1value("select sum(a.crob)-sum(a.debob) as r from (
			select sum(amount) as debob, 0 as crob from documents where deb_acc_id='$id'
			union
			select 0 as debob, sum(amount) as crob from documents where cr_acc_id='$id'
		)a");
	}
	function getDebUsedLimit($agr_id, $debitor_cust_id){
		$inv = $this->selectRows("select acc91418 from invoice 
			where payed is null and debitor_cust_id=$debitor_cust_id and agr_id=$agr_id");
		$r = 0;
		foreach($inv as $v){
			$r += $this->getAccountAmount($v['acc91418']);
		}
		return -$r; //так как 91418 - активный
	}	
	function getDebOb($acc_id){
		return $this->select1value("select sum(amount) from documents where deb_acc_id='$acc_id'");
	}
	function insertDocument($agr_id, $class_op, $doc_type, $amount, $deb_acc_id, $cr_acc_id, $nazn_pl){		
		return $this->execSqlAndGetId(
		"insert into documents(agr_id, class_op, doc_type, value_date, amount, deb_acc_id, cr_acc_id, nazn_pl)
		select '$agr_id', '$class_op', '$doc_type', oper_day, $amount, '$deb_acc_id', '$cr_acc_id', '$nazn_pl' 
		from users where login='".$_SESSION['login']."' and passwd='".$_SESSION['passwd']."'");
	}
	function getDocuments(){		
		return $this->selectRows("select id, amount, deb_acc_id, cr_acc_id, nazn_pl, strftime('%d-%m-%Y', value_date) as value_date	from documents");
	}
	function getDebInv4Repay($agr_id, $debitor_cust_id){
		//Накладные по дебитору договора, которые поставлены и не оплачены (остаток по счетам)
		return $this->selectRows( 
		"select invoice.id as id, invoice.urid_id as urid_id,  invoice.register_id , invoice.inv_date, invoice.date_otsr_agr, invoice.acc47803 as acc47803, invoice.acc91418 as acc91418, register.d register_d, count(documents.id) as cnt_doc from invoice 
left join documents on documents.cr_acc_id=invoice.acc91418
left join register on register.id=invoice.register_id
where invoice.payed is null and invoice.date_otsr_agr is not null and invoice.agr_id=$agr_id and invoice.debitor_cust_id=$debitor_cust_id
group by invoice.id, invoice.urid_id, invoice.register_id, invoice.inv_date, invoice.date_otsr_agr, invoice.acc47803, invoice.acc91418, register.d
order by cnt_doc desc, invoice.date_otsr_agr");
	}
	function getDebitors($agr_id){
		return $this->selectRows( 
		"select cust_id, customers.NAME_CYR as NAME_CYR, lim, acc61212, deliv_agr_id
			, strftime('%d-%m-%Y', deliv_agr_date) as deliv_agr_date_h
			, strftime('%d%m', deliv_agr_date)||substr(strftime('%Y', deliv_agr_date),3,2) as deliv_agr_date
			, com1, srok_otsr, penya, fct_type
		from debitor
		left join customers on debitor.cust_id=customers.id
		where agr_id=$agr_id");
	}
	function insertDebitor($agr_id, $cust_id, $lim, $acc61212, $deliv_agr_id, $deliv_agr_date, $com1, $srok_otsr, $penya, $fct_type){
		$this->execSql("insert into debitor(
			agr_id, cust_id, lim, acc61212, deliv_agr_id, deliv_agr_date, com1, srok_otsr,penya,fct_type)
			values($agr_id, $cust_id, $lim, $acc61212, '$deliv_agr_id', julianday('$deliv_agr_date'), 
			$com1, $srok_otsr, '$penya', $fct_type)");
	}
	function updateDebitor($agr_id, $cust_id, $lim, $acc61212, $deliv_agr_id, $deliv_agr_date, $com1, $srok_otsr, $penya, $fct_type){		
		$this->execSql("update debitor set 
				lim=$lim, acc61212=$acc61212, deliv_agr_id='$deliv_agr_id', deliv_agr_date=julianday('$deliv_agr_date'),
				com1=$com1, srok_otsr=$srok_otsr, penya='$penya', fct_type=$fct_type
				where agr_id=$agr_id and cust_id=$cust_id");
	}		
	function deleteDebitor($agr_id, $cust_id){
		return $this->execSql("delete from debitor where agr_id=$agr_id and cust_id=$cust_id");
	}	
	function getRegisters($agr_id){		
		return $this->selectRows("select id, num, strftime('%d-%m-%Y', d) as d from register where agr_id=$agr_id");
	}
	function getRepays($agr_id){
		return $this->selectRows("select id, strftime('%d-%m-%Y', oper_date) as oper_date, strftime('%d-%m-%Y', created) as created 
			from repay_header where agr_id=$agr_id");
	}
	function getRegister($id){		
		return $this->select1row(
			"select register.agr_id as agr_id, num, strftime('%d-%m-%Y', d) as d, d1.amount as d_rs_60311, d2.amount as d60311_70601, d3.amount as d60311_60309
			from register
			left join documents d1 on d1.id=doc_rs_60311
			left join documents d2 on d2.id=doc_60311_70601
			left join documents d3 on d3.id=doc_60311_60309
			where register.id=$id");
	}
	function deleteRepay($id){ //Реализовать внешними ключами
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
	function deleteRegister($id){		//Реализовать внешними ключами
		$operDayJ = $this->getOperDayJ();
		//Если были списания хотя бы по одной накладной из реестра, вывести номер этой накладной и выход.
		$row = $this->select1row(
		"select invoice.id as id, invoice.urid_id as urid_id
		from invoice, repay_entry
		where invoice.register_id=$id and repay_entry.invoice_id=invoice.id");
		if ($row!==FALSE) {
			return "Были списания по накладной №".$row['urid_id']." (".$row['id']."). Для отмены реестра сначала отмените все списания по накладным реестра.";
		}
		
		//Все в рамках транзакции:
		$error="1";
		$result = sqlite_exec($db, "BEGIN TRANSACTION;", $error);
		if (!$result) {
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
		//Удалятся документы, общие для всего реестра, по внешним ключам
		$result = sqlite_exec($db, "delete from register where id = $id", $error);
		if (!$result) {
			sqlite_exec($db, "ROLLBACK");
			return $error;
		}
		sqlite_exec($db, "COMMIT");
		return "Была отменена постановка накладных из реестра $id";
	}
	function getCom1($agr_id, $debitor_cust_id){		
		return $this->select1value("select com1 from debitor 
				where agr_id=$agr_id and cust_id=$debitor_cust_id");
	}
	function insertInvoice($agr_id, $num, $dat, $deb, $sum, $nds){		
		$dat4jd=substr($dat,6,4).'-'.substr($dat,3,2).'-'.substr($dat,0,2);

		return $this->execSqlAndGetErr("insert into invoice(urid_id, agr_id, debitor_cust_id, inv_date, nds, sum)
			values('$num', $agr_id, $deb, julianday('$dat4jd'),$nds, $sum)");
	}	
	function deleteNewInvoices($agr_id){		
		$this->execSql("delete from invoice where agr_id=$agr_id and register_id is null");
	}	
	function getNewInvoices($agr_id){		
		return $this->selectRows(
			"select invoice.id as id, customers.id deb_id, customers.NAME_CYR as deb_name, urid_id, strftime('%d%m%Y', inv_date) as dat, nds, sum, debitor.srok_otsr as srok_otsr from invoice 
			left join customers on invoice.debitor_cust_id=customers.id
			left join debitor on debitor.agr_id=invoice.agr_id and debitor.cust_id=invoice.debitor_cust_id
			where invoice.agr_id=$agr_id and invoice.register_id is null"
		);
	}	
	function getRegisterInvoices($reg_id){		
		return $this->selectRows(
			"select invoice.id as id, customers.id deb_id, customers.NAME_CYR as deb_name, urid_id, strftime('%d-%m-%Y', inv_date) as dat, nds, sum, acc47803, acc91418 from invoice 
			left join customers on invoice.debitor_cust_id=customers.id
			where register_id=$reg_id"
		);
	}	
	function updateInvoice($id, $acc47803, $acc91418, $reg_id, $srok_otsr){
		$this->execSql("update invoice set acc47803='$acc47803', acc91418='$acc91418'
			,register_id=$reg_id, date_otsr_agr=$srok_otsr+".$this->getOperDayJ()." where id=$id");
	}
	function insertAgr($urid_id, $signed, $cust_id, $next_reg_num, $acc_cur, $acc47401, $comfin, $penya_cl, $fct_type){		
		if($penya_cl=='') $penya_cl="0";
		$this->execSql("insert into agr(urid_id,signed,cust_id,acc_cur,acc47401, comfin, next_reg_num, penya_cl, fct_type)
			values('$urid_id',julianday('$signed'),$cust_id,'$acc_cur', '$acc47401', $comfin, $next_reg_num, $penya_cl, $fct_type)");
	}
	function incRegNum($agr_id)	{
		$operDayJ = $this->getOperDayJ();		
		$r = $this->execSqlAndGetId(
			"insert into register(agr_id, num, d) select $agr_id, next_reg_num, $operDayJ from agr where id=$agr_id");		
		$this->execSql("update agr set next_reg_num=next_reg_num+1 where id=$agr_id");
		return $r;
	}
	function setRegComDocs($reg_id, $doc_47401_rs, $doc_rs_60311, $doc_60311_70601, $doc_60311_60309)	{
		$this->execSql("update register set doc_47401_rs=$doc_47401_rs, doc_rs_60311=$doc_rs_60311, 
				doc_60311_70601=$doc_60311_70601,doc_60311_60309=$doc_60311_60309   where id=$reg_id");
	}
	function updateAgr($agr_id, $urid_id, $signed, $cust_id, $next_reg_num, $acc_cur, $acc47401, $comfin, $penya_cl){		
		if($penya_cl=='') $penya_cl="0";
		$this->execSql("update agr set urid_id = '$urid_id',
			signed=julianday('$signed'),
			cust_id=$cust_id,
			acc_cur='$acc_cur',
			acc47401='$acc47401',
			comfin=$comfin,
			penya_cl=$penya_cl,
			next_reg_num=$next_reg_num
			where id=$agr_id");
	}
	function deleteAgr($agr_id){
		$this->execSql("delete from agr where id=$agr_id");
	}
	function getAgr($agr_id){
		return $this->select1row("select urid_id
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
	}	
	function getAgrs(){
		return $this->selectRows("select agr.id as id, urid_id
		, strftime('%d-%m-%Y', signed) as signed
		, customers.name_cyr ||'('||agr.cust_id||')' as client
		from agr
		left join customers on customers.id=agr.cust_id");
	}
	function getClosedAgrs(){
		//~ return array(
			//~ array(
			//~ 'num' => '01Ф-2012',
			//~ 'client' => 'Клиент закрытый (65412)',
			//~ 'signed' => '12-03-2012',
			//~ '40817' => '40702810000000000001',
			//~ '47401' => '47401810000000000001',
			//~ 'closed' => '12-01-2013'
			//~ )
		//~ );
	}

    function getUserInfo(){
		$this->select1row("select * from users where login='".$_SESSION['login']."'");
	}


//Функции, переопределяемые пользователем
	function getNextAgrNumber(){
		return $this->select1value("select ifnull(max(urid_id)+1,1)||'-'||strftime('%Y','now')||'ф' from agr");
	}
}

?>
