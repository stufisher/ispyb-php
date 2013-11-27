$(function() {
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

  $('.current').editable('/proposal/ajax/set/', {
    type: 'autocomplete',
    autocomplete: { source: '/proposal/ajax/p/' },
    name: 'prop',
    width: '100%',
    submit: 'Ok',
    style: 'display: inline',
    placeholder: 'Click to Select',
    callback: function() { window.location.href = '/proposal/visits' },
  }).addClass('editable');

  $('#sidebar a.pull').click(function() {
    $('#sidebar ul').slideToggle()
  })
  
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
    $('[title]').each(function(i,e) {
      $(e).attr('t_old', $(e).attr('title'))
    })
    _toggle_help()
  })
  
})
