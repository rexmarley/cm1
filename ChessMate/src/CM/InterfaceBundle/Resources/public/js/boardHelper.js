//set positioning of letters
posOf = {'a': 1,'b': 2,'c': 3,'d': 4,'e': 5,'f': 6,'g': 7,'h': 8};
letterAt = ['a','b','c','d','e','f','g','h'];

//allow abstract validation
abstractBoard = [
	                ['wRook','wKnight','wBishop','wQueen','wKing','wBishop','wKnight','wRook'],
	                ['wPawn','wPawn','wPawn','wPawn','wPawn','wPawn','wPawn','wPawn'],
	                [false, false, false, false, false, false, false, false],
	                [false, false, false, false, false, false, false, false],
	                [false, false, false, false, false, false, false, false],
	                [false, false, false, false, false, false, false, false],
		            ['bPawn','bPawn','bPawn','bPawn','bPawn','bPawn','bPawn','bPawn'],
	                ['bRook','bKnight','bBishop','bQueen','bKing','bBishop','bKnight','bRook']
                ];
//include redundant middle board to avoid resolving indices
unmoved = [
			[true, true, true, true, true, true, true, true],
			[true, true, true, true, true, true, true, true],
			[false, false, false, false, false, false, false, false],
			[false, false, false, false, false, false, false, false],
			[false, false, false, false, false, false, false, false],
			[false, false, false, false, false, false, false, false],
			[true, true, true, true, true, true, true, true],
			[true, true, true, true, true, true, true, true]
        ];

enPassantAvailable = false;
enPassantPerformed = false;
castled = false;
	
/**
 * Resolve grid reference to array indices
 * @param x alphabet ref
 * @param y numeric ref
 * 
 * @return the corresponding [row,column] index in abstractBoard array
 */
function getAbstractIndicesFromGridRef(x, y) {
	return [y - 1, posOf[x] - 1];
}

/**
 * Resolve array index to grid reference
 * @param y row in abstractBoard
 * @param x column in abstractBoard
 * 
 * @return the corresponding grid reference
 */
function getGridRefFromAbstractIndices(y, x) {
	//get alpha x
	x = letterAt[x];
	//translate to grid ref
	return x+'_'+(y + 1);
}
	
/**
 * Validate rook movement
 * @param from	[y,x]
 * @param to	[y,x]
 */
function validateRook(from, to) {
	if ((from[0] == to[0] && !xAxisBlocked(from[1], to[1], from[0])) 
		|| (from[1] == to[1] && !yAxisBlocked(from[0], to[0], from[1]))) {
		return true;
	}

	return false;
}
	
/**
 * Validate knight movement
 * @param from	[y,x]
 * @param to	[y,x]
 */
function validateKnight(from, to) {
	if (((to[0] - from[0])*(to[0] - from[0])) + ((to[1] - from[1])*(to[1] - from[1])) == 5) {
		return true;
	}
	return false;
}

/**
 * Validate bishop movement
 * @param from	[y,x]
 * @param to	[y,x]
 */
function validateBishop(from, to) {
	if (onDiagonal(from, to) && !diagonalBlocked(from[1], from[0], to[1], to[0])) {
		return true;
	}
	return false;
}

/**
 * Validate queen movement
 * @param from	[y,x]
 * @param to	[y,x]
 */
function validateQueen(from, to) {
	if ((from[0] == to[0] && !xAxisBlocked(from[1], to[1], from[0])) 
		|| (from[1] == to[1] && !yAxisBlocked(from[0], to[0], from[1])) 
		|| (onDiagonal(from, to) && !diagonalBlocked(from[1], from[0], to[1], to[0]))) {
		return true;
	}	
	return false;
}
	
/**
 * Validate pawn movement
 * @param from	[y,x]
 * @param to	[y,x]
 */
function validatePawn(colour, from, to) {
	var spaces = 1;
	if (unmoved[from[0]][from[1]]) {
		//allow initial movement of 2 spaces
		spaces = 2;
	}
	//allow moving forward
	var dir = to[0] - from[0];
	var move = Math.abs(dir);
	if (vacant(to[0], to[1]) && from[1] == to[1] && move <= spaces) {
		if ((colour == 'w' && to[0] > from[0]) || (colour == 'b' && to[0] < from[0])) {
			checkApplyEnPassant(move, to, colour);
			return true;
		}
	} else if (onDiagonal(from, to) 
		&& ((colour == 'w' && dir == 1) || colour == 'b' && dir == -1))  {
		if (checkTakePiece(to, colour)) {
			return true;
		} else if (enPassantAvailable[0] == from[0] && enPassantAvailable[1] == to[1]) {
			//use En passant
			//allow revert if in check
			var epTaken = abstractBoard[from[0]][to[1]];
			abstractBoard[from[0]][to[1]] = false;
        	//update abstract board
    		updateAbstractBoard(from, to);
    		if (inCheck(colour)) {
				//revert
            	updateAbstractBoard(to, from);
				abstractBoard[to[0]][to[1]] = epTaken;
				return false;				
			}
			enPassantAvailable = false;
			enPassantPerformed = true;
			return true;
		}
	}
	return false;
}

