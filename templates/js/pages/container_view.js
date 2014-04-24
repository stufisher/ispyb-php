$(function() {
  
  function _map_callbacks() {
      _draw()
  
      // Set selection on table click
      $('table.samples tbody tr').unbind('click').click(function(e) {
        $('table.samples tbody tr').removeClass('selected')
        $(this).addClass('selected')
        _draw()
      })
  
      $('button.delete').button({ icons: { primary: 'ui-icon-closethick' }, text: false }).unbind('click').click(function() {
                                                                                                        
      })


      $('button.view').button({ icons: { primary: 'ui-icon-search' }, text: false }).unbind('click').click(function() {
        window.location.href = '/sample/sid/'+$(this).parent('td').parent('tr').attr('sid')
      })
  
      $('button.edit').button({ icons: { primary: 'ui-icon-pencil' }, text: false }).unbind('click').click(function(e) {
        if ($('select.protein').length) {
            var bid = $('table.samples tbody tr').index($(this).parent('td').parent('tr'))
            _get_samples(function() { $('button.edit', $('table.samples tbody tr').eq(bid)).trigger('click') })
            return
        }

        var r = $(this).parent('td').parent('tr')
        var sid = r.attr('sid')
        var p = r.children('td').eq(1).attr('pid')
        var sg = r.children('td').eq(3).html()
        r.children('td').eq(1).html('<select class="protein" name="p"></select>')
        r.find('[name=p]').combobox()
                                                                                              
        _get_proteins(function() {
            $(r).find('[name=p]').combobox('value', p)
        })
                                                  
        r.children('td').eq(2).html('<input type="text" name="n" value="'+r.children('td').eq(2).html()+'" />')
        r.children('td').eq(3).html('<select name="sg">'+sg_ops+'</select>')
        r.find('[name=sg]').val(sg)
                                                                                              
        r.children('td').eq(4).html('<input type="text" name="b" value="'+r.children('td').eq(4).html()+'" />')
        r.children('td').eq(5).html('<input type="text" name="c" value="'+r.children('td').eq(5).html()+'" />')
                                                                                                           
        r.children('td').eq(7).html('<button class="save">Save Changes</button>')
                                       
        $('select.protein, input[name=n]').change(function() { _validate(r) })                             
                                                                                                           
        $('button.save', r).button({ icons: { primary: 'ui-icon-check' }, text: false }).click(function() {
            var r = $(this).parent('td').parent('tr')
            
            if (_validate(r))
              $.ajax({
                url: '/shipment/ajax/updates/cid/'+cid,
                type: 'POST',
                data: { sid: r.attr('sid'),
                   n: $('input[name=n]', r).val(),
                   p: $('select[name=p]', r).combobox('value'),
                   sg: $('select[name=sg]', r).val(),
                   c: $('input[name=c]', r).val(),
                   b: $('input[name=b]', r).val(),
                   pos: r.children('td').eq(0).html(),
                         },
                dataType: 'json',
                timeout: 5000,
                success: function(status){
                    _get_samples()
                }
            })
        })
      })
  }
  
  
  // Validate editing a sample
  function _validate(r) {
    var valid = true
    var p = $('select[name=p]', r)
    if ($(p).combobox('value') == -1) {
      $(p).removeClass('ferror').addClass('fvalid')
      valid = false
    } else  $(p).addClass('ferror').removeClass('fvalid')
  
    if (!$('input[name=n]', r).val().match(/^([\w-])+$/)) {
      $('input[name=n]', r).removeClass('fvalid').addClass('ferror')
      valid = false
    } else $('input[name=n]', r).removeClass('ferror').addClass('fvalid')
  
    return valid
  }
  
  
  // Get protein acronyms
  function _get_proteins(fn) {
    var old = $('select.protein').map(function(i,e) { return $(e).combobox('value') }).get()

    $.ajax({
      url: '/shipment/ajax/pro',
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        var opts = '<option value="-1"></option>'
        $.each(json, function(i,p) {
            opts += '<option value="'+p['PROTEINID']+'">'+p['ACRONYM']+'</option>'
        })
        
        $('select.protein').html(opts)
           
        $('select.protein').each(function(i,e) {
            if (old[i] > -1) $(e).combobox('value', old[i])
        })
           
        if (fn) fn()
      }
    })  
  
  }
  
  
  // Get sample list for container
  function _get_samples(fn) {
    $.ajax({
      url: '/shipment/ajax/samples/cid/'+cid,
      type: 'GET',
      dataType: 'json',
      timeout: 5000,
      success: function(json){
        $('.samples tbody').empty()
        $.each(json, function(i,s) {
          $('<tr sid="'+s['BLSAMPLEID']+'">'+
            '<td>'+s['LOCATION']+'</td>'+
            '<td pid="'+s['PROTEINID']+'">'+s['ACRONYM']+'</td>'+
            '<td>'+s['NAME']+'</td>'+
            '<td>'+(s['SPACEGROUP']?s['SPACEGROUP']:'')+'</td>'+
            '<td>'+(s['CODE']?s['CODE']:'')+'</td>'+
            '<td>'+(s['COMMENTS']?s['COMMENTS']:'')+'</td>'+
            '<td>'+(s['BLSAMPLEID'] ? (s['DCOUNT'] > 0 ? 'Yes' : 'No') : '')+'</td>'+
            '<td>'+(in_use ? '' : '<button class="edit" title="Edit sample details">Edit Sample</button> <button class="delete">Delete Sample</button>')+(s['BLSAMPLEID'] ? ' &nbsp; <button class="view" title="View sample details">View Sample</button>' : '')+'</td>'+
            '</tr>').appendTo($('.samples tbody'))
        })
        _map_callbacks()
        if (fn) { fn() }
      }
    })
  }
           
  _get_samples()
  
  
  
  
  
  
  
  
  var centres = [[150,100],
                 [101,136],
                 [120,193],
                 [180,192],
                 [198,135],
                 [150, 40],
                 [90, 59],
                 [49, 105],
                 [40, 168],
                 [65, 224],
                 [119, 256],
                 [181, 257],
                 [234, 224],
                 [259, 167],
                 [251, 105],
                 [210, 59],
                 ]
  
  var canvas = $('.puck canvas')[0]
  var ctx = canvas.getContext('2d')
  
  canvas.width = $('.puck').width()
  canvas.height = $('.puck').width()
  
  var scale = canvas.width/300
  var puck = new Image()
  var hover = null
  
  puck.src = '/templates/images/puck.png'
  puck.onload = function() {
    _draw()
  }
  
  function _draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height)
    var r = 1
    _positions()
    ctx.drawImage(puck, 0, 0, canvas.width, canvas.height)
  }
  
  
  function _circle(x,y,r,c,line) {
    ctx.beginPath()
    ctx.arc(x,y,r, 0, 2*Math.PI, false)
    if (line) {
      ctx.lineWidth = 2
      ctx.strokeStyle = c;
      ctx.stroke()
    } else {
      ctx.fillStyle = c
      ctx.fill()
    }
  }
  
  function _positions() {
    $('table.samples tbody tr').each(function(i,e) {
      if ($(e).attr('sid')) {
        _circle(centres[i][0]*scale, centres[i][1]*scale, 28*scale, '#82d180')
      }

      if ($(e).hasClass('selected')) {
        _circle(centres[i][0]*scale, centres[i][1]*scale, 23*scale, 'grey', true)
      }
    })
  
    if (hover !== null) {
      _circle(centres[hover][0]*scale, centres[hover][1]*scale, 23*scale, 'grey', true)
    }
  }
  
  $('.puck canvas').click(function(e) {
    var cur = _get_xy(e, this)
                          
    var pos = null
    $.each(centres, function(i,c) {
      var r = 30*scale
      var minx = c[0]*scale - r
      var maxx = c[0]*scale + r
      var miny = c[1]*scale - r
      var maxy = c[1]*scale + r
                                 
      if (cur[0] < maxx && cur[0] > minx && cur[1] < maxy && cur[1] > miny) {
        pos = i
      }
    })
                          
    $('table.samples tbody tr').removeClass('selected').eq(pos).addClass('selected')
    var top = $('table.samples tbody tr').eq(pos).offset().top
    $('body').animate({scrollTop: top}, 300);
  })
  
  $('.puck canvas').mousemove(function(e) {
    hover = null
    var cur = _get_xy(e, this)
                          
    var pos = null
    $.each(centres, function(i,c) {
      var r = 30*scale
      var minx = c[0]*scale - r
      var maxx = c[0]*scale + r
      var miny = c[1]*scale - r
      var maxy = c[1]*scale + r
                                 
      if (cur[0] < maxx && cur[0] > minx && cur[1] < maxy && cur[1] > miny) {
        hover = i
      }
    })

    _draw()
  })
  
  // Return x,y offset for event
  function _get_xy(e, obj) {
    if (e.offsetX == undefined) {
      return [e.pageX-$(obj).offset().left, e.pageY-$(obj).offset().top]
    } else {
      return [e.offsetX, e.offsetY]
    }
  }
  
})
