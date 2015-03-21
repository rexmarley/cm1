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
	
	//ajax var for aborts
	posting = null;
    $("a#findGame").on('click', function() {
        //ajax form
    	ajaxNewGame(true);
        //change dialog
		$('#newGameOptions').dialog("close");
		//reset relax search
		$('a#relaxSearch').show();
		$('#findingGameDialog p').html('');
		$('#findingGameDialog').dialog("open");
		var loading = 0;
		setInterval(function() {
		    if(loading < 3) {
		        $('#findingGameDialog span').append('.');
		        loading++;
		    } else {
		        $('#findingGameDialog span').html('');
		        loading = 0;
		    }
		}, 600);
    });
	
	function ajaxNewGame(matchSearch) {
        //ajax form
        var form = $('#newGameForm'),
        	url = form.attr('action');
        var opponent = form.find('input[name="opponent"]:checked').val();
        if (opponent == 1) {
        	//human opponent
            if (matchSearch) {
                var skill = form.find('input[name="skill"]:checked').val(),
                	duration = form.find('input[name="duration"]:checked').val();
                posting = $.post(url, {'skill': skill, 'duration': duration });    
            } else {
            	posting = $.post(url, {'skill': null, 'duration': null });    
            }
            posting.done(function(data) {
                location.href = data['gameURL'];
            });	        	
        } else {
        	//computer opponent TODO
        }
	}
	
	$('a#cancelSearch').on('click', function() {
		posting.abort();
		$('#findingGameDialog').dialog("close");
	});
	
	$('a#relaxSearch').on('click', function() {
		//create new ajax call
		posting.abort();
    	ajaxNewGame(false);
		$(this).hide();
		$('#findingGameDialog').append('<center><p style="color:#0000ff;">Search relaxed</p></center>');
	});
	
	$('a#playComputer').on('click', function() {
		posting.abort();
		$('#findingGameDialog').dialog("close");
	});
});