window.addEvent('domready', function() {
	el = $('opt_all_members_0');
	
	if (el) {
		el.addEvent('change', function(event) {
			$('ctrl_participants').getElements('input[type=checkbox]').each(function(inp,index) {
				inp.disabled = el.checked;
			});
		});

		$('ctrl_participants').getElements('input[type=checkbox]').each(function(inp, index) {
			inp.disabled = el.checked;
		});
	}
	
	var el_matches = $('ctrl_matches');
	
	if (el_matches) {
		el_matches.getElements('span.matchgroup').addEvent('click', function(event) {
			event.stop();
			
			var cssClass   = this.parentElement.className.split(' ')[1];
			var checkValue = $($(this).getParent().getParent().get('for')).checked;
			
			el_matches.getElements('span.' + cssClass).each(function(span, index) {
				checkbox = $(span.getParent().get('for'));
				checkbox.checked = !checkValue;
			});
		});
	}


});
