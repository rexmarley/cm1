//difficulty
var searchDepth = 6;
//turn
var activeColour = 'w';
//castling
var wCastling = 'KQ';
var bCastling = 'kq';
var castling = wCastling+bCastling;
//en Passant
var ep = '-';
//50 moves rule - ignored
var halfMoves = '0';
var fullMoves = '1';
//start FEN
var fen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR';
	
$(document).ready(function(){
	if (typeof worker !== 'undefined') {
		worker.addEventListener('message', function(e) {
			//console.log(e.data);
			if (e.data.substr(0, 8) == 'bestmove') {
				var msg = e.data.split(' ');
				var move = msg[1];
				//update FEN
				var from = [parseInt(move.charAt(1), 10)-1, posOf[move.charAt(0)]-1];
				var to = [parseInt(move.charAt(3), 10)-1, posOf[move.charAt(2)]-1];
				var colour = activeColour;
				updateFENSuffixes(activeColour, from[0], from[1], to[0]);
				updateFEN(from, to);
				console.log('FEN2:'+fen + ' ' + activeColour + ' ' + castling + ' ' + ep + ' ' + halfMoves + ' ' + fullMoves);
				//get grid refs.
				var gridFrom = getGridRefFromAbstractIndices(from[0], from[1]);
				var gridTo = getGridRefFromAbstractIndices(to[0], to[1]);
				//check for takeable
    			if (abstractBoard[to[0]][to[1]]) {
					//take piece
					takePiece(gridTo, 'Lost');	    				
    			}
	        	//update abstract board
    			console.log('from:', from);
    			console.log('to:', to);
	    		updateAbstractBoard(from, to);
    			//move piece
				var moved = getOccupant(gridFrom);
				moved.position({
		            of: 'div#'+gridTo
		        });	
				//center piece
				$('div#'+gridTo).append(moved.css('position','static'));			
				//check for game over
	    		var over = checkGameOver(colour);
	    		if (over) {
		    		alert(getGameOverMessage(over));
		    		gameOver = true;
	    		} else {				
	    			playersTurn = true;
				}
		  }
		}, false);
	}
	
	/**
	 * Dialog settings e.g. piece-chooser
	 */
	$('.ui-dialog').dialog({
		 autoOpen: false,
		 closeOnEscape: false,
		 open: function(event, ui) {
			 $(".ui-dialog-titlebar-close").hide();
		 },
		 show: {
			 effect: "blind",
			 duration: 1000
		 },
		 hide: {
			 effect: "explode",
			 duration: 1000
		 },
		 position: {
			 my: "center center",
			 at: "center center",
			 of: ".board"
		 },
		 modal: true,
	});
	
	//set player default white
	$('.board').attr('id', 'game_w_x');
	//set skill level
	if (difficulty == 1) {
		searchDepth = 3;
	} else if (difficulty == 3) {
		searchDepth = 18;		
	} else {
		searchDepth = 8;
		//TODO slider
	}
});

function swapPieceInFEN(colour, type, [endRow, pawnCol]) {
	//TODO
}

function switchToComputerOpponent(from, to) {
	playersTurn = false;
	updateFENSuffixes(activeColour, from[0], from[1], to[0]);
	updateFEN(from, to);
	console.log('FEN:'+fen + ' ' + activeColour + ' ' + castling + ' ' + ep + ' ' + halfMoves + ' ' + fullMoves);
	worker.postMessage('position fen '+ fen + ' ' + activeColour + ' ' + castling + ' ' + ep + ' ' + halfMoves + ' ' + fullMoves);
	worker.postMessage('go depth '+searchDepth);
}

function updateFEN(from, to) {
	//from/to => [letterPos - 1, numberPos -1]
	var split = fen.split('/');
//	console.log(to[0]);
//	console.log(to[1]);
	var fRow = split[7 - from[0]];
	var tRow = split[7 - to[0]];
	//convert column indices to fen
	console.log(fRow);
	var fCol = getFenIndex(fRow, from[1]);
	var tCol = getFenIndex(tRow, to[1]);
	var moved = fRow.charAt(fCol);
	//update 'from' row
	split[7 - from[0]] = updateFRowFEN(fRow, fCol);
	//update 'to' row
	split[7 - to[0]] = updateTRowFEN(tRow, tCol, to[1], moved);
	
	fen = split.join('/');
}

function getFenIndex(row, col) {
	var count = 0;
	//console.log('row: ',row);
	for (var i = 0; i < row.length; i++) {
		if (count == col) {
			return i;
		} else if (row.charAt(i) % 1 === 0) {
			count += parseInt(row.charAt(i), 10);
			if (count > col) {
				break;
			}
		} else {
			count++;
		}
	}
	return i;
}

