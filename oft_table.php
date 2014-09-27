<?php
class oftTable{
	function init($name,$id = null){
		echo "<center><font size=\"4\"><b>$name</b></font><table ".(($id)?"id=\"$id\" ":"")."border=\"1\" bordercolor=\"#000000\" cellpadding=\"1\" cellspacing=\"0\">";
	}
	function header($h){
		echo "<tr>";
		foreach ($h as $i => $value) {
		    echo "<td><i><p align=\"CENTER\"><font size=\"2\">$value</font></p></i>";
		}
		echo "</tr>";
	}
	function row($r){
		echo "<tr>";
		foreach ($r as $i => $value) {
		    echo "<td>$value";
		}
		echo "</tr>";
	}
	function end(){
		echo "</table>";
	}

	function addCol($a,$num,$value){
		for($i=count($a);$i>$num;$i--){
			$a[$i]=$a[$i-1];
		}
		$a[$num]=$value;
		return $a;
	}
};	

?>
