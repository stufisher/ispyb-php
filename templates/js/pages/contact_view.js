$(function() {

  $.each(['cardname', 'familyname', 'givenname', 'phone', 'email', 'labname', 'courier', 'billing', 'courierac', 'customs', 'transport'], function(i,e) {
    $('.'+e).editable('/contact/ajax/update/cid/'+cid+'/ty/'+e+'/', {
       type: 'text',
       height: '100%',
       width: '20%',
       submit: 'Ok',
       style: 'display: inline',
    }).addClass('editable');
  })
  
  $('.address').editable('/contact/ajax/update/cid/'+cid+'/ty/address/', {
        type: 'textarea',
        rows: 5,
        submit: 'Ok',
        onblur: 'ignore',
  }).addClass('editable');
  
  
})