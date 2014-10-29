PRAGMA foreign_keys = ON;
BEGIN TRANSACTION;
create table customers(
	id integer primary key
	,name_cyr text
	,inn text
);
INSERT INTO customers VALUES(1,'Первый клиент','1234567890');
INSERT INTO customers VALUES(2,'Второй клиент','2234567890');
INSERT INTO customers VALUES(3,'Третий клиент','3234567890');
INSERT INTO customers VALUES(4,'Первый дебитор первого клиента','4234567890');
INSERT INTO customers VALUES(5,'Второй дебитор первого клиента','5234567890');
INSERT INTO customers VALUES(6,'Третий дебитор первого клиента','6234567890');
INSERT INTO customers VALUES(7,'Единственный дебитор второго клиента','7234567890');
INSERT INTO customers VALUES(8,'Первый дебитор третьего клиента','8234567890');
INSERT INTO customers VALUES(9,'Второй дебитор третьего клиента','9234567890');
INSERT INTO customers VALUES(10,'Банк','0234567890');
create table accounts(
	id text primary key
	,opened date
	,closed date
	,cust_id integer
	,name text
);
INSERT INTO accounts VALUES('40702810000000000001',2456413.5,NULL,1,'Первый клиент. Расчетный счет');
INSERT INTO accounts VALUES('40702810000000000002',2456511.5,NULL,2,'Второй клиент. Расчетный счет');
INSERT INTO accounts VALUES('40702810000000000003',2456597.5,NULL,3,'Третий клиент. Расчетный счет');
INSERT INTO accounts VALUES('47401810000000000001',2456413.5,NULL,1,'Первый клиент. Транзитный счет');
INSERT INTO accounts VALUES('47401810000000000002',2456511.5,NULL,2,'Второй клиент. Транзитный счет');
INSERT INTO accounts VALUES('47401810000000000003',2456597.5,NULL,3,'Третий клиент. Транзитный счет');
INSERT INTO accounts VALUES('60311810000000000000',2456293.5,NULL,10,'Транзитный счет для комиссий');
INSERT INTO accounts VALUES('70601810000000000000',2456293.5,NULL,10,'Доходы банка');
INSERT INTO accounts VALUES('60309810000000000000',2456293.5,NULL,10,'НДС');
INSERT INTO accounts VALUES('61212810000000100004',2456744.5,NULL,4,'Для погашений накладных от 1 деб 1 кл');
INSERT INTO accounts VALUES('61212810000000100005',2456744.5,NULL,4,'Для погашений накладных от 2 деб 1 кл');
INSERT INTO accounts VALUES('61212810000000300008','2014-05-29',NULL,8,'Первый дебитор третьего клиента');
INSERT INTO accounts VALUES('61212810000000300009','2014-05-29',NULL,9,'Второй дебитор третьего клиента');
create table agr(
	id integer primary key
	,urid_id text not null
	,signed date
	,closed date
	,cust_id integer not null REFERENCES customers(id) ON UPDATE CASCADE ON DELETE RESTRICT
	,acc_cur text not null REFERENCES accounts(id) ON UPDATE CASCADE ON DELETE RESTRICT
	,acc47401 text not null REFERENCES accounts(id) ON UPDATE CASCADE ON DELETE RESTRICT
	,comfin real not null
	,next_reg_num integer not null
	,penya_cl real
	,fct_type integer not null
);
INSERT INTO agr VALUES(1,'1-2014ф',2456901.5,NULL,1,'40702810000000000001','47401810000000000001',0.041,1,0,0);
INSERT INTO agr VALUES(2,'2-2014ф',2456901.5,NULL,3,'40702810000000000003','47401810000000000003',0.041,1,0,0);
create table debitor(
	agr_id integer not null REFERENCES agr(id) ON UPDATE CASCADE ON DELETE CASCADE
	,cust_id integer not null REFERENCES customers(id) ON UPDATE CASCADE ON DELETE RESTRICT
	,lim integer not null
	,acc61212 text not null REFERENCES accounts(id) ON UPDATE CASCADE ON DELETE RESTRICT
	,deliv_agr_id text not null
	,deliv_agr_date date not null
	,com1 real not null
	,srok_otsr integer not null
	,penya real
	,fct_type integer not null
	,PRIMARY KEY(agr_id,cust_id)
);
INSERT INTO debitor VALUES(1,4,12000000,'61212810000000100004','1/4',2456911.5,0.5,45,'',1);
INSERT INTO debitor VALUES(2,8,50000000,'61212810000000300008','1/5',2456884.5,0.75,90,0.1,2);
create table documents(
	id integer primary key
	,agr_id integer
	,class_op text
	,doc_type text
	,value_date date
	,amount integer
	,deb_acc_id text
	,cr_acc_id text
	,nazn_pl text
);
create table register(
	id integer primary key
	,agr_id integer not null
	,num integer not null
	,d date not null
	,doc_47401_rs number REFERENCES documents(id) ON UPDATE CASCADE ON DELETE CASCADE
	,doc_rs_60311 number REFERENCES documents(id) ON UPDATE CASCADE ON DELETE CASCADE
	,doc_60311_70601 number REFERENCES documents(id) ON UPDATE CASCADE ON DELETE CASCADE
	,doc_60311_60309 number REFERENCES documents(id) ON UPDATE CASCADE ON DELETE CASCADE
);
create table invoice(
	id integer primary key
	,urid_id text not null
	,agr_id integer not null 
	,register_id integer REFERENCES register(id) ON UPDATE CASCADE ON DELETE CASCADE
	,debitor_cust_id integer not null
	,inv_date date not null
	,date_otsr_agr date
	,payed date
	,nds integer not null
	,sum integer not null
	,acc47803 text REFERENCES accounts(id) ON UPDATE CASCADE ON DELETE RESTRICT
	,acc91418 text REFERENCES accounts(id) ON UPDATE CASCADE ON DELETE RESTRICT
	,FOREIGN KEY(agr_id, debitor_cust_id) REFERENCES debitor(agr_id, cust_id)
);
create table repay_header(
	id integer primary key
	,agr_id integer not null REFERENCES agr(id) ON UPDATE CASCADE ON DELETE CASCADE
	,oper_date date not null
	,created date not null
);
create table repay_entry(
	id integer primary key
	,header_id integer not null REFERENCES repay_header(id) ON UPDATE CASCADE ON DELETE CASCADE
	,invoice_id integer not null REFERENCES invoice(id) ON UPDATE CASCADE ON DELETE RESTRICT
	,doc_47803 integer not null REFERENCES documents(id) ON UPDATE CASCADE ON DELETE CASCADE
	,doc_70601 integer REFERENCES documents(id) ON UPDATE CASCADE ON DELETE CASCADE
	,doc_60309 integer REFERENCES documents(id) ON UPDATE CASCADE ON DELETE CASCADE
	,doc_rs integer REFERENCES documents(id) ON UPDATE CASCADE ON DELETE CASCADE
	,doc_91418 integer not null REFERENCES documents(id) ON UPDATE CASCADE ON DELETE CASCADE
);
create table users(
	id integer primary key
	,login text
	,passwd text
	,styles text
	,fio text
	,job text
	,nach text
	,oper_day date
);
INSERT INTO users VALUES(1,'test',1,NULL,'Тестовый пользователь','Тестовая должность','test_nach',2456818.5);
INSERT INTO users VALUES(2,'ovved',1,'body {
background: #c7b39b url(tviks.jpg); /* Цвет фона и путь к файлу */
color: #fff; /* Цвет текста */
}','Ведерникова Ольга Викторовна','Экономист','test_nach',2456660.5);
INSERT INTO users VALUES(3,'test_nach',1,NULL,'Тестовый начальник','Тестовая должность начальника','test_nach',2456660.5);
CREATE UNIQUE INDEX invoiceUI ON invoice(urid_id, agr_id, debitor_cust_id, inv_date,sum);
COMMIT;

