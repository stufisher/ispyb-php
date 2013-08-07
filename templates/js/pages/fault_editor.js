$(function() {
    var beamlines = [];
    var systemid = -1;
    var componentid = -1;
  
    var types = { systems: ['System', 'system'], components: ['Component', 'component'], subcomponents: ['Subcomponent', 'subcomponent'] }
  
    $('.confirm').dialog({ autoOpen: false, modal: true })
  
    $('button[name=add_system]').button(
        { icons: { primary: 'ui-icon-circle-plus' } }
    ).click(function() {
        if ($('table.systems tbody tr.new').length) return
            
        $('table.systems tbody').prepend('<tr class="new">'+
            '<td>&nbsp;</td>'+
            '<td><input type="text" name="system" /></td>'+
            '<td class="la">'+_beamline_select('system')+'</td>'+
            '<td><button class="save"></button></td>'+
            '<td><button class="cancel"></button></td>'+
        '</tr>')
            
        // Add a system
        $('table.systems button.save').button({ icons: { primary: 'ui-icon-check' } }).click(function() {
          var row = $(this).parent('td').parent('tr')
          $.ajax({
            url: '/fault/ajax/sysadd',
            type: 'POST',
            data: { name: $('input[name=system]').val(),
                    bls: $('input[name=system_beamlines]:checked').map(function() {return $(this).val()}).get(),
                    desc: $('input[name=systemdesc]').val() },
            dataType: 'json',
            timeout: 5000,
            success: function(status){
                _get_systems()
            }
          })
        })
            
        map_callbacks()
    })
  
  
    $('button[name=add_component]').button(
        { icons: { primary: 'ui-icon-circle-plus' } }
    ).click(function() {
        if (systemid == -1) return
        if ($('table.components tbody tr.new').length) return
            
        $('table.components tbody').prepend('<tr class="new">'+
            '<td>&nbsp;</td>'+
            '<td><input type="text" name="component" /></td>'+
            '<td><input type="text" name="componentdesc" /></td>'+
            '<td class="la">'+_beamline_select('component')+'</td>'+
            '<td><button class="save"></button></td>'+
            '<td><button class="cancel"></button></td>'+
        '</tr>')
            
        // Add a component
        $('table.components button.save').button({ icons: { primary: 'ui-icon-check' } }).click(function() {
          var row = $(this).parent('td').parent('tr')
          $.ajax({
            url: '/fault/ajax/comadd',
            type: 'POST',
            data: { name: $('input[name=component]').val(),
                    bls: $('input[name=component_beamlines]:checked').map(function() {return $(this).val()}).get(),
                    desc: $('input[name=componentdesc]').val(),
                    systemid: systemid },
            dataType: 'json',
            timeout: 5000,
            success: function(status){
                _get_components()
            }
          })
        })
            
        map_callbacks()
    })
  
    $('button[name=add_subcomponent]').button(
        { icons: { primary: 'ui-icon-circle-plus' } }
    ).click(function() {
        if (componentid == -1) return
        if ($('table.subcomponents tbody tr.new').length) return
            
        $('table.subcomponents tbody').prepend('<tr class="new">'+
            '<td>&nbsp;</td>'+
            '<td><input type="text" name="subcomponent" /></td>'+
            '<td><input type="text" name="subcomponentdesc" /></td>'+
            '<td class="la">'+_beamline_select('subcomponent')+'</td>'+
            '<td><button class="save"></button></td>'+
            '<td><button class="cancel"></button></td>'+
        '</tr>')
            
        // Add a subcomponent
        $('table.subcomponents button.save').button({ icons: { primary: 'ui-icon-check' } }).click(function() {
          var row = $(this).parent('td').parent('tr')
          $.ajax({
            url: '/fault/ajax/scomadd',
            type: 'POST',
            data: { name: $('input[name=subcomponent]').val(),
                    bls: $('input[name=subcomponent_beamlines]:checked').map(function() {return $(this).val()}).get(),
                    desc: $('input[name=subcomponentdesc]').val(),
                    componentid: componentid },
            dataType: 'json',
            timeout: 5000,
            success: function(status){
                _get_subcomponents()
            }
          })
        })
            
        map_callbacks()        
    })
  
  
  // Generate checkboxes for beamlines
  function _beamline_select(name,selected) {
    if (!selected) selected = ''
    selected = selected.split(',')
  
    var out  = ''
    for (var i = 0; i < beamlines.length; i++) {
        var s = $.inArray(beamlines[i], selected) > -1 ? ' checked="checked"' : ''
        out += '<input type="checkbox" name="'+name+'_beamlines" value="'+beamlines[i]+'" '+s+' /> '+beamlines[i] +' '
    }
  
    return out
  }
  
  
  // Generate a confirmation dialog
  function _confirm(t, q, ok_fn) {
    $('.confirm').html(q).dialog('option', 'title', t)
    $('.confirm').dialog('option', 'buttons', {
      'Ok': function() {
        ok_fn()
        $(this).dialog('close');
      },
      'Cancel': function () {
        $(this).dialog('close');
      }
    });

    $('.confirm').dialog('open');
  }  
  
  
  _get_beamlines()
  _get_systems()

  
  // Return list of beamlines
  function _get_beamlines() {
      $.ajax({
        url: '/fault/ajax/bls',
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(bls){
            beamlines = []
            $.each(bls, function(i,b) {
                beamlines.push(b['NAME'])
            })
            map_callbacks()             
        }
      })   
  }  
  
  // Refresh system list
  function _get_systems() {
    $.ajax({
         url: '/fault/ajax/sys',
         type: 'GET',
         dataType: 'json',
         timeout: 5000,
         success: function(systems){
            $('table.systems tbody').empty()
            $.each(systems, function(i,s) {
                $('table.systems tbody').append('<tr><td>'+s['SYSTEMID']+'</td><td class="la">'+s['NAME']+'</td><td class="la">'+s['BEAMLINES']+'</td><td><button class="edit"></button></td><td><button class="delete"></button></td></tr>')
            })
            map_callbacks()
         }
    })
  }
  
  // Refresh component list
  function _get_components() {
    $.ajax({
         url: '/fault/ajax/com/sid/'+systemid,
         type: 'GET',
         dataType: 'json',
         timeout: 5000,
         success: function(components){
            $('table.components tbody').empty()
            $('table.subcomponents tbody').empty().append('<tr><td colspan="5">Select a component to view subcomponents</td></tr>')
            $.each(components, function(i,c) {
                $('table.components tbody').append('<tr><td>'+c['COMPONENTID']+'</td><td class="la">'+c['NAME']+'</td><td class="la">'+c['DESCRIPTION']+'</td><td class="la">'+c['BEAMLINES']+'</td><td><button class="edit"></button></td><td><button class="delete"></button></td></tr>')
            })
           
            if (!components.length) $('table.components tbody').append('<tr><td colspan="5">No components available for that system</td></tr>')
           
            map_callbacks()
         }
    })
  }


  // Refresh subcomponent list
  function _get_subcomponents() {
    $.ajax({
         url: '/fault/ajax/scom/cid/'+componentid,
         type: 'GET',
         dataType: 'json',
         timeout: 5000,
         success: function(subcomponents){
            $('table.subcomponents tbody').empty()
            $.each(subcomponents, function(i,c) {
                $('table.subcomponents tbody').append('<tr><td>'+c['SUBCOMPONENTID']+'</td><td class="la">'+c['NAME']+'</td><td class="la">'+c['DESCRIPTION']+'</td><td class="la">'+c['BEAMLINES']+'</td><td><button class="edit"></button></td><td><button class="delete"></button></td></tr>')
            })
           
            if (!subcomponents.length) $('table.subcomponents tbody').append('<tr><td colspan="5">No subcomponents available for that component</td></tr>')
           
            map_callbacks()
         }
    })
  }
  
  // Map editables to new rows
  function map_callbacks() {
    // Map row click to load components
    $('table.systems tr td').unbind('click').click(function() {
        if ($(this).parent('tr').hasClass('new')) return
                                                   
        systemid = $(this).parent('tr').children('td:first').html()
                   
        $('table.systems tr').removeClass('selected')
        $(this).parent('tr').addClass('selected')
                                   
        _get_components()
    })
  
    // Map row click to load subcomponents
    $('table.components tr td').unbind('click').click(function() {
        if ($(this).parent('tr').hasClass('new')) return
                                                      
        componentid = $(this).parent('tr').children('td:first').html()
                   
        $('table.components tr').removeClass('selected')
        $(this).parent('tr').addClass('selected')
                                   
        _get_subcomponents()
    })
  
  
    // Cancel adding a row
    $('button.cancel').button({ icons: { primary: 'ui-icon-closethick' } }).unbind('click').click(function() {
        $(this).parent('td').parent('tr').remove()
    })
  
    // Delete an item from a table
    $('button.delete').button({ icons: { primary: 'ui-icon-closethick' } }).unbind('click').click(function() {
        var ty = $(this).parent('td').parent('tr').parent('tbody').parent('table').attr('class').replace('robot_actions ', '')
        var id = $(this).parent('td').siblings('td:first').html()
                                                                                                  
        _confirm('Delete Row', 'Are you sure you want to delete this row?', function() {
            $.ajax({
                 url: '/fault/ajax/dc/ty/'+ty+'/id/'+id,
                 type: 'GET',
                 dataType: 'json',
                 timeout: 5000,
                 success: function(status){
                   if (ty == 'beamlines') _get_beamlines()
                   else if (ty == 'systems') _get_systems()
                   else if (ty == 'components') _get_components()
                   else if (ty == 'subcomponents') _get_subcomponents()
                 }
            })
        })
    })
  
    // Edit an item from a table
    $('button.edit').button({ icons: { primary: 'ui-icon-pencil' } }).unbind('click').click(function() {
        var ty = $(this).parent('td').parent('tr').parent('tbody').parent('table').attr('class').replace('robot_actions ', '')
        var row = $(this).parent('td').parent('tr')

        _edit_component(row,ty)
                    
    })
  }
  
  
  // Edit a component
  function _edit_component(row,ty) {
    var prefix = types[ty][1]
    $(row.children('td')[1]).html('<input type="text" name="'+prefix+'" value="'+$(row.children('td')[1]).html()+'" />')
    if (ty != 'systems') $(row.children('td')[2]).html('<input type="text" name="'+prefix+'desc" value="'+$(row.children('td')[2]).html()+'" />')
    $(row.children('td').slice(-3,-2)).html(_beamline_select(prefix, $(row.children('td').slice(-3,-2)).html()))
  
    $(row.children('td').slice(-2,-1)).html('<button class="save"></button>')
  
    $('button.save', row).button({ icons: { primary: 'ui-icon-check' } }).click(function() {
        var row = $(this).parent('td').parent('tr')
        $.ajax({
            url: '/fault/ajax/ec',
            type: 'POST',
            data: { ty: ty,
               id: row.children('td:first').html(),
               name: row.find('input[name='+prefix+']').val(),
               desc: row.find('input[name='+prefix+'desc]').val(),
               bls: row.find('input[name='+prefix+'_beamlines]:checked').map(function() {return $(this).val()}).get(),
                     },
            dataType: 'json',
            timeout: 5000,
            success: function(status){
                eval('_get_'+ty+'()')
            }
        })
    })
  }
  
});
