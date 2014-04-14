$(function() {
  $('input[name=shippingdate]').datepicker({ dateFormat: "dd-mm-yy" });
  $('input[name=deliverydate]').datepicker({ dateFormat: "dd-mm-yy" });
  
  $('#add_shipment').validate({
    errorElement: 'span',
    validClass: 'fvalid', errorClass: 'ferror',
    rules: {
        shippingname: {
            wwsdash: true,
        },
        dewars: {
            number: true,
        },
        deliverydate: {
            edate: true
        },
        shippingdate: {
            edate: true
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
        $('<span><input type="text" name=fcodes[] value="" placeholder="DLS-XX-000'+(i+1)+'" /></span> ').hide().appendTo($('span.fcodes')).fadeIn()
      }
  
    } else if (d < l) {
      for (var i = (l-1); i >= d; i--) {
        $('span.fcodes span').eq(i).fadeOut().remove()
      }
    }
  
    $('input[name^=fcodes]').unbind('change').change(function() {
      $(this).val($(this).val().toUpperCase())
      _check_shipping()
    })
  }
  
  $('input[name=shippingname]').change(function() {
    _check_shipping()
  })
  
  function _check_shipping() {
    return
  
    valid_fc = false
    $('input[name^=fcodes]').each(function(i,e) {
      if ($(this).val().match(/DLS-MX-\d\d\d\d/i)) valid_fc = true
    })
  
    if (valid_fc && $('input[name=shippingname]').val()) $('button[name="dls"]').fadeIn()
    else $('button[name="dls"]').hide()
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
  
  var accepted = false
  $('.terms').dialog({ title: 'Terms & Conditions', autoOpen: false, height: 'auto', width: 'auto', buttons: { 'Accept': function() {
      $.ajax({
        url: '/shipment/ajax/termsaccept/',
        data: { title: $('input[name=shippingname]').val() },
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
          accepted = true;
          $('input[name="couriername"]').val('DHL')
          $('input[name="courierno"]').val(json[2])
          $('.terms').html('<p>Pin Code: <b>'+json[1]+'</b></p>'+json[0])
          $('.terms').dialog('option', 'buttons', {'Close': function() { $(this).dialog('close') }})
          $('.terms').dialog('option', 'title', 'Instructions')
        }
           
      })
    }, 'Close': function() { $(this).dialog('close') }}
                     
  });
  
  $('button[name="dls"]').button({ icons: { primary: 'ui-icon-contact' } }).hide().click(function() {
    $.ajax({
        url: '/shipment/ajax/terms',
        type: 'GET',
        dataType: 'json',
        timeout: 5000,
        success: function(json){
          if (!accepted) $('.terms').html(json)
          $('.terms').dialog('open')
        }
    })

    return false
  })
  
})
