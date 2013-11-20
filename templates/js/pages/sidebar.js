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
    submit: 'Ok',
    style: 'display: inline',
    callback: function() { location.reload() },
  }).addClass('editable');

  
  /*
  $('.current').editable('/proposal/ajax/set/', {
                       loadurl: '/proposal/ajax/p/',
                       type: 'select',
                       name: 'prop',
                       submit: 'Ok',
                       style: 'display: inline',
                       }).addClass('editable');
  */
})
