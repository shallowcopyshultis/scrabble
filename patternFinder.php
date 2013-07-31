<?php
require('functions.php');

if(isset($_POST['uLetters'])){
	$uLetters = $_POST['uLetters'];
	$horzArr = $_POST['horz'];//horizontal words
	$vertArr = $_POST['vert'];//vertical words
	$filledArr = $_POST['filled'];//filled tiles
	$numRows = $_POST['numRows'];//the number of rows on the board
	$numCols = $_POST['numCols'];//the number of cols on the board
	
	//echo "post - horzArr:<pre>\n\t$horzArr</pre>";
	
	//decode the data into multi-dimensional arrays
	$horzArr = multiDimJSONDecode($horzArr, "_","horz");
	$vertArr = multiDimJSONDecode($vertArr, "_","vert");
	$filledArr = multiDimJSONDecode($filledArr, "", "");//[ "row.col", ... ]

	// echo "multiDimJSONDecode - horzArr:<pre>";
	// print_r($horzArr);
	// echo "multiDimJSONDecode - vertArr:";
	// print_r($vertArr);
	// echo "multiDimJSONDecode - filledArr:<pre>";
	// print_r($filledArr);
	//echo "</pre><br>";
	
	//make a list of patterns to match the given words, with only user letters added, that fit on the board
	$horzArr = patternArr($horzArr,$uLetters,0);
	$vertArr = patternArr($vertArr,$uLetters,1);

	//echo "patternArr - horzArr:<pre>";
	//print_r($horzArr);
	//echo "patternArr - vertArr:";
	//print_r($vertArr);
	//echo "</pre><br>";
	
	//create a list possible words
	$contents = file_get_contents('dictionary.txt');
	$horzArr = buildList($contents,$horzArr,$uLetters);
	$vertArr = buildList($contents,$vertArr,$uLetters);
	
	// echo "<br>buildList - horzArr:<pre>";
	//print_r($horzArr);
	// echo "buildList - vertArr:";
	// print_r($vertArr);
	// echo "</pre><br>";
	
	//$wordList = array_merge($horzArr, $vertArr);
	//uasort($wordList, 'compareWords');
	//echo makeWordTable($wordList);


	//make a list of words that need only one letter
	$horzOnes = oneLetterList($horzArr);
	$vertOnes = oneLetterList($vertArr);
	
	//	echo "oneLetterList - horzOnes:<pre>";
	//	print_r($horzOnes);
	//	echo "oneLetterList - vertOnes:";
	//	print_r($vertOnes);
	//	echo "</pre><br>";

	
	// eliminate words that make fake orthogonal words
	$wordList = array_merge($horzArr,$vertArr);
	$wordList = checkUnions($wordList,$horzOnes,$vertOnes,$filledArr,$numRows,$numCols); 

	// echo "checkUnions - wordList:<pre>";
	// print_r($wordList);
	// echo "</pre><br>";
	
	//find each word's score
	$wordList = score($wordList);
	
	// sort the word list based on length then alphabet, then make the table
	uasort($wordList, 'compareWords');
	echo makeSummary($wordList);
	echo makeWordTable($wordList);
}

else
	echo "The solver received no data...";	
?>