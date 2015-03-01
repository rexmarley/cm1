$(document).ready( function() {
	$('.piece').draggable({
        containment : "#board"
	});
	
	$('.square').droppable();
});