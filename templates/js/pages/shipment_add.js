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
  
})