function updateFRowFEN(row, col) {
	//check if adjacent columns contain counts/empties or pieces
	if (col > 0 && row.charAt(col - 1) % 1 === 0 && col < row.length && row.charAt(col + 1) % 1 === 0) {
		//counts on both sides
		row = row.substr(0, col - 2) + (parseInt(row.charAt(col - 1), 10) + parseInt(row.charAt(col + 1), 10) + 1) + row.substr(col+2, row.length - 1);		
	} else if (col > 0 && row.charAt(col - 1) % 1 === 0) {
		//count on left side
		row = row.substr(0, col - 2) + (parseInt(row.charAt(col - 1), 10) + 1) + row.substr(col+1, row.length - 1);		
	} else if (col < row.length && row.charAt(col + 1) % 1 === 0) {
		//count on right side
		row = row.substr(0, col) + (parseInt(row.charAt(col + 1), 10) + 1) + row.substr(col+2, row.length - 1);		
	} else {
		//pieces both sides
		row = row.substr(0, col) + '1' + row.substr(col+1, row.length - 1);
	}
	return row;
}

function updateTRowFEN(row, fenCol, col, moved) {
	var square = row.charAt(fenCol);
	//check if to square is empty i.e. numeric
	console.log(square);
	if (square % 1 === 0) {
		if (row.length - 1 == fenCol) {
			//empty row
			row = col + moved + (7 - col);
		} else {
			//find position in total
			for (var i = 0; i < fenCol; i++) {
				if (row.charAt(i) % 1 === 0) {
					col -= parseInt(row.charAt(i), 10);
				} else {
					col--;
				}
			}
			var before = '';
			if (col > 0) {
				before = col;
			}
			var after = '';
			if (parseInt(square, 10) - col > 1) {
				after = parseInt(square, 10) - col - 1;
			}
			console.log('aaaaaaaaa');
			console.log(before);
			console.log(moved);
			console.log(after);
			row = row.substr(0, fenCol) + before + moved + after + row.substr(fenCol + 1, row.length - 1);			
		}	
	} else {
		row = row.substr(0, fenCol) + moved + row.substr(fenCol+1, row.length - 1);			
	}
	return row;
}
function switchPlayer(colour) {
	if (colour == 'w') {
		return 'b';
	}
	return 'w';
}

//castling handled one colour at a time
function updateFENSuffixes(colour, fRow, fCol, tRow) {
	var split = fen.split('/');
	//console.log(fCol, fRow,'aa');
	var fenRow = split[7 - fRow];
	//console.log(fenRow);
	var moved = fenRow.charAt(getFenIndex(fenRow, fRow));
	//console.log('ok');
	//handle castling
	if (colour == 'w') {
		wCastling = getIndividualCastlingFEN(moved, wCastling, fRow, fCol);
	} else {
		bCastling = getIndividualCastlingFEN(moved, bCastling, fRow, fCol);
	}
	castling = wCastling+bCastling;
	//handle en Passant
	setEnPassantFEN(moved, fRow, fCol, tRow);
	//change colour
	activeColour = switchPlayer(colour);
}

function setEnPassantFEN(moved, fRow, fCol, tRow) {
	if (moved == 'p' && fRow == 6 && tRow == 4) {
		ep = letterAt[fCol]+'6';
	} else if (moved == 'P' && fRow == 1 && tRow == 3) {
		ep = letterAt[fCol]+'3';		
	} else {
		ep = '-';
	}
}

function getIndividualCastlingFEN(moved, pCastling, fRow, fCol) {
	//handle castling 
	if (pCastling.length > 0) {
		if (moved == 'k' || moved == 'K') {
			pCastling == '';
		} else if ((moved == 'r' && fRow == 7) || (moved == 'R' && fRow == 0)) {
			if (pCastling.length > 1) {
				//castle possible on both sides
				if (fCol == 0) {
					//castle no longer possible on queen-side
					pCastling == pCastling.charAt(0);
				} else if (fCol == 7) {
					//castle no longer possible on king-side
					pCastling == pCastling.charAt(1);					
				}
			} else {
				//castle only possible on one side
				if (pCastling == 'K' || pCastling == 'k') {
					//castle king-side possible
					if (fCol == 7) {
						//castle no longer possible on king-side
						pCastling == '';					
					}
				} else {
					//castle queen-side possible
					if (fCol == 0) {
						//castle no longer possible on queen-side
						pCastling == '';	
					}
				}
			}
		}
	}
	return pCastling;
}

function resetState() {
	fen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR';
	ep = '-';
	halfMoves = '0';
	fullMoves = '1';
	activeColour = 'w';
	wCastling = 'KQ';
	bCastling = 'kq';
	castling = wCastling+bCastling;
}