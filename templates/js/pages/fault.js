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
        success: function(json){
            var pgs = []
            for (var i = 0; i < json[0]; i++) pgs.push('<li'+(i+1==page?' class="selected"':'')+'><a href="#'+(i+1)+'">'+(i+1)+'</a></li>')
             
            $('.pages').html('<ul>'+pgs.join('')+'</ul>')
             
            //if (search)
            $('table.robot_actions tbody').empty()
            $.each(json[1], function(i,f) {
                $('table.robot_actions tbody').append(
                    '<tr>'+
                        '<td><a href="/fault/fid/'+f['FAULTID']+'">'+f['TITLE']+'</a></td>'+
                        '<td>'+f['STARTTIME']+'</td>'+
                        '<td><a href="/fault/bl/'+f['BEAMLINEID']+'">'+f['BEAMLINE']+'</a></td>'+
                        '<td><a href="/fault/sid/'+f['SYSTEMID']+'">'+f['SYSTEM']+'</td>'+
                        '<td><a href="/fault/cid/'+f['COMPONENTID']+'">'+f['COMPONENT']+'</td>'+
                        '<td><a href="/fault/scid/'+f['SUBCOMPONENTID']+'">'+f['SUBCOMPONENT']+'</td>'+
                        '<td>'+(f['RESOLVED'] ? (f['RESOLVED'] == 2 ? 'Partial' : 'Yes') : 'No')+'</td>'+
                        '<td>'+(f['BEAMTIMELOST'] ? ('Yes ('+f['LOST']+'h)') : 'No')+'</td>'+
                    '</tr>'
                )

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
