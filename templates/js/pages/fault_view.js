$(function() {
  
  var values = { 'Yes': 1, 'No': 0, 'Partial': 2 }
  
  // Custom editable type for datetimepicker
  $.editable.addInputType('datetime', {
    element : function(settings, original) {
        settings.onblur = function(e) {
        };
                          
        var ele = $('<input value="'+original+'" />')
        $(this).append(ele)
        $(this).children('input').datetimepicker({ dateFormat: "dd-mm-yy" })
                          
        return ele
    },
  });
  
  
  function showhide(onload) {
    if (onload) {
      resolved != 0 ? $('.fresolved').show() : $('.fresolved').hide()
      btl == 1 ? $('.beamtime_lost').show() : $('.beamtime_lost').hide()
    } else {
      resolved != 0 ? $('.fresolved').slideDown() : $('.fresolved').slideUp()
      btl == 1 ? $('.beamtime_lost').slideDown() : $('.beamtime_lost').slideUp()
    }
  }
  
  showhide(true)
  
  function _diff() {
    var diff = (_d2u($('.btl_end').html()) - _d2u($('.btl_start').html()))/3600
    $('.lost').html(diff.toFixed(1))
  }
  
  
  // Convert UK date string into unix timestamp
  function _d2u(date) {
    var dt = date.split(' ')
    var dmy = dt[0].split('-')
    var hms = dt[1].split(':')
    return new Date(dmy[2], dmy[1]-1, dmy[0], hms[0], hms[1], 0, 0).getTime()/1000
  }
  
  
  if (owner) {
    $('.title').editable('/fault/ajax/update/fid/'+fid+'/ty/title/', {
        type: 'text',
        submit: 'Ok',
        style: 'display: inline',
    }).addClass('editable');
  
  $('.beamline').editable(function(v,s) {
        return v
        }, {
        loadurl: '/fault/ajax/bls/array/1/',
        type: 'select',
        submit: 'Ok',
        style: 'display: inline',
        callback: function(v,s) { bl = v },
    }).addClass('editable');

    $('.visit').editable('/fault/ajax/update/fid/'+fid+'/ty/visit/', {
        loadurl: '/fault/ajax/visits/array/1/',
        loaddata: function() {
            return { time: _d2u($('.starttime').html()), bl: bl }
        },
        type: 'select',
        submit: 'Ok',
        style: 'display: inline'
    }).addClass('editable');  
  
    $('.starttime').editable('/fault/ajax/update/fid/'+fid+'/ty/starttime/', {
        type: 'datetime',
        submit: 'Ok',
        style: 'display: inline'
    }).addClass('editable');
  
  
    $('.system').editable(function(v,s) {
            sid = v
            return $('option[value='+v+']', this).html()                        
        }, {
        loadurl: '/fault/ajax/sys/array/1/',
        loaddata: function() { return { bl: bl } },
        type: 'select',
        submit: 'Ok',
        style: 'display: inline',
    }).addClass('editable');

    $('.component').editable(function(v,s) {
            cid = v
            return $('option[value='+v+']', this).html()                           
        }, {
        loadurl: '/fault/ajax/com/array/1/',
        loaddata: function() { return { bl: bl, sid: sid } },
        type: 'select',
        submit: 'Ok',
        style: 'display: inline',
    }).addClass('editable');
  
    $('.subcomponent').editable('/fault/ajax/update/fid/'+fid+'/ty/scom/', {
        loadurl: '/fault/ajax/scom/array/1/',
        loaddata: function() { return { bl: bl, cid: cid } },
        type: 'select',
        submit: 'Ok',
        style: 'display: inline',
        callback: function(v,s) { scid = v }
    }).addClass('editable');  
  
    $('.description').editable('/fault/ajax/update/fid/'+fid+'/ty/desc/', {
        loadurl: '/fault/ajax/load/fid/'+fid+'/ty/desc/',
        type: 'textarea',
        rows: 5,
        submit: 'Ok',
        onblur: 'ignore',
    }).addClass('editable');
  
  
    $('.btl').editable('/fault/ajax/update/fid/'+fid+'/ty/btl/', {
        type: 'select',
        data: { 1: 'Yes', 0: 'No' },
        submit: 'Ok',
        style: 'display: inline',
        callback: function(v,s) { btl = values[v]; showhide() }
    }).addClass('editable');
  
    $('.btl_start').editable('/fault/ajax/update/fid/'+fid+'/ty/btlstart/', {
        type: 'datetime',
        submit: 'Ok',
        style: 'display: inline',
        callback: function(v,s) { _diff() }
    }).addClass('editable');
  
    $('.btl_end').editable('/fault/ajax/update/fid/'+fid+'/ty/btlend/', {
        type: 'datetime',
        submit: 'Ok',
        style: 'display: inline',
        callback: function(v,s) { _diff() }
    }).addClass('editable');
  
    $('.resolved').editable('/fault/ajax/update/fid/'+fid+'/ty/res/', {
        type: 'select',
        data: { 2: 'Partial', 1: 'Yes', 0: 'No' },
        submit: 'Ok',
        style: 'display: inline',
        callback: function(v,s) { resolved = values[v]; showhide() }
    }).addClass('editable');  
  
    $('.endtime').editable('/fault/ajax/update/fid/'+fid+'/ty/endtime/', {
        type: 'datetime',
        submit: 'Ok',
        style: 'display: inline'
    }).addClass('editable');
  
    $('.resolution').editable('/fault/ajax/update/fid/'+fid+'/ty/resolution/', {
        loadurl: '/fault/ajax/load/fid/'+fid+'/ty/resolution/',
        type: 'textarea',
        rows: 5,
        submit: 'Ok',
        onblur: 'ignore',
    }).addClass('editable');  
  
  }  
  
});
