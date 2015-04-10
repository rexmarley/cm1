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
        },
		helper: "clone",
		appendTo: ".board",
	    start: function(event, ui) {
	    	//remove any highlight
	    	$(this).closest('div.square').stop(true,true);
			selectedPiece = null;
	        return $(event.target).fadeTo(0, 0);
	    },
	    stop: function(event, ui) {
	        return $(event.target).fadeTo(0, 1);
	    },
	});

	/**
	 * Make squares droppable
	 */
	$('.square').droppable({
		accept: '.piece',
		drop: validateMoveOut,
    });
	
	//highlight own pieces on click
	$('.ui-draggable').on('click', function(e) {
		//check player's turn and piece (if actual game)
		if (!gameOver && playersTurn) {
			if (selectedPiece) {
				if (selectedPiece.id != $(this).id) {
					selectedPiece.closest('div.square').stop(true,true);
					if (this.id.charAt(0) == selectedPiece.id.charAt(0)) {
						//reselect piece
						selectedPiece = $(this);
						$(this).closest('div.square').effect("highlight", 50000);
					} else {
						//attempt take
						validatePointAndClick(selectedPiece, selectedPiece.closest('div.square').attr('id'), $(this).closest('div.square').attr('id'));
						selectedPiece = null;			
					}
				}
			} else if ($('.board').attr('id').charAt(7) == 'x' || this.id.charAt(0) == $('.board').attr('id').charAt(5)) {
				//select own piece
				selectedPiece = $(this);
				selectedPiece.closest('div.square').effect("highlight", 50000);				
			}
		}
	});
	//handle point and click movement
	$('.square').on('click', function(e) {
		if (selectedPiece && selectedPiece.hasClass('piece') && selectedPiece.closest('div.square').attr('id') != this.id) {
			selectedPiece.closest('div.square').stop(true,true);
			validatePointAndClick(selectedPiece, selectedPiece.closest('div.square').attr('id'), this.id);
			selectedPiece = null;
		}
	});
	
	//override position of loading dialog
	$('#joiningGameDialog').dialog({
		 position: {
			 my: "center center",
			 at: "center center",
			 of: ".container-fluid"
		 },
	});
	
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
		} else {
			performOnLoadActions(game[2]);
		}
    }
	
	$('.choosablePiece').click(function() {
		swapPawn(this.id);
	});
	
	$('#resign').click(function(e) {
		e.preventDefault();
		if (!gameOver) {
			$.post($(this).attr('href'));	
		}	
	});
	
	$('#offerDraw').click(function(e) {
		e.preventDefault();
		if (!gameOver) {
			$.post($(this).attr('href'));	
		}
	});
	
	$('#acceptDraw').click(function(e) {
		e.preventDefault();
		if (!gameOver) {
			acceptDraw($(this).attr('href'));
		}
	});
	
	//toggle chat
	$('a#toggleChat').on('click', function(e) {
		e.preventDefault();
		var chat = $('div#chatBox');
		if (chat.hasClass('hidden')) {
			chat.removeClass('hidden');
			$(this).html('Disable Chat');
		} else {
			chat.addClass('hidden');
			$(this).html('Enable Chat');
		}
		var url = $(this).attr('href');
		$.ajax({
			type: "POST",
			url: url
		});		
	});

	//listener for sent chat
	$('form#chatSend').on('submit', function(e) {
		e.preventDefault();
		var msg = $('input#chatMsg').val().trim();
		var url = $(this).attr('action');
		$.ajax({
			type: "POST",
			url: url,
			data: {'msg': '<span class="purple">'+msg+'</span><br>'}
		});
		lastChatSeen++;
		//get username
		var user = $('span#pName2').html().split(':');
		//add to own window
		$('div#chatLog').append('<label>'+user[0]+': &nbsp;</label><span class="blue">'+msg+'</span><br>');
		//always scroll to bottom
		var scroll = $('div#chatDisplay');
		scroll.scrollTop(scroll.scrollTop()+300);
		$('input#chatMsg').val('');
	});
});
//var for point & click
var selectedPiece = null;
//allow player to move
var playersTurn = true;
//timer interval
var tInterval;
//global for swapping pawn
var gFrom = [];
var lastChatSeen = 0;

