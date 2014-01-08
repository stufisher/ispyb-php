$(function() {
  $('input[name=shippingdate]').datepicker({ dateFormat: "dd-mm-yy" });
  $('input[name=deliverydate]').datepicker({ dateFormat: "dd-mm-yy" });
  
  $.validator.addMethod('wwdash', function(value, element) {
    return this.optional(element) || /^(\w|\-)+$/.test(value);
  }, "This field must contain only letters numbers, underscores, and dahses")
  
  $('#add_shipment').validate({
    validClass: 'fvalid', errorClass: 'ferror',
    rules: {
        shippingname: {
            wwdash: true,
        }
    }
  })
  
  
  function _get_lc(lid) {
    $.ajax({
      url: '/shipment/ajax/lcd/lcid/'+lid,
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(j){
        $('input[name=couriername]').val(j['DEFAULTCOURRIERCOMPANY'])
        $('input[name=courierno]').val(j['COURIERACCOUNT'])
      }       
    })
  }
  
  $('select[name=lcret]').change(function() { _get_lc($(this).val()) })
  _get_lc($('select[name=lcret]').val())
  
  
  $('input[name=dewars]').change(function() { _update_fcodes() })
  
  function _update_fcodes() {
    var d = $('input[name=dewars]').val()
    var l = $('span.fcodes span').length
  
    d > 0 ? $('li.d').fadeIn() : $('li.d').fadeOut()
  
    if (d > l) {
      for (var i = l; i < d; i++) {
        $('<span><input type="text" name=fcodes[] value="" placeholder="'+(i+1)+'" /></span> ').hide().appendTo($('span.fcodes')).fadeIn()
      }
  
    } else if (d < l) {
      for (var i = (l-1); i >= d; i--) {
        $('span.fcodes span').eq(i).fadeOut().remove()
      }
    }
  
  
  }
  
  _update_fcodes()
  
  function _get_exps() {
    $.ajax({
        url: '/shipment/ajax/vis',
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
           
            var vals = '<option value=""></option>'
            $.each(json, function(k,e) {
               vals += '<option value="'+k+'">'+e+'</option>'
            })
           
            $('select[name=exp]').html(vals)
        }
    })
  }
  
  _get_exps()
  
})