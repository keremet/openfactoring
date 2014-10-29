<?php
session_start();
include "localdb.php";
include "financial.php";

class FCTXML {   
	private $buf = "";
	private function out($str){
			$this->buf .= $str;
	}
	private function processXML($xml){
		return str_replace("\"","&quot;", str_replace(">","&gt;", str_replace("<", "&lt;", $xml)));		
	}
	
	function __construct($template) {
	   $this->out('<?xml version="1.0" encoding="windows-1251"?>
	<FILL DocFileName="'.$template.'">
	<Fields>
	');

	}

	function __destruct() {
		$this->out('</Fields>
		</FILL>');
		echo iconv("UTF-8", "CP-1251", $this->buf);
	}

	/**
	 * Склоняем словоформу
	 * @ author runcore
	 */
	function morph($n, $f1, $f2, $f5) {
		$n = abs(intval($n)) % 100;
		if ($n>10 && $n<20) return $f5;
		$n = $n % 10;
		if ($n>1 && $n<5) return $f2;
		if ($n==1) return $f1;
		return $f5;
	}

	function mb_ucfirst($text) {
		mb_internal_encoding("UTF-8");
		return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
	}
	
	
	function num2str($num) {
		$nul='ноль';
		$ten=array(
			array('','один','два','три','четыре','пять','шесть','семь', 'восемь','девять'),
			array('','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять'),
		);
		$a20=array('десять','одиннадцать','двенадцать','тринадцать','четырнадцать' ,'пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать');
		$tens=array(2=>'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят' ,'восемьдесят','девяносто');
		$hundred=array('','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот','восемьсот','девятьсот');
		$unit=array( // Units
			array('копейка' ,'копейки' ,'копеек',	 1),
			array('рубль'   ,'рубля'   ,'рублей'    ,0),
			array('тысяча'  ,'тысячи'  ,'тысяч'     ,1),
			array('миллион' ,'миллиона','миллионов' ,0),
			array('миллиард','миллиарда','миллиардов',0),
		);
		//
		list($rub,$kop) = explode('.',financial2str($num)); //sprintf("%015.2f", floatval($num))
		while(strlen($rub)<12)
			$rub='0'.$rub;
		$out = array();
		if (intval($rub)>0) {
			foreach(str_split($rub,3) as $uk=>$v) { // by 3 symbols
				if (!intval($v)) continue;
				$uk = sizeof($unit)-$uk-1; // unit key
				$gender = $unit[$uk][3];
				list($i1,$i2,$i3) = array_map('intval',str_split($v,1));
				// mega-logic
				$out[] = $hundred[$i1]; # 1xx-9xx
				if ($i2>1) $out[]= $tens[$i2].' '.$ten[$gender][$i3]; # 20-99
				else $out[]= $i2>0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
				// units without rub & kop
				if ($uk>1) $out[]= FCTXML::morph($v,$unit[$uk][0],$unit[$uk][1],$unit[$uk][2]);
			} //foreach
		}
		else $out[] = $nul;
		$out[] = FCTXML::morph(intval($rub), $unit[1][0],$unit[1][1],$unit[1][2]); // rub
		$out[] = $kop.' '.FCTXML::morph($kop,$unit[0][0],$unit[0][1],$unit[0][2]); // kop
		return FCTXML::mb_ucfirst(trim(preg_replace('/ {2,}/', ' ', join(' ',$out))));
	}

	
	
	
	
	function replaceAll($key, $value){
		$this->out( '<Field Name="'.$key.'" TypeOut="code_replaceall">'.$value.'</Field>');
	}
	function replaceField($key, $value){
		$this->out( '<Field Name="'.$key.'">'.$value.'</Field>');
	}
	function replaceFieldWithParagraph($key, $value){
		$this->out( '<Field Name="'.$key.'" TypeOut="code">'.FCTXML::processXML($value).'</Field>');
	}
	function addStyles($value){
		$this->out( '<Field Name="openoffice_styles_list" TypeOut="code">'.FCTXML::processXML($value).'</Field>');
	}
	function addRow($tableName, $value){
		$this->out( '<Field Name="'.$tableName.'" TypeOut="code_addrow">'.FCTXML::processXML($value).'</Field>');
	}
	function insertRow($tableName, $rowsBefore, $value){
		$this->out( '<Field Name="'.$tableName.'" TypeOut="code_insertrow">'.$rowsBefore.' '.FCTXML::processXML($value).'</Field>');
	}
	function deleteTable($tableName){
		$this->out( '<Field Name="'.$tableName.'" TypeOut="delete_table"> </Field>');
	}
	function copyTable($tableName, $newTableName){
		$this->out( '<Field Name="'.$tableName.'" TypeOut="copy_table">'.$newTableName.'</Field>');
	}
	function copyfile($filename){
		$this->out( '<Field Name=" " TypeOut="nextfile">'.$filename.'</Field>');
	}
	function copy(){
		$this->out( '<Field Name=" " TypeOut="copy"> </Field>');
	}   
}

try{
	$db = new localdb();
}catch (Exception $e) {
	die($e->getMessage());		
}


function create_registry($agr_id){
	$reg_sum = 0;
	$reg_nds_sum = 0;	
	global $db;
	$agr = $db->getAgr($agr_id);
	$reg_id = 0;
	$sum47401 = 0;
	$com_nds = 0;
	$com_doh = 0;
	foreach($db->getNewInvoices($_GET['agr_id']) as $i => $v){
		if($reg_id==0)
			$reg_id = $db->incRegNum($agr_id);
		$acc47803 = $db->createAccount('47803', $v['deb_id'], $v['deb_name']);
		$acc91418 = $db->createAccount('91418', $v['deb_id'], $v['deb_name']);
		$db->updateInvoice($v['id'], $acc47803, $acc91418, $reg_id, $v['srok_otsr']);
		$sum47803 = round($v['sum']*0.9);
		$db->insertDocument($agr_id, 'ПОСТ_БАЛАНС', '09', $sum47803, $acc47803, $agr['acc47401'], 'Приобретение прав требования по Реестру № '.$agr['next_reg_num'].' накладная '.$v['urid_id']);
		$db->insertDocument($agr_id, 'ПОСТ_ВНЕБАЛАНС', '09', $v['sum'], $acc91418, localdb::acc99999, 'Постановка на внебаланс по Реестру № '.$agr['next_reg_num'].' накладная '.$v['urid_id']);
		$reg_sum += $v['sum'];
		$reg_nds_sum += $v['nds'];
		$sum47401 += $sum47803;
		
		$percentCom1 = $db->getCom1($agr_id, $v['deb_id']);
		$com_nds += round($sum47803*$percentCom1/100*0.18);
        $com_doh += round($sum47803*$percentCom1/100);
	}	
	if($sum47401>0){
		$doc_47401_rs = $db->insertDocument($agr_id, 'ОПЛАТА_НА_РС', '09', $sum47401, $agr['acc47401'], $agr['acc_cur']
			, 'Оплата прав требования по Реестру № '.$agr['next_reg_num'].' от '.$db->getOperDay().', в т.ч. НДС '.($reg_nds_sum/100).' руб.');
		if($com_nds+$com_doh>0){
			$doc_rs_60311 = $db->insertDocument($agr_id, 'КОМ_И_НДС', '09', $com_nds+$com_doh, $agr['acc_cur'], localdb::acc60311
				, 'Единовременная комиссия по Договору факторинга № '.$agr['urid_id'].', Реестр № '.$agr['next_reg_num'].' от '.$db->getOperDay().', в т.ч. НДС '.($reg_nds_sum/100).' руб.');		
			$doc_60311_70601 = $db->insertDocument($agr_id, 'КОМ', '09', $com_doh, localdb::acc60311, localdb::acc70601
				, 'Отнесение на доходы единовременной комиссии по факторинговым операциям, '.$agr['name_cyr']);
			$doc_60311_60309 = $db->insertDocument($agr_id, 'НДС', '09', $com_nds, localdb::acc60311, localdb::acc60309
				, 'Отражение НДС по факторинговым операциям, '.$agr['name_cyr']);
			$db->setRegComDocs($reg_id, $doc_47401_rs, $doc_rs_60311, $doc_60311_70601, $doc_60311_60309);
		}
	}
	return ($reg_sum>0)?$reg_id:null;
}

function addSignatures(&$fctxml){  
	global $db;  
	
    $info = $db->getUserInfo();
	$fctxml->replaceField('user_job', $info['job']);
	$fctxml->replaceField('user_name', $info['fio']);
    $fctxml->replaceField('user_nach_job', 'Начальник кредитного отдела');
    $fctxml->replaceField('user_nach_name', 'ФИО начальника кр. отдела');
}

function new_registry_rasp_base($reg_id){
	global $db;  
	
	$fctxml = new FCTXML('FCT_NEW_REGISTRY.ODT');
    addSignatures($fctxml);   
    $reg = $db->getRegister($reg_id);
    $agr = $db->getAgr($reg['agr_id']);
    $fctxml->replaceField('dog_fact','№ '.$agr['urid_id'].' от '.$agr['signed_h']);
    $fctxml->replaceField('client', $agr['name_cyr']);
    $fctxml->replaceField('inn', $agr['inn']);
    $fctxml->addStyles('<style:style style:name="HLN_Таблица1.1" style:family="table-row">
 <style:table-row-properties style:keep-together="true" fo:keep-together="auto"/>
 </style:style>
 <style:style style:name="HLN_Таблица1.A1" style:family="table-cell">
 <style:table-cell-properties style:vertical-align="top" fo:padding-left="0.191cm" fo:padding-right="0.191cm" fo:padding-top="0cm" fo:padding-bottom="0cm" fo:border-left="0.018cm solid #000000" fo:border-right="none" fo:border-top="0.018cm solid #000000" fo:border-bottom="0.018cm solid #000000" style:writing-mode="lr-tb"/>
 </style:style>
 <style:style style:name="HLN_Таблица1.D1" style:family="table-cell">
 <style:table-cell-properties style:vertical-align="top" fo:padding-left="0.191cm" fo:padding-right="0.191cm" fo:padding-top="0cm" fo:padding-bottom="0cm" fo:border="0.018cm solid #000000" style:writing-mode="lr-tb"/>
 </style:style>
 <style:style style:name="HLN_P7" style:family="paragraph" style:parent-style-name="Standard">
 <style:paragraph-properties fo:text-align="center" style:justify-single-word="false" style:snap-to-layout-grid="false"/>
 <style:text-properties  style:font-name="Times New Roman" fo:font-size="10pt" fo:font-weight="bold" style:font-size-asian="10pt" style:font-weight-asian="bold" style:font-size-complex="10pt"/>
 </style:style>');   
	$v_nakl_num=0;
	$v_nakladnye="";
	$sumB=0;
	$sumVB=0;
	$v_nds_from_nakl=0;
	foreach($db->getRegisterInvoices($reg_id) as $i => $v){
		$v_nakl_num++;
		if($v_nakladnye!="")
			$v_nakladnye.=', ';
		$v_nakladnye.=$v['urid_id'];
		$debOb=$db->getDebOb($v['acc47803']);
		$sumB+=$debOb;
		$sumVB+=$v['sum'];
		$v_nds_from_nakl+=$v['nds'];
		for($i=1;$i<=2;$i++){
			$fctxml->insertRow('Таблица_баланс_'.$i, $v_nakl_num, ' <table:table-row table:style-name="HLN_Таблица1.1">
			 <table:table-cell table:style-name="HLN_Таблица1.A1" office:value-type="string">
			  <text:p text:style-name="HLN_P7">'.(($v_nakl_num==1)?'Счет':'').'</text:p>
			 </table:table-cell>
			 <table:table-cell table:style-name="HLN_Таблица1.A1" office:value-type="string">
			  <text:p text:style-name="HLN_P7">'.$v['acc47803'].'</text:p>
			 </table:table-cell>
			  <table:table-cell table:style-name="HLN_Таблица1.A1" office:value-type="string">
			   <text:p text:style-name="HLN_P7">'.$agr['acc47401'].'</text:p>
			  </table:table-cell>
			  <table:table-cell table:style-name="HLN_Таблица1.D1" office:value-type="string">
			   <text:p text:style-name="HLN_P7">'.financial2str($debOb).'</text:p>
			  </table:table-cell>
			 </table:table-row>');
			$fctxml->insertRow('Таблица_внебаланс_'.$i, $v_nakl_num,             
					' <table:table-row table:style-name="HLN_Таблица1.1">
			 <table:table-cell table:style-name="HLN_Таблица1.A1" office:value-type="string">
			  <text:p text:style-name="HLN_P7">'.(($v_nakl_num==1)?'Счет':'').'</text:p>
			 </table:table-cell>
			 <table:table-cell table:style-name="HLN_Таблица1.A1" office:value-type="string">
			  <text:p text:style-name="HLN_P7">'.$v['acc91418'].'</text:p>
			 </table:table-cell>
			 <table:table-cell table:style-name="HLN_Таблица1.A1" office:value-type="string">
			  <text:p text:style-name="HLN_P7">'.localdb::acc99999.'</text:p>
			 </table:table-cell>
			 <table:table-cell table:style-name="HLN_Таблица1.D1" office:value-type="string">
			  <text:p text:style-name="HLN_P7">'.financial2str($v['sum']).'</text:p>
			 </table:table-cell>
			</table:table-row>' );
		}        
         
	}
    
    $fctxml->replaceField('date', $reg['d']);
    $fctxml->replaceField('reestr', $reg['num'].' от '.$reg['d']);
    $fctxml->replaceField('nakladnye', $v_nakladnye); //~ hln_xml.AddField_DOC(p_doc, 'nakladnye',v_nakladnye);
    $fctxml->replaceField('sum', financial2str($sumB*2));
    $fctxml->replaceField('sum_prop', $fctxml->num2str($sumB*2));
    $fctxml->replaceField('sum_vb', financial2str($sumVB));
    $fctxml->replaceField('sum_vb_prop', $fctxml->num2str($sumVB));
    $fctxml->replaceField('sum_nds_nakl', ($v_nds_from_nakl==0)?', без НДС':', в т.ч. НДС '.financial2str($v_nds_from_nakl).' руб.');
    $fctxml->replaceField('sum_nds', financial2str($reg['d60311_60309']));
    $fctxml->replaceField('sum_doh', financial2str($reg['d60311_70601']));
    $fctxml->replaceField('sum_doh_nds', financial2str($reg['d_rs_60311']));
    $fctxml->replaceField('sum_doh_nds_prop', $fctxml->num2str($reg['d_rs_60311']));
    $fctxml->replaceField('rs_clienta', $agr['acc_cur']);
    //~ hln_xml.AddField_DOC(p_doc, 'sum_prop',REPS_SUPPORT.SPELL_AMOUNT(v_sum*2, '810', 'РУССКИЙ', 0));
    //~ hln_xml.AddField_DOC(p_doc, 'sum_vb_prop',REPS_SUPPORT.SPELL_AMOUNT(v_sum_vb, '810', 'РУССКИЙ', 0));
    //~ hln_xml.AddField_DOC(p_doc, 'sum_doh_nds_prop', REPS_SUPPORT.SPELL_AMOUNT(v_com_nds+v_com_doh, '810', 'РУССКИЙ', 0));
	for($i=1;$i<=2;$i++){
		$fctxml->insertRow('Таблица_баланс_'.$i, $v_nakl_num+1, ' <table:table-row table:style-name="HLN_Таблица1.1">
		 <table:table-cell table:style-name="HLN_Таблица1.A1" office:value-type="string">
		  <text:p text:style-name="HLN_P7"> </text:p>
		 </table:table-cell>
		 <table:table-cell table:style-name="HLN_Таблица1.A1" office:value-type="string">
		  <text:p text:style-name="HLN_P7">'.$agr['acc47401'].'</text:p>
		 </table:table-cell>
		  <table:table-cell table:style-name="HLN_Таблица1.A1" office:value-type="string">
		   <text:p text:style-name="HLN_P7">'.$agr['acc_cur'].'</text:p>
		  </table:table-cell>
		  <table:table-cell table:style-name="HLN_Таблица1.D1" office:value-type="string">
		   <text:p text:style-name="HLN_P7">'.financial2str($sumB).'</text:p>
		  </table:table-cell>
		 </table:table-row>');		   
	} 
    return $fctxml;
}  


function new_registry($agr_id){
	$reg_id = create_registry($agr_id);

	$fctxml = new_registry_rasp_base($reg_id);	

	//~ $fctxml->copyfile('FCT_SPR_DB.ods');

}

function do_repay_agr($agr_id){
	global $db;  
	
	$operDayJ = $db->getOperDayJ();
	$header_id = $db->insertRepayHeader($agr_id);
	$agr = $db->getAgr($agr_id);	
	foreach ($db->getDebitors($agr_id) as $i => $value) {
		$amount61212 = $db->getAccountAmount($value['acc61212']);
		if($amount61212==0)
			continue;
			//~ echo "amount61212 = $amount61212;";
		foreach ($db->getDebInv4Repay($agr_id, $value['cust_id']) as $i_inv => $value_inv) { //!!!!!!!Переписать getDebInv4Repay
			$a91418_amount=abs($db->getAccountAmount($value_inv['acc91418']));
			if($a91418_amount==0)
				continue;
			$a47803_amount=abs($db->getAccountAmount($value_inv['acc47803']));
			if($a47803_amount==0)
				continue;
			$days = $operDayJ-$value_inv['register_d'];
			$sum_ssud = 0;
			$sum_vb = 0;
			if($amount61212 < $a91418_amount){
				$sum_ssud=round($amount61212*0.9); 
				$sum_vb=$amount61212; 				
			}else{ //Достаточно для полного погашения
				$sum_ssud=$a47803_amount; 
				$sum_vb=$a91418_amount;     				
			}
			$amount61212-=$sum_vb;
			$sum_doh=round($sum_ssud*$days*$agr['comfin']/100); 
			$sum_nds=round($sum_doh*0.18); 		
			$doc_61212_70601 = 0;
			$doc_61212_60309 = 0;
			$doc_61212_47803 = $db->insertDocument($agr_id, 'ПОГ', '09', $sum_ssud, $value['acc61212'], $value_inv['acc47803']
				, 'Погашение приобретенных прав требования по договору поставки № '.$value['deliv_agr_id'].' накладная № '.$value_inv['urid_id']);
			
			if($sum_doh>0){
				$doc_61212_70601 = $db->insertDocument($agr_id, 'ПОГ', '09', $sum_doh, $value['acc61212'], localdb::acc70601
				, 'Отнесение финансового результата на счета учета доходов от проведения факторинговых операций по договору поставки № '.$value['deliv_agr_id']
					.' накладная № '.$value_inv['urid_id']);
			}
			if($sum_nds>0){
				$doc_61212_60309 = $db->insertDocument($agr_id, 'ПОГ', '09', $sum_nds, $value['acc61212'], localdb::acc60309
				, 'Отражение НДС по доходам от проведения факторинговых операций по договору поставки № '.$value['deliv_agr_id'].' накладная № '.$value_inv['urid_id']);
			}
			
			$sum_rs=$sum_vb-($sum_ssud+$sum_doh+$sum_nds);
			
			$doc_61212_rs = $db->insertDocument($agr_id, 'ПОГ', '09', $sum_rs, $value['acc61212'], $agr['acc_cur']
				, 'Выплата второго платежа по факторинговым операциям по договору поставки № '.$value['deliv_agr_id'].' накладная № '.$value_inv['urid_id']);

			$doc_91418 = $db->insertDocument($agr_id, 'ПОГ', '09', $sum_vb, localdb::acc99999, $value_inv['acc91418']
				, 'Списание номинальной стоимости по накладной № '.$value_inv['urid_id'].' '.$value['NAME_CYR'].'/ '.$value_inv['acc47803']);
								
  			$db->insertRepayEntry($header_id, $value_inv['id'], $doc_61212_47803, $doc_61212_70601, $doc_61212_60309, $doc_61212_rs, $doc_91418);	
		}		
	}	
}

function rasp_repay_agr($header_id){	
	$fctxml = new FCTXML('FCT_REPAY.ODT');
    addSignatures($fctxml);   
}

function repay_agr($agr_id){
	$header_id = do_repay_agr($agr_id);
	
	$fctxml = rasp_repay_agr($header_id);
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=DATA.FCTXML');

switch($_GET['rep']){
	case 'new_registry':
		new_registry($_GET['agr_id']);
		break;
	case 'registry':
		new_registry_rasp_base($_GET['id']);
		break;
	case 'repay_agr':
		repay_agr($_GET['agr_id']);
		break;
	case 'rasp_repay_agr':
		rasp_repay_agr($_GET['header_id']);
		break;
		
}
?>   
