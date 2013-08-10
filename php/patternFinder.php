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

	//printArr('multiDimJSONDecode', 'horzArr', $horzArr);
	//printArr('multiDimJSONDecode', 'vertArr', $vertArr);
	//printArr('multiDimJSONDecode', 'filledArr', $filledArr);
	
	//make a list of patterns to match the given words, with only user letters added, that fit on the board
	$horzArr = patternArr($horzArr,$uLetters,0);
	$vertArr = patternArr($vertArr,$uLetters,1);

	// printArr('patternArr', 'horzArr', $horzArr);
	// printArr('patternArr', 'vertArr', $vertArr);
	
	//create a list possible words
	$contents = file_get_contents('../txt/dictionary.txt');
	$horzArr = buildList($contents,$horzArr,$uLetters);
	$vertArr = buildList($contents,$vertArr,$uLetters);
	
	// printArr('buildList', 'horzArr', $horzArr);
	// printArr('buildList', 'vertArr', $vertArr);

	//make a list of words that need only one letter
	$horzOnes = oneLetterList($horzArr);
	$vertOnes = oneLetterList($vertArr);
	
	// printArr('oneLetterList', 'horzOnes', $horzOnes);
	// printArr('oneLetterList', 'vertOnes', $vertOnes);

	// eliminate words that make fake orthogonal words
	$wordList = array_merge($horzArr,$vertArr);
	$wordList = checkUnions($wordList,$horzOnes,$vertOnes,$filledArr,$numRows,$numCols); 

	// printArr('checkUnions', 'wordList', $wordList);
	
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