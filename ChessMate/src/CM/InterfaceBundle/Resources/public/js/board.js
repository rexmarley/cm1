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
	
	//allow player to move
	var playersTurn = true;
	//turn interval
	var tInterval;
	
	/**
	 * Join game/check opponent has joined &
	 * wait for first move (also used for reloads)
	 */
	if (typeof activePlayer !== 'undefined') {
		//get game id
		var game = $('.board').attr('id').split('_');
		var root = 'https://'+document.location.hostname+'/CM/ChessMate/web/app_dev.php/game/';
		if (!inProgress) {
			//join game
	    	$.ajax({
	    		type: "POST",
	    		async: false,
	    		url: root + 'join/'+game[2]
	    	});
			//check opponent has joined
	    	$.ajax({
	    		type: "POST",
	    		async: false,
	    		url: root + 'checkJoined/'+game[2],
	    		success: function(data) {
	    			if (!data['joined']) {
	    				//game cancelled
	    				alert('Game aborted by opponent!');
	    				//back to start
	        			location.href = root + 'start';
	    			}    			
	    		}
	    	});
		}
    	//wait for first move
		if ((activePlayer === 0 && $('.board').attr('id').charAt(5) == 'b')
				|| (activePlayer === 1 && $('.board').attr('id').charAt(5) == 'w')) {
	    	playersTurn = false;
	    	$.ajax({
	    		type: "POST",
	    		url: root + 'getFirstMove',
	    		data: { 'gameID' : game[2] },
	    		success: function(data) {
	    			if (data['valid']) {
	    				performMoveByOpponent(data['board'], data['from'], data['to']);
	    		    	playersTurn = true;
	    		    	switchTimers();
	    			}
	    		}
	    	});
			//start opponent's timer
	    	$('div#timer1').addClass('red');
	    	startTimer('div#timer1');
	    } else {
			//start own timer
	    	$('div#timer2').addClass('red');
	    	startTimer('div#timer2');
		}
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
	
	//workaround for hidden overflow hiding draggable
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
		var num = 1;
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
		if ($('.board').attr('id').charAt(7) != 'x') {
	    	//ajax move & validate server-side
	    	//should only fail due to cheating --> display message and manually revert board
			//(TODO disable board on success?)
			ajaxMove(gFrom, [endRow, pawnCol], 'pawn', colour);
		}
	});
	//global var for swapping pawn
	var gFrom = [];

	/**
	 * Validate chess move
	 */
	function validateMove(event, ui) {
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
    			checkAndTakePiece(to, 'Won');
    		} else {
				takePiece(getGridRefFromAbstractIndices(from[0],to[1]), 'Won');
			}
			//check for pawn reaching opposing end
			if (piece['type'] == 'pawn' && (piece['colour'] == 'w' && to[0] == 7) || (piece['colour'] == 'b' && to[0] == 0)) {
				//ajax move on piece selection
				gFrom = from;
				openPieceChooser(piece['colour']);
			} else if ($('.board').attr('id').charAt(7) != 'x') {
	        	//ajax move & validate server-side
	        	//should only fail due to cheating --> display message and manually revert board
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
		//get game id
		var game = $('.board').attr('id').split('_');
		playersTurn = false;
		switchTimers();
    	$.ajax({
    		type: "POST",
    	    //async: false,
    		url: 'https://'+document.location.hostname+'/CM/ChessMate/web/app_dev.php/game/checkMove',
    		data: { 'gameID' : game[2],'from' : from, 'to' : to , 'type' : type, 'colour' : colour, 'newPiece' : newPiece },
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
    				performMoveByOpponent(data['board'], data['from'], data['to']);
    				playersTurn = true;
    				switchTimers();
    			}
    		}
    	});
	}
	
	/**
	 * Perform opponent's move
	 * @param board the updated abstractBoard
	 * @param from grid-ref.
	 * @param to grid-ref
	 */
	function performMoveByOpponent(board, from, to) {
		//update abstract board
		abstractBoard = board;
		//get opponent's valid move
		var gridFrom = getGridRefFromAbstractIndices(from[0], from[1]);
		var gridTo = getGridRefFromAbstractIndices(to[0], to[1]);
		//check for takeable piece
		checkAndTakePiece(to, 'Lost');
		//make move
		var moved = getOccupant(gridFrom);
		moved.position({
            of: 'div#'+gridTo
        });
		//center piece
		$('div#'+gridTo).append(moved.css('position','static'));	
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