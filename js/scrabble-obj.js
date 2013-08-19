/*************************************************************************************
*The board class
**************************************************************************************/
function Board(rows, cols, type, boardClass, id) {
	this.rows = rows;
	this.cols = cols;
	this.type = type;
	this.boardClass = boardClass;
	this.ID = id;
	this.laidTiles = new Array();

	if(type == 'boardTile' || type == 'letterTile')
		this.defaultImg = 'icons/blank.png';
	else
		this.defaultImg = '';

	this.tiles = new Array();
	for(var i=0; i<rows; i++){
		this.tiles[i] = new Array(cols);
		for(var j=0; j<cols; j++){
			this.addTile(i,j,'NA',0,this.defaultImg, type+"Img",false);
		}
	}
}

Board.prototype.addTile = function (row, col, lett, points, img, classes, laid){
	var pID = this.makeCellID(row, col);
	var tile = new Tile(row, col, lett, points, img, classes, pID);

	if(laid){
		this.laidTiles[this.laidTiles.length] = tile;
		tile.draw();
	}
	this.tiles[row][col] = tile;
}

Board.prototype.makeCellID = function (row, col){
	return this.ID+"_tile"+(row*this.cols+col).toString();
}

Board.prototype.getHTML = function (){
	var tile;
	var html = "<table class='center' id='"+this.ID+"'> <tbody> ";
	for(var i=0; i<this.rows; i++){
		html += "<tr> ";
		for(var j=0; j<this.cols; j++){
			tile = this.getTile(i,j);
			html += "<td> <div class='"+this.type+"' id='"+this.makeCellID(i,j)+"'> ";
			html += tile.getHTML();
			html += " </div> </td> ";
		}
		html += "</tr>";
	}
	html += "</tbody> </table>";
	return html;
}

Board.prototype.getTile = function (row, col){
	return this.tiles[row][col];
}

/*************************************************************************************
*The tile class
**************************************************************************************/
function Tile(row, col, lett, points, img, tileClass, parentID) {
  this.row = row;//row on the board
  this.col = col;//column on the board
  this.letter = lett;//the value of the letter (eg 'A')
  this.points = points;//how many points this letter is worth by itself
  this.img = img;//the path to the image file for the tile
  this.tileClass = tileClass;//the type of tile (eg 'board' for board tiles)
  this.parID = parentID;//the parent object of this image object
  this.ID = parentID+"_img";//the html id of the image
  this.mult = 'NA';//the value of the multiplier (ie 'DL' for double letter)
}

Tile.prototype.draw = function (){
	var id = this.ID;
	$('#'+id).attr('src', this.img);
};

Tile.prototype.getVal = function (){
	var mult = this.getMult();
	if(mult=='DL')
		mult = 2;
	else if(mult=='TL')
		mult = 3;
	else
		mult = 1;
	return this.points*mult;
};

Tile.prototype.getHTML = function (){
	return "<img class='tileImg "+this.tileClass+"' src='"+this.img+"' id='"+this.ID+"'>";
};

/*************************************************************************************
*The word class
**************************************************************************************/
function Word(direction) {
  this.tiles = [];
  this.direction = direction;
  this.points = 0;
  this.val = '';
}

Word.prototype.addTile = function (tile){
	this.tiles[this.tiles.length] = tile;
	this.points += tiles.getPoints();
	this.val += tile.getVal();
}

//getters
Word.prototype.getTiles = function (){
	return this.tiles;
}
Word.prototype.getDirection = function (){
	return this.direction;
}
Word.prototype.getPoints = function (){
	return this.points;
}
Word.prototype.getVal = function (){
	return this.val;
}