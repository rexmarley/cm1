$(document).ready( function() {
	
	/**
	 * Change skill labels on selection of human/computer opponent
	 */
	$("input:radio[name='opponent']").on('change', function() {
		  if (this.value == 1) {
			  $('#skill1').html('Best Match');
			  $('#skill2').html('Lesser');
			  $('#skill3').html('Greater');
			  //hide options for guest players
			  if (!$('#skillLevel').hasClass('visible')) {
				  $('#skillLevel').addClass('hidden');
			  }
		  } else {
			  $('#skill1').html('Easy');
			  $('#skill2').html('Moderate');
			  $('#skill3').html('Difficult');
			  //show options for computer opponent
			  if ($('#skillLevel').hasClass('hidden')) {
				  $('#skillLevel').removeClass('hidden');
			  }
		  }
	});
});