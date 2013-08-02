$(function() {
  
  $('input[name=start]').datetimepicker();
  $('input[name=end]').datetimepicker();
  $('input[name=blstart]').datetimepicker();
  $('input[name=blend]').datetimepicker();
  
  $('.beamtime_lost').hide()
  $('.resolution').hide()
  
  _get_beamlines()
  
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
             
            refresh_systems()
        }
      })   
  }

  $('select[name=beamtime_lost]').change(function() {
    $(this).val() == 1 ? $('.beamtime_lost').slideDown() : $('.beamtime_lost').slideUp()
  })

  $('select[name=resolved]').change(function() {
    $(this).val() > 0 ? $('.resolution').slideDown() : $('.resolution').slideUp()
  })
  
  
  // Get visits for time on beamline
  $('input[name=start],select[name=beamline]').change(function() {
      if (!$('input[name=start]').val()) return
                                                      
      var t = new Date($('input[name=start]').val()).getTime()/1000
      $.ajax({
        url: '/fault/ajax/visits/time/'+t+'/bl/'+$('select[name=beamline]').val(),
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(visits){
            $('select[name=visit]').empty()
            $.each(visits, function(i,v) {
                $('select[name=visit]').append('<option value='+v['SESSIONID']+'>'+v['VISIT']+'</option>')
                    
            })
        }
  
      })    
                                
                                
  })

  
  $('select[name=beamline]').change(function() {
        refresh_systems()
  })
  
  $('select[name=system]').change(function() {
        refresh_components()
  })  
  
  $('select[name=component]').change(function() {
        refresh_sub_components()
  })                                   
  
  
  // Refresh system list based on beamline
  function refresh_systems() {
      var bl = $('select[name=beamline]').val()
      $.ajax({
        url: '/fault/ajax/sys/bl/'+bl,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(systems){
            $('select[name=system]').empty()
            $.each(systems, function(i,s) {
                $('select[name=system]').append('<option value='+s['SYSTEMID']+'>'+s['NAME']+'</option>')
            })
             
            if (systems.length) refresh_components()
        }
      })
  }
  
  // Refresh component list based on beamline and system
  function refresh_components() {
      var bl = $('select[name=beamline]').val()
      var sys = $('select[name=system]').val()

      $.ajax({
        url: '/fault/ajax/com/bl/'+bl+'/sid/'+sys,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(components){
            $('select[name=component]').empty()
            $.each(components, function(i,c) {
                $('select[name=component]').append('<option value='+c['COMPONENTID']+'>'+c['NAME']+'</option>')
            })
             
            if (components.length) refresh_sub_components()
        }
      })
  }  
  
  // Refresh subcomponent list based on beamline and component
  function refresh_sub_components() {
      var bl = $('select[name=beamline]').val()
      var com = $('select[name=component]').val()
  
      $.ajax({
        url: '/fault/ajax/scom/bl/'+bl+'/cid/'+com,
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(subcomponents){
            $('select[name=sub_component]').empty()
            $.each(subcomponents, function(i,s) {
                $('select[name=sub_component]').append('<option value='+s['SUBCOMPONENTID']+'>'+s['NAME']+'</option>')
            })
             
            $('select[name=sub_component]').html() ? $('select[name=sub_component]').show() : $('select[name=sub_component]').hide()             
        }
      })
  }
  
  
});
