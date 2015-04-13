/**
 * Get FEN from array representation of board
 * @param array board
 * @returns string
 */
function getFENFromBoard(board) {
	var fen = [];
	for (var i = 7; i > -1; i--) {
		//get row
		var row = board[i];
		fen[7 - i] = '';
		var count = 0;
		for (var j = 0; j < 8; j++) {
			if (row[j]) {
				var entry = row[j];
				if (count != 0) {
					entry = count + entry;
					count = 0;
				}
				fen[7 - i] = fen[7 - i] + entry;
			} else {
				count++;
				if (count == 8) {
					fen[7 - i] = count;
				} 
			}
		}
	}
	return fen.join('/');
}

/**
 * Get array representation of board from FEN
 * @param fen
 * @returns array
 */
function getBoardFromFEN(fen) {
	var split = fen.split('/');
	var board = [[],[],[],[],[],[],[],[]];
	for (var i = 7; i > -1; i--) {
		var row = split[i];
		if (row.length == 1) {
			//empty row
			board[7 - i] = [false, false, false, false, false, false, false, false];
		} else {
			for (var j = 0; j < 8; j++) {
				var entry = row.charAt(j);
				if (entry % 1 === 0) {
					for (var k = 0; k < entry; k++) {
						board[7 - i][j+k] = false;
						j++;
					}
				} else {
					board[7 - i][j] = entry;
				}
			}
		}
	}
	return board;
}

/**
 * Translate column index to FEN position
 * @param row FEN
 * @param col array index
 * @returns int
 */
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

/**
 * Update 'from' row in FEN with move
 * @param row FEN row
 * @param col FEN index
 * @returns String
 */
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

/**
 * Update 'to' row in FEN with move
 * @param row FEN row
 * @param fenCol FEN index
 * @param col array index
 * @param moved the moved piece
 * @returns String
 */
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

/**
 * Get piece from FEN
 * @param row
 * @param col
 * @returns
 */
function getPieceFromFEN(fen, row, col) {
	var split = fen.split('/');
	var fRow = split[7 - row];
	var fCol = getFenIndex(fRow, col);
	return fRow.charAt(fCol);	
}

/**
 * Swap piece in FEN
 * @param newPiece the new piece
 * @param position [row, col]
 */
function swapPieceInFEN(fen, newPiece, position) {
	var split = fen.split('/');
	var row = split[7 - position[0]];
	var col = getFenIndex(row, position[1]);
	split[7 - position[0]] = updateTRowFEN(row, col, position[1], newPiece);	
	return split.join('/');	
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
 * Get opposing colour 
 * @param colour
 * @returns char
 */
function switchPlayer(colour) {
	if (colour == 'w') {
		return 'b';
	}
	return 'w';
}

function getFEN(fen, activeColour, castling, ep, halfMoves, fullMoves) {
	return fen + ' ' + activeColour + ' ' + castling + ' ' + ep + ' ' + halfMoves + ' ' + fullMoves;
}