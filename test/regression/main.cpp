#include <stdio.h>
#include <stdlib.h>
#include <string>

using namespace std;

#include <curl/curl.h>
#include <sqlite.h>

const char* SCHEMA =
"create table accounts(\n"
"	id text primary key\n"
"	,opened date\n"
"	,closed date\n"
"	,cust_id integer\n"
"	,name text\n"
");\n"
"create table agr(\n"
"	id integer primary key\n"
"	,urid_id text not null\n"
"	,signed date\n"
"	,closed date\n"
"	,cust_id integer not null\n"
"	,acc_cur text not null\n"
"	,acc47401 text not null\n"
"	,comfin real not null\n"
"	,next_reg_num integer not null\n"
"	,penya_cl real\n"
"	,fct_type integer not null\n"
");\n"
"create table customers(\n"
"	id integer primary key\n"
"	,name_cyr text\n"
"	,inn text\n"
");\n"
"create table debitor(\n"
"	agr_id integer not null\n"
"	,cust_id integer not null\n"
"	,lim integer not null\n"
"	,acc61212 text not null\n"
"	,deliv_agr_id text not null\n"
"	,deliv_agr_date date not null\n"
"	,com1 real not null\n"
"	,srok_otsr integer not null\n"
"	,penya real\n"
"	,fct_type integer not null\n"
");\n"
"create table documents(\n"
"	id integer primary key\n"
"	,agr_id integer\n"
"	,class_op text\n"
"	,doc_type text\n"
"	,value_date date\n"
"	,amount integer\n"
"	,deb_acc_id text\n"
"	,cr_acc_id text\n"
"	,nazn_pl text\n"
");\n"
"create table invoice(\n"
"	id integer primary key\n"
"	,urid_id text not null\n"
"	,agr_id integer not null\n"
"	,register_id integer\n"
"	,debitor_cust_id integer not null\n"
"	,inv_date date not null\n"
"	,date_otsr_agr date\n"
"	,payed date\n"
"	,nds integer not null\n"
"	,sum integer not null\n"
"	,acc47803 text\n"
"	,acc91418 text\n"
");\n"
"create table register(\n"
"	id integer primary key\n"
"	,agr_id integer not null\n"
"	,num integer not null\n"
"	,d date not null\n"
"	,doc_47401_rs number\n"
"	,doc_rs_60311 number\n"
"	,doc_60311_70601 number\n"
"	,doc_60311_60309 number\n"
");\n"
"create table repay_entry(\n"
"	id integer primary key\n"
"	,header_id integer not null\n"
"	,invoice_id integer not null\n"
"	,doc_47803 integer not null\n"
"	,doc_70601 integer\n"
"	,doc_60309 integer\n"
"	,doc_rs integer\n"
"	,doc_91418 integer not null\n"
");\n"
"create table repay_header(\n"
"	id integer primary key\n"
"	,agr_id integer not null\n"
"	,oper_date date not null\n"
"	,created date not null\n"
");\n"
"create table users(\n"
"	id integer primary key\n"
"	,login text\n"
"	,passwd text\n"
"	,styles text\n"
"	,fio text\n"
"	,job text\n"
"	,nach text\n"
"	,oper_day date\n"
");\n"
"CREATE UNIQUE INDEX invoiceUI ON invoice(urid_id, agr_id, debitor_cust_id, inv_date,sum);\n";


