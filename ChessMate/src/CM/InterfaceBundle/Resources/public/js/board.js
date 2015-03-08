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
	
	/**
	 * Check if king is in check
	 */
	function inCheck(colour) {
		var king = colour+'King';
		//get opponent colour
		if (colour == 'w') {
			colour = 'b';
		} else {
			colour = 'w';			
		}
		//get king's position
		var kingSquare = 0;
		for (var row = 0; row < 8; row++) {
			var col = $.inArray(king, abstractBoard[row]);
			if (col !== -1) {
				kingSquare = [row, col];
				break;
			}
		}
		//check in check by pawn
		if (inCheckByPawn(colour, kingSquare)) {
			console.log('check by pawn');	
			return true;
		} else if (inCheckByKnight(colour, kingSquare)) {
			console.log('check by knight');
			return true;
		} else if (inCheckOnXAxis(colour, kingSquare)) {
			console.log('check on x-axis');
			return true;
		}
		
		//y-axis
		
		//diagonal
		
		console.log('no check');	
		
		return false;
	}
	
	/**
	 * Check if king is in check by knight
	 */
	function inCheckByKnight(colour, kingSquare) {
		if (pieceAt(kingSquare[0]+2, kingSquare[1]-1, colour+'Knight')
			|| pieceAt(kingSquare[0]+2, kingSquare[1]+1, colour+'Knight')
			|| pieceAt(kingSquare[0]+1, kingSquare[1]-2, colour+'Knight')
			|| pieceAt(kingSquare[0]+1, kingSquare[1]+2, colour+'Knight')
			|| pieceAt(kingSquare[0]-1, kingSquare[1]-2, colour+'Knight')
			|| pieceAt(kingSquare[0]-1, kingSquare[1]+2, colour+'Knight')
			|| pieceAt(kingSquare[0]-2, kingSquare[1]-1, colour+'Knight')
			|| pieceAt(kingSquare[0]-2, kingSquare[1]+1, colour+'Knight')) {
			return true;
		}
		return false;
	}
	
	/**
	 * Check given piece is at given square
	 */
	function pieceAt(row, column, piece) {
		if (row > -1 && row < 8 && column > -1 && column < 8) {
			if (abstractBoard[row][column] == piece) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Check if king is in check by pawn
	 */
	function inCheckByPawn(colour, kingSquare) {
		var dir = 1;
		if (colour == 'w') {
			dir = -1;
		}
		if (pieceAt(kingSquare[0]+dir, kingSquare[1]-1, colour+'Pawn')
			|| pieceAt(kingSquare[0]+dir, kingSquare[1]+1, colour+'Pawn')) {
			return true;
		}
		return false;
	}
	
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
    		//if in check, invalidate move
    		if (inCheck(piece['colour'])) {
            	//revert board
        		updateAbstractBoard(to, from);
        		abstractBoard[to[0]][to[1]] = occupant;
        		//invalidate move
        		ui.draggable.addClass('invalid');
        		return false;
    		}
    		//else
			if (enPassant && enPassant !== to) {
				//time-out En passant
				enPassant = false;
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
	 * Validate rook movement
	 * @param from	[y,x]
	 * @param to	[y,x]
	 */
	function validateRook(from, to) {
		if ((from[0] == to[0] && !xAxisBlocked(from[1], to[1], from[0])) 
			|| (from[1] == to[1] && !yAxisBlocked(from[0], to[0], from[1]))) {
			//allow piece to be taken
			checkTakePiece(to);
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
			checkTakePiece(to);
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
			checkTakePiece(to);
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
		if ((from[0] == to[0] && !xAxisBlocked(from[1], to[1], from[0])) 
			|| (from[1] == to[1] && !yAxisBlocked(from[0], to[0], from[1])) 
			|| (onDiagonal(from, to) && !diagonalBlocked(from[1], from[0], to[1], to[0]))) {
			//allow piece to be taken
			checkTakePiece(to);
			return true;
		}	
		return false;
	}
	
	/**
	 * Validate king movement
	 * @param from	[y,x]
	 * @param to	[y,x]
	 */
	function validateKing(colour, from, to) {
		//TODO moving into check
		if (Math.abs(to[1] - from[1]) <= 1 && Math.abs(to[0] - from[0]) <= 1) {
			//allow piece to be taken
			checkTakePiece(to);
			return true;
		} else if (unmoved[from[0]][from[1]] && to[0] == from[0]) {
			//handle castling
			if (to[1] == 6 && unmoved[from[0]][7]
				&& vacant(from[0], 5) && vacant(from[0], 6)) {
				//move castle
				$('#f_'+(to[0]+1)).append($('#'+colour+'_rook_2'));
	        	//update abstract board
	    		updateAbstractBoard([from[0], 7], [to[0], 5]);
	    		//set rook as moved - not actually necessary
				unmoved[from[0]][7] = false;
				//allow short castle
				return true;
			} else if (to[1] == 2 && unmoved[from[0]][0] && vacant(from[0], 1)
				&& vacant(from[0], 3) && vacant(from[0], 3)) {
				//move castle
				$('#d_'+(to[0]+1)).append($('#'+colour+'_rook_1'));
	        	//update abstract board
	    		updateAbstractBoard([from[0], 0], [to[0], 3]);
	    		//set rook as moved - not actually necessary
				unmoved[from[0]][0] = false;
				//allow long castle
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Validate pawn movement
	 * @param from	[y,x]
	 * @param to	[y,x]
	 */
	function validatePawn(colour, from, to) {
		var spaces = 1;
		if (unmoved[from[0]][from[1]]) {
			//allow initial movement of 2 spaces
			spaces = 2;
		}
		//allow moving forward
		var dir = to[0] - from[0];
		var move = Math.abs(dir);
		if (vacant(to[0], to[1]) && from[1] == to[1] && move <= spaces) {
    		if ((colour == 'w' && to[0] > from[0]) || (colour == 'b' && to[0] < from[0])) {
				checkApplyEnPassant(move, to, colour);
				return true;
    		}
		} else if (onDiagonal(from, to) 
			&& ((colour == 'w' && dir == 1) || colour == 'b' && dir == -1))  {
			if (!vacant(to[0], to[1])) {
				//occupied by own already checked --> allow take
				takePiece(getGridRefFromAbstractIndices(to[0],to[1]));
				return true;
			} else if (enPassant[0] == from[0] && enPassant[1] == to[1]) {
				//use En passant
				takePiece(getGridRefFromAbstractIndices(from[0],to[1]));
				return true;
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
	 */
	function checkTakePiece(square) {
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