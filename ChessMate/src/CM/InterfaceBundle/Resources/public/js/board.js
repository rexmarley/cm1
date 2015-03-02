$(document).ready( function() {
	$('.piece').draggable({
        containment : '#board'
	});
	
	$('.square').droppable({
		accept: '.piece',
		drop: function (event, ui) {
			//centre piece
			$(this).append(ui.draggable.css('position','static')); //prevents visible re-move?
		}
    });
});