<?php 
function financial2str($f){
	$s='';
	$t=$f;
	for($i=0;($i<3)||($t>0);$i++){
		if($i==2){
			$s='.'.$s;
		}else
			if(($i-2)%3==0){
				$s="'".$s;
			}
		$s=($t%10).$s;
		$t=floor($t/10);
	}
	return $s;
}
?>
