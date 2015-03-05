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
	
	//set positioning of letters
	posOf = {'a': 1,'b': 2,'c': 3,'d': 4,'e': 5,'f': 6,'g': 7,'h': 8};
	letterAt = ['a','b','c','d','e','f','g','h'];
	
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
    	} else if (pieceType == 'rook') {
			if (fLetter == tLetter || fNumber == tNumber) {
				valid = true;
			}		
		} else if (pieceType == 'knight') {
			if (((tNumber - fNumber)*(tNumber - fNumber)) + ((posOf[tLetter] - posOf[fLetter])*(posOf[tLetter] - posOf[fLetter])) == 5) {
				valid = true;
			}		
		} else if (pieceType == 'bishop') {
			if (onDiagonal(tNumber, fNumber, posOf[tLetter], posOf[fLetter])) {
				valid = true;
			}
		} else if (pieceType == 'queen') {
			if (fLetter == tLetter || fNumber == tNumber 
				|| (onDiagonal(tNumber, fNumber, posOf[tLetter], posOf[fLetter]) && !diagonalBlocked(fNumber, tNumber, fLetter, tLetter))) {
				valid = true;
			}		
		} else if (pieceType == 'king') {
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
	
	function diagonalBlocked(fNumber, tNumber, fLetter, tLetter) {
		var fLetterPos = posOf[fLetter] - 1;
		var tLetterPos = posOf[tLetter] - 1;
		var range = Math.abs(tNumber - fNumber);
		if (fLetterPos > tLetterPos) {
			//moving left
			if (fNumber < tNumber) {
				//moving up
				for (var i = 1; i < range; i++) {
					fNumber++;
					fLetterPos--;
					console.log(letterAt[fLetterPos]+'_'+ fNumber);
					if(!vacant(letterAt[fLetterPos]+'_'+ fNumber)) {
						return true;
					}
				}				
			} else {
				//moving down
				for (var i = 1; i < range; i++) {
					fNumber--;
					fLetterPos--;
					console.log(letterAt[fLetterPos]+'_'+ fNumber);
					if(!vacant(letterAt[fLetterPos]+'_'+ fNumber)) {
						return true;
					}
				}
			}			
		} else {
			//moving right
			if (fNumber < tNumber) {
				//moving up
				for (var i = 1; i < range; i++) {
					fNumber++;
					fLetterPos++;
					console.log(letterAt[fLetterPos]+'_'+ fNumber);
					if(!vacant(letterAt[fLetterPos]+'_'+ fNumber)) {
						return true;
					}
				}				
			} else {
				//moving down
				for (var i = 1; i < range; i++) {
					fNumber--;
					fLetterPos++;
					console.log(letterAt[fLetterPos]+'_'+ fNumber);
					if(!vacant(letterAt[fLetterPos]+'_'+ fNumber)) {
						return true;
					}
				}
			}	
		}

		return false;
	}
});