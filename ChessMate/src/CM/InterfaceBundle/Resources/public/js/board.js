$(document).ready( function() {
	/**
	 * Make pieces draggable
	 */
	$('.ui-draggable').draggable({
        containment : '.board',
        revert: function() {
        	//validate based on droppable.drop
            if ($(this).hasClass('invalid')) {
            	$(this).removeClass('invalid');
                return true;
            }
        }
	});	

	/**
	 * Make squares droppable
	 */
	$('.square').droppable({
		accept: '.piece',
		drop: validateMove,
    });
	
	//Temp workaround for hidden overflow hiding draggable
	$('.square').mouseover(function() {
		$(this).removeClass('clipped');
	});
	
	$('.square').mouseleave(function() {
		$(this).addClass('clipped');
	});

	/**
	 * Swap pawn on selection
	 */
	$('.choosablePiece').click(function() {
		//get selected piece
		var piece = this.id.split('_');
		var colour = piece[1];
		var type = piece[2];
		//get new id
		var num = 3;
		if (type == 'queen') {
			num--;
		}
		newPiece = colour+'_'+type;
		//check for conflict
		var conflict = $('#'+newPiece+'_'+num);
		while (conflict.length) {
			num++;
			conflict = $('#'+newPiece+'_'+num);
		}
		//get pawn position
		var endRow = 7;
		if (colour == 'b') {
			endRow = 0;
		}
		var pawnCol = $.inArray(colour+'_pawn', abstractBoard[endRow]);
		//update abstract board
		abstractBoard[endRow][pawnCol] = newPiece;
		//update real board
		var pawn = getOccupant(getGridRefFromAbstractIndices(endRow, pawnCol));
		//change piece
		pawn.html($(this).html());
		//set new id
		pawn.attr('id', newPiece+'_'+num);
		//close piece-chooser
		$('#choosePiece_'+colour).dialog("close");
		//ajax move if real game
		if ($('.board').attr('id').charAt(5) != 'x') {
	    	//ajax move & validate server-side
	    	//should only fail due to cheating --> display message and manually revert board
			//(TODO disable board on success?)
			ajaxMove(gFrom, [endRow, pawnCol], 'pawn', colour);
		}
	});
	//global var for swapping pawn
	gFrom = [];

	/**
	 * Validate chess move
	 */
	function validateMove(event, ui) {
		//get moved piece
		var piece = getPieceDetails(ui.draggable.attr('id'));		
		//get abstract indices for from/to squares
		var from = getAbstractedSquareIndex(ui.draggable.parent().attr('id'));
		var to = getAbstractedSquareIndex(this.id);
		//check if target is occupied by own piece
		if (occupiedByOwnPiece(to[0], to[1], piece['colour'])) {
    		//invalidate move
    		ui.draggable.addClass('invalid');
			return false;
		}		
		//validate move
    	var valid = validatePieceType(piece['type'], piece['colour'], from, to);
    	if(valid) {
    		//get target square occupant - in case of revert
    		var occupant = abstractBoard[to[0]][to[1]];
    		if (!checkEnPassantPerformed(to)) {
            	//update abstract board
        		updateAbstractBoard(from, to);
	    		if (!castled) {
	        		//if in check, invalidate move
	    			if (inCheck(piece['colour'])) {
	                	//revert board
	            		updateAbstractBoard(to, from);
	            		abstractBoard[to[0]][to[1]] = occupant;
	            		//invalidate move
	            		ui.draggable.addClass('invalid');
	            		return false;
	        		}
	    		} else {
	    			//check already checked
					//move castle
	    			if (to[1] == 2) {
						$('#d_'+(to[0]+1)).append($('#'+piece['colour']+'_rook_1'));
	    			} else {
						$('#f_'+(to[0]+1)).append($('#'+piece['colour']+'_rook_2'));
	    			}
	    			castled = false;
	    		}
	    		//check for takeable piece
    			checkAndTakePiece(to);
    		} else {
				takePiece(getGridRefFromAbstractIndices(from[0],to[1]));
			}
			//check for pawn reaching opposing end
			if (piece['type'] == 'pawn' && (piece['colour'] == 'w' && to[0] == 7) || (piece['colour'] == 'b' && to[0] == 0)) {
				//ajax move on piece selection
				gFrom = from;
				openPieceChooser(piece['colour']);
			} else if ($('.board').attr('id').charAt(5) != 'x') {
	        	//ajax move & validate server-side
	        	//should only fail due to cheating --> display message and manually revert board
				//(TODO disable board on success?)
	        	ajaxMove(from, to, piece['type'], piece['colour']);				
			}
			unmoved[from[0]][from[1]] = false;
    		//center piece
    		$(this).append(ui.draggable.css('position','static'));
    	} else {
    		//invalidate move
    		ui.draggable.addClass('invalid');
    		return false;
    	}
    	return valid;
	}
	
	function ajaxMove(from, to, type, colour) {
    	//ajax move & validate server-side
    	//TODO: change to app.php for live
		//var valid = false;
		//get game id
		var game = $('.board').attr('id').split('_');
    	$.ajax({
    		type: "POST",
    	    //async: false,
    		url: 'https://'+document.location.hostname+'/CM/ChessMate/web/app_dev.php/checkMove',
    		data: { 'gameID' : game[1],'from' : from, 'to' : to , 'type' : type, 'colour' : colour, 'newPiece' : newPiece },
    		success: function(data) {
    			//console.log(data['board']);
    			if (!data['valid']) {
    				$('div.errors').html('<h2>Nice try but why cheat at chess? You\'re docked a minute!</h2>');
    			    // show for 5 seconds
    			    setTimeout(function() { $('div.errors h2').fadeOut(2000); }, 5000);
    			    setTimeout(function() { $('div.errors').html('') }, 8000);
    			    //revert board
    				//abstractBoard = data['board'];
    				//could just cancel game for now
    			} else {
    				//update abstract board
    				abstractBoard = data['board'];
    				//get opponent's valid move
    				from = data['from'];
    				to = data['to'];
    				var gridFrom = getGridRefFromAbstractIndices(from[0], from[1]);
    				var gridTo = getGridRefFromAbstractIndices(to[0], to[1]);
    				//move piece
    				var moved = getOccupant(gridFrom);
    				moved.position({
    		            of: 'div#'+gridTo
    		        });
    			}
    		}
    	});
	}
	
	/**
	 * Open piece-chooser dialog
	 */
	function openPieceChooser(colour) {
		$('#choosePiece_'+colour).dialog("open");		
	}
	
	/**
	 * Get piece type/colour from id
	 */
	function getPieceDetails(pieceID) {
		var piece = pieceID.split('_');
		return {'colour':piece[0], 'type':piece[1]};
	}
	
	/**
	 * Get board square's abstracted index in array
	 */
	function getAbstractedSquareIndex(squareID) {
		//get grid reference
		var square = squareID.split('_');
		//convert to array indices
		return getAbstractIndicesFromGridRef(square[0], parseInt(square[1], '10'));		
	}
	
	/**
	 * Check for takeable piece and remove if found
	 * Given square must already be checked for own piece
	 */
	function checkAndTakePiece(square) {
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
    	taken.draggable({ disabled: true });
    	//prevent transparency
    	taken.removeClass('ui-state-disabled');
	}
	
	/**
	 * Get occupant of given square
	 */
	function getOccupant(squareID) {
		return $('#'+ squareID).children('div.piece');
	}
});