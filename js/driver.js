var rows = 15;
var cols = 15;
var board = new Board(rows, cols, 'boardTile', 'center', 'boardTable');
var userTiles = new Board(1, 7, 'userTile', 'center', 'optionsTable');
var letterTiles = new Board(2, 13, 'letterTile', 'center', 'letterDiv');

$('#letterDiv').html($('#letterDiv').html()+letterTiles.getHTML());
$('#boardDiv').html($('#boardDiv').html()+board.getHTML());
$('#userLetterDiv').html(userTiles.getHTML());

var ascii = 65;
var src, charVal;
for(var i=0; i<letterTiles.rows; i++){
	for(var j=0; j<letterTiles.cols; j++){
		charVal = String.fromCharCode(ascii);
		src = "icons/"+charVal+".png";
		letterTiles.addTile(i, j, charVal, 0, src, 'draggableImg', true);
		ascii++;
	}
}