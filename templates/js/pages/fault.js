$(function() {
  var search = ''; // search string
  var page = 1
  var thread;
  var auto_load_thread;
  var auto_load = 0;
  
  _get_faults()
  
  $('input.search-mobile').focus().keyup(function() {
    $('input[name=search]').val($(this).val()).trigger('keyup')
    }).parent('span').addClass('enable')
  $('#sidebar,.cont_wrap').addClass('searchbox')
  
  $('input[name=search]').focus()
  
  // Search as you type
  var thread = null;
  $('input[name=search]').keyup(function() {
      clearTimeout(thread);
      $this = $(this);
      thread = setTimeout(function() {
            if ($this.val() != '') {
                clearTimeout(auto_load_thread)
                auto_load = 0
            } else auto_load = 1                          
                          
            page = 1
            search = $this.val()
            _get_faults();
      }, 500);
  });  
  
  // Return list of faults
  function _get_faults() {

      $.ajax({
        url: '/fault/ajax' + (search ? '/s/'+search : '') + (bl ? '/bl/'+bl : '') + (sid ? '/sid/'+sid : '') + (cid ? '/cid/'+cid : '') + (scid ? '/scid/'+scid : '') + '/page/'+page,
        type: 'GET',
        dataType: 'json',
        timeout: 15000,
        success: function(json){
            var pgs = []
            for (var i = 0; i < json[0]; i++) pgs.push('<li'+(i+1==page?' class="selected"':'')+'><a href="#'+(i+1)+'">'+(i+1)+'</a></li>')
             
            $('.pages').html('<ul>'+pgs.join('')+'</ul>')
             
            //if (search)
            $('table.robot_actions tbody').empty()
            $.each(json[1], function(i,f) {
                $(
                    '<tr>'+
                        '<td class="la"><a href="/fault/fid/'+f['FAULTID']+'">'+f['TITLE']+'</a></td>'+
                        '<td>'+f['STARTTIME']+'</td>'+
                        '<td><a href="/fault/bl/'+f['BEAMLINE']+'">'+f['BEAMLINE']+'</a></td>'+
                        '<td><a href="/vstat/visit/'+f['BAG']+'-'+f['VISIT']+'">'+f['BAG']+'-'+f['VISIT']+'</a></td>'+
                        '<td><a href="/fault/sid/'+f['SYSTEMID']+'">'+f['SYSTEM']+'</td>'+
                        '<td><a href="/fault/sid/'+f['SYSTEMID']+'/cid/'+f['COMPONENTID']+'">'+f['COMPONENT']+'</a> &raquo; <a href="/fault/sid/'+f['SYSTEMID']+'/cid/'+f['COMPONENTID']+'/scid/'+f['SUBCOMPONENTID']+'">'+f['SUBCOMPONENT']+'</a></td>'+
                        '<td>'+(f['RESOLVED'] != 0 ? (f['RESOLVED'] == 2 ? 'Partial' : 'Yes') : 'No')+'</td>'+
                        '<td>'+(f['BEAMTIMELOST'] == 1 ? ('Yes ('+f['LOST']+'h)') : 'No')+'</td>'+
                        '<td>'+f['NAME']+'</td>'+
                    '</tr>'
                ).hide().appendTo('table.robot_actions tbody').fadeIn()

            })
             
            if (!json[1].length) $('<tr><td colspan="8">No faults found</td></tr>').hide().prependTo('table.robot_actions tbody').fadeIn()
             
            map_callbacks()
        }
      })
  
      if (auto_load) {
        auto_load_thread = setTimeout(function() {
            _get_faults()
        }, 5000)
      }
  
  }
  
  function map_callbacks() {
      // Page links
      $('.pages a').unbind('click').click(function() {
           page = parseInt($(this).attr('href').replace('#', ''))
           $('table.robot_actions tbody').empty()
           _get_faults()
           url = window.location.pathname.replace(/\/page\/\d+/, '')+'/page/'+page
           window.history.pushState({}, '', url)
           return false
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
            $('select[name=beamline]').html('<option value="">-</option><option value="P01">Phase I</option>')
            $.each(bls, function(i,b) {
                $('select[name=beamline]').append('<option value='+b['NAME']+'>'+b['NAME']+'</option>')
            })
            if (bl) $('select[name=beamline]').val(bl)
        }
      })   
  }

  $('select[name=beamline]').change(function() {
    bl = $(this).val()
    refresh_systems()
    _get_faults()
                                    
    url = window.location.pathname.replace(/\/bl\/\w\d\d(-\d)?/, '')+(bl ? '/bl/'+bl : '')
    window.history.pushState({}, '', url)
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
            else if (sid) {
                $('select[name=system]').val(sid)
                refresh_components()
            }
        }
      })
  }
  
  $('select[name=system]').change(function() {
    sid = $(this).val()
    cid = ''
    scid = ''
                                  
    refresh_components()
    refresh_sub_components()
    _get_faults()
                                  
    url = window.location.pathname.replace(/\/sid\/\d+/, '')+(sid ? '/sid/'+sid : '')
    url = url.replace(/\/cid\/\d+/, '').replace(/\/scid\/\d+/, '')
    window.history.pushState({}, '', url)
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
            else if (cid) {
                $('select[name=component]').val(cid)
                refresh_sub_components()
            }
        }
      })
  }
  
  $('select[name=component]').change(function() {
    cid = $(this).val()
    scid = ''
    refresh_sub_components()
    _get_faults()
                                     
    url = window.location.pathname.replace(/\/cid\/\d+/, '')+(cid ? '/cid/'+cid : '')
    url = url.replace(/\/scid\/\d+/, '')
    window.history.pushState({}, '', url)
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
            else if (scid) $('select[name=subcomponent]').val(scid)
        }
      })
  }
  
  $('select[name=subcomponent]').change(function() {
    scid = $(this).val()
    _get_faults()
                                        
    url = window.location.pathname.replace(/\/scid\/\d+/, '')+(scid ? '/scid/'+scid : '')
    window.history.pushState({}, '', url)
  })
  
  refresh_sub_components()
  
});
