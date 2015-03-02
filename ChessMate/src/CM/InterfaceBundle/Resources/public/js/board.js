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
		var data = {
			   	        pieceType: 'pawn',
						//colour: 'white',
						colour: 1, //or -1 for black
				        fromSquare: 1,
				        toSquare: 2,
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
				$(this).append(ui.draggable.css('position','static')); //prevents visible re-move? 
				//TODO: disable on success
	        }
	    });
	}
});