/**
 * Join Game/check oppponent has joined
 */
function joinGame(gameID) {
	//show loading dialog
	$('#joiningGameDialog').dialog("open");
	var loading = 0;
	var joining = setInterval(function() {
	    if(loading < 3) {
	        $('#joiningGameDialog span').append('.');
	        loading++;
	    } else {
	        $('#joiningGameDialog span').html('');
	        loading = 0;
	    }
	}, 600);
	
	$.ajax({
		type: "POST",
		url: root + 'join/'+gameID,
		success: function(data) {
			//close loading dialog
			clearInterval(joining);
			$('#joiningGameDialog').dialog("close");
			if (!data['joined']) {
				//game cancelled
				alert('Game aborted by opponent!');
				//back to start
    			location.href = root + 'start';
			} else {
				performOnLoadActions(gameID);
				inProgress = true;
			} 	
		}
	});		
}

/**
 * Game initialisation for load/reload
 */
function performOnLoadActions(gameID) {
	//if not players turn
	if ((activePlayer === 0 && $('.board').attr('id').charAt(5) == 'b')
			|| (activePlayer === 1 && $('.board').attr('id').charAt(5) == 'w')) {
    	playersTurn = false;
		//start opponent's timer
    	$('#tLeft1').addClass('red');
    	setTimeout(function(){
	    	startTimer('#tLeft1');
    	}, 1000);
    } else {
		//start own timer
    	$('#tLeft2').addClass('red');
    	startTimer('#tLeft2');
	}
	//open general listener
	listen(gameID);
}

/**
 * Switch timers
 */
function switchTimers() {
	clearInterval(tInterval);
	if ($('#tLeft1').hasClass('red')) {
    	$('#tLeft1').removeClass('red');
    	setTimeout(function(){
	    	startTimer('#tLeft2');	
    	}, 1500);		
	} else {
    	$('#tLeft2').removeClass('red');
    	setTimeout(function(){
	    	startTimer('#tLeft1');
    	}, 1500);
	}
}

/**
 * Start timer with given id
 */
function startTimer(timerID) {
	var timeLeft = $(timerID);
	timeLeft.addClass('red');
	var time = timeLeft.text().split(':');
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
		timeLeft.text(time[0]+':'+time[1]);
	}, 1000);
}

/**
 * Stop timer with given id
 */
function stopTimer(timerID) {
	clearInterval(tInterval);
	if ($(timerID).hasClass('red')) {
    	$(timerID).removeClass('red');	
	}		
}

/**
 * Validate move made by player
 */
function validateMoveOut(event, ui) {
	//get moved piece
	var piece = getPieceDetails(ui.draggable.attr('id'));
	//check player's turn and piece (if actual game)
	if (!checkPieceAndTurnForPlayer(piece['colour'])) {
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
	} else if ($('.board').attr('id').charAt(7) != 'x' && !gameOver) {
    	//ajax move & confirm validity
		sendMove(from, to, piece['colour']);
	}
	//center piece
	$(this).append(ui.draggable.css('position','static'));

	return true;
}

/**
 * Validate/perform point & click move
 * @param array from grid-ref.
 * @param array to grid-ref
 * @param bool 
 */
