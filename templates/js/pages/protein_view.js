$(function() {
  var dt = {sPaginationType: 'full_numbers',
            bProcessing: true,
            bServerSide: true,
            sAjaxSource: '/sample/ajax/pid/'+pid+'/',
            bAutoWidth:false ,
            aaSorting: [[ 0, 'desc' ]],
            //fnDrawCallback: _map_callbacks(),
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

  
  $.validator.addMethod('wwdash', function(value, element) {
    return this.optional(element) || /^(\w|\-)+$/.test(value);
  }, "This field must contain only letters numbers, underscores, and dashes")
  
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
  
  
  // Get list of pdbs for proposal
  function _get_pdbs(pid) {
    $.ajax({
      url: '/sample/ajax/pdbs'+(pid ? ('/pid/'+pid) : ''),
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        var pdb_out = '<option value="">&nbsp;</option>'
        $.each(json, function(i,p) {
          pdb_out += '<option value="'+p['PDBID']+'">'+p['NAME']+'</option>'
        })
           
        $('select[name=pdb]').html(pdb_out)
      }
    })
  }
  _get_pdbs()
  
  
  $('#add_pdb .progress').progressbar({ value: 0 });
  $('#add_pdb').dialog({ title: 'Add PDB', autoOpen: false, buttons: { 'Add': function() { _add_pdb() }, 'Cancel': function() { $(this).dialog('close') } } });
  
  $('button.add').button({ icons: { primary: 'ui-icon-plus' } }).click(function(i,e) { $('#add_pdb').dialog('open') })
  
  
  // Upload new pdb file
  function _add_pdb() {
    var n = $('#add_pdb input[name=name]').val()
    var file = $('input[name=pdb_file]')[0].files[0]
  
    if (file && n) {
      var fd = new FormData($('form#ap')[0])
      $.ajax({
        url: '/sample/ajax/addpdb',
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
  }
  
  function _upload_progress(e) {
    var pc = (e.loaded / e.total)*100;
    $('.progress').progressbar({ value: pc });
  }
  
})