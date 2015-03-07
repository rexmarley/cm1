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
		var fLetter = from[0];
		var fNumber = parseInt(from[1], '10');
		//get to square
		var toSquare = this.id;
		var to = toSquare.split('_');
		var tLetter = to[0];
		var tNumber = parseInt(to[1], '10');		
		//get abstract indices for from/to
		var absFrom = getAbstractIndicesFromGridRef(fLetter, fNumber);
		var absTo = getAbstractIndicesFromGridRef(tLetter, tNumber);
		
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
			valid = validateKing(unmoved, colour, fLetter, tLetter, fNumber, tNumber);
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
	 * Validate pawn movement
	 */
	function validatePawn(colour, absFrom, absTo) {
		var valid = false;
		var spaces = 1;
		if (unmoved[absFrom[0]][absFrom[1]]) {
			//allow initial movement of 2 spaces
			spaces = 2;
		}
		//allow moving forward
		var dir = absTo[0] - absFrom[0];
		var move = Math.abs(dir);
		if (vacant(absTo[0], absTo[1]) && absFrom[1] == absTo[1] && move <= spaces) {
    		if ((colour == 'w' && absTo[0] > absFrom[0]) || (colour == 'b' && absTo[0] < absFrom[0])) {
				valid = true;
				checkApplyEnPassant(move, absTo, colour);
    		}
		} else if (onDiagonal(absFrom, absTo) 
			&& ((colour == 'w' && dir == 1) || colour == 'b' && dir == -1))  {
			console.log(absFrom[0], absTo[1]);
			if (!vacant(absTo[0], absTo[1])) {
				//occupied by own already checked --> allow take
				valid = true;
				//remove taken piece and move to side
				takePiece(getGridRefFromAbstractIndices(absTo[0],absTo[1]));
			} else if (enPassant[0] == absFrom[0] && enPassant[1] == absTo[1]) {
				//use En passant
				valid = true;
				takePiece(getGridRefFromAbstractIndices(absFrom[0],absTo[1]));
			}
		}
		return valid;
	}
	
	/**
	 * Validate rook movement
	 * @param from	[y,x]
	 * @param to	[y,x]
	 */
	function validateRook(from, to) {
		if ((fromIndices[0] == toIndices[0] && !xAxisBlocked(fromIndices[0], toIndices[0], fromIndices[1])) 
			|| (fromIndices[1] == toIndices[1] && !yAxisBlocked(fromIndices[1], toIndices[1], fromIndices[0]))) {
			//allow piece to be taken
			//checkTakePiece(tLetter+'_'+tNumber);	
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
			//checkTakePiece(tLetter+'_'+tNumber);
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
			//checkTakePiece(tLetter+'_'+tNumber);
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
		if ((from[0] == to[0] && !xAxisBlocked(from[0], to[0], from[1])) 
			|| (from[1] == to[1] && !yAxisBlocked(from[1], to[1], from[0])) 
			|| (onDiagonal(from, to) && !diagonalBlocked(from[1], from[0], to[1], to[0]))) {
			//allow piece to be taken
			//checkTakePiece(tLetter+'_'+tNumber);
			return true;
		}	
		return false;
	}
	
	/**
	 * Validate king movement
	 */
	function validateKing(unmoved, colour, fLetter, tLetter, fNumber, tNumber) {
		var valid = false;
		if (Math.abs(tNumber - fNumber) <= 1 && Math.abs(posOf[tLetter] - posOf[fLetter]) <= 1) {
			//TODO moving into check
			//allow piece to be taken
			checkTakePiece(tLetter+'_'+tNumber);
			valid = true;
		} else if (unmoved && tNumber == fNumber) {
			if (tLetter == 'g' && $('#'+colour+'_rook_2').hasClass('unmoved') 
				&& vacant('f_'+tNumber) && vacant('g_'+tNumber)) {
				//allow short castle
				valid = true;
				//move castle
				$('#f_'+tNumber).append($('#'+colour+'_rook_2'));
			} else if (tLetter == 'c' && $('#'+colour+'_rook_1').hasClass('unmoved') && vacant('b_'+tNumber) 
				&& vacant('c_'+tNumber) && vacant('d_'+tNumber)) {
				//allow long castle
				valid = true;
				//move castle
				$('#d_'+tNumber).append($('#'+colour+'_rook_1'));
			}
		}
		return valid;
	}
	
	/**
	 * Remove any existing piece, from given square, and move to side 
	 */
	function checkTakePiece(toSquare) {
		if (!vacant(toSquare)) {
			takePiece(toSquare);
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