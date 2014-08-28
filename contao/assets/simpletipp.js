$(document).ready(function() {

    $matchRows = $('table.matches tr');

    $matchRows.click(function(event) {
        $('input', this).focus();
    });

    $('table.matches td.tipp input').focus(function() {
        $('table.matches tr').removeClass('active');
        $(this.parentElement.parentElement).addClass('active');

        $('table.matches tr').removeClass('active');

        var $row = $(this.parentElement.parentElement);
        $row.addClass('active');

        $('table.ranking tr').removeClass('active');
        $('td.logos i', $row).each(function() {

            console.log(this.className);

            $('table.ranking tr.' + this.className).addClass('active');
        });
    });



    $('table.matches td.matchgroup').add('table.matches td.deadline').click(function(event) {
        var id = this.parentElement.id.split("_match_")[1];
    });

    $(".tipp_control span").click(function(event) {
	   var row = this.parentElement.parentElement.parentElement;
	   var input = $('input.tipp', row);
	   var value_ori = $('input.original', row).attr('value');

	   if (this.className.indexOf('reset') != -1) {
		   input.attr('value', value_ori);
		   return true;
	   }

	   var arr = input.attr('value').split(":");
	   
	   if(arr.length != 2) {
		   arr = value_ori.split(":");
	   }

	   if(arr.length != 2 || isNaN(parseInt(arr[0]))|| isNaN(parseInt(arr[1]))) {
		   arr = new Array(0, 0); 
		   input.attr('value', arr.join(':'));
		   return true;
	   }
	   
	   
	   maximum = (this.className.indexOf('max') !=-1) ? 5 : 999;
	   index   = (this.className.indexOf('home') != -1) ? 0 : 1;
	   new_val = parseInt(arr[index]) + ((this.className.indexOf('up') != -1) ? 1 : -1);
	   arr[index] = (new_val > 0 && new_val <= maximum) ? new_val : 0;

	   input.attr('value', arr.join(':'));
   });
 });
