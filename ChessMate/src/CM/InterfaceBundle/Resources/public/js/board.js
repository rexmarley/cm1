$(document).ready( function() {
	$('.piece').draggable({
        containment : '#board',
        //revert: 'invalid'
        revert: function() {
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
		var pieceType = 'pawn';
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
				var colour = ui.draggable.attr('id').charAt(0);
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
			if (tNumber - fNumber == pos[tLetter] - pos[fLetter]) {
				valid = true;
			}
		} else if (pieceType == 'queen') {
			if (tNumber - fNumber <= 1 && pos[tLetter] - pos[fLetter] <= 1) {
				valid = true;
			}		
		} else if (pieceType == 'king') {
			if (tNumber - fNumber <= 1 && pos[tLetter] - pos[fLetter] <= 1) {
				valid = true;
			}		
		}
    	console.log(valid);

    	if(!valid) {
    		//ui.draggable.remove();
    		ui.draggable.addClass('invalid');
    	} else {
    		//center (and disable?)
    		$(this).append(ui.draggable.css('position','static'));
    	}
    	
    	return valid;
	}
	
//	function validateMove(event) {
//		//get colour/direction (for pawns)
//		console.log(event);
//		console.log(this);
//		console.log($(this).parent());
//		var direction = 1;
//		if ($(this).attr('id').charAt(0) == 'b') {
//			//black moves back
//			direction = -1;
//		}
////		var data = {
////			   	        pieceType: 'pawn',
////						//colour: 'white',
////						colour: direction,
////				        fromSquare: ui.draggable.parent().attr('id'),
////				        toSquare: this.id,
////			        };
//		var invalid = true;
//		var fromSquare = this.parent().attr('id');
//		var toSquare = event.attr('id');
//		var from = fromSquare.split('_');
//		var fLetter = from[0];
//		var fNumber = from[1];
//		var to = toSquare.split('_');
//		var tLetter = to[0];
//		var tNumber = to[1];
//		var pieceType = 'pawn';
//		var colour = direction;
//		//set positioning of letters TODO: something better (ASCII ?)
//		var pos = {'a': 1,'b': 2,'c': 3,'d': 4,'e': 5,'f': 6,'g': 7,'h': 8};
//
////		$(this).detach();
////		console.log($(this).parent());
//		
//    	if (pieceType == 'pawn') {
//			if (fNumber == 2 || fNumber == 7) {
//				//allow initial movement of 2 spaces
//				//no need to worry about moving extra space: if direction is different end of board is reached
//				colour = colour * 2;
//			}
//			console.log(tNumber);
//			console.log(fNumber);
//			console.log(tLetter);
//			console.log(fLetter);
//			console.log(colour);
//			//if ($colour == 'white') {
//			if (tNumber > fNumber && fLetter == tLetter && tNumber - colour <= fNumber) {
//				invalid = false;
//			}
//			//}
//    	} else if (pieceType == 'rook') {
//			if (fLetter == tLetter || fNumber == tNumber) {
//				invalid = false;
//			}		
//		} else if (pieceType == 'knight') {
//			if (((tNumber - fNumber)*(tNumber - fNumber)) + ((pos[tLetter] - pos[fLetter])*(pos[tLetter] - pos[fLetter])) == 5) {
//				invalid = false;
//			}		
//		} else if (pieceType == 'bishop') {
//			if (tNumber - fNumber == pos[tLetter] - pos[fLetter]) {
//				invalid = false;
//			}
//		} else if (pieceType == 'queen') {
//			if (tNumber - fNumber <= 1 && pos[tLetter] - pos[fLetter] <= 1) {
//				invalid = false;
//			}		
//		} else if (pieceType == 'king') {
//			if (tNumber - fNumber <= 1 && pos[tLetter] - pos[fLetter] <= 1) {
//				invalid = false;
//			}		
//		}
//    	console.log(invalid);
//    	//console.log(event);
//    	
//    	if (!invalid) {
//    		//$(event).append(this);
//    	}
//
//    	return invalid;
//		
////	    $.ajax({
////	    	type: "POST",
////	        url : "http://localhost/CM/ChessMate/web/app_dev.php/checkMove",
////	        dataType : 'json',
////	        data: data,
////	        error : function(data, errorThrown) {
////	            alert(errorThrown);
////	        },
////	        success : function(data) {
////				//centre piece
////	        	//console.log($(this));
////				//$(this).append(ui.draggable.css('position','static')); //prevents visible re-move? 
////				//TODO: disable on success?
////	        }
////	    });
//	}
});