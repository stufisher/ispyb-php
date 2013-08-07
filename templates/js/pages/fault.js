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
      var bl = $('select[name=beamline]').val()
      var sys = $('select[name=system]').val()
      var com = $('select[name=component]').val()
      var scom = $('select[name=subcomponent]').val()
  
      $.ajax({
        url: '/fault/ajax' + (search ? '/s/'+search : '') + (bl ? '/bl/'+bl : '') + (sys ? '/sid/'+sys : '') + (com ? '/cid/'+com : '') + (scom ? '/scid/'+scom : ''),
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
                        '<td><a href="/fault/bl/'+f['BEAMLINE']+'">'+f['BEAMLINE']+'</a></td>'+
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
        url: '/fault/ajax/bls',
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(bls){
            $('select[name=beamline]').html('<option value="">-</option>')
            $.each(bls, function(i,b) {
                $('select[name=beamline]').append('<option value='+b['NAME']+'>'+b['NAME']+'</option>')
            })
        }
      })   
  }

  $('select[name=beamline]').change(function() {
    refresh_systems()
    _get_faults()
  })  
  
  _get_beamlines()
  
  // Refresh system list based on beamline
  function refresh_systems() {
      var bl = $('select[name=beamline]').val()
      var last = $('select[name=system]').val()

      $.ajax({
        url: '/fault/ajax/sys/' + (bl ? 'bl/'+bl : ''),
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(systems){
            $('select[name=system]').html('<option value="">-</option>')
            $.each(systems, function(i,s) {
                $('select[name=system]').append('<option value='+s['SYSTEMID']+'>'+s['NAME']+'</option>')
            })
             
            if (last) $('select[name=system]').val(last)
        }
      })
  }
  
  $('select[name=system]').change(function() {
    refresh_components()
    _get_faults()
  })
  
  refresh_systems()

  
  // Refresh component list based on beamline and system
  function refresh_components() {
      var bl = $('select[name=beamline]').val()
      var sys = $('select[name=system]').val()
      var last = $('select[name=component]').val()
  
      if (!sys) {
        $('select[name=component]').empty()
        return
      }
  
      $.ajax({
        url: '/fault/ajax/com/sid/'+sys + (bl ? '/bl/'+bl : ''),
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(components){
            $('select[name=component]').html('<option value="">-</option>')
            $.each(components, function(i,c) {
                $('select[name=component]').append('<option value='+c['COMPONENTID']+'>'+c['NAME']+'</option>')
            })
            if (last) $('select[name=component]').val(last)
        }
      })
  }
  
  $('select[name=component]').change(function() {
    refresh_sub_components()
    _get_faults()
  })  
  
  // Refresh subcomponent list based on beamline and component
  function refresh_sub_components() {
      var bl = $('select[name=beamline]').val()
      var com = $('select[name=component]').val()
      var last = $('select[name=subcomponent]').val()
  
      if (!com) {
        $('select[name=subcomponent]').empty()
        return
      }
  
      $.ajax({
        url: '/fault/ajax/scom/cid/'+com + (bl ? '/bl/'+bl : ''),
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(subcomponents){
            $('select[name=subcomponent]').html('<option value="">-</option>')
            $.each(subcomponents, function(i,s) {
                $('select[name=subcomponent]').append('<option value='+s['SUBCOMPONENTID']+'>'+s['NAME']+'</option>')
            })
            if (last) $('select[name=subcomponent]').val(last)
        }
      })
  }
  
  $('select[name=subcomponent]').change(function() {
    _get_faults()
  })
  
  refresh_sub_components()
  
});
