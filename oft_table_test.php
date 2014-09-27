<?php
include "oft_table.php";
	oftTable::init('Test');
	oftTable::header(array('123','456','789'));
	oftTable::row(array('A123','A456','A789'));
	oftTable::end();
?>
