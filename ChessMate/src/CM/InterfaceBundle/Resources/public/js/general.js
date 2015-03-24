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
	
	$('.closeable').dialog({
		 open: function(event, ui) {
			 $(".ui-dialog-titlebar-close").show();
		 },
	});
	
	$('a#startGame').on('click', function() {
		$('#newGameOptions').dialog("open");
	});
	
	$('a#showCurrentGames').on('click', function() {
		$('#currentGamesDialog').dialog("open");
	});
	
    $("a#findGame").on('click', function() {
        if ($('#newSearchForm').find('input[name="opponent"]:checked').val() == 1) {
        	//human opponent
            //change dialog
    		$('#newGameOptions').dialog("close");
    		$('#findingGameDialog').dialog("open");
    		//reset relax search
    		$('a#relaxSearch').show();
    		$('#findingGameDialog p').html('');
	    	createSearch(true);
	    } else {
	        //computer opponent TODO
	    }
    });

    function createSearch(match) {
        //ajax form
        var form = $('#newSearchForm'),
        	url = form.attr('action');
        if (match) {
            var skill = form.find('input[name="skill"]:checked').val(),
            	duration = form.find('input[name="duration"]:checked').val();
            var search = $.post(url, {'skill': skill, 'duration': duration });    
        } else {
        	var search = $.post(url, {'skill': null, 'duration': null });    
        }
        search.done(function(data) {
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
    		//get search id
    		searchID = data['searchID'];
    		//get search id
    		searchID = data['searchID'];
    		//add to cancel
    		$('a#cancelSearch').attr('href', $('a#cancelSearch').attr('href') + '/' + searchID);
    		//wait for search to be matched
    		checkSearchMatched(searchID);
        });
	}
    
    matchSearch = null;
	function checkSearchMatched(searchID) {
		var url = 'https://'+document.location.hostname+'/CM/ChessMate/web/app_dev.php/game/matchSearch/'+searchID;
    	matchSearch = $.post(url);
		matchSearch.done(function(data) {
    		if(data['matched']) {
    			//load game
    			location.href = data['gameURL'];    			
    		} else {
    			//retry
    			checkSearchMatched(searchID);
    		}
        });
	}
	
	function cancelSearch() {
		var i = setInterval(function() {
			if(matchSearch) {
				matchSearch.abort();
				var url = $('a#cancelSearch').attr('href');
		        var cancel = $.post(url);
			    matchSearch = null;
			    //reset url
			    url = $('a#cancelSearch').attr('href');
			    split = url.split('/');
			    var idLength = url[url.length - 1].length + 1;
				$('a#cancelSearch').attr('href', url.substring(0, url.length - idLength - 1));
				clearInterval(i);
		    }
		}, 1000);
	}
	
	$('a#cancelSearch').on('click', function(e) {
		e.preventDefault();
		cancelSearch();
		$('#findingGameDialog').dialog("close");
	});
	
	$('a#relaxSearch').on('click', function() {
		cancelSearch();
		//create new ajax call
		createSearch(false);
		$(this).hide();
		$('#findingGameDialog').append('<center><p style="color:#0000ff;">Search relaxed</p></center>');
	});
	
	$('a#playComputer').on('click', function() {
		//cancel first
		cancelSearch();
		$('#findingGameDialog').dialog("close");
	});
});