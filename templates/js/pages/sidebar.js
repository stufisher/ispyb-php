$(function() {
  $('button.atp').prop('title', 'Add/Remove to/from Project').button({ icons: { primary: 'ui-icon-note' }, text: false }).unbind('click').click(function() {
    _load_project_dialog($(this).attr('ty'), $(this).attr('iid'), $(this).attr('name'))
  })
  
  var c = $.cookie('ispyb_help')
  var help = c === undefined ? 1 : c
  
  $('[title]').each(function(i,e) {
    $(e).attr('t_old', $(e).attr('title'))
  })
  
  $.editable.addInputType('autocomplete', {
      element : $.editable.types.text.element,
      plugin : function(settings, original) {
          $('input', this).autocomplete(settings.autocomplete);
      }
  })

  /*$('.current').editable('/proposal/ajax/set/', {
    type: 'autocomplete',
    autocomplete: { source: '/proposal/ajax/p/' },
    name: 'prop',
    width: '60px',
    submit: 'Ok',
    style: 'display: inline',
    placeholder: 'Click to Select',
    //onblur: 'ignore',
    callback: function() { window.location.href = '/proposal/visits' },
  }).addClass('editable');*/

  //$('#sidebar a.pull').click(function() {
  //  $('#sidebar ul').slideToggle()
  //})
  
  $('a.pull').addClass('enable').click(function() { $('body').toggleClass('active'); return false })
  
  $('#sidebar ul li.help a').click(function() {
    help = help == 1 ? 0 : 1;
    $.cookie('ispyb_help', help);
    _toggle_help()
                                   
    return false;
  })
              
  function _toggle_help() {
    if (help == 1) {
      $('#sidebar ul li.help').addClass('active')
      $('[title]').tipTip({delay:100, fadeIn: 400, defaultPosition: 'top'})
      $('[t_old]').each(function(i,e) { $(e).data('tt_disable', false) })
      $('p.help').fadeIn()
                                 
    } else {
      $('#sidebar ul li.help').removeClass('active')
      $('[t_old]').each(function(i,e) { $(e).data('tt_disable', true) })
      $('p.help').fadeOut()
    }
  }
  
  _toggle_help()
  
  $(document).ajaxComplete(function() {
    $('button.atp').prop('title', 'Add/Remove to/from Project').button({ icons: { primary: 'ui-icon-note' }, text: false }).unbind('click').click(function() {
      _load_project_dialog($(this).attr('ty'), $(this).attr('iid'), $(this).attr('name'))
    })
                           
    $('[title]').each(function(i,e) {
      $(e).attr('t_old', $(e).attr('title'))
    })
    _toggle_help()
  })
  
  
  // Add to project popup
  $('.project').dialog({ title: 'Add To Project', autoOpen: false, height: 'auto', width: 'auto' });
  
  
  $('.project select[name=pid]').change(function() { _check_project_item })
  // Check whether item is already in project, show add / remove button as needed
  function _check_project_item() {
    var pid = $('.project select[name=pid]').val()
    var ty = $('.project').attr('ty')
    var id = $('.project').attr('pid')
                                        
    $.ajax({
      url: '/projects/ajax/check/ty/'+ty+'/pid/'+pid+'/iid/'+id,
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(r) {
        var btns = {}
        btns[(r ? 'Remove' : 'Add')] =  function() {
          if (pid && ty && id) {
            $.ajax({
              url: '/projects/ajax/addto/pid/'+pid+'/ty/'+ty+'/iid/'+id+(r ? '/rem/1' : ''),
              type: 'GET',
              dataType: 'json',
              timeout: 5000,
              success: function(r){
                $('.project').dialog('close')
              }
            })
          }
        }
        btns['Close'] = function() { $(this).dialog('close') }
  
        $('.project').dialog('option', 'buttons', btns)
        $('.project').dialog('option', 'title', r ? 'Remove From Project' : 'Add To Project')
      }
    })
  }
  
  
  // Show add to project popup
  function _load_project_dialog(ty, id, name) {
    $('.project').attr('ty', ty)
    $('.project').attr('pid', id)
    $('.project span.title').html(name)
    
    $.ajax({
      url: '/projects/ajax/array/1',
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(r){
        $('.project select[name=pid]').empty()
        $.each(r, function(p,t) {
          $('<option value="'+p+'">'+t+'</option>').appendTo($('.project select[name=pid]'))
        })
        _check_project_item()
           
        $('.project').dialog('open')
      }
    })
  }
  
})
