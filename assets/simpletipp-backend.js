
window.addEvent('domready', function() {



    $$('div.pokal select').addEvent('change', function(){

        var selectedValues = new Array();
        $$('div.pokal select').each(function(select, index) {
            select.getSelected().each(function(element, index) {
                selectedValues.push(element.get("value"));
            });
        });
        selectedValues = selectedValues.unique();

        $$('div.pokal option').each(function(option, index) {
            if (!option.selected && selectedValues.indexOf(option.get('value')) != -1) {
                // disable option
                option.set('disabled', true);
            } else {
                // enable option
                option.set('disabled', false);
            }
        });


        /**/
    });


});
