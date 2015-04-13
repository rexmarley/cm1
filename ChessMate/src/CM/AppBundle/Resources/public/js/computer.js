//TODO: change to app.php for live
var root = 'https://'+document.location.hostname+'/CM/ChessMate/web/app_dev.php/game/';
//difficulty
var searchDepth = 6;
//turn
var activeColour = 'w';
//en Passant
var ep = '-';
//50 moves rule - ignored
var halfMoves = '0';
var fullMoves = '1';
//start FEN
var fen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR';

$(document).ready(function() {
	if (typeof worker !== 'undefined') {
		worker.addEventListener('message', function(e) {
			//console.log(e.data);
			if (e.data.substr(0, 8) == 'bestmove') {
				//get move
				var msg = e.data.split(' ');
				performComputerMove(msg[1]);
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
		setDifficulty(2);
	} else if (difficulty == 3) {
		setDifficulty(7);		
	} else {
		setDifficulty(4);
	}
	
	//change skill level
	$('#difficultySlider').slider({
	      min: 1,
	      max: 10,
	      value: (searchDepth + 1)/2,
	      animate: 'fast',
	      slide: function( event, ui ) {
	    	  setDifficulty(ui.value);
	    	  $('#difficultySlider').attr('title', ui.value);
	      }
	  }).attr("title", (searchDepth + 1)/2);
	
	$('#restart').on('click', function() {
		resetState();
	});
	$('#switchSides').on('click', function() {
		resetState('b');
	});
});

/**
 * Set search depth
 * @param skill
 * @returns
 */
function setDifficulty(skill) {
	var diff;
	if (skill == 1) {
		diff = 'Very Easy';
	} else if (skill < 4) {
		diff = 'Easy';
	} else if (skill < 6) {
		diff = 'Average';
	} else if (skill < 8) {
		diff = 'Hard';
	} else {
		diff = 'Very Hard';
	}
	$('#diffLabel').html('('+diff+')');
	searchDepth = Math.min((skill*2)-1, 19);
}

/**
 * Perform computer move
 * @param move algebraic notation
 * @returns
 */
function performComputerMove(move) {
	//update FEN
	var from = [parseInt(move.charAt(1), 10)-1, posOf[move.charAt(0)]-1];
	var to = [parseInt(move.charAt(3), 10)-1, posOf[move.charAt(2)]-1];
	var colour = activeColour;
	updateFENSuffixes(activeColour, from[0], from[1], to[0]);
	updateFEN(from, to);
	updateComputerCastling(from);
	var piece = getPieceFromFEN(from[0], from[1]);
	setEnPassant(piece, from[0], from[1], to[0]);
	console.log('FEN2:'+getFEN());
	//get grid refs.
	var gridFrom = getGridRefFromAbstractIndices(from[0], from[1]);
	var gridTo = getGridRefFromAbstractIndices(to[0], to[1]);
	//check for takeable
	if (abstractBoard[to[0]][to[1]]) {
		//take piece
		takePiece(gridTo, 'Lost');	    				
	}
	//update abstract board
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

function swapPieceInFEN(newPiece, position) {
	//position = [endRow, pawnCol]
	var split = fen.split('/');
	var row = split[7 - position[0]];
	var col = getFenIndex(position[0], position[1]);
	split[7 - position[0]] = updateTRowFEN(position[0], col, position[1], newPiece);	
	fen = split.join('/');	
}

/**
 * Update FEN and send to computer opponent
 * @param from
 * @param to
 */
function switchToComputerOpponent(from, to) {
	playersTurn = false;
	updateFENSuffixes(activeColour, from[0], from[1], to[0]);
	updateFEN(from, to);
	console.log('FEN:'+ getFEN());
	worker.postMessage('position fen '+ getFEN());
	worker.postMessage('go depth '+searchDepth);
}

function getFEN() {
	return fen + ' ' + activeColour + ' ' + getCastlingFEN() + ' ' + ep + ' ' + halfMoves + ' ' + fullMoves;
}

function updateFEN(from, to) {
	var split = fen.split('/');
	//get 'from' row
	var fRow = split[7 - from[0]];
	var fCol = getFenIndex(fRow, from[1]);
	//update 'from' row
	split[7 - from[0]] = updateFRowFEN(fRow, fCol);
	//get moved piece
	var moved = fRow.charAt(fCol);
	//get 'to' row
	var tRow = split[7 - to[0]];
	var tCol = getFenIndex(tRow, to[1]);
	//update 'to' row
	split[7 - to[0]] = updateTRowFEN(tRow, tCol, to[1], moved);
	//check for castling
	if (moved == 'k' || moved == 'K') {
		if (Math.abs(from[1]-to[1]) == 2) {
			//only possible if castling
			if (moved == 'K') {
				var rook = 'R';
			} else {
				var rook = 'r';				
			}
			//get row again
			tRow = split[7 - to[0]];
			//console.log('a:', tRow);
			if (from[1] < to[1]) {
				//king-side
				var rookFCol = 7;
				var rookTCol = 5;
				fCol = getFenIndex(tRow, 5);
				tRow = tRow.substr(0, fCol) + (parseInt(tRow.charAt(fCol), 10) - 1) + rook + tRow.charAt(fCol+1) + '1';
			} else {
				//queen-side
				var rookFCol = 0;
				var rookTCol = 3;
				tRow = '2' + tRow.charAt(2) + rook + '1' + tRow.substr(4, tRow.length - 4);
			}
			//move castle
			split[7 - to[0]] = tRow;
			//update abstract & actual board if computer move
			if (!abstractBoard[from[0]][rookTCol]) {
	    		updateAbstractBoard([from[0], rookFCol], [to[0], rookTCol]);
				//get grid refs.
				var gridFrom = getGridRefFromAbstractIndices(from[0], rookFCol);
				var gridTo = getGridRefFromAbstractIndices(to[0], rookTCol);
				var moved = getOccupant(gridFrom);
				moved.position({
		            of: 'div#'+gridTo
		        });	
				//center piece
				$('div#'+gridTo).append(moved.css('position','static'));
			}
		}
	}
	
	fen = split.join('/');
}

function getFenIndex(row, col) {
	var count = 0;
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
	if (col == 0) {
		//edge piece
		if (row.charAt(1) % 1 === 0) {
			row = (parseInt(row.charAt(1), 10) + 1) + row.substr(2, row.length - 2);
		} else {
			row = '1' + row.substr(1, row.length - 1);			
		}
	} else if (col == row.length - 1) {
		//edge piece
		if (row.charAt(col - 1) % 1 === 0) {
			row = row.substr(0, col - 1) + (parseInt(row.charAt(col - 1), 10) + 1);
		} else {
			row = row.substr(0, col) + '1';			
		}		
	} else if (col > 0 && row.charAt(col - 1) % 1 === 0 && col < row.length && row.charAt(col + 1) % 1 === 0) {
		//counts on both sides
		row = row.substr(0, col - 1) + (parseInt(row.charAt(col - 1), 10) + parseInt(row.charAt(col + 1), 10) + 1) + row.substr(col+2, row.length - col - 1);		
	} else if (col > 0 && row.charAt(col - 1) % 1 === 0) {
		//count on left side
		row = row.substr(0, col - 1) + (parseInt(row.charAt(col - 1), 10) + 1) + row.substr(col+1, row.length - col);
	} else if (col < row.length && row.charAt(col + 1) % 1 === 0) {
		//count on right side
		row = row.substr(0, col) + (parseInt(row.charAt(col + 1), 10) + 1) + row.substr(col+2, row.length - col - 1);
	} else {
		//pieces both sides
		row = row.substr(0, col) + '1' + row.substr(col+1, row.length - 1);
	}
	return row;
}

function updateTRowFEN(row, fenCol, col, moved) {
	var square = row.charAt(fenCol);
	//check if to square is empty i.e. numeric
	var before = '';
	var after = '';
	if (square % 1 === 0) {
		if (row.length == 1) {
			//empty row
			if (col > 0) {
				before = col;
			}
			if (col < 7) {
				after = (7 - col);
			}
			row = before + moved + after;
		} else {
			//find position in total
			for (var i = 0; i < fenCol; i++) {
				if (row.charAt(i) % 1 === 0) {
					col -= parseInt(row.charAt(i), 10);
				} else {
					col--;
				}
			}
			if (col > 0) {
				before = col;
			}
			if (parseInt(square, 10) - col > 1) {
				after = parseInt(square, 10) - col - 1;
			}
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

function updateFENSuffixes(colour, fRow, fCol, tRow) {
	var split = fen.split('/');
	var fenRow = split[7 - fRow];
	var moved = fenRow.charAt(getFenIndex(fenRow, fCol));
	//handle en Passant
	setEnPassantFEN(moved, fRow, fCol, tRow);
	//change colour
	activeColour = switchPlayer(colour);
}

/**
 * Set En passant position using algebraic notation
 */
function setEnPassantFEN(moved, fRow, fCol, tRow) {
	if (moved == 'p' && fRow == 6 && tRow == 4) {
		ep = letterAt[fCol]+'6';
	} else if (moved == 'P' && fRow == 1 && tRow == 3) {
		ep = letterAt[fCol]+'3';		
	} else {
		ep = '-';
	}
}

/**
 * Update computer castling state
 * @param from
 */
function updateComputerCastling(from) {
	var moved = getPieceFromFEN(from[0], from[1]);
	if ($.inArray(moved, ['k', 'K', 'r', 'R']) !== -1) {
		//update castling availability
		updateCastling(moved, getPieceColour(moved), from[0], from[1]);
	}
}

/**
 * Get castling FEN
 * @return String
 */
function getCastlingFEN() {
	var fen = castling['w']+castling['b'];
	if (fen.length > 0) {
		return fen;
	}
	return '-';
}

/**
 * Get piece from FEN
 * @param row
 * @param col
 * @returns
 */
function getPieceFromFEN(row, col) {
	var split = fen.split('/');
	var fRow = split[7 - row];
	var fCol = getFenIndex(fRow, col);
	return fRow.charAt(fCol);	
}

/**
 * Reset game
 * @param colour - side to play as
 */
function resetState(colour = 'w') {
	fen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR';
	ep = '-';
	halfMoves = '0';
	fullMoves = '1';
	activeColour = 'w';
	castling = {'w': 'KQ', 'b': 'kq'};
	enPassant = null;
	castled = false;
	enPassantPerformed = false;
	checkThreat = null;
	$('.board').attr('id', 'game_'+colour+'_x');
	//reset taken pieces
	$('.subscript').each(function() {
		$(this).html('');
		$(this).closest('div.piece').addClass('hidden');
	});
	//reset abstract board
	abstractBoard = [
	        ['R','N','B','Q','K','B','N','R'],
	        ['P','P','P','P','P','P','P','P'],
	        [false, false, false, false, false, false, false, false],
	        [false, false, false, false, false, false, false, false],
	        [false, false, false, false, false, false, false, false],
	        [false, false, false, false, false, false, false, false],
	        ['p','p','p','p','p','p','p','p'],
	        ['r','n','b','q','k','b','n','r']
        ];
	//refresh actual board
    $.get(root+'showBoard/'+colour, function(data) {
    	$('.board').closest('div.col-md-6').html(data);
        setMovement();
        if (colour == 'b') {
        	//get first move
        	worker.postMessage('position fen '+ getFEN());
        	worker.postMessage('go depth '+searchDepth);        	
        }
    });
}