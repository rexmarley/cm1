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
	
	/**
	 * Dialog settings e.g. piece-chooser
	 */
	$('.ui-dialog').dialog({
		 autoOpen: false,
		 open: function(event, ui) {
			 $(".ui-dialog-titlebar-close").hide();
		 },
		 show: {
			 effect: "blind",
			 duration: 1000
		 },
		 hide: {
			 effect: "explode",
			 duration: 1000
		 },
		 position: {
			 my: "center center",
			 at: "center center",
			 of: ".board"
		 },
		 modal: true,
	});
	$('#newGameOptions').dialog({
		 open: function(event, ui) {
			 $(".ui-dialog-titlebar-close").show();
		 },
	});
	
	$('a#startGame').on('click', function() {
		$('#newGameOptions').dialog("open");
	});
	
    $("a#findGame").on('click', function() {
        //ajax form
        var form = $('#newGameForm'),
        	url = form.attr('action'),
            opponent = form.find('input[name="opponent"]').val(),
            skill = form.find('input[name="skill"]').val(),
            duration = form.find('input[name="duration"]').val();
        var posting = $.post(url, {'opponent': opponent, 'skill': skill, 'duration': duration });

        posting.done(function(data) {
            window.location = data['gameURL'];
        });
    });
});