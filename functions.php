<?php

/**
Decode a list of delimited JSON arrays into and array of arrays. 
Ex: multiDimJSONDecode('{row,col,wrd,bfr,aft}_{row,col,wrd,bfr,aft}' , '_', 'lbl') -> [[row,col,wrd,bfr,aft,'lbl'],[row,col,wrd,bfr,aft,'lbl']]
$encoded: the list of arrays. 
$delim: a string of delimiters (in order from 'outermost' array to 'innermost')
$label: a label to be put at the end of each innermost array ('' will cancel this operation)
*/ 
function multiDimJSONDecode($encoded, $delims,$label){
	if($delims == ''){
		$arr = (array)wordDecode($encoded);
		if($label!='')
		 	$arr[count($arr)] = $label;
		return $arr;
	}
	else{
		$delim = substr($delims, 0, 1);
		$exploded = explode($delim,$encoded);
		$nextDelim = substr($delims, 1, strlen($delims)-1);
		$i = 0;
		foreach($exploded as $enc){
			$ret[$i] = multiDimJSONDecode($enc,$nextDelim,$label);
			$i++;
		}
		return $ret;
	}
}	

/**
I'm sick of json_decode being different between php versions.  This will decode a non-associative json encoded array from javascript pretty well no matter what
*/
function wordDecode($encoded){
	$arr = explode(',',$encoded);
	foreach($arr as $ind => $item){
		$arr[$ind] = str_replace(array('[',']','"','{','}'), '', $item);
	}
	return $arr;
}

/**
Returns an array of regex patterns based on word and user letter info
$wordArr: An array of words object (with position, etc)
$uLetts: The user's letters in the form if 'ABC'
$ind: the index in the word where the 'line' the word is on can be found (for horz words, its the row, for vert words, its the col)
returns: [ [row, col, word, blanksBfr, blanksAft, pattern, label], ... ]
*/
function patternArr($wordArr, $uLetts, $ind){
	$patterns = array();
	foreach($wordArr as $wordInfo){
		$row = $wordInfo[0];
		$col = $wordInfo[1];
		$word = $wordInfo[2];
		$spacesBefore = $wordInfo[3];
		$spacesAfter = $wordInfo[4];
		$label = $wordInfo[5];
		$line = $wordInfo[$ind];
		
		//if this is the first entry for a given line (in-order delivery is guranteed)
		if(!array_key_exists($line, $patterns)){
			$patterns[$line] = array(array());
		} else if($spacesBefore > 0){//if not, the spaces before need to be one fewer to prevent intersection
			$spacesBefore--;
		}
		
		//make a pattern matching all new words that include only this word
		$pre = "/\s[$uLetts]{0,".$spacesBefore."}";
		$aft = "[$uLetts]{0,".$spacesAfter."}\s/m";
		
		//make the new entry into the single-word array
		$prevWordCount = count($patterns[$line][0]);
		//I embeded the letter's position in the regex--have to pull it out later...sorry
		$thisPatt = $pre.$word."_"."$row,$col,$word,$label"."_".$aft;
		$patterns[$line][0][$prevWordCount] = $thisPatt;
		
		//now update the multi-word arrays
		for($i=0; $i<$prevWordCount; $i++){
			//join this patt with the patt for the previous word, and save it
			$prevWord = $patterns[$line][0][$prevWordCount-$i-1];
			$thisPatt = joinRegx($prevWord, $thisPatt, $uLetts);
			$patterns[$line][$i+1][$prevWordCount-$i-1] = $thisPatt;
			
			/*since we've found new word at the end of the line, the previously last 
			word has one less available space after it.*/
			$count = count($patterns[$line][$i]);
			$prevWord = $patterns[$line][$i][$count-2];
			$patterns[$line][$i][$count-2] = decLettsAfter($prevWord);
		}
	}
	
	//pull out all of that lame data embeded in the regex
	return decodePatternsArr($patterns, $label);
}

