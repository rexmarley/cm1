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

enPassant = false;

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
 * @param from
 * @param to
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
 * @param from
 * @param to
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
			enPassant = to;
		}
	}	
}
