$(function() {

  $('.peaks').hide()
  //$('.image_container .image').height(600)
  $('#glmol01').height(600)
  

  var glmol01 = new GLmol('glmol01', true);
  glmol01.repressZoom = true
  glmol01.maps = []
  var chains = {}
  var symmat = true
  
  $('input[name="symmat"]').attr('checked', 'checked').change(function(e) {
    e.preventDefault()
    symmat = $(this).is(':checked')
    glmol01.rebuildScene()
    updateMesh()
    glmol01.show()
  })
  
  function download() {
    $.ajax({url: '/download/map/pdb/1/ty/'+ty+'/id/'+id,
            type: 'GET',
            xhr: function() {
              var xhr = new window.XMLHttpRequest();
              xhr.addEventListener('progress', function(e) {
                $('.status_bar').html('Downloading Model ' + ((e.loaded/e.total)*100).toFixed(0) + '%')
              })
              return xhr
            },
          
            success:function(ret) {
              $("#glmol01_src").val(ret);
              glmol01.loadMolecule();
              _generate_chains()
              _load_maps()
            }
   })
  }

  
  function _get_sym_ops(callback) {
    var p = glmol01.protein
  
    $.ajax({url: '/dc/ajax/sym/a/'+p.a+'/b/'+p.b+'/c/'+p.c+'/al/'+p.alpha+'/be/'+p.beta+'/ga/'+p.gamma+'/sg/'+p.spacegroup.replace(/\s+/g, ''),
            type: 'GET',
            xhr: function() {
              var xhr = new window.XMLHttpRequest();
              xhr.addEventListener('progress', function(e) {
                $('.status_bar').html('Generating Symmetry Mates ' + ((e.loaded/e.total)*100).toFixed(0) + '%')
              })
              return xhr
            },
          
            success:function(ops) {
              $.each(ops, function(m,o) {
                if (glmol01.protein.symMat[m] == undefined) glmol01.protein.symMat[m] = new THREE.Matrix4().identity();
                $.each(o, function(n,e) {
                  glmol01.protein.symMat[m].elements[n] = e[0]
                  glmol01.protein.symMat[m].elements[n + 4] = e[1]
                  glmol01.protein.symMat[m].elements[n + 8] = e[2]
                  glmol01.protein.symMat[m].elements[n + 12] = e[3]
                })
              })
           
              if (callback) callback()
            }
   })
  }
  

  function _load_maps() {
    var xhr = new XMLHttpRequest();
    xhr.onload = function() {
      var gunzip = new Zlib.Gunzip(new Uint8Array(this.response));
      var plain = gunzip.decompress();
      parseCCP4(plain.buffer, 0x5555aa, '2fofc', 1.5);
  
       if (ty == 'dimple') {
         var xhr2 = new XMLHttpRequest();
         xhr2.onload = function() {
           var gunzip = new Zlib.Gunzip(new Uint8Array(this.response));
           var plain = gunzip.decompress();
           parseCCP4(plain.buffer, 0x33cc33, 'fofc', 2.8);
           parseCCP4(plain.buffer, 0xcc3333, 'fofc', -2.8);
  
           _get_sym_ops(function() {
             glmol01.rebuildScene();
             glmol01.rotationGroup.position.z = -80;
  
             _load_peaks()
             $('.status_bar').hide()
           })
         };
  
         xhr2.addEventListener('progress', function(e) {
           $('.status_bar').html('Downloading Map 2 ' + ((e.loaded/e.total)*100).toFixed(0) + '%')
         })
         xhr2.open('GET', '/download/map/ty/'+ty+'/id/'+id+'/map/2');
         xhr2.responseType = 'arraybuffer';
         xhr2.send();
       } else {
          _get_sym_ops(function() {
            glmol01.rebuildScene();
            glmol01.rotationGroup.position.z = -80;
            _goto_residue()
            $('.status_bar').hide()
          })
       }
    }
  
    xhr.addEventListener('progress', function(e) {
     $('.status_bar').html('Downloading Map 1 ' + ((e.loaded/e.total)*100).toFixed(0) + '%')
    })
  
    xhr.open('GET', '/download/map/ty/'+ty+'/id/'+id);
    xhr.responseType = 'arraybuffer';
    xhr.send();
  }
  
  
  function _generate_chains() {
    chains = {}
    $.each(glmol01.atoms, function(i,a) {
      if (a) {
        if (a.chain == ' ') a.chain = 'A'
        if (!(a.chain in chains)) chains[a.chain] = {}
        if (!(a.resi in chains[a.chain])) chains[a.chain][a.resi] = {name: a.resn, atoms: {}}
           
        chains[a.chain][a.resi].atoms[a.atom] = a
      }
    })

    $('select[name=chain]').empty()
    $.each(chains, function(n,c) {
      $('<option value="'+n+'">'+n+'</option>').appendTo($('select[name=chain]'))
    })
  
    _refresh_residues()
  }
  
  $('select[name=chain]').change(function() {
    _refresh_residues()
  })
               
  function _refresh_residues() {
    var c = $('select[name=chain]').val()
  
    $('select[name=residue]').empty()
      if (c in chains) {
        $.each(chains[c], function(i, r) {
          $('<option value="'+i+'">'+i+' '+r.name+'</option>').appendTo($('select[name=residue]'))
        })
      }
  }
  
  $('select[name=residue]').change(function() { _goto_residue() })
  
  //.button({ icons: { primary: 'ui-icon-carat-1-e' } }).
  $('button[name=next]').click(function() { _goto_next() })
  //.button({ icons: { primary: 'ui-icon-carat-1-w' } })
  $('button[name=previous]').click(function() { _goto_next(1) })
  
  function _goto_next(prev) {
    var cur = $('select[name=residue]').val()
    var idx = $('select[name=residue] option').index($('select[name=residue] option[value="'+cur+'"]'))
    var nidx = prev ? (idx - 1) : (idx + 1)
    var val = $('select[name=residue] option').eq(nidx).val()
    $('select[name=residue]').val(val).trigger('change')
  }
  
  $('a.fullscreen').click(function() {
    var element = $('#map_model')[0]
    if(element.requestFullscreen) {
      element.requestFullscreen();
    } else if(element.mozRequestFullScreen) {
      element.mozRequestFullScreen();
    } else if(element.webkitRequestFullscreen) {
      element.webkitRequestFullscreen();
    } else if(element.msRequestFullscreen) {
      element.msRequestFullscreen();
    }
    return false
  })
  
                                  
  function _goto_residue() { 
    var c = $('select[name=chain]').val()
    var r = $('select[name=residue]').val()
                                  
    if (c in chains) {
        var ch = chains[c]
        if (r in ch) {
            var res = ch[r]
  
            if ('CA' in res.atoms) atom = res.atoms['CA']
            else {
                $.each(res.atoms, function(i,a){
                    atom = a
                    return false
                })
            }
  
            glmol01.rotationGroup.position.z = -110
            glmol01.modelGroup.position.x = -atom.x;
            glmol01.modelGroup.position.y = -atom.y;
            glmol01.modelGroup.position.z = -atom.z;

            updateMesh();
            glmol01.show();
        }
    }
  }

  
  $('.maps .m1').slider({
      step: 0.1,
      value: 1.5,
      min: 0.5,
      max: 3,
      slide: function( event, ui ) {
        _map_sigma(0, ui.value)
        $(this).siblings('span').html(ui.value)
      }
  });
  
  if (ty == 'dimple') {
    $('.maps').append('<span class="wrap">Map 2: <span class="value">2.8</span>&sigma;<div class="m2"></div></span>')
    $('.maps .m2').slider({
      step: 0.1,
      value: 2.8,
      min: 2,
      max: 6,
      slide: function( event, ui ) {
        _map_sigma(1, ui.value)
        _map_sigma(2, -ui.value)
        $(this).siblings('span').html(ui.value)
      }
    });
  }
  
  
  function _load_peaks() {
    $.ajax({
      url: '/dc/ajax/dp/id/'+id,
      type: 'GET',
      timeout: 5000,
      success: function(json) {
        $('.peaks table tbody').empty()
           
        $.each(json, function(i, t) {
          if (t['TYPE'] == 'Dimple') {
            $.each(t['PKLIST'], function(i,pk) {
              $('<tr><td>'+pk[0]+'</td><td>'+pk[1]+'</td><td>'+pk[2]+'</td><td>'+pk[3]+'</td></tr>').appendTo($('.peaks table tbody'))
            })
          }
        })
           
        $('.peaks tbody tr').unbind('click').click(function(e) {
          _goto_peak($('.peaks tbody tr').index($(this)))
        })
           
        if ($('.peaks table tbody tr').length) {
           $('.peaks').show()
           _goto_peak(0)
        } else _goto_residue()
      }
    })
  }
  
  
  function _goto_peak(num) {
    var r = $('.peaks table tbody tr').eq(num)
    glmol01.modelGroup.position.x = -parseFloat($('td', r).eq(0).html());
    glmol01.modelGroup.position.y = -parseFloat($('td', r).eq(1).html());
    glmol01.modelGroup.position.z = -parseFloat($('td', r).eq(2).html());

    updateMesh();
    glmol01.show();
  }
  
  function _map_sigma(id, sigma) {
    var m = glmol01.maps[id]
    var abs = m.map_header.AMEAN + sigma * m.map_header.ARMS
    m.mc.generateGeometry(m.mc.cc, m.mc.cr, m.mc.cs, ty == 'dimple' ? 15 : 50, abs);
  
  }
  

  function parseCCP4(data, color, name, sig) {
    var t = new Date();
    var header_int = new Int32Array(data, 0, 56);
    var header_float = new Float32Array(data, 0, 56);
    var map_header = {};
    map_header.NC = header_int[0];
    map_header.NR = header_int[1];
    map_header.NS = header_int[2];
    map_header.NCSTART = header_int[4];
    map_header.NRSTART = header_int[5];
    map_header.NSSTART = header_int[6];
    map_header.NX = header_int[7];
    map_header.NY = header_int[8];
    map_header.NZ = header_int[9];
    map_header.a = header_float[10];
    map_header.b = header_float[11];
    map_header.c = header_float[12];
    map_header.alpha = header_float[13];
    map_header.beta = header_float[14];
    map_header.gamma = header_float[15];
    map_header.MAPC = header_int[16];
    map_header.MAPR = header_int[17];
    map_header.MAPS = header_int[18];
    map_header.ISPG = header_int[22];
    map_header.NSYMBT = header_int[23];
    map_header.AMEAN = header_float[21];
    map_header.ARMS = header_float[54];

    var map = {name: name}
  
    map.map_header = map_header;
    map.map_data = new Float32Array(data, 256 * 4 + map_header.NSYMBT, map_header.NC * map_header.NR * map_header.NS);

    map.mc = new THREE.MarchingCubes(map.map_data,
                  map_header.NC, map_header.NR, map_header.NS,
                  map_header.NCSTART, map_header.NRSTART, map_header.NSSTART);
    var geo = map.mc.generateGeometry(0, 0, 0, 0, map_header.AMEAN + (sig ? sig : 1.5) * map_header.ARMS); // dummy

    var mesh = new THREE.Line(geo, new THREE.LineBasicMaterial({color: (color ? color : 0x5555AA), linewidth: 1}));
    mesh.type = THREE.LinePieces;

    var basis_a = [map_header.a, 0, 0];
    var basis_b = [map_header.b * Math.cos(Math.PI / 180.0 * map_header.gamma),
              map_header.b * Math.sin(Math.PI / 180.0 * map_header.gamma),
              0];
    var basis_c = [map_header.c * Math.cos(Math.PI / 180.0 * map_header.beta),
              map_header.c * (Math.cos(Math.PI / 180.0 * map_header.alpha)
               - Math.cos(Math.PI / 180.0 * map_header.gamma) 
               * Math.cos(Math.PI / 180.0 * map_header.beta))
               / Math.sin(Math.PI / 180.0 * map_header.gamma), 0];
    basis_c[2] = Math.sqrt(map_header.c * map_header.c * Math.sin(Math.PI / 180.0 * map_header.beta)
               * Math.sin(Math.PI / 180.0 * map_header.beta) - basis_c[1] * basis_c[1]);

    var basis = [0, basis_a, basis_b, basis_c];
    var nxyz = [0, map_header.NX, map_header.NY, map_header.NZ];
    var mapcrs = [0, map_header.MAPC, map_header.MAPR, map_header.MAPS];

    mesh.matrix.set(basis[mapcrs[1]][0] / nxyz[mapcrs[1]], basis[mapcrs[2]][0] / nxyz[mapcrs[2]], basis[mapcrs[3]][0] / nxyz[mapcrs[3]], 0,
                   basis[mapcrs[1]][1] / nxyz[mapcrs[1]], basis[mapcrs[2]][1] / nxyz[mapcrs[2]], basis[mapcrs[3]][1] / nxyz[mapcrs[3]], 0,
                   basis[mapcrs[1]][2] / nxyz[mapcrs[1]], basis[mapcrs[2]][2] / nxyz[mapcrs[2]], basis[mapcrs[3]][2] / nxyz[mapcrs[3]], 0,
                   0, 0, 0, 1);

    mesh.matrixAutoUpdate = false;

    map.mesh = mesh;
    console.log("Generate map mesh: ", +new Date() - t);

    glmol01.maps.push(map)
  }


  function defineRepFromController() {
    var idHeader = "#" + this.id + '_';

    var all = this.getAllAtoms();
    if (ty == 'dimple') {
      this.colorByAtom(all, {});
      var asu = new THREE.Object3D();
      this.drawBondsAsLine(asu, all, this.lineWidth * 2);
      if (symmat) this.drawSymmetryMatesWithTranslation2(this.modelGroup, asu, this.protein.symMat);
      this.modelGroup.add(asu)
  
    }
    this.drawUnitcell(this.modelGroup);

    var nonBonded = this.getNonbonded(all);
    this.drawAsCross(this.modelGroup, nonBonded, 0.3, true);

    $.each(glmol01.maps, function(i,m) {
      glmol01.modelGroup.add(m.mesh);
    })
  
    this.slabNear = -8; this.slabFar = 8;
   //updateMesh();
  }

  glmol01.defineRepresentation = defineRepFromController;

  glmol01.current = 1;
  glmol01.translate_callback = function() {
    updateMesh()
    glmol01.show()
  }

  function updateMesh() {
    $.each(glmol01.maps, function(i,m) {
      var ortho_to_frac = new THREE.Matrix4().getInverse(m.mesh.matrix);
      var center = ortho_to_frac.multiplyVector3(glmol01.modelGroup.position.clone());
      var mc = m.mc

      var geo = mc.generateGeometry(Math.floor(-center.x) - mc.ncstart,
                        Math.floor(-center.y) - mc.nrstart,
                        Math.floor(-center.z) - mc.nsstart, ty == 'dimple' ? 15 : 50, mc.isol);
           
      console.log('map', i, center, geo)
    })
  }

  
  $('body').bind('keydown', function(ev) {
    var keyCode = ev.keyCode;

    if (keyCode == 32) {
      _goto_next(ev.shiftKey)
    }
    glmol01.show();

    return false;
  });

  download();
  
  
})