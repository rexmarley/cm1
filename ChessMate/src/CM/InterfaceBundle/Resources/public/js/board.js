$(document).ready( function() {	
	$.each($('.piece'), function() { 
		$(this).draggable();
	});
	
	$.each($('.square'), function() { 
		$(this).droppable();
	});
});