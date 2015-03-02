$(document).ready( function() {
	$('.piece').draggable({
        containment : '#board',
        revert: 'invalid'
	});
	
	$('.square').droppable({
		accept: '.piece',
		drop: validateMove
    });
	
	function validateMove(event, ui) {
		//get colour/direction (for pawns)
		var direction = 1;
		if (ui.draggable.attr('id').charAt(0) == 'b') {
			//black moves back
			direction = -1;
		}
		var data = {
			   	        pieceType: 'pawn',
						//colour: 'white',
						colour: direction,
				        fromSquare: ui.draggable.parent().attr('id'),
				        toSquare: this.id,
			        };
		
	    $.ajax({
	    	type: "POST",
	        url : "http://localhost/CM/ChessMate/web/app_dev.php/checkMove",
	        dataType : 'json',
	        data: data,
	        error : function(data, errorThrown) {
	            alert(errorThrown);
	        },
	        success : function(data) {
				//centre piece
	        	//console.log($(this));
				//$(this).append(ui.draggable.css('position','static')); //prevents visible re-move? 
				//TODO: disable on success?
	        }
	    });
	}
});