$(document).ready( function() {	
	//make pieces draggable
	$('.piece').draggable({
        containment : '#board',
        revert: function() {
        	//validate based on droppable.drop
            if ($(this).hasClass('invalid')) {
            	$(this).removeClass('invalid');
                return true;
            }
        }
	});
	
	//make squares droppable
	$('.square').droppable({
		accept: '.piece',
		drop: validateMove,
    });
	
	function inCheck(colour) {
		var kingID = '#'+colour + '_king';
		var kingSquare = $(kingID).parent().attr('id').split('_');
		var x = kingSquare[0];
		var y = kingSquare[1];
		//check in check by pawn
		if (inCheckByPawn(colour, x, y)) {
			//$(kingID).removeClass('inCheck');	
			return true;
		}
		return false;
	}
	
	function inCheckByPawn(colour, xKing, yKing) {
		var x1 = letterAt[posOf[xKing]-2];
		var x2 = letterAt[posOf[xKing]];
		if (colour == 'w') {
			yKing++;
		} else {
			yKing--;			
		}
		if ((occupiedByOtherPiece(x1+'_'+yKing, colour) && getOccupant(x1+'_'+yKing).hasClass(colour+' pawn'))
				||(occupiedByOtherPiece(x2+'_'+yKing, colour) && getOccupant(x2+'_'+yKing).hasClass(colour+' pawn'))) {
			console.log('check by pawn')
			return true;
		}
		return false;
	}
	
	/**
	 * Validate chess move
	 */
	function validateMove(event, ui) {
		var valid = false;
		//get moved piece
		var pieceID = ui.draggable.attr('id');
		var piece = pieceID.split('_');
		var colour = piece[0];
		var pieceType = piece[1];
		//if in check, force moving out
		//if (inCheck(colour)) {
			//$(kingID).addClass('inCheck');			
		//}
		//get from square
		var fromSquare = ui.draggable.parent().attr('id');
		var from = fromSquare.split('_');
		//get to square
		var toSquare = this.id;
		var to = toSquare.split('_');		
		//get abstract indices for from/to
		var absFrom = getAbstractIndicesFromGridRef(from[0], parseInt(from[1], '10'));
		var absTo = getAbstractIndicesFromGridRef(to[0], parseInt(to[1], '10'));		
		//check if target is occupied by own piece
		if (occupiedByOwnPiece(absTo[0], absTo[1], colour)) {
    		//invalidate move
    		ui.draggable.addClass('invalid');
			return false;
		}		
		//validate move
    	if (pieceType == 'pawn') {
    		valid = validatePawn(colour, absFrom, absTo);
    	} else if (pieceType == 'rook') {
    		valid = validateRook(absFrom, absTo);
		} else if (pieceType == 'knight') {
			valid = validateKnight(absFrom, absTo);
		} else if (pieceType == 'bishop') {
			valid = validateBishop(absFrom, absTo);
		} else if (pieceType == 'queen') {
			valid = validateQueen(absFrom, absTo);	
		} else if (pieceType == 'king') {
			valid = validateKing(colour, absFrom, absTo);
		}

    	if(valid) {
			if (enPassant && enPassant !== absTo) {
				//remove timed-out En passant
				enPassant = false;
			}
			unmoved[absFrom[0]][absFrom[1]] = false;
    		//center (TODO disable board?)
    		$(this).append(ui.draggable.css('position','static'));
        	//update abstract board
    		updateAbstractBoard(absFrom, absTo);
    	} else {
    		//invalidate move
    		ui.draggable.addClass('invalid');
    	}
    	
    	return valid;
	}
	
	/**
	 * Validate rook movement
	 * @param from	[y,x]
	 * @param to	[y,x]
	 */
	function validateRook(from, to) {
		if ((from[0] == to[0] && !xAxisBlocked(from[1], to[1], from[0])) 
			|| (from[1] == to[1] && !yAxisBlocked(from[0], to[0], from[1]))) {
			//allow piece to be taken
			checkTakePiece(to);
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
			//allow piece to be taken
			checkTakePiece(to);
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
			//allow piece to be taken
			checkTakePiece(to);
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
			//allow piece to be taken
			checkTakePiece(to);
			return true;
		}	
		return false;
	}
	
	/**
	 * Validate king movement
	 * @param from	[y,x]
	 * @param to	[y,x]
	 */
	function validateKing(colour, from, to) {
		//TODO moving into check
		if (Math.abs(to[1] - from[1]) <= 1 && Math.abs(to[0] - from[0]) <= 1) {
			//allow piece to be taken
			checkTakePiece(to);
			return true;
		} else if (unmoved[from[0]][from[1]] && to[0] == from[0]) {
			//handle castling
			if (to[1] == 6 && unmoved[from[0]][7]
				&& vacant(from[0], 5) && vacant(from[0], 6)) {
				//move castle
				$('#f_'+(to[0]+1)).append($('#'+colour+'_rook_2'));
	        	//update abstract board
	    		updateAbstractBoard([from[0], 7], [to[0], 5]);
	    		//set rook as moved - not actually necessary
				unmoved[from[0]][7] = false;
				//allow short castle
				return true;
			} else if (to[1] == 2 && unmoved[from[0]][0] && vacant(from[0], 1)
				&& vacant(from[0], 3) && vacant(from[0], 3)) {
				//move castle
				$('#d_'+(to[0]+1)).append($('#'+colour+'_rook_1'));
	        	//update abstract board
	    		updateAbstractBoard([from[0], 0], [to[0], 3]);
	    		//set rook as moved - not actually necessary
				unmoved[from[0]][0] = false;
				//allow long castle
				return true;
			}
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
			if (!vacant(to[0], to[1])) {
				//occupied by own already checked --> allow take
				takePiece(getGridRefFromAbstractIndices(to[0],to[1]));
				return true;
			} else if (enPassant[0] == from[0] && enPassant[1] == to[1]) {
				//use En passant
				takePiece(getGridRefFromAbstractIndices(from[0],to[1]));
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Check for takeable piece and remove if found 
	 */
	function checkTakePiece(square) {
		if (!vacant(square[0],square[1])) {
			takePiece(getGridRefFromAbstractIndices(square[0],square[1]));
		}
	}
	
	/**
	 * Remove piece, from given square, and move to side 
	 */
	function takePiece(toSquare) {
		//get taken piece
		var taken = getOccupant(toSquare);
		//move off board
    	if ($('div#piecesWon div.piece').length == 0) {
    		$('div#piecesWon div.row:first div.col-md-2:first').append(taken);
    	} else {
    		var lastOccupied = $('div#piecesWon div.piece:last').parent();
    		lastOccupied.next('div.col-md-2').append(taken);
    	}
    	//prevent further movement
    	taken.removeClass('ui-draggable');
	}
	
	/**
	 * Get occupant of given square
	 */
	function getOccupant(squareID) {
		return $('#'+ squareID).children('div.piece');
	}
});