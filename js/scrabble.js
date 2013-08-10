var imgSrc = '';
var imgVal = '';
var laidLetters = new Array();
var userLetters = new Array();
var undoHist = new Array();
var rows = 15, cols = 15;
var vertWords = new Array();
var horzWords = new Array();
var filledSpots = new Array();
var userLettersPost = '';
var horzPost = '';
var vertPost = '';
var selectedTile = '';
var tempButton = '';
var savePoints = 'false';

//make all items with class "draggableImg" draggable
$(".draggableImg").mousedown(function(){
  imgSrc = $(this).attr('src');
  imgVal = $(this).attr('value');
});

//put an image in each tile place holder
$(".boardTile").append("<img class='tileImg bTileImg' src='icons/blank.png'/>");
$(".userTile").append("<img class='tileImg uTileImg' src='icons/clear.gif'/>");

//Tell the browser that we can drop on the drop zones
$(".tileImg").each(function( index ) {
  $(this).attr('value','NA');
  $(this).bind("dragover", cancel);
  $(this).bind("dragenter", cancel);
  $(this).bind("drop", dropIn);
});

//set initial tile atributes
$(".uTileImg").each(function( index ) {
  $(this).attr('id','uTileImg'+index.toString());
});
$(".bTileImg").each(function( index ) {
  $(this).attr('id','bTileImg'+index.toString());
	//set selectedTile when a board tile is clicked
	$(this).mousedown(function(){
		selectTile($(this));
	}); 
});

//Key commands
$(window).keydown(function(event) {
	//(Meta)
  if(event.ctrlKey || event.metaKey){
		//(Meta)+Z
		if(event.keyCode == 90){ 
			undo(event);
		}
  }
	else if(selectedTile != ''){//if a current tile is selected
		//arrow keys to navigate the board
		if(event.keyCode > 36 && event.keyCode < 41) {//if its an arrow key
			cancel(event);//prevent the default action		
			arrowSelection(event);
		}
		//letter keys to type on the board
		else if(event.keyCode > 64 && event.keyCode < 91) {//if its a letter key
			cancel(event);//prevent the default action		
			typeLetter(event);
		}
	}
});

//Make the find options button work
//Note: duplicate this within the post func of findOptions()
toggleOptButtonEnable($("#optionButton"));

//toggle whether the option button is opaque as well as whether it has a function
function toggleOptButtonEnable(button){
	if(button.hasClass('enabledButton')){
		button.removeClass('enabledButton');
		button.addClass('disabledButton');
		$("#optionButton").click( function (){});
	}
	else{
		button.removeClass('disabledButton');
		button.addClass('enabledButton');
		$("#optionButton").click( function (){
			$("#optionData").html("<div class='loadGif center'><img class='loadGif' src='icons/pacman.gif'/></div>");
			findOptions();
		});
	}
}

//find all of the options that the user can play
function findOptions(){
	//get the letters and words from the board
	getUserLetters();
	buildWords();
	
	if(filledSpots.length != 0){//if there are letters on the board
		//Encode the words so they can be passed as post values
		horz = "";
		for(var i=0; i<horzWords.length; i++){
			horz = horz+JSON.stringify(horzWords[i])+"_";
		}
		horzPost = horz.substring(0, horz.length-1);
		
		vert = "";
		for(var i=0; i<vertWords.length; i++){
			vert = vert+JSON.stringify(vertWords[i])+"_";
		}
		vertPost = vert.substring(0, vert.length-1);
		
		filledPost = JSON.stringify(filledSpots);
		
		//send the post data to the pattern finder
		$.post("php/patternFinder.php",
			{uLetters: userLettersPost, vert: vertPost, horz: horzPost, filled: filledPost, numRows: rows, numCols: cols},
			function(data){
				$("#optionData").html(data);//print the data
				$(".wordOpt").mouseover(function(){ showPlay($(this), 'false'); });
				$(".wordOpt").mouseout(function(){ hidePlay($(this, false)); });
				$(".wordOpt").click(function(){ showPlay($(this), 'true'); });
			});
		
	}
	else{//if there are no letters on the board
		$("#optionData").html('<br>There were no letters on the board...');
	}
}

