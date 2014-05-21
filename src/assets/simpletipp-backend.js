window.addEvent('domready', function() {

    new Element('div',{id : 'pokal_groups'}).inject($$('div.pokal_ranges')[0]);
    simpletipp_update_pokal();

    $$('div.pokal_ranges select').addEvent('change', function(){
        simpletipp_update_pokal();
    });
});

var TMPL_pokal_groups = '<table><tr><td>Gruppenphase: </td><td>%s</td></tr><tr><td>1. Runde: </td><td>%s</td></tr><tr><td>Achtelfinale: </td><td>%s</td></tr><tr><td>Viertelfinale: </td><td>%s</td></tr><tr><td>Halbfinale: </td><td>%s</td></tr><tr><td>Finale: </td><td>%s</td></tr></table>';

function simpletipp_update_pokal() {
    var s = new Array('', '', '', '', '', '');
    var e = new Array('', '', '', '', '', '');
    var i = 0;
    var p = true;
    var previousItem;
    $$('div.pokal_ranges option').each(function(option, index) {

        if (option.selected && p) {
            s[i] = option.get('value');
            p    = false;
        } else if (!p && !option.selected) {
            e[i] = previousItem.get('value');
            p = true;
            i++;
        }
        if (option.selected) {
            previousItem = option;
        }
    });

    $('pokal_groups').set('html', TMPL_pokal_groups.sprintf(
        s[0] + ' - ' + e[0], s[1] + ' - ' + e[1], s[2] + ' - ' + e[1],
        s[3] + ' - ' + e[3], s[4] + ' - ' + e[4], s[5] + ' - ' + e[5]
    ));
}
