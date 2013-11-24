$(function() {

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
    callback: function() { location.reload() },
  }).addClass('editable');

  $('#sidebar a.pull').click(function() {
    $('#sidebar ul').slideToggle()
  })
  
})