//show how the word button selected by the user should be played on the board
function showPlay(button, clicked){
	//make sure the points continue to be shown after mouseoff if clicked
	savePoints = clicked;

	//get rid of any previously played words
	if(tempButton != ''){
		hidePlay(tempButton, true);
	}

	tempButton = button;
	var tempLetters = phpLettArrDecode(button.attr('value'));
	var i, row, col, letter, tileID, html;
	i=0;
	while(i<tempLetters.length-1){
		row = parseInt(tempLetters[i][0]);
		col = parseInt(tempLetters[i][1]);
		letter = tempLetters[i][2];
		tileID = "#bTileImg"+ (row*cols+col).toString();
		$(tileID).attr('src',"icons/"+letter+".png");
		$(tileID).addClass('tempLetter');
		$(tileID).attr('enabled',clicked);
		i++;
	}
	if(button.attr('enabled') != 'true' && clicked == 'false'){
		html = button.html();
		if(html.indexOf('(') < 0){//if points aren't being shown already
			//show how many points the word is worth
			var points = parseInt(tempLetters[i]);
			button.html(html+" ("+points+")");
		}
	}
	button.attr('enabled','true');
}

//hide the last temporary play made
function hidePlay(button, replacing){
	var i, row, col, tileID, clicked, pointStr, html;
	var temp = phpLettArrDecode(button.attr('value'));
	
	//remove any already played letters
	if(temp.length>0){
		i=0;
		while(i<temp.length-1){
			row = parseInt(temp[i][0]);
			col = parseInt(temp[i][1]);
			tileID = "#bTileImg"+ (row*cols+col).toString();
			if(replacing || $(tileID).attr('enabled') == 'false'){
				$(tileID).attr('src',"icons/blank.png");
				$(tileID).removeClass('tempLetter');
				button.attr('enabled','false');
			}
			i++;
		}
		
		//remove the point value from the end of the word, if necessary
		html = button.html();
		if(html.indexOf('(') >= 0 && savePoints == 'false'){
			pointStr = " ("+parseInt(temp[i])+")";
			button.html(html.substring(0, html.length- pointStr.length));
		}
	}
}