/**
 * Check if king is in check
 */
function inCheck(colour) {
	var king = colour+'King';
	//get opponent colour
	colour = getOpponentColour(colour);
	//get king's position
	var kingSquare = 0;
	for (var row = 0; row < 8; row++) {
		var col = $.inArray(king, abstractBoard[row]);
		if (col !== -1) {
			kingSquare = [row, col];
			break;
		}
	}
	//check in check by pawn
	if (inCheckByPawn(colour, kingSquare)) {
		console.log('check by pawn');	
		return true;
	} else if (inCheckByKnight(colour, kingSquare)) {
		console.log('check by knight');
		return true;
	} else if (inCheckOnXAxis(colour, kingSquare)) {
		console.log('check on x-axis');
		return true;
	} else if (inCheckOnYAxis(colour, kingSquare)) {
		console.log('check on y-axis');
		return true;
	} else if (inCheckOnDiagonal(colour, kingSquare)) {
		console.log('check on diagonal');
		return true;
	}
	
	console.log('no check');	
	
	return false;
}

/**
 * Check if in check on diagonal
 */
function inCheckOnDiagonal(colour, kingSquare) {
	var row = kingSquare[0];
	var col = kingSquare[1];
	var blocks = [false,false,false,false];
	for (var i = 1; i < 8; i++) {
		var threats = [getPieceAt(row+i, col-i), getPieceAt(row+i, col+i), getPieceAt(row-i, col-i), getPieceAt(row-i, col+i)];
		if ((!blocks[0] && (threats[0] == colour+'Bishop' || threats[0] == colour+'Queen'))
			|| (!blocks[1] && (threats[1] == colour+'Bishop' || threats[1] == colour+'Queen'))
			|| (!blocks[2] && (threats[2] == colour+'Bishop' || threats[2] == colour+'Queen'))
			|| (!blocks[3] && (threats[3] == colour+'Bishop' || threats[3] == colour+'Queen'))
			) {
			return true;
		}
		//get blocking pieces
		for (var j = 0; j < 4; j++) {
			if (!blocks[j]) {
				blocks[j] = threats[j];					
			}
		}
	}
	return false;
}

/**
 * Check if in check on x-axis
 */
