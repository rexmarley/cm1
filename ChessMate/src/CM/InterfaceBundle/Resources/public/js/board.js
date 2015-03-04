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
		var unmoved = false;		
		if (ui.draggable.hasClass('unmoved')) {
			unmoved = true;
		}
		//console.log(unmoved);
		//console.log(ui.draggable);
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
		var colour = piece[0];
		var pieceType = piece[1];
		//set positioning of letters
		var pos = {'a': 1,'b': 2,'c': 3,'d': 4,'e': 5,'f': 6,'g': 7,'h': 8};
    	if (pieceType == 'pawn') {
    		var spaces = 1;
			//if (fNumber == 2 || fNumber == 7) {
			if (unmoved) {
				//allow initial movement of 2 spaces
				spaces = 2;
			}
			if (fLetter == tLetter) {
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
			if (Math.abs(tNumber - fNumber) <= 1 && Math.abs(pos[tLetter] - pos[fLetter]) <= 1) {
				valid = true;
			} else if (unmoved && tNumber == fNumber) {
				if (tLetter == 'g' && $('#'+colour+'_rook_2').hasClass('unmoved') 
					&& !$.trim($('#f_'+tNumber).text()).length && !$.trim($('#g_'+tNumber).text()).length) {
					//allow short castle
					valid = true;
					//move castle
					$('#f_'+tNumber).append($('#'+colour+'_rook_2'));
				} else if (tLetter == 'c' && $('#'+colour+'_rook_1').hasClass('unmoved') && !$.trim($('#b_'+tNumber).text()).length 
					&& !$.trim($('#c_'+tNumber).text()).length && !$.trim($('#d_'+ tNumber).text()).length) {
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
});