<?php
$a = 'value.a.b.c';

function standardized_variable_form($a) {
	if (false == strpos($a, '.')) {
		return '$this->data[\'' . $a . '\']';
	} else {
		$b = explode('.', $a);
		$c = array();
		$c[] = '$this->data';
		foreach ($b as $i => $v) {
			$c[] = '[\'' . $v . '\']';
		}
		
		return implode('', $c);
	}
}

echo standardized_variable_form($a);