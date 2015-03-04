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
		var fromSquare = ui.draggable.parent().attr('id');
		var toSquare = this.id;
		var from = fromSquare.split('_');
		var fLetter = from[0];
		var fNumber = from[1];
		var to = toSquare.split('_');
		var tLetter = to[0];
		var tNumber = to[1];
		//get piece type & colour
		var piece = ui.draggable.attr('id').split('_');
		var pieceType = piece[1];
		//set positioning of letters TODO: something better (ASCII ?)
		var pos = {'a': 1,'b': 2,'c': 3,'d': 4,'e': 5,'f': 6,'g': 7,'h': 8};
    	if (pieceType == 'pawn') {
    		var spaces = 1;
			//allow initial movement of 2 spaces
			if (fNumber == 2 || fNumber == 7) {
				//no need to worry about moving extra space; if direction is different, end of board is reached
				spaces = 2;
			}
			if (fLetter == tLetter) {
				var colour = piece[0];
	    		if ((colour == 'w' && tNumber > fNumber && tNumber - fNumber <= spaces)
	    			|| (colour == 'b' && tNumber < fNumber && fNumber - tNumber <= spaces)) {
					valid = true;
	    		}
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
			if (tNumber - fNumber <= 1 && pos[tLetter] - pos[fLetter] <= 1) {
				valid = true;
			}		
		}

    	if(!valid) {
    		//invalidate move
    		ui.draggable.addClass('invalid');
    	} else {
    		//center (and disable?)
    		$(this).append(ui.draggable.css('position','static'));
    	}
    	
    	return valid;
	}
});