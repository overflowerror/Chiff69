#!/usr/bin/php
<?php
	/*
	 * parameters:
	 *	1: passphrase
	 *	2: encrypt or decrypt
	 *	3: base64 (optimal)
	 *	
	 * everthing else: stdin/stdout
	 */

	if ($_SERVER['argc'] < 2)
		exit(); // no password given
	$passphrase = $_SERVER['argv'][1];

	$key = array();
	for ($i = 0; $i < 5; $i++)
		$key[$i] = 0x55;

	for ($i = 0; $i < strlen($passphrase); $i++) {
		$char = ord(substr($passphrase, $i, 1));
		$key[$i % 5] ^= $char;
	}

	for ($i = 0; $i < 4; $i++)
		$key[$i] &= 0xff;
	$key[4] &= 0x1f;

	$text = "";

	$stdin = fopen('php://stdin', 'r');
	$stderr = fopen("php://stderr", "w");
	
 	for ($i = 0; !feof($stdin); $i++) {
		$char = fread($stdin, 1);
		$text .= $char;
	}

	if (($_SERVER['argc'] > 3) && ($_SERVER['argv'][3] == "base64") && ($_SERVER['argv'][2] == "decrypt"))
		$text = base64_decode($text);

	if ($key[4] == 0)
		$key[4] = 0x55;
		
	//$key[4] = 2;
		
	$tmp = array();
	
	if (($_SERVER['argc'] > 2) && ($_SERVER['argv'][2] == "decrypt")) {
		for ($i = 0; $i < strlen($text); $i++) {
			$tmp[] = $text[$i];
		}
		
		$text = array();
		
		for ($i = 0; $i < count($tmp); $i++) {
			$text[] = $tmp[$i];
		}
	} else {
		for ($i = 0; $i < $key[4]; $i++)
			$tmp[$i] = array();
		
		for ($i = 0; $i < strlen($text); $i++) {
			$tmp[$i % $key[4]][] = $text[$i];
		}
		
		$max = 0;
		
		for($i = 0; $i < count($tmp); $i++) {
			if (count($tmp[$i]) > $max) {
				$max = count($tmp[$i]);
				$i = 0;
				continue;
			}
			while (count($tmp[$i]) < $max) {
				$tmp[$i][] = " ";
			}
		}
		
		$text = array();
		
		for ($i = 0; $i < count($tmp); $i++) 
			for ($j = 0; $j < count($tmp[$i]); $j++)
				$text[] = $tmp[$i][$j];
	}

	
	
	$tmp = array();

	for ($i = 0; $i < count($text); $i++) {
		$tmp[$i] = ord($text[$i]);
	}
	$text = $tmp;
	
	$alg = "";
	// we are decrypting
	if (($_SERVER['argc'] > 2) && ($_SERVER['argv'][2] == "decrypt")) {
		$alg = create_function('$char, $key, $i', 'return (($char - $i) - $key) & 0xff;');
	// we are encrypting
	} else {
		$alg = create_function('$char, $key, $i', 'return (($char + $key) + $i) & 0xff;');
	}

	$result = array();
	for ($i = 0; $i < count($text); $i++) {
		$result[$i] = $alg($text[$i], $key[$i % 4], $i * (2 * ($i % 2) - 1));
	}

	for ($i = 0; $i < count($result); $i++)
		$result[$i] = chr($result[$i]);
		
	if (($_SERVER['argc'] > 2) && ($_SERVER['argv'][2] == "decrypt")) {
		$tmp = array();
		for ($i = 0; $i < $key[4]; $i++)
			$tmp[$i] = array();
		
		for ($i = 0; $i < count($result); $i++) {
			$a = (count($result) / $key[4]);
			$b = $i % ceil($a);
			$tmp[intval($b)][] = $result[$i];
		}
		
		$result = array();
		
		for ($i = 0; $i < count($tmp); $i++) 
			for ($j = 0; $j < count($tmp[$i]); $j++)
				$result[] = $tmp[$i][$j];
	}
	
	$result = implode($result);

	if (($_SERVER['argc'] > 3) && ($_SERVER['argv'][3] == "base64") && ($_SERVER['argv'][2] == "encrypt"))
		$result = base64_encode($result);

	echo $result . "\n";
?>