function validatePointAndClick(moved, gridFrom, gridTo) {
	var piece = getPieceDetails(moved.attr('id'));
	//check player's turn and piece (if actual game)
	if (!checkPieceAndTurnForPlayer(piece['colour'])) {
		//invalidate move
		return false;			
	}
	//get abstract indices for from/to squares
	var from = getAbstractedSquareIndex(gridFrom);
	var to = getAbstractedSquareIndex(gridTo);
	//validate move
	if (!validateMove(piece, from, to, 'Won')) {
		return false;			
	}
	//check for pawn reaching opposing end
	if (pawnHasReachedOtherSide(piece['type'], piece['colour'], to[0])) {
		//ajax move on piece selection
		gFrom = from;
		openPieceChooser(piece['colour']);
	} else if ($('.board').attr('id').charAt(7) != 'x' && !gameOver) {
    	//ajax move & confirm validity
		sendMove(from, to, piece['colour']);
	}
	//make move
	moved.position({
        of: 'div#'+gridTo
    });
	//center piece
	$('div#'+gridTo).append(moved.css('position','static'));
	
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
	var piece = getPieceDetails(moved.attr('id'));
	//check piece exists & question move validity
	if (moved.length && validateMoveIn(piece, from, to, swapped, enPassant, newBoard)) {
		//check if opponent's move ended game
		var over = checkGameOver(piece['colour']);		
		//save move
		saveMove(over);
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
		//validate server-side & find cheat
		findCheat();
	}
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
		if (checkEnPassantPerformed(to)) {
			takePiece(getGridRefFromAbstractIndices(from[0],to[1]), takenSide);
		} else {
    		//get target square occupant - in case of revert
    		var occupant = abstractBoard[to[0]][to[1]];
        	//update abstract board
    		updateAbstractBoard(from, to);
    		if (castled) {
    			//check already checked
				moveCastle(to, piece['colour']);
    		} else {
        		//if in check, invalidate move
        		//get king's position
        		var kingSquare = getKingSquare(piece['colour']);
    			if (inCheck(getOpponentColour(piece['colour']), kingSquare)) {
            		//highlight king briefly
            		var king = getOccupant(getGridRefFromAbstractIndices(kingSquare[0], kingSquare[1]));
            		var highlight = setInterval(function() {
                		king.effect("highlight", {color:"#ff3333"}, 250);
	            	}, 500);
            		setTimeout(function() {
            			clearInterval(highlight);
	            	}, 2500);
                	//revert board
            		updateAbstractBoard(to, from);
            		abstractBoard[to[0]][to[1]] = occupant;
            		//invalidate move
            		return false;
        		}
    			if (occupant) {
					//take piece
					takePiece(getGridRefFromAbstractIndices(to[0],to[1]), takenSide);	    				
    			}
    		}
		}
		unmoved[from[0]][from[1]] = false;
	}

	return valid;
}

/**
 * Check player's turn and piece (if actual game)
 * @param colour
 * @returns
 */
