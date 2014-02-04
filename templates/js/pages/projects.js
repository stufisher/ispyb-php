$(function() {
  
  var dt = {sPaginationType: 'full_numbers',
            bProcessing: true,
            bServerSide: true,
            sAjaxSource: '/projects/ajax/',
            bAutoWidth:false ,
            aaSorting: [[ 0, 'desc' ]],
            bFilter: false,

            fnServerData: function ( sSource, aoData, fnCallback ) {
                $.getJSON( sSource, aoData, function (json) { 
                   fnCallback(json)
                   _map_callbacks()
                })
            }
  }
  
  
  if ($(window).width() <= 600) dt = $.extend({
        'bScrollCollapse': true,
        'sScrollX': '100%',
  }, dt)
  
  var tbl = $('.robot_actions').dataTable(dt)
  
  
  $('button.add').button({ icons: { primary: 'ui-icon-plus' } }).click(function() {
    if ($('table.projects tbody tr.new').length) return
                                                                       
    $('<tr class="new">'+
      '<td><input name="title" /></td>'+
      '<td><input name="acronym" /></td>'+
      '<td><button class="save">Add</button> <button class="cancel">Cancel</button></td>'+
      '</tr>').appendTo('table.projects tbody')
                 
                            
    // Add a new project
    $('button.save').button({ icons: { primary: 'ui-icon-check' }, text: false }).click(function() {
        if (!/^(\w|\-)+$/.test($('.new input[name=acronym]').val())) {
           $('.new input[name=acronym]').addClass('ferror')
           return
        } else $('.new input[name=acronym]').removeClass('ferror')

        if (!$('.new input[name=title]').val()) {
           $('.new input[name=title]').addClass('ferror')
           return
        } else $('.new input[name=title]').removeClass('ferror')
                                                                           
        $.ajax({
            url: '/projects/ajax/add/',
            type: 'POST',
            data: { title: $('.new input[name=title]').val(),
                    acronym: $('.new input[name=acronym]').val(),
            },
            dataType: 'json',
            timeout: 5000,
            success: function(json){
               tbl.fnDraw()
            }
        })
    })
                                                                            
    // Cancel adding a project
    $('button.cancel').button({ icons: { primary: 'ui-icon-closethick' }, text: false }).unbind('click').click(function() {
        $(this).parent('td').parent('tr').remove()
    })
    
                                                                       
    return false
  })
  
  function _map_callbacks() {
    $('a.view').button({ icons: { primary: 'ui-icon-search' }, text: false }).hide()
  
    $('table.projects tbody tr').unbind('click').click(function() {
      window.location = $('td:last-child a', this).attr('href')
    })

  }

})
