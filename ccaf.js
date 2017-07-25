$('.datepicker').pickadate({
    format: 'yyyy-mm-dd',
    formatSubmit: 'yyyy-mm-dd',
    hiddenName: true,
    selectYears: true,
    selectMonths: true
});

$( document ).ready(function() {
  $(".printeddate").html(function() {
    var tempdate = $( this ).html();
    if(tempdate != "") {
      return moment(tempdate).format('DD/MM/YYYY');
    }
  });
});
