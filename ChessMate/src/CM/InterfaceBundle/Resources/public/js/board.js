$(document).ready( function() {
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
	
	$('.square').droppable({
		accept: '.piece',
		drop: validateMove,
    });
	
	function validateMove(event, ui) {
		var valid = false;
		//get moved piece
		var piece = ui.draggable.attr('id').split('_');
		var colour = piece[0];
		var pieceType = piece[1];
		//get target square
		var toSquare = this.id;
		console.log($('#'+ toSquare).children('div.piece').get(0));
		//check if target is occupied by own piece
		if (occupiedByOwnPiece(toSquare, colour)) {
    		//invalidate move
    		ui.draggable.addClass('invalid');
			return false;
		}
		var to = toSquare.split('_');
		var tLetter = to[0];
		var tNumber = to[1];
		//get from square
		var fromSquare = ui.draggable.parent().attr('id');
		var from = fromSquare.split('_');
		var fLetter = from[0];
		var fNumber = from[1];
		//check if piece's first move
		var unmoved = false;		
		if (ui.draggable.hasClass('unmoved')) {
			unmoved = true;
		}
		//set positioning of letters - TODO global?
		var pos = {'a': 1,'b': 2,'c': 3,'d': 4,'e': 5,'f': 6,'g': 7,'h': 8};
		//validate move
    	if (pieceType == 'pawn') {
    		var spaces = 1;
			if (unmoved) {
				//allow initial movement of 2 spaces
				spaces = 2;
			}
			if (fLetter == tLetter) {
	    		if ((colour == 'w' && tNumber > fNumber && tNumber - fNumber <= spaces)
	    			|| (colour == 'b' && tNumber < fNumber && fNumber - tNumber <= spaces)) {
					valid = true;
	    		}
			} else if (!vacant(toSquare)) {
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
			if (((tNumber - fNumber)*(tNumber - fNumber)) + ((pos[tLetter] - pos[fLetter])*(pos[tLetter] - pos[fLetter])) == 5) {
				valid = true;
			}		
		} else if (pieceType == 'bishop') {
			if (Math.abs(tNumber - fNumber) == Math.abs(pos[tLetter] - pos[fLetter])) {
				valid = true;
			}
		} else if (pieceType == 'queen') {
			if (fLetter == tLetter || fNumber == tNumber || Math.abs(tNumber - fNumber) == Math.abs(pos[tLetter] - pos[fLetter])) {
				valid = true;
			}		
		} else if (pieceType == 'king') {
			if (Math.abs(tNumber - fNumber) <= 1 && Math.abs(pos[tLetter] - pos[fLetter]) <= 1) {
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
	 * Check if target square is unoccupied
	 */
	function vacant(squareID) {		
		return !$.trim($('#'+squareID).text()).length;
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
	
	function diagonalNotBlocked(fNumber, fLetter, tNumber, tLetter) {
		//Math.abs(tNumber - fNumber) == Math.abs(pos[tLetter] - pos[fLetter])
	}
});