/**
Join two regular expressions into one that will catch both words in series on the board
%one / $two: the one that should come first/ second
%uLetts: the user's letters -- could parse this, but it'd be more expensive
*/
function joinRegx($one, $two, $uLetts){
	//get rid of the line-beginning part of the most recent word
	$offset = strpos($two,'}');
	$off = strpos($two,',');
	//the 'spaces after' must be incremented, as we are joining words now, rather than keeping them apart
	$num = substr($two, $off+1, $offset-$off-1) + 1;
	$two = "[$uLetts]{".$num.substr($two, $offset);

	//get the word before the last one, without the 'spaces after' part
	$offset = strrpos($one,'[');
	$one = substr($one, 0, $offset);
	
	return $one.$two;
}

/**
Decrement the 'letters after' portion of a regex
*/
function decLettsAfter($regx){
	$offset = strrpos($regx, ',');
	$off = strrpos($regx, '}');
	$num = substr($regx, $offset+1, $off-$offset) - 1;
	return substr($regx, 0, $offset+1).$num.substr($regx, $off);
}

/**
Decodes the $patterns array with embeded word info generated in patternArr()
return: [ [row, col, wordArr, regx, label], ...]
*/
function decodePatternsArr($patterns, $label){
	$ret = array();
	$i = 0;
	foreach($patterns as $line){
		foreach($line as $wordCountDim){
			foreach($wordCountDim as $pattern){
				$patternArr = explode('_',$pattern);
				$regex = '';
				$reg = true;
				$j = 0;
				$wordData = array();
				foreach($patternArr as $key => $value){
					if($reg){ //put the regex together (its every other index)
						$regex .= $value;
						$reg = false;
					} 
					else{//put each word in the word array
						$wordData[$j] = explode(',',$value);
						$j++;
						$reg = true;
					}
				}
				//don't add regex that only matches one letter--its useless
				if(!checkOneLettReg($regex)){
					$ret[$i] = $wordData[0];//update row,col
					$ret[$i][2] = $wordData;
					$ret[$i][3] = $regex;
					$ret[$i][4] = $label;
					$i++;
				}
			}
		}
	}
	return $ret;
}

/**
Check if the regex will only match the existing word--no letters before or after (eg /\s[STR]{0,0}WORD[STR]{0,0}\s/m)
return: true if the pattern only matches one letter
*/
function checkOneLettReg($reg){
	$charCounts = count_chars($reg, 1);
	if($charCounts[ord('{')] == 2 && $charCounts[ord('}')] ){
		$offset = strpos($reg, ',');
		$off = strpos($reg, '}');
		$numBefore = substr($reg, $offset+1, $off-$offset-1);
		
		$offset = strrpos($reg, ',');
		$off = strrpos($reg, '}');
		$numAfter = substr($reg, $offset+1, $off-$offset-1);
		
		if($numBefore=='0' && $numAfter == '0'){
			return true;
		}
	}
	return false;
}

/**
Create a list of words which match the given regex patterns as well as letter count constraints
$dict: the contents of the dictionary file
$patterns: an array of the patterns to match
$uLttrs: a string of user letters
return: [ [row, col, match, label], ... ]
*/
function buildList($contents,$patterns,$uLttrs){
	$wordList = array();
	$letterList = array();
	$i = 0;
	foreach($patterns as $pattern){
		//find the maximim number of occurances of possible each character
		$letts = '';
		foreach($pattern[2] as $letter){
			$letts .= $letter[2];
		}
		$maxCharCounts = (array)count_chars($letts.$uLttrs, 1);
		
		$matches = array();
		preg_match_all($pattern[3], $contents, $matches);
		foreach($matches[0] as $match){//for each regex match
			$match = trim($match);//get rid of the white space around the word
			//make sure this isn't just the original word repeated
			if($match != $pattern[2][0][2]){
				//make sure there are not too many occurances of any single character
				$cont = 1;
				foreach (count_chars($match,1) as $ind => $cnt) {
					if($maxCharCounts[$ind] < $cnt){
						$cont = 0;
					}
				}
				//if the character counts checked out, add the word
				if($cont==1){
					$wordList[$i] = newWd($pattern,$match);
					$i++;
				}
			}
		}
	}
	return $wordList;
}

