$(function() {
  var search = ''; // search string
  var page = 1
  
  
  _get_faults()
  
  // Search as you type
  var thread = null;
  $('input[name=search]').keyup(function() {
      clearTimeout(thread);
      $this = $(this);
      thread = setTimeout(function() {
            page = 1
            search = $this.val()
            _get_faults();
      }, 500);
  });  
  
  // Return list of faults
  function _get_faults() {
      $.ajax({
        url: '/fault/ajax' + (search ? '/s/'+search : ''),
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(faults){
            $.each(faults, function(i,f) {

            })
        }
      })     
  
  }
  
  
  // Return list of beamlines
  function _get_beamlines() {
      $.ajax({
        url: '/fault/ajax/bl',
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(bls){
            $('select[name=beamline]').empty()
            $.each(bls, function(i,b) {
                $('select[name=beamline]').append('<option value='+b['BEAMLINEID']+'>'+b['NAME']+'</option>')
            })
        }
      })   
  }
  
});