//decode the multidimensional letter arrays that are passed back from the php page
function phpLettArrDecode(str){
	var letterArray = str.split('_');
	var i, j, temp;
	for(i=0; i<letterArray.length; i++){
		temp = letterArray[i].split(',');
		for(j=0; j<temp.length; j++){
			temp[j] = temp[j].replace(/[\[\]\{\}"]/g, "");
		}
		letterArray[i] = temp;
	}
	return letterArray;
}

//get the letters that the user has to play with
function getUserLetters(){
	userLettersPost = '';
  $(".uTileImg").each( function(index){
    var val = $(this).attr('value');
    if(val != 'NA'){
      userLettersPost = userLettersPost+val;
    }
  });
}

//take in a tile number and give its row and col based on the size of the board
function getPosition(tileNo){
	var row = Math.floor(tileNo/cols);
	var col = tileNo-row*cols;
	return [row,col];
}

//get the special aspect of the tile (e.g. double letter)
function getSpecial(tileNo){
	return 0;
}

function buildWords(){
	//get the letters that are currently on the board
	var newTiles = getNewestTiles();
	//get the position of each letter
	var position, row, col;
	var tiles = new Array();
	for(var i=0; i<newTiles.length; i++){
		position = getPosition(newTiles[i][2]);
		row = position[0];
		col = position[1];
		filledSpots[i] = row+"."+col;
		tiles[i] = [row, col, newTiles[i][0]];
	}
	//get all of the words, their positions, and the spaces before and after
	horzWords = acrossWrds(tiles);
	vertWords = downWrds(tiles);
}

//returns the most recently laid tiles
function getNewestTiles(){
	var currTile;
	var list = laidLetters.slice();//do a deep copy, so the real deal aint jacked
	var ret = new Array();
	while(list.length>0){
		currTile = list[list.length-1][2];
		for(var j=list.length-2; j>=0; j--){
			if(list[j][2] == currTile){
				list.splice(j,1);
			}
		}
		ret[ret.length] = list[list.length-1];
		list = list.splice(0,list.length-1);
	}
	return ret;
}

//return: [[row, col, word, blanksBefore, blanksAfter],...]
function acrossWrds(tiles){
	tiles.sort(function(a,b){return (a[0]-b[0])*rows*cols+a[1]-b[1]});

	//get the horizontal words
	var currWord = "";
	var words = new Array();
	var blanksBefore = 0;
	var blanksAfter = 0;
	var currRow = 0;
	var lastCol = 0;
	var thisTile, thisRow, thisCol,thisLetter;
	for(var i=0; i<tiles.length;i++){
		thisTile = tiles[i];
		thisRow = thisTile[0];
		thisCol = thisTile[1];
		thisLetter = thisTile[2];
		if(thisRow==currRow){//if we're still on the same row
			if(currWord==""){//if this is the first tile on the board, set the values
				blanksBefore = thisCol;
				lastCol = thisCol;
				blanksAfter = cols-thisCol;
			}
			if(thisCol-lastCol>1){//check if there is a gap between this tile and the last
				words[words.length] = [currRow, lastCol-currWord.length+1, currWord, blanksBefore, thisCol-lastCol-1];
				blanksBefore = thisCol-lastCol-1;
				blanksAfter = cols-(thisCol+1);
				currWord = thisLetter;
			}
			else{//if this letter is adjacent to the previous one, just add it to the string
				currWord = currWord+thisLetter;
				blanksAfter--;
			}
		}
		else{//move on to the next row
			if(currWord!=""){
				words[words.length] = [currRow, lastCol-currWord.length+1, currWord, blanksBefore, blanksAfter];
			}
			currRow = thisRow;
			currWord = thisLetter;
			blanksBefore = thisCol;
			blanksAfter = cols-(thisCol+1);
		}
		if(i==tiles.length-1){//if this is the last tile
			words[words.length] = [currRow, thisCol-currWord.length+1, currWord, blanksBefore, blanksAfter];
		}
		lastCol = thisCol;
	}
	return words;
}

//return: [[row, col, word, blanksBefore, blanksAfter],...]
function downWrds(tiles){
	//sort the tiles from top to bottom, left to right
	tiles.sort(function(a,b){return (a[1]-b[1])*rows*cols+a[0]-b[0]});

	//get the horizontal words
	var currWord = "";
	var words = new Array();
	var blanksBefore = 0;
	var blanksAfter = 0;
	var currCol = 0;
	var lastRow = 0;
	var thisTile, thisRow, thisCol,thisLetter;
	for(var i=0; i<tiles.length;i++){
		thisTile = tiles[i];
		thisRow = thisTile[0];
		thisCol = thisTile[1];
		thisLetter = thisTile[2];
		if(thisCol==currCol){//if we're still on the same row
			if(currWord==""){//if this is the first tile on the board, set the values
				blanksBefore = thisRow;
				lastRow = thisRow;
				blanksAfter = rows-thisRow;
			}
			if(thisRow-lastRow>1){//check if there is a gap between this tile and the last
				words[words.length] = [lastRow-currWord.length+1, currCol, currWord, blanksBefore, thisRow-lastRow-1];
				blanksBefore = thisRow-lastRow-1;
				blanksAfter = rows-(thisRow+1);
				currWord = thisLetter;
			}
			else{//if this letter is adjacent to the previous one, just add it to the string
				currWord = currWord+thisLetter;
				blanksAfter--;
			}
		}
		else{//move on to the next col
			if(currWord!=""){
				words[words.length] = [lastRow-currWord.length+1, currCol, currWord, blanksBefore, blanksAfter];
			}
			currCol = thisCol;
			currWord = thisLetter;
			blanksBefore = thisRow;
			blanksAfter = rows-(thisRow+1);
		}
		if(i==tiles.length-1){//if this is the last tile
			words[words.length] = [thisRow-currWord.length+1, currCol, currWord, blanksBefore, blanksAfter];
		}
		lastRow = thisRow;
	}
	return words;
}

//type a letter on the board
function typeLetter(event){
	imgVal = String.fromCharCode(event.keyCode);
	imgSrc = "icons/" + imgVal + ".png";
	layLetter(selectedTile);//lay the typed letter on this tile
	moveSelection(1);//move selection to the next tile
}

//select a tile, given the tile as an input
function selectTile(tile){
	if(selectedTile != ''){//if the selected tile has already been set
		selectedTile.removeClass('selected');
	}
	selectedTile = tile;
	selectedTile.addClass('selected');
}

//move the tile selection as indicated by the arrow key pressed
function arrowSelection(event){
	var add = 0;
	switch(event.keyCode){
		case 37://left
			add = -1;
			break;
		case 38: //up
			add = -cols;
			break; 
		case 39: //right
			add = 1;
			break;
		case 40: //down
			add = cols;
			break; 
	}
	moveSelection(add);
}

//move the selected tile forward the specified number of places along the board
function moveSelection(add){
	var spot = parseInt(selectedTile.attr('id').replace(/[^0-9]/g,''));
	spot = spot+add;
	if(spot < 0){
		spot = spot+rows*cols;
	}
	else if(spot >= rows*cols){
		spot = spot-rows*cols;
	}
	var newTile = '#' + selectedTile.attr('id').replace(/[0-9]/g, '') + spot;
	selectTile($(newTile));
}

//drag and drop functions
function dropIn(event) {
  cancel(event);
	if(imgSrc!='' && imgVal!=''){
		var jQImgID = '#'+event.target.id;
		layLetter($(jQImgID));
  }
}

//put the letter into target tile
function layLetter(targ){
	var tileNo = targ.attr('id').replace( /^\D+/g, '');
	
	//set the src and value of the target spot
	targ.attr('value', imgVal);
	targ.attr('src', imgSrc);
	
	//determine if the target is the board or a user tile
	if(targ.parent().attr('class') == 'boardTile'){
		laidLetters[laidLetters.length] = [imgVal,letterPoints(imgVal),tileNo,getSpecial(tileNo)];
		undoHist[undoHist.length] = 'board';
	}
	else{
		userLetters[userLetters.length] = [imgVal,letterPoints(imgVal),tileNo];
		undoHist[undoHist.length] = 'user';
	}
	//reset the image source and value
	imgSrc = '';
	imgVal = '';
}

function undo(event){
  event.preventDefault();

  //check if the last tile lais was on the board or user area
  var lastType = undoHist[undoHist.length-1];
  undoHist = undoHist.slice(0,undoHist.length-1);

  //grab the right list of tiles
  var letters, tileNo, currID;
  if(lastType=='board'){
    letters = laidLetters;
		tileNo = letters[letters.length-1][2];
		currID = "#bTileImg"+tileNo;
		laidLetters = laidLetters.slice(0,laidLetters.length-1);
  }
  else if(lastType=='user'){
    letters = userLetters;
		tileNo = letters[letters.length-1][2];
		currID = "#uTileImg"+tileNo;
		userLetters = userLetters.slice(0,userLetters.length-1);
  }
	else
		return;

  //check if the last letter was dropped on top of another
  var done = false;
  for(i=letters.length-2; i>=0; i--){
    if(letters[i][2] == tileNo){
      $(currID).attr('src','icons/'+letters[i][0]+'.png');
      $(currID).attr('value',letters[i][0]);
      done = true;
      break;
    }
  }

  //if there was nothing previously on the tile
  if(!done){
    $(currID).attr('value','NA');
    if(lastType=='board')
      $(currID).attr('src','icons/blank.png');
    else
      $(currID).attr('src','icons/clear.gif');
  }
}

//cancel the default action
function cancel(event) {
  if (event.preventDefault) {
    event.preventDefault();
  }
  return false;
}

//lookup a letter's value
function letterPoints(letter){
	var val;
	switch(letter){
		case "A": case "E": case "I": case "L": case "N": case "O": case "R": case "S": case "T": case "U":
			val = 1;
			break;
		case "D": case "G":
			val = 2;
			break;
		case "B": case "C": case "M": case "M": case "P":
			val = 3;
			break;
		case "F": case "H": case "V": case "W": case "Y":
			val = 4;
			break;
		case "K":
			val = 5;
			break;
		case "J": case "X":
			val = 8;
			break;
		case "Q": case "Q":
			val = 10;
			break;
		default:
			val=0;
		}
	return val;
}