/**
Find the info needed to lay a new word
$pattern: the pattern that birthed this word
$match: the new word
*/
function newWd($pattern, $match){
	//if the word is vertical, the "changing" index is the row
	$changingInd = 0;
	$constInd = 1;
	$label = $pattern[4];
	if($label == "horz"){//otherwise, the "changing" index is the col
		$changingInd = 1;
		$constInd = 0;
	}

	//get an array of the new letters needed to make this word
	$letters = makeLetters($pattern, $match, array(), $changingInd, $constInd, 0);

	//update the starting position of the word, if necessary
	$changedInd = $pattern[$changingInd];
	if($pattern[$changingInd] > $letters[0][$changingInd]){
		$changedInd = $letters[0][$changingInd];
	}
	
	$new[$changingInd] = $changedInd;//the changing postion
	$new[$constInd] = $pattern[$constInd];//constant postion
	$new[2] = $match;//new word
	$new[3] = $letters;//array of letters added to make the word
	$new[4] = $pattern[4];//label
	return $new;
}

/**
Make an array of the new letters need to make a word
*/
function makeLetters($pattern, $match, $arr, $changingInd, $constInd, $ind){
	$reg = $pattern[3];
	$offset = strpos($reg,'}');
	$lastBrack = strrpos($reg,'}');
	$last = false;
	$const = $pattern[$constInd];
	//echo "----<br>makeLetters<br>match: $match<br>reg: $reg<br>";
	
	//if the first bracket is also the last bracket, then only new letters remain
	if($offset == $lastBrack){
		$offset = strlen($match);
		$last = true;//recursive terminationg condition
	}
	else{//otherwise, we have to find where the new letters end
		//make a new regex for the substring beginning with the first existing word
		$newReg = '/'.substr($reg, $offset+1, $lastBrack-$offset).'/';
		//echo "newReg: $newReg";
		//find the position of the first existing word in the new word
		$regMatch = array();
		preg_match($newReg, $match, $regMatch, PREG_OFFSET_CAPTURE );
		$offset = $regMatch[0][1];
		$wordLen = strlen($pattern[2][$ind][2]);
		$pattern[$changingInd] = $pattern[2][$ind][$changingInd];
	}
	
	//there is a weird case where the last two letters are the same
	$len = strlen($match);
	if($len==2 && $match[0] == $match[1]){
		//if the last two letters aren't both already laid
		if(strpos($newReg, $match)==false){
			//echo "<br>You're in the special case<br>";
			$letters[0][$changingInd] = intval($pattern[$changingInd])-1;
			$letters[0][$constInd] = intval($const);
			$letters[0][2] = $match[0];
			$arr = array_merge($arr, $letters);
			//echo "last: $last<br>arr:";
			//var_dump($arr);
			//echo "-----<br><br>";
			return $arr;
		}
	}
	
	if($offset>0){//make an array of each individual new letter
		$letters = array();
		$varbl = $pattern[$changingInd];//the start of the old word
		for($i=0; $i<$offset; $i++){
			//if this isn't the end, these new letters appear before the old word's index
			$changed = $varbl-$offset+$i;
			//if this is the tail end, then these new letters begin at the current index
			if($last){
				$changed = $varbl+$i;
			}
			$letters[$i][$changingInd] = intval($changed);
			$letters[$i][$constInd] = intval($const);
			$letters[$i][2] = substr($match, $i, 1);
		}
		$arr = array_merge($arr, $letters);
	}
	
	//echo "last: $last<br>arr:";
	//var_dump($arr);
	//echo "-----<br><br>";
	
	if($last){//recursive termination
		return $arr;
	}
	
	//wash, rinse, repeat
	$pattern[3] = $newReg;
	$pattern[$changingInd] += $wordLen;
	$ind++;
	$match = substr($match, $offset+$wordLen);
	return makeLetters($pattern, $match, $arr, $changingInd, $constInd, $ind);
}

