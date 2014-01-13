$(function() {
  var dt = {sPaginationType: 'full_numbers',
            bProcessing: true,
            bServerSide: true,
            sAjaxSource: '/sample/ajax/pid/'+pid+'/',
            bAutoWidth:false ,
            aaSorting: [[ 0, 'desc' ]],
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
  
  var dt = $('.robot_actions').dataTable(dt)
  $('.table input').focus()
  
  $(window).resize(function() { _resize() })
  
  function _resize() {
  $.each([0,3,4,5,6,7],function(i,n) {
         dt.fnSetColumnVis(n, !($(window).width() <= 600))
         })
  }
  
  _resize()
  
  function _map_callbacks() {
    $('a.view').button({ icons: { primary: 'ui-icon-search' }, text: false })
  }

  $.each({'name': 'wwdash', 'acronym': 'wwdash', 'mass': 'number'}, function(e,t) {
    $('.'+e).editable('/sample/ajax/updatep/pid/'+pid+'/ty/'+e+'/', {
                      height: '100%',
                      type: 'text',
                      submit: 'Ok',
                      style: 'display: inline',
                      onsubmit: function(s,td) {
                        var r = { value: {}}
                        r.value[t] = true
                        $(this).validate({
                            validClass: 'fvalid', errorClass: 'ferror',
                            errorElement: 'span',
                            rules: r
                        })
                        return $(this).valid();
                      },
                      
                      }).addClass('editable');
  })
  
  
  $('.seq').editable('/sample/ajax/updatep/pid/'+pid+'/ty/seq/', {
    type: 'textarea',
    rows: 5,
    width: '100%',
    submit: 'Ok',
    style: 'display: inline',
    
  }).addClass('editable');
  
  
  $('#ap').validate({
    validClass: 'fvalid', errorClass: 'ferror',
    rules: {
        pdb_file: {
          extension: 'pdb',
        },
        pdb_code: {
          minlength: 4,
          maxlength: 4,
        },
    }
  })
  
  // Get list of pdbs for proposal
  function _get_pdbs() {
    $.ajax({
      url: '/sample/ajax/pdbs'+(pid ? ('/pid/'+pid) : ''),
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        var pdb_out = ''
        $.each(json, function(i,p) {
          pdb_out += '<li pdbid="'+p['PDBID']+'">'+p['NAME']+(p['CODE'] ? ' [Code]' : ' [File]')+' <span class="r"><button class="delete">Delete</button></span></li>'
        })
           
        $('.pdb ul').html(pdb_out)
           
        $('button.delete').button({ icons: { primary: 'ui-icon-closethick' }, text: false }).click(function(i,e) {

        })
      }
    })
  }
  _get_pdbs()
  
  
  $('#add_pdb .progress').progressbar({ value: 0 });
  $('#add_pdb').dialog({ title: 'Add PDB', autoOpen: false, buttons: { 'Add': function() { _add_pdb() }, 'Cancel': function() { $(this).dialog('close') } } });
  
  $('button.add').button({ icons: { primary: 'ui-icon-plus' } }).click(function(i,e) {
    $('.progress').progressbar({ value: 0 });
    _get_all_pdbs(function() { $('#add_pdb').dialog('open') })
  })
  
  function _get_all_pdbs(fn) {
    $.ajax({
      url: '/sample/ajax/pdbs',
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        var pdb_out = '<option value="">N/A</option>'
        $.each(json, function(i,p) {
          pdb_out += '<option value="'+p['PDBID']+'">'+p['NAME']+(p['CODE'] ? ' [Code]' : ' [File]')+'</option>'
        })
           
        $('select[name^=existing_pdb]').html(pdb_out)
        if (fn) fn()
      }
    })
  }
  
  
  // Upload new pdb file
  function _add_pdb() {
      var fd = new FormData($('form#ap')[0])
      $.ajax({
        url: '/sample/ajax/addpdb/pid/'+pid,
        type: 'POST',
        data: fd,
        dataType: 'json',
        xhr: function() {
          var myXhr = $.ajaxSettings.xhr()
          if(myXhr.upload) myXhr.upload.addEventListener('progress', _upload_progress, false)
          return myXhr;
        },
             
        cache: false,
        contentType: false,
        processData: false,
             
        success: function(json){
          $('#add_pdb').dialog('close')
          _get_pdbs()
        }
      })
  }
  
  function _upload_progress(e) {
    var pc = (e.loaded / e.total)*100;
    $('.progress').progressbar({ value: pc });
  }
  
})