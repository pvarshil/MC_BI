<?php

function Auth($authorization){
	list($type, $authorization) = explode(" ", $authorization);
	
	$valid_tokens = array(
		'FREETOKEN2',
	);
	
	if(in_array($authorization, $valid_tokens)) return true;
	
	return false;
}

?>