/**
Take in a list of words and return only the words that need one extra letter
$words: must be an array of words formatted as the output of newWd()
*/
function oneLetterWds($words){
	$ret = array();
	$i = 0;
	foreach($words as $word){
		if(count($word[5]) == 1)
			$ret[$i] = $word;
		$i++;
	}
	return $ret;
}

/**
Take in a list of words and return an array of: 
 -the letters that create new words by themselves, and the position where they do this ($ret[0] aka $letts)
 -the length of the word created by adding the letter ($ret[1] aka $lens)
$words: must be an array of words formatted as the output of newWd()
return: [ ["A" => ['row,col', 'row,col'], ...], ["A" => [2, 6], ...] ]
*/
function oneLetterList($words){
	$letts = array();
	$lens = array();
	$i = 0;
	foreach($words as $word){
		$lettObjArr = $word[3];
		if(count($lettObjArr) == 1){//if its a one-letter-word
			$len = strlen($word[2]);//the length of the new word created
			$lettObj = $lettObjArr[0];
			$letter = $lettObj[2];
			$pos = $lettObj[0].",".$lettObj[1];
			if(!array_key_exists($letter, $letts)){//make sure the index exists
				$letts[$letter] = array();
				$lens[$letter] = array();
			}
			$letts[$letter][count($letts[$letter])] = $pos;
			$lens[$letter][count($lens[$letter])] = $len;
		}
	}
	
	$ret = array($letts,$lens);
	/*
	echo "oneLetterList - letts:<br>";
	var_dump($letts);
	echo "oneLetterList - lens:<br>";
	var_dump($lens);
	echo "oneLetterList - ret:<br>";
	var_dump($ret);
	*/
	return $ret;
}


/**
Make sure that when a word is laid, it doesn't make an invalid orthogonal word.  If it does, then remove the word is removed from the word lis
$wordList: an array of the words you want to lay on the board (return value of NewWd() style)
$horzOnes / $vertOnes: a list of the valid letters which will make a horizantal two-letter word (return value of oneLetterList() )
$filledArr: an array listing all filled tiles on the board
$numRows / $numCols: the number of rows/cols on the board
return: a list of validated words, NewWd() style
*/
function checkUnions($wordList,$horzOnes,$vertOnes,$filledArr,$numRows,$numCols){

	foreach($wordList as $ind => $word){
		$orient = $word[4];
		$colliding = array();
		//echo "<pre>";
		foreach($word[3] as $newLetter){
			$row = $newLetter[0];
			$col = $newLetter[1];
			$char = $newLetter[2];
			$remove = false;
			
			if($orient=='horz'){
				$positionKeys[0] = array($row+1, $col, ($row+1).".".$col);
				$positionKeys[1] = array($row-1, $col, ($row-1).".".$col);
				$ones = $vertOnes[0];
				$charCounts = $vertOnes[1];
			}
			else{
				$positionKeys[0] = array($row, $col+1, $row.".".($col+1));
				$positionKeys[1] = array($row, $col-1, $row.".".($col-1));
				$ones = $horzOnes[0];
				$charCounts = $horzOnes[1];
			}
			
			//echo $wordList[$ind][2]." at (".$wordList[$ind][0].",".$wordList[$ind][1].") - $orient: \n";
			
			foreach($positionKeys as $pos){//for each space around the new tile
				$collisionCount = 0;
				$adjRow = $pos[0];
				$adjCol = $pos[1];
				$key = $pos[2];
				$posKey = '';
				
				//if the specified letter is on the board
				if($adjRow>=0 && $adjCol>=0 && $adjRow*$numCols+$adjCol <= $numRows*$numCols){
					//echo "\t$char - ($adjRow,$adjCol) On the board. \n";
					
					//if this letter is adjacent to an old letter
					if(in_array($key,$filledArr)){
						//echo "\t\tAdjacent. \n";
						$collisionCount ++;
						$posKey = $row.",".$col;
						
						//break, if the new character doesn't make any orth words
						if(!array_key_exists($char, $ones) || !in_array($posKey,$ones[$char])){
							//echo "\t\tDoesn't make a word. ";
							if(!array_key_exists($char, $ones)){
								//echo "That letter never makes a 1-letter word \n";
							} else{
								//echo "Here's the list: \n";
								//var_dump($ones[$char]);
							}
							
							$remove = true;
							break;
						}
						//it might complete a two-letter word but be between two letters, making an illegal word
						else if($collisionCount>1){
							//echo "\t\t\tMakes a word and collides more than once.\n";
							$remove = true;
							foreach($one[$char] as $oneInd => $oneKey){
								//look for a word needing a letter at this position, which has more than two letters
								if($posKey == $oneKey && $charCounts[$char][$oneInd] > 2){
									//echo "\t\t\t\tNo adjacent words use >2.\n";
									$remove = false;
									break;
								} else{
									//echo "\t\t\t\tAn adjacent word uses >2.\n";
								}
							}
						} else{
							//echo "\t\t\tMakes a word and doesn't collide more than once.\n";
						}
					} else{
						//echo "\t\tNot Adjacent. \n";
					}
				} else{
					//echo "\t(adjRow,$adjCol)Not on the board. \n";
				}
			}
			if($remove){
				//echo "\tRemoved.\n\n";
				unset($wordList[$ind]);
				break;
			} else{
				//echo "\tNot Removed.\n\n";
			}
		}
	}
	//echo "</pre>";
	return $wordList;
}

