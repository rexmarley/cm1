$(document).ready( function() {
	//set positioning of letters
	posOf = {'a': 1,'b': 2,'c': 3,'d': 4,'e': 5,'f': 6,'g': 7,'h': 8};
	letterAt = ['a','b','c','d','e','f','g','h'];
	
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
	
	/**
	 * Validate chess move
	 */
	function validateMove(event, ui) {
		var valid = false;
		//get moved piece
		var piece = ui.draggable.attr('id').split('_');
		var colour = piece[0];
		var pieceType = piece[1];
		//get target square
		var toSquare = this.id;
		//check if target is occupied by own piece
		if (occupiedByOwnPiece(toSquare, colour)) {
    		//invalidate move
    		ui.draggable.addClass('invalid');
			return false;
		}
		var to = toSquare.split('_');
		var tLetter = to[0];
		var tNumber = parseInt(to[1], '10');
		//get from square
		var fromSquare = ui.draggable.parent().attr('id');
		var from = fromSquare.split('_');
		var fLetter = from[0];
		var fNumber = parseInt(from[1], '10');
		//check if piece's first move
		var unmoved = false;		
		if (ui.draggable.hasClass('unmoved')) {
			unmoved = true;
		}
		//validate move
    	if (pieceType == 'pawn') {
    		valid = validatePawn(unmoved, colour, toSquare, fLetter, tLetter, fNumber, tNumber);
    	} else if (pieceType == 'rook') {
    		valid = validateRook(fLetter, tLetter, fNumber, tNumber);
		} else if (pieceType == 'knight') {
			valid = validateKnight(fLetter, tLetter, fNumber, tNumber);
		} else if (pieceType == 'bishop') {
			valid = validateBishop(fLetter, tLetter, fNumber, tNumber);
		} else if (pieceType == 'queen') {
			valid = validateQueen(fLetter, tLetter, fNumber, tNumber);	
		} else if (pieceType == 'king') {
			valid = validateKing(unmoved, colour, fLetter, tLetter, fNumber, tNumber);
		}

    	if(!valid) {
    		//invalidate move
    		ui.draggable.addClass('invalid');
    	} else {
			ui.draggable.removeClass('unmoved');
    		//center (TODO disable board?)
    		$(this).append(ui.draggable.css('position','static'));
    	}
    	
    	return valid;
	}
	
	/**
	 * Validate rook movement
	 */
	function validateRook(fLetter, tLetter, fNumber, tNumber) {
		if ((fLetter == tLetter && !yAxisBlocked(fNumber, tNumber, fLetter))
			|| (fNumber == tNumber && !xAxisBlocked(fLetter, tLetter, fNumber))) {
			return true;
		}
		return false;
	}
	
	/**
	 * Validate knight movement
	 */
	function validateKnight(fLetter, tLetter, fNumber, tNumber) {
		if (((tNumber - fNumber)*(tNumber - fNumber)) + ((posOf[tLetter] - posOf[fLetter])*(posOf[tLetter] - posOf[fLetter])) == 5) {
			return true;
		}
		return false;
	}
	
	/**
	 * Validate bishop movement
	 */
	function validateBishop(fLetter, tLetter, fNumber, tNumber) {
		if (onDiagonal(tNumber, fNumber, posOf[tLetter], posOf[fLetter]) 
			&& !diagonalBlocked(fNumber, tNumber, fLetter, tLetter)) {
			return true;
		}
		return false;
	}
	
	/**
	 * Validate queen movement
	 */
	function validateQueen(fLetter, tLetter, fNumber, tNumber) {
		if ((fLetter == tLetter && !yAxisBlocked(fNumber, tNumber, fLetter))
			|| (fNumber == tNumber && !xAxisBlocked(fLetter, tLetter, fNumber)) 
			|| (onDiagonal(tNumber, fNumber, posOf[tLetter], posOf[fLetter]) 
				&& !diagonalBlocked(fNumber, tNumber, fLetter, tLetter))) {
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
	 * Validate pawn movement TODO: refactor
	 */
	function validatePawn(unmoved, colour, toSquare, fLetter, tLetter, fNumber, tNumber) {
		var valid = false;
		var spaces = 1;
		if (unmoved) {
			//allow initial movement of 2 spaces
			spaces = 2;
		}
		if (vacant(toSquare)) {
			//allow moving forward
			if (fLetter == tLetter) {
	    		if ((colour == 'w' && tNumber > fNumber && tNumber - fNumber <= spaces)
	    			|| (colour == 'b' && tNumber < fNumber && fNumber - tNumber <= spaces)) {
					valid = true;
	    		}
			}				
		} else if (onDiagonal(tNumber, fNumber, posOf[tLetter], posOf[fLetter]) 
			&& ((colour == 'w' && tNumber - 1 == fNumber) || colour == 'b' && fNumber - 1 == tNumber))  {
			//occupied by own already checked --> allow take
			valid = true;
			//remove taken piece and move to side
			var taken = getOccupant(toSquare);
			$('#piecesWon').append(taken);
        	taken.removeClass('ui-draggable');
		}
		return valid;
	}
	
	/**
	 * check if target is on diagonal with source
	 * 
	 * @param tNumber
	 * @param fNumber
	 * @param tLetterPos
	 * @param fLetterPos
	 * @return Boolean
	 */
	function onDiagonal(tNumber, fNumber, tLetterPos, fLetterPos) {
		return Math.abs(tNumber - fNumber) == Math.abs(tLetterPos - fLetterPos);
	}
	
	/**
	 * Check if target square is unoccupied
	 */
	function vacant(squareID) {
		return $('div#'+squareID+' div.piece').length < 1;
	}
	
	/**
	 * Get occupant of given square
	 */
	function getOccupant(squareID) {
		return $('#'+ squareID).children('div.piece');
	}
	
	/**
	 * Check if target square is occupied by own piece
	 */
	function occupiedByOwnPiece(targetID, colour) {
		if (!vacant(targetID) && getOccupant(targetID).attr('id').charAt(0) == colour) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Check if x-axis squares are blocked
	 */
	function xAxisBlocked(fLetter, tLetter, number) {
		var fLetterPos = posOf[fLetter] - 1;
		var tLetterPos = posOf[tLetter] - 1;
		var range = Math.abs(fLetterPos - tLetterPos);
		//get x-axis direction
		var x = (tLetterPos - fLetterPos) / range;
		//check squares are empty
		for (var i = 1; i < range; i++) {
			if(!vacant(letterAt[fLetterPos + (i*x)]+'_'+number)) {
				return true;
			}
		}

		return false;
	}
	
	/**
	 * Check if y-axis squares are blocked
	 */
	function yAxisBlocked(fNumber, tNumber, letter) {
		var range = Math.abs(tNumber - fNumber);
		//get x-axis direction
		var y = (tNumber - fNumber) / range;
		//check squares are empty
		for (var i = 1; i < range; i++) {
			if(!vacant(letter+'_'+(fNumber + (i*y)))) {
				return true;
			}
		}

		return false;
	}
	
	/**
	 * Check if diagonal squares are blocked
	 */
	function diagonalBlocked(fNumber, tNumber, fLetter, tLetter) {
		var fLetterPos = posOf[fLetter] - 1;
		var tLetterPos = posOf[tLetter] - 1;
		var range = Math.abs(tNumber - fNumber);
		//get x-axis direction
		var x = (tLetterPos - fLetterPos) / range;
		//get y-axis direction
		var y = (tNumber - fNumber) / range;
		//check squares are empty
		for (var i = 1; i < range; i++) {
			if(!vacant(letterAt[fLetterPos + (i*x)]+'_'+(fNumber + (i*y)))) {
				return true;
			}
		}

		return false;
	}
});