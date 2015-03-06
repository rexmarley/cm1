//set positioning of letters
posOf = {'a': 1,'b': 2,'c': 3,'d': 4,'e': 5,'f': 6,'g': 7,'h': 8};
letterAt = ['a','b','c','d','e','f','g','h'];

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
 * @param y column in abstractBoard
 * @param x row in abstractBoard
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
 * Update abstract board
 * @param from
 * @param to
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
	//get direction
	var start = from;
	if (from > to) {
		start = to;
		to = from;
	}
	//check squares are empty
	for (var i = (start + 1); i < to; i++) {
		if(abstractBoard[row][i]) {
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
	//get direction
	var start = from;
	if (from > to) {
		start = to;
		to = from;
	}
	//check squares are empty
	for (var i = (start + 1); i < to; i++) {
		if(abstractBoard[i][column]) {
			return true;
		}
	}

	return false;
}