const char* INIT_DATA =
"INSERT INTO customers VALUES(1,'Первый клиент',1234567890);\n"
"INSERT INTO customers VALUES(2,'Второй клиент',2234567890);\n"
"INSERT INTO customers VALUES(3,'Третий клиент',3234567890);\n"
"INSERT INTO customers VALUES(4,'Первый дебитор первого клиента',4234567890);\n"
"INSERT INTO customers VALUES(5,'Второй дебитор первого клиента',5234567890);\n"
"INSERT INTO customers VALUES(6,'Третий дебитор первого клиента',6234567890);\n"
"INSERT INTO customers VALUES(7,'Единственный дебитор второго клиента',7234567890);\n"
"INSERT INTO customers VALUES(8,'Первый дебитор третьего клиента',8234567890);\n"
"INSERT INTO customers VALUES(9,'Второй дебитор третьего клиента',9234567890);\n"
"INSERT INTO customers VALUES(10,'Банк',0234567890);\n"
"\n"
"INSERT INTO users VALUES(1,'test',1,NULL,'Тестовый пользователь','Тестовая должность','test_nach',2456818.5);\n"
"INSERT INTO users VALUES(2,'ovved',1,'body {\n"
"background: #c7b39b url(tviks.jpg); /* Цвет фона и путь к файлу */\n"
"color: #fff; /* Цвет текста */\n"
"}','Ведерникова Ольга Викторовна','Экономист','test_nach',2456660.5);\n"
"INSERT INTO users VALUES(3,'test_nach',1,NULL,'Тестовый начальник','Тестовая должность начальника','test_nach',2456660.5);\n"
"\n"
"INSERT INTO accounts VALUES(40702810000000000001,2456413.5,NULL,1,'Первый клиент. Расчетный счет');\n"
"INSERT INTO accounts VALUES(40702810000000000002,2456511.5,NULL,2,'Второй клиент. Расчетный счет');\n"
"INSERT INTO accounts VALUES(40702810000000000003,2456597.5,NULL,3,'Третий клиент. Расчетный счет');\n"
"INSERT INTO accounts VALUES(47401810000000000001,2456413.5,NULL,1,'Первый клиент. Транзитный счет');\n"
"INSERT INTO accounts VALUES(47401810000000000002,2456511.5,NULL,2,'Второй клиент. Транзитный счет');\n"
"INSERT INTO accounts VALUES(47401810000000000003,2456597.5,NULL,3,'Третий клиент. Транзитный счет');\n"
"INSERT INTO accounts VALUES(60311810000000000000,2456293.5,NULL,10,'Транзитный счет для комиссий');\n"
"INSERT INTO accounts VALUES(70601810000000000000,2456293.5,NULL,10,'Доходы банка');\n"
"INSERT INTO accounts VALUES(60309810000000000000,2456293.5,NULL,10,'НДС');\n"
"INSERT INTO accounts VALUES(61212810000000100004,2456744.5,NULL,4,'Для погашений накладных от 1 деб 1 кл');\n"
"INSERT INTO accounts VALUES(61212810000000300008,'2014-05-29',NULL,8,'Первый дебитор третьего клиента');\n"
"INSERT INTO accounts VALUES(61212810000000300009,'2014-05-29',NULL,9,'Второй дебитор третьего клиента');\n";


const char* LOGIN_FORM =
"\n"
"	<html><head>\n"
"		<meta http-equiv=\"CONTENT-TYPE\" content=\"text/html; charset=UTF-8\">\n"
"		<title>Вход</title>\n"
"	</head><body>	\n"
"	 <FORM action=\"index.php\" method=\"post\">\n"
"		Логин <input id=\"login\" name=\"login\" size=\"10\" type=\"text\"><br/> Пароль <input id=\"passwd\" name=\"passwd\" size=\"10\" type=\"password\">\n"
"		<br/>\n"
"		<INPUT type=\"submit\" value=\"Вход\">\n"
"	</form>	</body></html>";

