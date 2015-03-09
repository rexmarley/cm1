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
	 * Open piece-chooser dialog
	 */
	function openPieceChooser(colour) {
		$('#choosePiece_'+colour).dialog("open");		
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
	 * Validate king movement
	 * @param from	[y,x]
	 * @param to	[y,x]
	 */
	function validateKing(colour, from, to) {
		if (Math.abs(to[1] - from[1]) <= 1 && Math.abs(to[0] - from[0]) <= 1) {
			return true;
		} else if (unmoved[from[0]][from[1]] && to[0] == from[0] && !inCheck(colour)) {
			//handle castling
			if (to[1] == 2 || to[1] == 6) {
				var rookFromCol = 0;
				var start = 1;
				var end = 4;
				var rookToCol = 3;
				var rookToLetter = 'd';
				var rookNum = 1;
				if (to[1] == 6) {
					rookFromCol = 7;
					start = 5;
					end = 7;
					rookToCol = 5;
					rookToLetter = 'f';
					rookNum = 2;
				}
				//check castle is unmoved
				if (unmoved[from[0]][rookFromCol]) {
					//check intermittent points are vacant
					for (var i = start; i < end; i++) {
						if (!vacant(from[0], i)) {
							return false;
						}
						// if in check at intermittent points, return false
						var nextSpace = [from[0], i];
			    		updateAbstractBoard(from, nextSpace);
			    		if (inCheck(colour)) {
							//put king back in place
				    		updateAbstractBoard(nextSpace, from);
			    			return false;
			    		}
						//put king back in place
			    		updateAbstractBoard(nextSpace, from);
					}
					//move castle
					$('#'+rookToLetter+'_'+(to[0]+1)).append($('#'+colour+'_rook_'+rookNum));
		        	//update abstract board
		    		updateAbstractBoard([from[0], rookFromCol], [to[0], rookToCol]);
		    		//set rook as moved - not actually necessary
					unmoved[from[0]][rookFromCol] = false;
					//flag castled - prevent recheck of inCheck()
					castled = true;
					return true;
				}
			}
		}
		return false;
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