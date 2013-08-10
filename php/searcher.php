<?php

if(isset($_GET['letters']) || isset($_POST['letters'])){
	if(isset($_GET['letters']))
		$letters = $_GET['letters'];
	else
		$letters = $_POST['letters'];
	
	//read in the dictionary
	$dict = '../txt/dictionary.txt';
	$index = '../txt/index.txt';
	$contents = file_get_contents($index);

	//get all the combinations of the letters
	$combs = array_unique((array)getCharSets($letters));

	//find all of the instances of these letter count combinations
	$allMatches = array();
	foreach($combs as $comb){
		//create the substring and save its length
		$arr = (array)count_chars($comb, 1);
		$subStr = json_encode($arr);
		$subStr = substr($subStr, 1, strlen($subStr)-2);
		$length = strlen($subStr);

		//create an escaped regex
		$pattern = "/\s".preg_quote($subStr, '/')."_.*\s/m";

		//find the lines in the index that match the comb's character count
		$matches = array();
		preg_match_all($pattern, $contents, $matches);

		if(!empty($matches[0])){
			array_push($allMatches, $matches[0]);
		}
	}

	//print the words in a comma delimited list
	$words = array();
	$msg='';
	foreach($allMatches as $arr){
		foreach($arr as $line){
			$arr = explode('_', $line);
			$msg = $msg.substr($arr[1],0,-2).",";
		}
	}
	echo substr($msg, 0, -1);
}

function getCharSets($letters){
	$combs = array();
	for($takeOut=strlen($letters)-2; $takeOut>0; $takeOut--){
		for($offset=0; $offset<strlen($letters); $offset++){
			$lastChar = $offset+strlen($letters)-$takeOut;
			if($lastChar>strlen($letters))	
				$next = substr($letters, $offset).substr($letters, 0, $lastChar-strlen($letters));
			else
				$next = substr($letters, $offset, strlen($letters)-$takeOut);

			$stringParts = str_split($next);
			sort($stringParts);
			$next = implode('', $stringParts);
			array_push($combs, $next);
		}
	}
	array_push($combs, $letters);
	return $combs;
}

/*The Older, Less Cool way*/

// // create a list of all combinations of the given letters
// $combs = array_unique((array)buildCombs('',$letters), SORT_STRING);

// function buildCombs($baseStr,$letters){
// 	$list = array();
// 	//save any exoression of 2 or more letters
// 	if(strlen($baseStr)>1){
// 		array_push($list,$baseStr);
// 	}
// 	//for each letter in the list of remaining letters, make it the next letter in the string.  Then check every remaining possible sub-string
// 	for($i=0; $i<strlen($letters); $i++){
// 		$curLetter =  substr($letters,$i,1);
// 		$currBase = $baseStr.$curLetter;
// 		$currLetters = substr($letters,0,$i).substr($letters, $i+1, strlen($letters)-($i+1) );
// 		$list = array_merge($list,buildCombs($currBase,$currLetters));
// 	}
// 	return $list;
// }

?>