const char* FREE_MAIN_FORM =
"\n"
"\n"
"<html><head>\n"
"	<meta http-equiv=\"CONTENT-TYPE\" content=\"text/html; charset=UTF-8\">\n"
"	<title>Договоры</title>\n"
"	<meta name=\"GENERATOR\" content=\"OpenOffice.org 3.1  (Solaris x86)\">\n"
"	<meta name=\"CREATED\" content=\"0;0\">\n"
"	<meta name=\"CHANGED\" content=\"20130311;21321100\">\n"
"	<style type=\"text/css\">\n"
"	<!--\n"
"		@page { size: 21cm 29.7cm; margin: 2cm }\n"
"		P { margin-bottom: 0.21cm }\n"
"		PRE { font-family: \"Liberation Mono\", monospace; font-size: 10pt }\n"
"		A:link { color: #000080; so-language: zxx; text-decoration: underline }\n"
"		A:visited { color: #800000; so-language: zxx; text-decoration: underline }\n"
"	-->\n"
"	</style>\n"
"</head><body dir=\"LTR\" lang=\"ru-RU\" link=\"#000080\" vlink=\"#800000\">\n"
"<table style=\"page-break-before: always;\" width=\"650\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n"
"<tr valign=\"TOP\">\n"
"		<td>\n"
"			<pre style=\"text-align: left;\"><a href=\"exit.php\"><font face=\"Liberation Mono, monospace\"><font size=\"2\">Выход</font></font></a></pre>\n"
"		</td>\n"
"		<td>\n"
"			<pre style=\"text-align: left;\"><a href=\"agr.php\"><font face=\"Liberation Mono, monospace\"><font size=\"2\">Добавить договор</font></font></a></pre>\n"
"		</td>\n"
"		<td>\n"
"			<pre style=\"text-align: left;\"><a href=\"close_inv.php\"><font face=\"Liberation Mono, monospace\"><font size=\"2\">Закрыть списанные накладные</font></font></a></pre>\n"
"		</td>\n"
"		<td>\n"
"			<pre style=\"text-align: left;\"><a href=\"test.php\"><font face=\"Liberation Mono, monospace\"><font size=\"2\">Для тестирования</font></font></a></pre>\n"
"		</td>\n"
"		<td>\n"
"			<pre style=\"text-align: left;\"><font face=\"Liberation Mono, monospace\"><font size=\"2\">10-06-2014</font></font></pre>\n"
"		</td>\n"
"	</tr>\n"
"</table>\n"
"<center><font size=\"4\"><b>Действующие договоры</b></font><table border=\"1\" bordercolor=\"#000000\" cellpadding=\"1\" cellspacing=\"0\"><tr><td><i><p align=\"CENTER\"><font size=\"2\">Номер</font></p></i><td><i><p align=\"CENTER\"><font size=\"2\">Клиент</font></p></i><td><i><p align=\"CENTER\"><font size=\"2\">Дата подписания</font></p></i><td><i><p align=\"CENTER\"><font size=\"2\">Действия</font></p></i></tr></table>\n"
"\n"
"</p>\n"
"</body></html>\n";
 