function inCheckOnXAxis(colour, kingSquare) {
	var row = kingSquare[0];
	for (var col = 0; col < 8; col++) {
		if (abstractBoard[row][col] == colour+'Rook' || abstractBoard[row][col] == colour+'Queen') {
			if ((col + 1) == kingSquare[1] || (col - 1) == kingSquare[1] || !xAxisBlocked(kingSquare[1], col, row)) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Check if in check on y-axis
 */
function inCheckOnYAxis(colour, kingSquare) {
	var col = kingSquare[1];
	for (var row = 0; row < 8; row++) {
		if (abstractBoard[row][col] == colour+'Rook' || abstractBoard[row][col] == colour+'Queen') {
			if ((row + 1) == kingSquare[0] || (row - 1) == kingSquare[0] || !yAxisBlocked(kingSquare[0], row, col)) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Check if king is in check by knight
 */
function inCheckByKnight(colour, kingSquare) {
	if (pieceAt(kingSquare[0]+2, kingSquare[1]-1, colour+'Knight')
		|| pieceAt(kingSquare[0]+2, kingSquare[1]+1, colour+'Knight')
		|| pieceAt(kingSquare[0]+1, kingSquare[1]-2, colour+'Knight')
		|| pieceAt(kingSquare[0]+1, kingSquare[1]+2, colour+'Knight')
		|| pieceAt(kingSquare[0]-1, kingSquare[1]-2, colour+'Knight')
		|| pieceAt(kingSquare[0]-1, kingSquare[1]+2, colour+'Knight')
		|| pieceAt(kingSquare[0]-2, kingSquare[1]-1, colour+'Knight')
		|| pieceAt(kingSquare[0]-2, kingSquare[1]+1, colour+'Knight')) {
		return true;
	}
	return false;
}

/**
 * Check if king is in check by pawn
 */
function inCheckByPawn(colour, kingSquare) {
	var dir = 1;
	if (colour == 'w') {
		dir = -1;
	}
	if (pieceAt(kingSquare[0]+dir, kingSquare[1]-1, colour+'Pawn')
		|| pieceAt(kingSquare[0]+dir, kingSquare[1]+1, colour+'Pawn')) {
		return true;
	}
	return false;
}

/**
 * Check given piece is at given square
 */
function pieceAt(row, column, piece) {
	if (row > -1 && row < 8 && column > -1 && column < 8) {
		if (abstractBoard[row][column] == piece) {
			return true;
		}
	}
	return false;
}

/**
 * Get piece/false at given square
 */
function getPieceAt(row, column) {
	if (row > -1 && row < 8 && column > -1 && column < 8) {
		return abstractBoard[row][column];
	}
	return false;
}

/**
 * Get opponent's colour
 */
function getOpponentColour(colour) {
	if (colour == 'w') {
		colour = 'b';
	} else {
		colour = 'w';			
	}
	return colour;
}

/**
 * Update abstract board,
 * handles taking automatically
 * @param from	[y,x]
 * @param to	[y,x]
 */
function updateAbstractBoard(from, to) {
	abstractBoard[to[0]][to[1]] = abstractBoard[from[0]][from[1]];
	abstractBoard[from[0]][from[1]] = false;
}

/**
 * Check if x-axis squares are blocked
 * @param from	y1
 * @param to	y2
 * @param row
 */
function xAxisBlocked(from, to, row) {
	//get x-axis direction
	var range = Math.abs(to - from);
	var x = (to - from) / range;
	//check squares inbetween are empty
	for (var i = 1; i < range; i++) {
		if(abstractBoard[row][from + (i*x)]) {
			return true;
		}
	}

	return false;
}

/**
 * Check if y-axis squares are blocked
 * @param from	x1
 * @param to	x2
 * @param column
 */
function yAxisBlocked(from, to, column) {
	//get y-axis direction
	var range = Math.abs(to - from);
	var y = (to - from) / range;
	//check squares inbetween are empty
	for (var i = 1; i < range; i++) {
		if(abstractBoard[from + (i*y)][column]) {
			return true;
		}
	}

	return false;
}

/**
 * Check if diagonal squares are blocked
 */
function diagonalBlocked(fromX, fromY, toX, toY) {
	var range = Math.abs(fromX - toX);
	//get x-axis direction
	var xDir = (toX - fromX) / range;
	//get y-axis direction
	var yDir = (toY - fromY) / range;
	//check squares inbetween are empty
	for (var i = 1; i < range; i++) {
		if(abstractBoard[fromY + (i*yDir)][fromX + (i*xDir)]) {
			return true;
		}
	}

	return false;
}

/**
 * check if target square is diagonal with source
 * @param from	[y,x]
 * @param to	[y,x]
 * 
 * @return Boolean
 */
function onDiagonal(from, to) {
	return Math.abs(to[0] - from[0]) == Math.abs(to[1] - from[1]);
}

/**
 * Check if target square is unoccupied
 */
function vacant(row, column) {
	return abstractBoard[row][column] === false;
}

/**
 * Check if target square is occupied by own piece
 */
function occupiedByOwnPiece(row, column, colour) {
	if (row > -1 && row < 8 && column > -1 && column < 8) {
		if (!vacant(row, column) && abstractBoard[row][column].charAt(0) == colour) {
			return true;
		}
	}
	
	return false;
}

/**
 * Check if target square is occupied by other piece
 */
function occupiedByOtherPiece(row, column, colour) {
	if (row > -1 && row < 8 && column > -1 && column < 8) {
		if (!vacant(row, column) && abstractBoard[row][column].charAt(0) != colour) {
			return true;
		}
	}
	
	return false;
}
	
/**
 * Check for takeable piece
 */
function checkTakePiece(square, colour) {
	if (occupiedByOtherPiece(square[0], square[1], colour)) {
		return true;
	}
	return false;
}

/**
 * Mark a piece as vulnerable to En passant
 */
function checkApplyEnPassant(move, to, colour) {
	if (move == 2) {
		//get opponent's colour
		if (colour == 'w') {
			colour = 'b';
		} else {
			colour = 'w';
		}
		//look left/right
		if (abstractBoard[to[0]][to[1]-1] == colour+'Pawn' || abstractBoard[to[0]][to[1]+1] == colour+'Pawn') {
			enPassantAvailable = to;
		}
	}	
}
	
/**
 * Check En passant has been performed
 */
function checkEnPassantPerformed() {
	if (enPassantPerformed) {
		enPassantPerformed = false;
		return true;
	} 		
	//check En passant time-out
	if (enPassantAvailable) {
		enPassantAvailable = false;
	}
	return false;		
}