function checkPieceAndTurnForPlayer(colour) {
	//check player's turn and piece (if actual game)
	if (gameOver || !playersTurn || ($('.board').attr('id').charAt(7) != 'x' 
		&& colour != $('.board').attr('id').charAt(5))) {
		return false;			
	}
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
 * Find cheat, if validity consensus differs
 */
function findCheat() {
	//get game id
	var game = $('.board').attr('id').split('_');
	$.ajax({
		type: "POST",
		url: root+'findCheat/'+game[2],
		success: function(data) {
			//re-open listener
			listen(game[2]);
		}
	});
}

/**
 * Accept draw
 */
function acceptDraw(url) {
	$.ajax({
		type: "POST",
		url: url,
		success: function(data) {
			gameOver = true;
			updateRatings(data['pRating'], data['opRating']);
			alert("Game Over: Draw Accepted");
		}
	});
	$('#drawOffered').addClass('hidden');
}

/**
 * Ajax move for opponent retrieval/validation
 * & wait for opponent's move
 * @param array from [y,x]
 * @param array to [y,x]
 * @param char 	colour
 */
function sendMove(from, to, colour) {
	//check if move ended game
	var over = false;
	var message = '';
	if (!newPiece) {
		over = checkGameOver(colour);
		if (over !== false) {
			message = 'Game Over: ';
			if (over == 1) {
				message = message + 'Drawn';
			} else if (over == 2) {
				message = message + 'Stalemate';
			} else if (over == 3) {
				message = message + 'Checkmate';				
			}
		}
	}
	//get game id
	var game = $('.board').attr('id').split('_');
	var data = { 
		'gameID' : game[2],
		'board' : abstractBoard, 
		'from' : from, 
		'to' : to , 
		'enPassant' : enPassantAvailable, 
		'newPiece' : newPiece,
		'gameOver' : over
	};
	$.ajax({
		type: "POST",
		url: root+'sendMove',
		dataType: 'json',
		contentType: 'application/json',
		data: JSON.stringify(data),
		success: function(data) {
			if (data['gameOver']) {
				//update ratings cosmetically
				gameOver = true;
				updateRatings(data['pRating'], data['opRating']);
				alert(message);
			}
		}
	});
	playersTurn = false;
	newPiece = false;
	switchTimers();
}

/**
 * Save opponent's move
 * @param bool|int over 1=drawn, 2=stalemate, 3=checkmate
 */
function saveMove(over) {
	//get game id
	var game = $('.board').attr('id').split('_');
	//save move
	$.ajax({
		type: "POST",
		url: root+'saveMove/'+game[2],
		dataType: 'json',
		contentType: 'application/json',
		data: JSON.stringify({'gameOver': over}),
		success: function(data) {
			if (data['gameOver']) {
				gameOver = true;
				updateRatings(data['gameOver']['pRating'], data['gameOver']['opRating']);
				alert(data['gameOver']['overMsg']);
			}
			//re-open listener
			listen(game[2]);
		}
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

var newPiece = false;
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
	if ($('.board').attr('id').charAt(7) != 'x'&& !gameOver) {
    	//send move for validation
		sendMove(gFrom, [endRow, pawnCol], colour);
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
	var oldID = taken.attr('id').split('_');
	var newID = ' div#'+oldID[0]+'_'+oldID[1]+'_t';
	taken.remove();
	if ($(newID+' sub.subscript').length) {
		var newT = $('div#pieces'+wonOrLost+newID);
		if (newT.hasClass('hidden')) {
			newT.removeClass('hidden');    		
		} else if ($(newID+' sub.subscript:first').html().trim() != '') {
			//increment count
			$(newID+' sub.subscript:first').html(parseInt($(newID+' sub.subscript:first').html(), 10)+1)
		} else {
			$(newID+' sub.subscript:first').html(2);
		}
	}
}

/**
 * Get occupant of given square
 */
function getOccupant(squareID) {
	return $('#'+ squareID).children('div.piece');
}

/**
 * Long poll
 * @param gameID
 * @param gameOverReceived
 */
function listen(gameID) {
	$.ajax({
		type: "POST",
		url: root+'listen',
		dataType: 'json',
		contentType: 'application/json',
		data: JSON.stringify({'gameID': gameID, 'opChatty': opChatty, 'lastChat': lastChatSeen, 'overReceived': gameOver}),
		success: function(data) {
			if (data['change']) {
				//display any chat messages
				handleChat(data['chat']);
				//check for game over or new move
				if (data['moved']) {
	    			checkMoveByOpponent(data['from'], data['to'], data['swapped'], data['enPassant'], data['newBoard']);
				} else {
					if (data['gameOver']) {
						gameOver = true;
						updateRatings(data['pRating'], data['opRating']);
						alert(data['overMsg']);	
					} else if (data['drawOffered']) {
						//show draw offered options
						$('#drawOffered').removeClass('hidden');
			    		//hide in 10 seconds if not accepted
				    	setTimeout(function(){
				    		if (!$('#drawOffered').hasClass('hidden')) {
								$('#drawOffered').addClass('hidden');					    			
				    		}
				    	}, 10000);
					}
			    	listen(gameID);					
				}
			} else {
		    	listen(gameID);					
			}
		}
	});	
}

/**
 * Update displayed player ratings
 * @param pRating
 * @param opRating
 */
function updateRatings(pRating, opRating) {
	$('label#rating1').html('('+opRating+')');
	$('label#rating2').html('('+pRating+')');
	//stop timers
	clearInterval(tInterval);
}

/**
 * Handle chat messages/toggle
 * @param data ajax response
 */
function handleChat(chat) {
	lastChatSeen = chat['msgs'][0];
	for(var i = 0; i < chat['msgs'][1].length; i++) {
		$('div#chatLog').append(chat['msgs'][1][i].trim());
	}
	//always scroll to bottom
	var scroll = $('div#chatDisplay');
	scroll.scrollTop(scroll.scrollTop()+300);
	//check for chat toggled
	if (chat['toggled']) {
		opChatty = !opChatty;
	}
}