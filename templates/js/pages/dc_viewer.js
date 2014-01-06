$(function() {

  // Check for ios
  function isiPhone() {
    return ((navigator.platform.indexOf("iPhone") != -1) ||
            (navigator.platform.indexOf("iPod") != -1) ||
            (navigator.platform.indexOf("iPad") != -1));
  }  
  
  $('#zoom').slider({min: 0, max: 200, step: 5})
  $('#brightness').slider({min: -100, max: 100, step: 5})
  $('#contrast').slider({min: -100, max: 100, step: 5})
  
  $('.im_progress').progressbar({ value: 0 });
  
  var width = 0
  var height = 0
  
  var offsetx = 0
  var offsety = 0
  
  var brightness = 0
  var contrast = 0
  
  var scalef = 1
  
  var record = 0
  var startx = 0
  var starty = 0
  
  var lastx = 0
  var lasty = 0
  var lastv = 0
  
  var moved = false
  
  var recache_thread = null
  var resize_thread = null
  var redraw_thread = null
  
  var img = new Image()
  var cache = new Image()
  
  var canvas = $('#img')[0]
  var ctx = canvas.getContext('2d')
  
  var blocks = 0
  
  //if (!isiPhone())
  var c = Caman('#img')
  
  // Set canvas size to parent element
  function resize() {
    $('.image_container .image').height($(window).height()*0.65)
  
    $('#img')[0].width = $('.image_container .image').width()
    $('#img')[0].height = $('.image_container .image').height()
  
    var left = $('#img').offset().left + ($('#img').width()/2) - 125
    var top = $('#img').offset().top + ($('#img').height()/2) - 10
    $('.im_progress').offset({ left: 0, top: 0 }).show()
    $('.im_progress').offset({ left: left, top: top }).hide()
  }

  
  $(window).resize(function() {
    clearTimeout(resize_thread)
    resize_thread = setTimeout(function() {
      resize()
      _calc_zoom()
      draw()
      _recache()
    }, 200)
  })
  
  // Change image
  function change(n) {
    $('.image_container .image #img').fadeOut(100,function() {
      load(n)
    })
  }  
  
  // Cache counter
  var ci = 1
  
  // Load image from remote source
  function load(n) {
    img.src = '/image/di/id/'+id+'/n/'+n
    img.onload = function() {
        width = img.width
        height = img.height
        _calc_zoom()
        draw()
        _recache()
        $('#img').fadeIn(100);
        _plot_profiles(20,10)
  
        // Precache next image
        //if (n < ni) cache.src = '/image/di/id/'+id+'/n/'+(n+1)
  
        // Set cache point
        ci = n+1
        precache()
    }
  }
  
  // Load the first image
  setTimeout(function() {
    load(1)
    resize()
  }, 500)
  
  // Start precaching images
  function precache() {
    var pro = function() {
        console.log('loaded', ci)
        setTimeout(function() {
          if (ci < ni) precache(++ci)
        }, 500)
        $('.precache').html('Precached '+ci+' of '+ni)
    }
  
    $.ajax({
        url: '/image/di/id/'+id+'/n/'+ci,
        type: 'GET',
        timeout: 5000,
        success: pro,
        error: pro
    })
  }
  
  //precache()
  
  
  // Polyfill for devices without bind
  if (!Function.prototype.bind) {
    Function.prototype.bind = function (oThis) {
      if (typeof this !== "function") {
        throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");
      }

      var aArgs = Array.prototype.slice.call(arguments, 1),
        fToBind = this, 
        fNOP = function () {},
        fBound = function () {
          return fToBind.apply(this instanceof fNOP && oThis
                                 ? this
                                 : oThis,
                               aArgs.concat(Array.prototype.slice.call(arguments)));
        };

      fNOP.prototype = this.prototype;
      fBound.prototype = new fNOP();

      return fBound;
    };
  }
  
  
  // iOS Bug with large images, detect squished image and rescale it
  function detectVerticalSquash(img) {
    var iw = img.naturalWidth, ih = img.naturalHeight;
    var canvas = document.createElement('canvas');
    canvas.width = 1;
    canvas.height = ih;
    var ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0);
    var data = ctx.getImageData(0, 0, 1, ih).data;
    // search image edge pixel position in case it is squashed vertically.
    var sy = 0;
    var ey = ih;
    var py = ih;
    while (py > sy) {
        var alpha = data[(py - 1) * 4 + 3];
        if (alpha === 0) {
            ey = py;
        } else {
            sy = py;
        }
        py = (ey + sy) >> 1;
    }
    var ratio = (py / ih);
    return (ratio===0)?1:ratio;
  }
  
  
  // Draw image at correct scale / position
  function draw(adjust) {
    //ctx.clearRect(0, 0, canvas.width, canvas.height)
    ctx.setTransform(scalef,0,0,scalef,offsetx,offsety)
    var r = detectVerticalSquash(img)
    ctx.drawImage(img, 0, 0, width, height/r)
  
    if ($('input[name=res]').is(':checked')) _draw_res_rings()
    if ($('input[name=ice]').is(':checked')) _draw_ice_rings()
  }
  
  // Apply image adjustments
  function adjust() {
    //if (isiPhone()) return
    c.revert()
    if ($('input[name=invert]').is(':checked')) {
      c.invert()
      //_plot_profiles(lastx, lasty)
    }
  
    c.brightness(brightness).contrast(contrast).render(function() {
        $('.im_progress').fadeOut(100)
    })
  }
  
  
  // Calculate zoom factor for current window size
  function _calc_zoom() {
    $('#zoom').slider('option', 'min', 100*$('#img').width()/width)
    scalef = $('#zoom').slider('value')/100
    $('#zval').html((scalef*100).toFixed(0))
  }
  
  
  // Recache canvas to caman
  function _recache() {
    //if (isiPhone()) return
    c.reloadCanvasData()
    c.resetOriginalPixelData()
  }
  
  
  // Draw ice rings
  function _draw_ice_rings() {
    rings = [3.897, 3.669,3.441,2.671,2.249,2.07,1.95,1.92,1.88,1.72]
  
    ctx.strokeStyle='blue';
    for (var i = 0; i < rings.length; i++) {
      ctx.beginPath();
      rad = _res_to_dist(rings[i])/0.172
      ctx.arc(dc['Y']/0.172,dc['X']/0.172,rad,0,2*Math.PI);
      ctx.stroke();
    }
  }
  
  // Draw resolution rings
  function _draw_res_rings() {
    ctx.strokeStyle='black';
    ctx.font='30px Arial';
  
    for (var i = 0; i < 5; i++) {
      rad = (((height-10)/2)/5)*(i+1)
      ctx.beginPath();
      ctx.arc(dc['Y']/0.172,dc['X']/0.172,rad,0,2*Math.PI);
      ctx.stroke();
      ctx.fillText(_dist_to_res(rad*0.172).toFixed(2) + 'A',dc['Y']/0.172-40,dc['X']/0.172-rad+40);
    }
  }
  
  
  // Convert distance from centre to resolution and back
  function _dist_to_res(dist) {
    return dc['LAM'] / (2*Math.sin(Math.atan(dist/dc['DET'])/2))
  }
  
  function _res_to_dist(res) {
    return Math.tan(2*Math.asin(dc['LAM'] / (2*res)))*dc['DET']
  }
  
  // Convert xy coord on image to distance from centre
  function _xy_to_dist(x, y) {
     // assume everyone is using a pilatus
     var ps = 0.172
  
     return Math.sqrt(Math.pow(Math.abs(x*ps-dc['Y']),2)+Math.pow(Math.abs(y*ps-dc['X']),2))
  }
  
  
  // Set cursor position and resolution
  function _cursor(x, y) {
      posx = (x/scalef)-(offsetx/scalef)
      posy = (y/scalef)-(offsety/scalef)
  
      var res = _dist_to_res(_xy_to_dist(posx,posy))
  
      $('#res').html(res.toFixed(2))
  }
  
  
  // Plot spot profile
  function _plot_profiles(xp, yp) {
    if (xp < 20) xp = 20
    if (yp < 10) yp = 10
  
    lastx = xp
    lasty = yp
  
    var w = $(window).width() <= 600 ? 20 : 40
    var h = 20
  
    $('.im_highlight').offset({ left: $('#img').offset().left+xp-(w/2), top: $('#img').offset().top+yp-(h/2) })  
  
    var xdat = ctx.getImageData(xp-w/2, yp, w, 1).data
    var ydat = ctx.getImageData(xp, yp-h/2, 1, h).data
  
    var x = []
    for (var i = 0; i < w; i++) {
        var val = (xdat[i*4] + xdat[i*4+1] + xdat[i*4+2])
        if (!$('input[name=invert]').is(':checked')) val = 765-val
        x.push([i,val])
    }

    var y = []
    for (var i = 0; i < h; i++) {
        var val = (ydat[i*4] + ydat[i*4+1] + ydat[i*4+2])
        if (!$('input[name=invert]').is(':checked')) val = 765-val
        y.push([val,h-1-i])
    }
  
    var options = {
        yaxis: {
            ticks: []
        },
        xaxis: {
            ticks: []
        },
        grid: {
            borderWidth: 0,
        },
    }
  
    $.plot('.xprofile', [x], options)
    $.plot('.yprofile', [y], options)
  
    var zc = $('#im_zoom')[0].getContext('2d')
    zc.drawImage(ctx.canvas,xp-(w/2), yp-(h/2), w, h, 0, 0, 200, 100)
    zc.strokeStyle='blue'
    zc.beginPath();
    zc.moveTo(95,50)
    zc.lineTo(105,50)
    zc.stroke()
    zc.beginPath();
    zc.moveTo(100,45)
    zc.lineTo(100,55)
    zc.stroke()
  }
  
  
  // Return x,y offset for event
  function _get_xy(e, obj) {
    if (e.offsetX == undefined) {
        return [e.pageX-$(obj).offset().left, e.pageY-$(obj).offset().top]
    } else {
        return [e.offsetX, e.offsetY]
    }
  }
  
  
  // Next / prev image functions
  function prev() {
    val = $('input[name="num"]').val()
    if (val > 1) {
      if (val > ni) val = ni
      val--
      change(val)
      val = $('input[name="num"]').val(val)
    }
  }
  
  function next() {
    val = $('input[name="num"]').val()
    if (val < ni) {
      val++
      change(val)
      val = $('input[name="num"]').val(val)
    }
  }
  
  
  // Set up hotkeys
  $(document).keypress(function(e) {
    //console.log(e)
    switch (e.which) {
      // ,
      case 44:
        prev()
        break
          
      // .
      case 46:
        next()
        break
             
      // i
      case 105:
        $('input[name=invert]').prop('checked', !$('input[name=invert]').is(':checked'))
        adjust()
        break
            
      // w
      case 119:
        $('input[name=ice]').prop('checked', !$('input[name=ice]').is(':checked'))
        draw()
        _recache()
        adjust()
        break
          
      // r
      case 114:
        $('input[name=res]').prop('checked', !$('input[name=res]').is(':checked'))
        draw()
        _recache()
        adjust()
        break
                       
      // z / Z
      case 122:
        $('#zoom').slider('value', $('#zoom').slider('value')+5)
        _clamp_offset()
        draw()
        _recache()
        adjust()
        break

      case 90:
        $('#zoom').slider('value', $('#zoom').slider('value')-5)
        _clamp_offset()
        draw()
        _recache()
        adjust()
        break
                       
      // c / C
      case 99:
        $('#contrast').slider('value', $('#contrast').slider('value')+5)
        break
                       
      case 67:
        $('#contrast').slider('value', $('#contrast').slider('value')-5)
        break
    
      // b / B
      case 98:
        $('#brightness').slider('value', $('#brightness').slider('value')+5)
        break
                       
      case 66:
        $('#brightness').slider('value', $('#brightness').slider('value')-5)
        break
                       
      default: return;
    }
  })
  
  
  // Load profile on image click
  $('#im_zoom').click(function(e) {
    var c = _get_xy(e, '#im_zoom')
    var newx = Math.round((100-c[0])/5)
    var newy = Math.round((50-c[1])/5)
    _plot_profiles(lastx-newx, lasty-newy-1)
  })
  
  
  
  // Scrolling around diffraction image
  $('#img').bind('mousedown touchstart', function(e) {
    e.preventDefault()
    if(e.originalEvent.touches && e.originalEvent.touches.length) e = e.originalEvent.touches[0];
    record = 1
    startx = e.clientX
    starty = e.clientY
  })
  
  $('#img').bind('mousemove touchmove', function(e) {
    e.preventDefault()
    if (e.originalEvent.touches && e.originalEvent.touches.length >  1) return
    if (e.originalEvent.touches && e.originalEvent.touches.length) e = e.originalEvent.touches[0];
    var c = _get_xy(e, '#img')
    _cursor(c[0], c[1])
                 
    if (record) {
        moved = true
        offsetx += e.clientX - startx
        offsety += e.clientY - starty
                 
        _clamp_offset()
                  
        clearTimeout(redraw_thread)
        redraw_thread = setTimeout(function() {
          draw()
        }, 10)
        startx = e.clientX
        starty = e.clientY
    }
  })
                      
  $('#img').bind('mouseup touchend', function(e) {
    e.preventDefault()
    if (moved) {
      draw()
      _recache()
      adjust()
      _plot_profiles(lastx, lasty)
      moved = false
    } else {
      if(e.originalEvent.changedTouches && e.originalEvent.changedTouches.length) e = e.originalEvent.changedTouches[0];
      var c = _clamp_z_box(_get_xy(e, '#img'))
      _plot_profiles(c[0], c[1]-2)
    }
    record = 0
  })
  
  
  // Clamp zoom box
  function _clamp_z_box(c) {
    if (c[0]+20 > $('#img').width()) c[0] = $('#img').width()-20
    if (c[1]+10 > $('#img').height()) c[1] = $('#img').height()-10
  
    return c
  }
  
  // Clamp offsets for zooming / panning
  function _clamp_offset() {
    if (offsety > 0) offsety = 0
    if (offsety < $('#img').height() - scalef*height) offsety = $('#img').height() - scalef*height
                  
    if (offsetx > 0) offsetx = 0
    if (offsetx < $('#img').width() - scalef*width) offsetx = $('#img').width() - scalef*width
  }
  
  
  $('#img').bind('touchmove', function(e) {
    if (e.originalEvent.touches && e.originalEvent.touches.length == 2) {
        x = e.originalEvent.touches[0]
        y = e.originalEvent.touches[1]
        v = Math.sqrt(Math.pow(x.pageX-y.pageX,2)+Math.pow(x.pageY-y.pageY,2))
                
        xy = []
        xy.push((x.pageX < y.pageX ? x.pageX : y.pageX) + (Math.abs(x.pageX-y.pageX)/2))
        xy.push((x.pageY < y.pageY ? x.pageY : y.pageY) + (Math.abs(x.pageY-y.pageY)/2))
                 
        if (v && lastv) zoom(xy, v > lastv ? 1 : -1)
        lastv = v
    }
                 
  })

  $('#img').bind('touchend', function(e) { lastv = 0 })
  
  // Bind mousewheel to zoom in / out
  $('#img').bind('mousewheel', function(e) {
    e.preventDefault()
    zoom(_get_xy(e, '#img'), e.originalEvent.wheelDelta)
  })
                 
  function zoom(xy, delta) {
    var last_scale = scalef

    scalef += delta > 0 ? 0.1 : -0.1
    if (scalef < ($('#img').width()/width)) scalef = $('#img').width()/width
      
    var curp = -offsetx + xy[0]
    var newp = curp*(scalef/(last_scale))
    offsetx -= newp-curp

    var curp = -offsety + xy[1]
    var newp = curp*(scalef/(last_scale))
    offsety -= newp-curp
                 
    _clamp_offset()
    if (scalef < 2) $('#zoom').slider('value', scalef*100)
    $('#zval').html((scalef*100).toFixed(0))
                 
    draw()
                 
    clearTimeout(recache_thread)
    recache_thread = setTimeout(function() {
      _recache()
      adjust()
      _plot_profiles(lastx, lasty)
    }, 200)
                 
    return false
  }
  
  
  // Bind next / previous image buttons
  $('button[name=prev]').on('click', function() {
    prev()
    return false
  })
  
  $('button[name=next]').on('click', function() {
    next()
    return false
  })
  
  
  // Bind load image on return
  $('input[name="num"]').keypress(function(e) {
    if(e.which == 13) {
        if ($(this).val() < ni) {
            change(parseInt($(this).val()))
        } else $(this).val(ni)
        
    }
  })
  
  
  // Bind image adjustments
  $('#contrast').on('slidechange', function( e, ui ) {
     contrast = $(this).slider('value')
     $('#cval').html(contrast)
     adjust()
  })
  
  $('#brightness').on('slidechange', function( e, ui ) {
     brightness = $(this).slider('value')
     $('#bval').html(brightness)
     adjust()
  })
  
  
  // Bind zoom slider
  $('#zoom').on('slidechange', function( e, ui ) {
    scalef = $(this).slider('value')/100.0
    $('#zval').html((scalef*100).toFixed(0))
    if (e.originalEvent) {
      _clamp_offset()
      draw()
    }
  })
  
  
  // Bind checkboxes
  $('input[name=res]').click(function() {
    draw()
    _recache()
    adjust()
  })

  $('input[name=ice]').click(function() {
    draw()
    _recache()
    adjust()
  })

  $('input[name=invert]').click(function() {
    adjust()
  })
  
  
  // Bind CamanJS Status
  //if (!isiPhone()) {
    Caman.Event.listen(c, 'blockFinished', function (info) {
        blocks++
        tot = $('input[name=invert]').is(':checked') ? 12 : 8
        $('.im_progress').progressbar('value', 100*(blocks/tot))
    })

    Caman.Event.listen(c, 'renderStart', function (info) {
        blocks = 0
        $('.im_progress').progressbar('value', 0)
        $('.im_progress').fadeIn(100)
    })
  //}
  
});
