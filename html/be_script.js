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

});
