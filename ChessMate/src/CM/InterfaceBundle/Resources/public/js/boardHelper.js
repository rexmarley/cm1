//set positioning of letters
posOf = {'a': 1,'b': 2,'c': 3,'d': 4,'e': 5,'f': 6,'g': 7,'h': 8};
letterAt = ['a','b','c','d','e','f','g','h'];

abstractBoard = [
	                'wRook','wKnight','wBishop','wQueen','wKing','wBishop','wKnight','wRook',
	                'wPawn','wPawn','wPawn','wPawn','wPawn','wPawn','wPawn','wPawn',
	                 false, false, false, false, false, false, false, false,
	                 false, false, false, false, false, false, false, false,
	                 false, false, false, false, false, false, false, false,
	                 false, false, false, false, false, false, false, false,
		            'bPawn','bPawn','bPawn','bPawn','bPawn','bPawn','bPawn','bPawn',
	                'bRook','bKnight','bBishop','bQueen','bKing','bBishop','bKnight','bRook'
                ];

/**
 * Resolve grid reference to array index
 * @param x alphabet ref
 * @param y numeric ref
 * @return the corresponding index in abstractBoard array
 */
function getIndexFromGridRef(x, y) {
	//get numeric for x
	x = posOf[x];
	//decrement for array index
	x--;
	y--;
	//translate to index
	return (y * 8) + x ;
}

/**
 * Resolve array index to grid reference
 * @param index the index in abstractBoard array
 * @return the corresponding grid reference
 */
function getGridRefFromIndex(index) {
	//get numeric x
	var x = index % 8;
	//get y
	var y = ((index - x) / 8) + 1;
	//get alpha x
	x = letterAt[x];
	//translate to grid ref
	return x+'_'+y;
}