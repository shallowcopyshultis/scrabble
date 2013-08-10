<?php 
set_time_limit (60*60*60);
ini_set('memory_limit', '512M');
header("Content-type: text/plain");
exec ('chmod 777 *');

$file = '../txt/dictionary.txt';
$lines = file($file);

/*Make a basic index--one word per line*/
$arr = array();
$i = 0;
$used = array();
$file = '../txt/index.txt';

// foreach($lines as $line){
// 	$arr = (array)count_chars($line, 1);

// 	$len = strlen('{"10":1,"13":1}');
// 	$subStr = json_encode($arr);
// 	$subStr = substr($subStr, $len, strlen($subStr)-$len-1);
	
// 	file_put_contents($file, $subStr."_$line",FILE_APPEND);
// 	if($i>10){
// 		break;
// 	}
// 	$i++;
// }


// $lines = file('../txt/betterIndex.txt');
// $patterns = array();
// $i = 0;
// foreach($lines as $line){
// //	get the character count in regex form
	// $arr = explode('_', $line);
	// $charCount = $arr[0];
	// $pattern = "/\s".preg_quote($charCount,'/')."_.*\s/m";
	// $patterns[$i] = $pattern;
	// $i++;
// }

/*Now consolidate all words with a certain charcter count to a single line*/
$lines = file($file);
$contents = file_get_contents($file);
$file = '../txt/betterIndex.txt';
$matches = array();
foreach($lines as $line){
	//get the character count in regex form
	$arr = explode('_', $line);
	$charCount = $arr[0];
	$pattern = "/\s".preg_quote($charCount,'/')."_.*\s/m";
	
	$matches = array();
	preg_match($pattern, file_get_contents($file), $matches);

	//check to see if this pattern has already been matched
	if(empty($matches)){
		preg_match_all($pattern, $contents, $matches);
		$msg = "";
		foreach($matches[0] as $match){
			$word = (array)explode("_", $match);
			$msg = $msg.substr($word[1],0,-2).',';
		}
		$msg = $charCount."_".substr($msg,0,-1)."\n";
		file_put_contents($file, $msg,FILE_APPEND);
	}
}

echo "Complete!";
?>