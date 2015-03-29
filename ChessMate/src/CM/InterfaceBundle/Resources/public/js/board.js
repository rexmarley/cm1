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
		drop: validateMoveOut,
    });
	
	//allow player to move
	var playersTurn = true;
	//timer interval
	var tInterval;
	
	/**
	 * Join game/check opponent has joined &
	 * wait for first move (also used for reloads)
	 */
	if (typeof activePlayer !== 'undefined') {
		//get game id
		var game = $('.board').attr('id').split('_');
		if (!inProgress) {
			//join game/check joined
			joinGame(game[2]);
		}
    	//if not players turn
		if ((activePlayer === 0 && $('.board').attr('id').charAt(5) == 'b')
				|| (activePlayer === 1 && $('.board').attr('id').charAt(5) == 'w')) {
	    	//wait for opponent's move
	    	playersTurn = false;
	    	var gm = setInterval(function() {
	    		//ensure game is joined
	    		if (inProgress) {
	    			getMove(game[2]);
	    			clearInterval(gm);
	    		}
	    	}, 1000);
			//start opponent's timer
	    	$('div#timer1').addClass('red');
	    	startTimer('div#timer1');
	    } else {
			//start own timer
	    	$('div#timer2').addClass('red');
	    	startTimer('div#timer2');
		}
    }
	
	//workaround for hidden overflow hiding draggable
	$('.square').mouseover(function() {
		$(this).removeClass('clipped');
	});
	$('.square').mouseleave(function() {
		$(this).addClass('clipped');
	});
	
	//global var for swapping pawn
	var gFrom = [];
	$('.choosablePiece').click(function() {
		swapPawn(this.id);
	});
	
	/**
	 * Join Game/check oppponent has joined
	 */
	function joinGame(gameID) {
    	$.ajax({
    		type: "POST",
    		url: root + 'join/'+gameID,
			success: function() {
				checkOpponentJoined(gameID);
			}
    	});		
	}
	
	/**
	 * Check oppponent has joined game
	 */
	function checkOpponentJoined(gameID) {
		//check opponent has joined
    	$.ajax({
    		type: "POST",
    		url: root + 'checkJoined/'+gameID,
    		success: function(data) {
    			if (!data['joined']) {
    				//game cancelled
    				alert('Game aborted by opponent!');
    				//back to start
        			location.href = root + 'start';
    			} else {
    				inProgress = true;
    			} 			
    		}
    	});		
	}
	
	/**
	 * Switch timers
	 */
	function switchTimers() {
		clearInterval(tInterval);
		if ($('div#timer1').hasClass('red')) {
	    	$('div#timer1').removeClass('red');
	    	$('div#timer2').addClass('red');
	    	startTimer('div#timer2');			
		} else {
	    	$('div#timer2').removeClass('red');
	    	$('div#timer1').addClass('red');
	    	startTimer('div#timer1');
		}
	}
	
	/**
	 * Start timer with given id
	 */
	function startTimer(timerID) {
		var timeLeft = $(timerID + ' h1');
		var time = timeLeft.html().split(':');
		tInterval = setInterval(function() {
			if (time[1] == '00') {
				if (time[0] == '0') {
					//end game
					clearInterval(tInterval);
				} else {
					time[1] = '59';
					time[0] = time[0] - 1;					
				}
			} else {
				time[1] = time[1] - 1;
				if (time[1] < 10) {
					time[1] = '0' + time[1];
				}
			}
			timeLeft.html(time[0]+':'+time[1]);
		}, 1000);
	}

	/**
	 * Validate move made by opponent
	 * If invalid, one of the players has cheated
	 */
	function validateMoveIn(piece, from, to, newPiece, enPassant, newBoard) {
		//check opponent's piece
		if (piece['colour'] == $('.board').attr('id').charAt(5)) {
			return false;			
		}
		//check if target is occupied by own piece
		if (occupiedByOwnPiece(to[0], to[1], piece['colour'])) {
			return false;
		}
		//validate move
		if (!validateMove(piece, from, to, 'Lost')) {
			return false;			
		}
		//ensure en passant is correct
		if (enPassant[0] != enPassantAvailable[0] || enPassant[1] != enPassantAvailable[1]) {
			return false;
		}
		//check swapped piece
		if (newPiece) {
			if (!pawnHasReachedOtherSide(piece['type'], piece['colour'], to[0])) {
				return false;	
			}
			//update abstract board
			abstractBoard[to[0]][to[1]] = newPiece;
		}
		//check boards match following updates
		for (var row = 0; row < 8; row++) {
			for (var col = 0; col < 8; col++) {
				if (abstractBoard[row][col] != newBoard[row][col]) {
					return false;
				}
			}
		}

    	return true;
	}
	
	/**
	 * Check opponent's move
	 * @param array from grid-ref.
	 * @param array to grid-ref
	 * @param bool|string swapped has pawn been swapped 
	 */
	function checkMoveByOpponent(from, to, swapped, enPassant, newBoard) {
		var gridFrom = getGridRefFromAbstractIndices(from[0], from[1]);
		var gridTo = getGridRefFromAbstractIndices(to[0], to[1]);
		//get moved piece
		var moved = getOccupant(gridFrom);
		//check piece exists & question move validity
		if (moved.length && validateMoveIn(getPieceDetails(moved.attr('id')), from, to, swapped, enPassant, newBoard)) {
			//save move
			saveMove();
			//make move
			moved.position({
	            of: 'div#'+gridTo
	        });
			//center piece
			$('div#'+gridTo).append(moved.css('position','static'));
			//perform pawn swap
			if (swapped) {
				//get new id
				var num = getNewPieceNumber(swapped);
				//change piece
				moved.html($('#pick_'+swapped).html());
				//set new id
				moved.attr('id', swapped+'_'+num);					
			}		
		} else {
			console.log('b');
			//validate server-side & find cheat
			findCheat();
		}
	}
	
	/**
	 * Get opponent's move
	 */
	function getMove(gameID) {
    	$.ajax({
    		type: "POST",
    		url: root + 'getMove',
    		data: { 'gameID' : gameID },
    		success: function(data) {
    			if (data['moved']) {
    				if (typeof data['cheat'] === 'undefined') {
        				checkMoveByOpponent(data['from'], data['to'], data['swapped'], data['enPassant'], data['newBoard']);
        			} else {
        				alert(data['cheat']);     				
        			}
    			} else {
    				getMove(gameID);
    			}
    		}
    	});		
	}
	
	/**
	 * Validate move made by player
	 */
	function validateMoveOut(event, ui) {
		//get moved piece
		var piece = getPieceDetails(ui.draggable.attr('id'));
		//check player's turn and piece (if actual game)
		if (!playersTurn || ($('.board').attr('id').charAt(7) != 'x' 
			&& piece['colour'] != $('.board').attr('id').charAt(5))) {
    		//invalidate move
    		ui.draggable.addClass('invalid');
			return false;			
		}
		//get abstract indices for from/to squares
		var from = getAbstractedSquareIndex(ui.draggable.parent().attr('id'));
		var to = getAbstractedSquareIndex(this.id);
		//validate move
		if (!validateMove(piece, from, to, 'Won')) {
    		//invalidate move
    		ui.draggable.addClass('invalid');
			return false;			
		}
		//check for pawn reaching opposing end
		if (pawnHasReachedOtherSide(piece['type'], piece['colour'], to[0])) {
			//ajax move on piece selection
			gFrom = from;
			openPieceChooser(piece['colour']);
		} else if ($('.board').attr('id').charAt(7) != 'x') {
        	//ajax move & validate server-side
        	//should only fail due to cheating --> display message and manually revert board
        	ajaxMove(from, to, piece['type'], piece['colour']);
		}
		//center piece
		$(this).append(ui.draggable.css('position','static'));

		return true;
	}
	
	/**
	 * Check for pawn reaching opposing end
	 * @param pieceType
	 * @param pieceColour
	 * @param toY
	 * @return Boolean
	 */
	function pawnHasReachedOtherSide(pieceType, pieceColour, toY) {
		if (pieceType == 'pawn' && ((pieceColour == 'w' && toY == 7) || (pieceColour == 'b' && toY == 0))) {
			return true;
		}
		return false;
	}

	/**
	 * Validate move
	 */
	function validateMove(piece, from, to, takenSide) {
		//check if target is occupied by own piece
		if (occupiedByOwnPiece(to[0], to[1], piece['colour'])) {
			return false;
		}		
		//validate move
    	var valid = validatePieceType(piece['type'], piece['colour'], from, to);
    	if(valid) {
    		//get target square occupant - in case of revert
    		var occupant = abstractBoard[to[0]][to[1]];
    		if (checkEnPassantPerformed(to)) {
				takePiece(getGridRefFromAbstractIndices(from[0],to[1]), takenSide);
    		} else {
            	//update abstract board
        		updateAbstractBoard(from, to);
	    		if (castled) {
	    			//check already checked
					moveCastle(to, piece['colour']);
	    		} else {
	        		//if in check, invalidate move
	    			if (inCheck(piece['colour'])) {
	                	//revert board
	            		updateAbstractBoard(to, from);
	            		abstractBoard[to[0]][to[1]] = occupant;
	            		//invalidate move
	            		return false;
	        		}
					//check for takeable piece
					checkAndTakePiece(to, takenSide);
	    		}
			}
			unmoved[from[0]][from[1]] = false;
    	}

    	return valid;
	}
	
	/**
	 * Move castle in accordance with castling.
	 * Validity must be pre-checked
	 * @param to [y,x]
	 * @param colour the piece's colour
	 */
	function moveCastle(to, colour) {
		to[0] = parseInt(to[0], '10')
		if (to[1] == 2) {
			$('#d_'+(to[0]+1)).append($('#'+colour+'_rook_'+to[0]+'0'));
		} else {
			$('#f_'+(to[0]+1)).append($('#'+colour+'_rook_'+to[0]+'7'));
		}
		castled = false;
	}

	/**
	 * Ajax move for opponent retrieval/validation
	 * & wait for opponent's move
	 * @param from [y,x]
	 * @param to [y,x]
	 */
	function findCheat() {
		//get game id
		var game = $('.board').attr('id').split('_');
    	$.ajax({
    		type: "POST",
    		url: root+'findCheat/'+game[2],
    		success: function(data) {
    			alert(data['cheat']);
    		}
    	});
	}
	
	/**
	 * Ajax move for opponent retrieval/validation
	 * & wait for opponent's move
	 * @param from [y,x]
	 * @param to [y,x]
	 */
	function ajaxMove(from, to) {
		//get game id
		var game = $('.board').attr('id').split('_');
    	$.ajax({
    		type: "POST",
    		url: root+'sendMove',
    		dataType: 'json',
    		contentType: 'application/json',
    		data: JSON.stringify({ 'gameID' : game[2], 'board' : abstractBoard, 'from' : from, 'to' : to , 'enPassant' : enPassantAvailable, 'newPiece' : newPiece }),
    		success: function(data) {
    			getMove(game[2]);
    		}
    	});
		playersTurn = false;
		switchTimers();
    	newPiece = false;
    	//enPassantAvailable = false; ?
	}
	
	/**
	 * Save opponent's move
	 * @param from [y,x]
	 * @param to [y,x]
	 * @param type the moved piece type
	 * @param colour the moved piece colour
	 */
	function saveMove() {
		//get game id
		var game = $('.board').attr('id').split('_');
    	//save move
    	$.ajax({
    		type: "POST",
    		url: root+'saveMove/'+game[2]
    	});
		playersTurn = true;
		switchTimers();
	}
	
	/**
	 * Get piece type/colour from id
	 */
	function getPieceDetails(pieceID) {
		var piece = pieceID.split('_');
		return {'colour':piece[0], 'type':piece[1]};
	}
	
	/**
	 * Open piece-chooser dialog
	 */
	function openPieceChooser(colour) {
		$('#choosePiece_'+colour).dialog("open");		
	}
	
	/**
	 * Get new piece number, for html id
	 * @param newPiece e.g. 'w_queen'
	 */
	function getNewPieceNumber(newPiece) {
		//get new id
		var num = 1;
		//check for conflict
		var conflict = $('#'+newPiece+'_'+num);
		while (conflict.length) {
			num++;
			conflict = $('#'+newPiece+'_'+num);
		}
		return num;
	}
	
	var newPiece = false;	//TODO: reset on valid move
	/**
	 * Swap pawn on selection
	 */
	function swapPawn(pieceID) {
		//get selected piece
		var piece = pieceID.split('_');
		var colour = piece[1];
		var type = piece[2];
		//get new piece & id
		newPiece = colour+'_'+type;
		var num = getNewPieceNumber(newPiece);
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
		pawn.html($('#'+pieceID).html());
		//set new id
		pawn.attr('id', newPiece+'_'+num);
		//close piece-chooser
		$('#choosePiece_'+colour).dialog("close");
		//ajax move if real game
		if ($('.board').attr('id').charAt(7) != 'x') {
	    	//validate server-side/get opponent's move
			ajaxMove(gFrom, [endRow, pawnCol], 'pawn', colour);
		}
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
	function checkAndTakePiece(square, wonOrLost) {
		if (!vacant(square[0],square[1])) {
			takePiece(getGridRefFromAbstractIndices(square[0],square[1]), wonOrLost);
		}
	}
	
	/**
	 * Remove piece, from given square, and move to side 
	 */
	function takePiece(toSquare, wonOrLost) {
		//get taken piece
		var taken = getOccupant(toSquare);
		//move off board
    	if ($('div#pieces'+wonOrLost+' div.piece').length == 0) {
    		$('div#pieces'+wonOrLost+' div.row:first div.col-md-2:first').append(taken);
    	} else {
    		var lastOccupied = $('div#pieces'+wonOrLost+' div.piece:last').parent();
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