const char* AGR_FORM=
"<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">\n"
"<html><head>\n"
"	<meta http-equiv=\"CONTENT-TYPE\" content=\"text/html; charset=UTF-8\">\n"
"	<title>Параметры договора</title>\n"
"<script src=\"financial.js\"></script>	\n"
"<script src=\"dates.js\"></script>	\n"
"<script>\n"
"function deleteRow(el) {\n"
"  // while there are parents, keep going until reach TR  		\n"
"  while (el.parentNode && el.tagName.toLowerCase() != 'tr') {\n"
"    el = el.parentNode;\n"
"  }\n"
"  \n"
"  if(el.getElementsByTagName('td')[1].innerHTML==''){\n"
"	  // If el has a parentNode it must be a TR, so delete it\n"
"	  if (el.parentNode) {\n"
"		el.parentNode.removeChild(el);\n"
"	  }\n"
"	  return;\n"
"  }\n"
"  if(confirm('Действительно удалить?')){\n"
"	document.getElementById('oper_type').value='delete_deb';\n"
"	document.getElementById('deb_cust_id').value=el.getElementsByTagName('td')[0].getElementsByTagName('input')[0].value;\n"
"	document.getElementById('main_form').submit();\n"
"  }\n"
"}\n"
"\n"
"function addDebitor()\n"
"{\n"
"	var table=document.getElementById(\"tblDebitors\");\n"
"	var row=table.insertRow(table.rows.length);\n"
"	var cell=row.insertCell(0); cell.innerHTML='<p align=\"LEFT\"><input type=\"text\" size=10>';\n"
"	var cell=row.insertCell(1); cell.innerHTML='';\n"
"	var cell=row.insertCell(2); cell.innerHTML='<p align=\"RIGHT\"><input type=\"text\" size=10>';\n"
"	var cell=row.insertCell(3); cell.innerHTML= '<p align=\"RIGHT\"><input type=\"text\" size=20 maxlength=\"20\">';\n"
"	var cell=row.insertCell(4); cell.innerHTML='<p align=\"LEFT\"><input type=\"text\"  size=10>';		\n"
"	var cell=row.insertCell(5); cell.innerHTML='<p align=\"RIGHT\"><input type=\"text\" size=10 maxlength=\"10\">';		\n"
"	var cell=row.insertCell(6); cell.innerHTML='<p align=\"RIGHT\"><input type=\"text\" size=5>';		\n"
"	var cell=row.insertCell(7); cell.innerHTML='<p align=\"RIGHT\"><input type=\"text\" size=2>';	\n"
"	var cell=row.insertCell(8); cell.innerHTML='<p align=\"RIGHT\"><input type=\"text\" size=5>';	\n"
"	var cell=row.insertCell(9); cell.innerHTML='<select><option value=\"1\">Открытый с регрессом (пени только с клиента)</option>;<option value=\"2\">Открытый с регрессом</option>;<option value=\"3\">Закрытый</option>; </select>';\n"
"	var cell=row.insertCell(10); cell.innerHTML='<p align=\"RIGHT\"><input value=\"Удалить\" type=\"button\"  onclick=\"deleteRow(this);\">';\n"
"}\n"
"\n"
"function checkDelAgr(){\n"
"	if(confirm('Действительно удалить договор?')){\n"
"		document.getElementById('oper_type').value='delete';\n"
"		return true;\n"
"	}\n"
"	return false;\n"
"}\n"
"\n"
"function saveAgr()\n"
"{\n"
"	var v_urid_id = document.getElementById(\"urid_id\").value;\n"
"	if(v_urid_id==''){\n"
"		alert('Введите юридический номер договора');\n"
"		document.getElementById(\"urid_id\").focus();\n"
"		document.getElementById(\"urid_id\").select();\n"
"		return false;\n"
"	}\n"
"	\n"
"	var arr_err = []\n"
"	\n"
"	document.getElementById(\"signed_cor\").value = form_and_check_std_dat(document.getElementById(\"signed\").value, arr_err);\n"
"	if(arr_err.length>0){\n"
"		alert(\"Ошибка в дате подписания: \"+arr_err[0])\n"
"		document.getElementById(\"signed\").focus();\n"
"		document.getElementById(\"signed\").select();\n"
"		return false;\n"
"	}\n"
"	\n"
"	var v_cust_id = document.getElementById(\"cust_id\").value;\n"
"	if(v_cust_id==''){\n"
"		alert('Введите номер клиента');\n"
"		document.getElementById(\"cust_id\").focus();\n"
"		document.getElementById(\"cust_id\").select();\n"
"		return false;\n"
"	}\n"
"\n"
"	var v_next_reg_num = document.getElementById(\"next_reg_num\").value;\n"
"	if(v_next_reg_num==''){\n"
"		alert('Введите следующий номер реестра');\n"
"		document.getElementById(\"next_reg_num\").focus();\n"
"		document.getElementById(\"next_reg_num\").select();\n"
"		return false;\n"
"	}\n"
"	\n"
"	\n"
"	var v_acc_cur = document.getElementById(\"acc_cur\").value;\n"
"	if(v_acc_cur.length!=20){\n"
"		alert('Номер расчетного счета должен содержать 20 цифр');\n"
"		document.getElementById(\"acc_cur\").focus();\n"
"		document.getElementById(\"acc_cur\").select();\n"
"		return false;\n"
"	}	\n"
"\n"
"	var v_acc47401 = document.getElementById(\"acc47401\").value;\n"
"	if(v_acc47401.length!=20){\n"
"		alert('Номер счета 47401 должен содержать 20 цифр');\n"
"		document.getElementById(\"acc47401\").focus();\n"
"		document.getElementById(\"acc47401\").select();\n"
"		return false;\n"
"	}	\n"
"\n"
"	var v_comfin = getFinancial(document.getElementById('comfin').value, arr_err)\n"
"	if(arr_err.length>0){\n"
"		alert(\"Ошибка в комиссии за финансирование: \"+arr_err[0])\n"
"		document.getElementById(\"comfin\").focus()\n"
"		document.getElementById(\"comfin\").select()\n"
"		return false\n"
"	}	\n"
"	\n"
"	var arr_debs = [];\n"
"	var table=document.getElementById(\"tblDebitors\");	\n"
"	for(var i=1;i<table.rows.length;i++){\n"
"		var cust_id = table.rows[i].cells[0].getElementsByTagName('input')[0].value;\n"
"		var arr_err = [];\n"
"		var lim_input = table.rows[i].cells[2].getElementsByTagName('input')[0];\n"
"		var lim = getFinancial(lim_input.value, arr_err);\n"
"		if(arr_err.length>0){\n"
"			alert(\"Ошибка в лимите: \"+arr_err[0])\n"
"			lim_input.focus()\n"
"			lim_input.select()\n"
"			return false\n"
"		}\n"
"		var acc61212 = table.rows[i].cells[3].getElementsByTagName('input')[0].value;\n"
"		var deliv_agr_id = table.rows[i].cells[4].getElementsByTagName('input')[0].value;\n"
"		var deliv_agr_date_input = table.rows[i].cells[5].getElementsByTagName('input')[0];\n"
"		var deliv_agr_date = form_and_check_std_dat(deliv_agr_date_input.value, arr_err);\n"
"		if(arr_err.length>0){\n"
"			alert(\"Ошибка в дате договора поставки: \"+arr_err[0])\n"
"			deliv_agr_date_input.focus();\n"
"			deliv_agr_date_input.select();\n"
"			return false;\n"
"		}		\n"
"		\n"
"		\n"
"		var com1 = table.rows[i].cells[6].getElementsByTagName('input')[0].value;\n"
"		var srok_otsr = table.rows[i].cells[7].getElementsByTagName('input')[0].value;\n"
"		var penya = table.rows[i].cells[8].getElementsByTagName('input')[0].value;\n"
"		var fct_type = table.rows[i].cells[9].getElementsByTagName('select')[0].value;\n"
"		\n"
"		arr_debs.push({\n"
"			'cust_id':cust_id,\n"
"			'lim':lim, \n"
"			'acc61212':acc61212, \n"
"			'deliv_agr_id':deliv_agr_id, \n"
"			'deliv_agr_date':deliv_agr_date, \n"
"			'com1':com1, \n"
"			'srok_otsr':srok_otsr,\n"
"			'penya':penya,\n"
"			'fct_type':fct_type\n"
"		});\n"
"	}	\n"
"	//alert('debs='+JSON.stringify(arr_debs));\n"
"	document.getElementById(\"arr_debs\").value = JSON.stringify(arr_debs);	\n"
"	return true;\n"
"}\n"
"\n"
"</script>	\n"
"</head><body>\n"
"\n"
"\n"
"<table style=\"page-break-before: always;\" width=\"262\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n"
"<tr valign=\"TOP\">\n"
"		<td>\n"
"			<pre style=\"text-align: left;\"><a href=\"index.php\"><font face=\"Liberation Mono, monospace\"><font size=\"2\">Договоры</font></font></a></pre>\n"
"		</td>		\n"
"		<td>\n"
"			<pre style=\"text-align: left;\"><a href=\"exit.php\"><font face=\"Liberation Mono, monospace\"><font size=\"2\">Выход</font></font></a></pre>\n"
"		</td>		\n"
"	</tr>\n"
"</table>\n"
"<form id=\"main_form\" action=\"agr.php\" method=\"post\">\n"
"<table border=\"0\" cellpadding=\"0\" cellspacing=\"2\">\n"
"\n"
"<b>Обычный<b><tr><td>Юридический номер<td><input id=\"urid_id\" name=\"urid_id\" size=\"16\" type=\"text\" maxlength=\"16\" value=\"1-2014ф\">\n"
"<tr><td>Дата подписания<td><input id=\"signed\" name=\"signed\"  size=\"6\" type=\"text\" maxlength=\"6\" onkeyup=\"return proverka_dat(this);\" onchange=\"return proverka_dat(this);\"> \n"
"<tr><td>Номер клиента<td><input id=\"cust_id\"  name=\"cust_id\" size=\"10\" type=\"text\" maxlength=\"10\" onkeyup=\"return proverka_dat(this);\" onchange=\"return proverka_dat(this);\">\n"
"<tr><td>Следующий номер реестра<td><input id=\"next_reg_num\"  name=\"next_reg_num\" size=\"10\" type=\"text\" maxlength=\"10\" onkeyup=\"return proverka_dat(this);\" onchange=\"return proverka_dat(this);\" value=\"1\">\n"
"<tr><td>Расчетный счет<td><input id=\"acc_cur\"  name=\"acc_cur\" size=\"20\" type=\"text\" maxlength=\"20\" onkeyup=\"return proverka_dat(this);\" onchange=\"return proverka_dat(this);\">\n"
"<tr><td>Счет 47401<td><input id=\"acc47401\"  name=\"acc47401\" size=\"20\" type=\"text\" maxlength=\"20\" onkeyup=\"return proverka_dat(this);\" onchange=\"return proverka_dat(this);\">\n"
"<tr><td>Комиссия за финансирование<td><input id=\"comfin\"  name=\"comfin\" size=\"10\"  maxlength=\"10\" type=\"text\" onkeyup=\"return proverka_fin(this);\" onchange=\"return proverka_fin(this);\">\n"
"<tr><td>Пеня с клиента<td><input id=\"penya_cl\"  name=\"penya_cl\" size=\"10\"  maxlength=\"10\" type=\"text\" onkeyup=\"return proverka_fin(this);\" onchange=\"return proverka_fin(this);\"><tr><td>Тип договора<td><select name=\"fct_type\" id=\"fct_type\"><option value=\"0\">Обычный</option>;<option value=\"1\">Реверсивный</option>; </select></table>\n"
"<br><input value=\"Создать договор\" type=\"submit\"  onclick=\"return saveAgr();\">\n"
"<input type=\"hidden\" id=\"signed_cor\" name=\"signed_cor\">\n"
"<input type=\"hidden\" id=\"arr_debs\" name=\"arr_debs\">\n"
"<input type=\"hidden\" id=\"agr_id\" name=\"agr_id\" value=\"\"></form>\n"
"	\n"
"</body></html>\n"
;