/**
compare words based on length, then alphabet
*/
function compareWords($a, $b){
	$a = $a[2];
	$b = $b[2];
	return (strlen($b)-strlen($a))*100 + strcmp($a,$b);
}

/**
Give every word in the list a score by making the last element in the new letter array an array containing just the word's score.
*/
function score($wordList){
	$score = 9001;
	$i =0;
	$newList = array();
	foreach($wordList as $word){
		$newLetterCount = count($word[3]);
		$word[3][$newLetterCount] = array($score);
		$newList[$i] = $word;
		$i++;
	}
	return $newList;
}

/**
Make a summary, based on the word list
*/
function makeSummary($wordList){
	$count = count($wordList);
	$ret = "<div id='optionSummary' class='optionSummary'><br>";
	$ret.= "OMG, you have <b><i>so many</i></b> options...<br>";
	$ret.= "(like, $count options)<br>";
	$ret .= "<br><div>";
	return $ret;
}


/**
Make and html table from a word list
*/
function makeWordTable($wordList){
	$table = '';

	$len = 0;
	$col = 0;
	$maxCol = 3;//the numbers of columns in each row
	$table .= "<table class='optionTable'>";
	foreach($wordList as $word){
		$text = $word[2];
		$newLetters = $word[3];
		$enc = '';
		foreach($newLetters as $letter){
			ksort($letter);//sort the array by key (in case index 0 is after 1)
			$letter = array_merge($letter, array());//recast array into a sequential array
			$encodedLetter = json_encode($letter);
			$enc = $enc."_$encodedLetter";
		}
		$enc = substr($enc, 1);
		
		//make a heading, if there is a new letter count
		$newLen = strlen($text);
		if($newLen != $len){
			$len = $newLen;
			$table .=  "<tr class='optionRow optionRowHead'><td colspan='$maxCol'> $newLen Letter Words </td></tr> <tr class='optionRow'>";
			$col = 0;
		}
		
		//add the word
		if($col==$maxCol){//if its time for a new row, add a row
			$table .=  "</tr> <tr class='optionRow'>";
			$col = 0;
		}
		$table .=  "<td><button type='button' class='wordOpt' value='$enc'>$text</button></td>";
		$col++;
	}
	for($i=$col; $i<$maxCol; $i++){//make sure the last row has enough cols
		$table .=  "<td></td>";
	}
	$table .=  "</tr></table>";
	
	return $table;
}

?>