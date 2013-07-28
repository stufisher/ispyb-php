$(function() {
  
  $('input[name=start]').datetimepicker();
  $('input[name=end]').datetimepicker();
  $('input[name=blstart]').datetimepicker();
  $('input[name=blend]').datetimepicker();
  
  $('.beamtime_lost').hide()
  $('.resolution').hide()

  $('select[name=beamtime_lost]').change(function() {
    $(this).val() == 1 ? $('.beamtime_lost').slideDown() : $('.beamtime_lost').slideUp()
  })

  $('select[name=resolved]').change(function() {
    $(this).val() == 1 ? $('.resolution').slideDown() : $('.resolution').slideUp()
  })
  
  var data = {
    i03: { 
        EPICS: {
            s4slit: ['x', 'y', 'width', 'height'],
            scintilator: ['x', 'y', 'z'],
        },
        GDA: {
            server: [],
            client: [],
        },
        Robot: {
            hardware: ['eStop', 'Mitsubishi Hardware Error'],
            software: ['Unknown Position', 'Other'],
        }
    },
  }
  
  $('select[name=beamline]').change(function() {
        refresh_systems()
  })
  
  $('select[name=system]').change(function() {
        refresh_components()
  })  
  
  $('select[name=component]').change(function() {
        refresh_sub_components()
  })                                   
                                   
  function refresh_systems() {
    var bl = $('select[name=beamline]').val()

    if (bl in data) {
        $('select[name=system]').empty()
        for (var s in data[bl]) {
            $('select[name=system]').append('<option value="'+s+'">'+s+'</option>')
        }
        refresh_components()
    }
  }

  function refresh_components() {
    var bl = $('select[name=beamline]').val()
    var sys = $('select[name=system]').val()

    if (sys in data[bl]) {
        $('select[name=component]').empty()
        for (var c in data[bl][sys]) {
            $('select[name=component]').append('<option value="'+c+'">'+c+'</option>')
        }
        refresh_sub_components()
    }
  }  
  
  function refresh_sub_components() {
    var bl = $('select[name=beamline]').val()
    var sys = $('select[name=system]').val()
    var component = $('select[name=component]').val()
  
    if (component in data[bl][sys]) {
        $('select[name=sub_component]').empty()
        $.each(data[bl][sys][component], function(i,sc) {
            $('select[name=sub_component]').append('<option value="'+sc+'">'+sc+'</option>')
        })
    }
  }
  
  refresh_systems()
  
  
});