static size_t WriteMemoryCallback(void *contents, size_t size, size_t nmemb, void *userp){
	size_t realsize = size * nmemb;
	string *s = (string *)userp;

	s->append((char*)contents, realsize);
	s+=char(0);
	return realsize;
}
 
CURL *curl_handle;

string getQuery(const char* url){ 
	curl_easy_setopt(curl_handle, CURLOPT_COOKIEFILE, "");
	curl_easy_setopt(curl_handle, CURLOPT_URL, url);
	curl_easy_setopt(curl_handle, CURLOPT_FOLLOWLOCATION, 1L);
	curl_easy_setopt(curl_handle, CURLOPT_WRITEFUNCTION, WriteMemoryCallback);
	string chunk;
	curl_easy_setopt(curl_handle, CURLOPT_WRITEDATA, (void *)&chunk);
	CURLcode res = curl_easy_perform(curl_handle);

	/* check for errors */ 
	if(res != CURLE_OK) {
		fprintf(stderr, "curl_easy_perform() failed: %s\n",
		curl_easy_strerror(res));
	}
	

	return chunk;
}

string postQuery(const char* url, const char* postFields){ 
	curl_easy_setopt(curl_handle, CURLOPT_COOKIEFILE, "");
	curl_easy_setopt(curl_handle, CURLOPT_URL, url);
	curl_easy_setopt(curl_handle, CURLOPT_FOLLOWLOCATION, 1L);
	curl_easy_setopt(curl_handle, CURLOPT_WRITEFUNCTION, WriteMemoryCallback);
	string chunk;
	curl_easy_setopt(curl_handle, CURLOPT_WRITEDATA, (void *)&chunk);
	curl_easy_setopt(curl_handle, CURLOPT_POSTFIELDS, postFields);
	CURLcode res = curl_easy_perform(curl_handle);

	/* check for errors */ 
	if(res != CURLE_OK) {
		fprintf(stderr, "curl_easy_perform() failed: %s\n",
		curl_easy_strerror(res));
	}
	return chunk;
}

