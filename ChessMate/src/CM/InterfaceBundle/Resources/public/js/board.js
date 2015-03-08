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
		colour = getOpponentColour(colour);
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
		} else if (inCheckOnYAxis(colour, kingSquare)) {
			console.log('check on y-axis');
			return true;
		} else if (inCheckOnDiagonal(colour, kingSquare)) {
			console.log('check on diagonal');
			return true;
		}
		
		console.log('no check');	
		
		return false;
	}
	
	/**
	 * Check if in check on diagonal
	 */
	function inCheckOnDiagonal(colour, kingSquare) {
		var row = kingSquare[0];
		var col = kingSquare[1];
		var blocks = [false,false,false,false];
		for (var i = 1; i < 8; i++) {
			var threats = [getPieceAt(row+i, col-i), getPieceAt(row+i, col+i), getPieceAt(row-i, col-i), getPieceAt(row-i, col+i)];
			if ((!blocks[0] && (threats[0] == colour+'Bishop' || threats[0] == colour+'Queen'))
				|| (!blocks[1] && (threats[1] == colour+'Bishop' || threats[1] == colour+'Queen'))
				|| (!blocks[2] && (threats[2] == colour+'Bishop' || threats[2] == colour+'Queen'))
				|| (!blocks[3] && (threats[3] == colour+'Bishop' || threats[3] == colour+'Queen'))
				) {
				return true;
			}
			//get blocking pieces
			for (var j = 0; j < 4; j++) {
				if (!blocks[j]) {
					blocks[j] = threats[j];					
				}
			}
		}
		return false;
	}
	
	/**
	 * Check if in check on x-axis
	 */
	function inCheckOnXAxis(colour, kingSquare) {
		var row = kingSquare[0];
		console.log(row);
		for (var col = 0; col < 8; col++) {
			if (abstractBoard[row][col] == colour+'Rook' || abstractBoard[row][col] == colour+'Queen') {
				if ((col + 1) == kingSquare[1] || (col - 1) == kingSquare[1] || !xAxisBlocked(kingSquare[1], col, row)) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Check if in check on y-axis
	 */
	function inCheckOnYAxis(colour, kingSquare) {
		var col = kingSquare[1];
		for (var row = 0; row < 8; row++) {
			if (abstractBoard[row][col] == colour+'Rook' || abstractBoard[row][col] == colour+'Queen') {
				if ((row + 1) == kingSquare[0] || (row - 1) == kingSquare[0] || !yAxisBlocked(kingSquare[0], row, col)) {
					return true;
				}
			}
		}
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
	 * Get piece/false at given square
	 */
	function getPieceAt(row, column) {
		if (row > -1 && row < 8 && column > -1 && column < 8) {
			return abstractBoard[row][column];
		}
		return false;
	}
	
	/**
	 * Get opponent's colour
	 */
	function getOpponentColour(colour) {
		if (colour == 'w') {
			colour = 'b';
		} else {
			colour = 'w';			
		}
		return colour;
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
    		var epTaken = false;
    		if (enPassantPerformed) {
    			epTaken = abstractBoard[from[0]][to[1]];
    			abstractBoard[from[0]][to[1]] = false;
    		}
        	//update abstract board
    		updateAbstractBoard(from, to);
    		if (!castled) {
        		//if in check, invalidate move
    			if (inCheck(piece['colour'])) {
                	//revert board
            		updateAbstractBoard(to, from);
            		abstractBoard[to[0]][to[1]] = occupant;
            		if (epTaken) {
            			//revert En passant
                		abstractBoard[to[0]][to[1]] = epTaken;
            		}
            		//invalidate move
            		ui.draggable.addClass('invalid');
            		return false;
        		}
    		} else {
    			//check already checked
    			castled = false;
    		}
			//allow piece to be taken
    		if (!enPassantPerformed) {
    			checkTakePiece(to);
    			//check En passant time-out
    			if (enPassantAvailable) {
    				enPassantAvailable = false;
    			}  			
    		} else {
				takePiece(getGridRefFromAbstractIndices(from[0],to[1]));
				enPassantPerformed = false;
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
			    		console.log('from/nextSpace');
			    		console.log(from, nextSpace);
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
				return true;
			} else if (enPassantAvailable[0] == from[0] && enPassantAvailable[1] == to[1]) {
				//use En passant
				enPassantPerformed = true;
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