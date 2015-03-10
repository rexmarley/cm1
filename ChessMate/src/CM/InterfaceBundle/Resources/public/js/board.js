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
	$('.ui-dialog').dialog({
		 autoOpen: false,
		 show: {
			 effect: "blind",
			 duration: 1000
		 },
		 hide: {
			 effect: "explode",
			 duration: 1000
		 }
	});   
	//center piece-chooser on board - TODO: not working
    var dContainer = $('#board');
    var dialog = $('.ui-dialog');
    var x1 = dContainer.offset().left;
    var y1 = dContainer.offset().top;
    var width = dialog.outerWidth();
    var height = dialog.outerHeight();
    var x2 = dContainer.width() + x1 - width;
    var y2 = dContainer.height() + y1 - height;
    //dialog.draggable("option", "containment", [x1, y1, x2, y2]);
    dialog.draggable({
        containment : [x1, y1, x2, y2]
	});

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
        	//update abstract board
    		updateAbstractBoard(from, to);
    		if (!castled && !enPassantPerformed) {
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
					$('#d_'+to[0]+1).append($('#'+colour+'_rook_1');
    			} else {
					$('#f_'+to[0]+1).append($('#'+colour+'_rook2');
    			}
    			castled = false;
    		}
			//allow piece to be taken
    		if (!checkEnPassantPerformed()) {
    			checkAndTakePiece(to);
    			//check for pawn reaching opposing end
    			if (piece['type'] == 'pawn') {
    				if ((piece['colour'] == 'w' && to[0] == 7) || (piece['colour'] == 'b' && to[0] == 0)) {
    					console.log('other side reached');
    					openPieceChooser(piece['colour']);
    				}
    			}
    		} else {
				takePiece(getGridRefFromAbstractIndices(from[0],to[1]));
			}
			unmoved[from[0]][from[1]] = false;
    		//center piece
    		$(this).append(ui.draggable.css('position','static'));
			//(TODO disable board?)
    	} else {
    		//invalidate move
    		ui.draggable.addClass('invalid');
    	}
    	
    	return valid;
	} 

	/**
	 * Get validation for different pieces
	 * @param colour 'w'/'b'
	 * @param from	[y,x]
	 * @param to	[y,x]
	 */
	function validatePieceType(type, colour, from, to) {
		if (type == 'pawn') {
			return validatePawn(colour, from, to);
		} else if (type == 'rook') {
			return validateRook(from, to);
		} else if (type == 'knight') {
			return validateKnight(from, to);
		} else if (type == 'bishop') {
			return validateBishop(from, to);
		} else if (type == 'queen') {
			return validateQueen(from, to);	
		} else if (type == 'king') {
			return validateKing(colour, from, to);
		}
		return false;
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
	}
	
	/**
	 * Get occupant of given square
	 */
	function getOccupant(squareID) {
		return $('#'+ squareID).children('div.piece');
	}
});