void getDiff(bool f, string res, const char*etalon){
	static int testNum = 0;
	testNum++;
	if(!f){
		char buf[200];
		snprintf(buf, sizeof(buf),"%i_r",testNum);
		FILE * file = fopen(buf,"w");
		fwrite(res.c_str(), res.length(),1,file);
		fclose(file);
		snprintf(buf, sizeof(buf),"%i_e",testNum);
		file = fopen(buf,"w");
		fwrite(etalon, strlen(etalon),1,file);
		fclose(file);
		snprintf(buf, sizeof(buf),"diff -u %i_r %i_e",testNum,testNum);
		system(buf);
	}	
}

void checkGetQuery(const char* url, const char* etalon){
	string res = getQuery(url);
	bool f = (res==etalon);
	printf("%s: %s\n", f?"OK":"Err",url);
	getDiff(f, res, etalon);
}

void checkPostQuery(const char* url, const char* postFields, const char* etalon){
	string res = postQuery(url, postFields);
	bool f = (res==etalon);
	printf("%s: %s | %s \n", f?"OK":"Err",url, postFields);
	if(!f)
		printf("%s\n", res.c_str());
}

int main(void)
{
	sqlite *db;
	char *zErr;
	unlink("../../fct.db");
	db = sqlite_open("../../fct.db", 0, &zErr);
	if( db==0 ){
		printf("Cannot initialize sqlite2: %s\n", zErr);
		return 1;
	}
	sqlite_exec(db, SCHEMA, 0, 0, 0);
	sqlite_exec(db, INIT_DATA, 0, 0, 0);
	sqlite_close(db);

	curl_global_init(CURL_GLOBAL_ALL);
	curl_handle = curl_easy_init();
	checkGetQuery("http://localhost:81/openfactoring_mini/index.php", LOGIN_FORM);
	checkPostQuery("http://localhost:81/openfactoring_mini/index.php", "login=test&passwd=1", FREE_MAIN_FORM);
	checkGetQuery("http://localhost:81/openfactoring_mini/agr.php", AGR_FORM);
	//printf("%s\n", postQuery("http://localhost:81/openfactoring_mini/index.php", "login=test&passwd=1").c_str());

	curl_easy_cleanup(curl_handle);
	curl_global_cleanup();
	return 0;
}
