$(function() {

  $('.image_container .image').height(600) //$(window).height()*0.65)
  
  $('#glmol01').width($('.image_container .image').width())
  $('#glmol01').height($('.image_container .image').height())
  
  
var glmol01 = new GLmol('glmol01', true);
glmol01.maps = []
  
$('#loading').hide();

function download() {
   $('#loading').show();
   $.get('/download/map/pdb/1/ty/'+ty+'/id/'+id, function(ret) {
      $("#glmol01_src").val(ret);
      glmol01.loadMolecule();
   })

   var xhr = new XMLHttpRequest();
   xhr.onload = function() {
      $('#loading').hide();
      var gunzip = new Zlib.Gunzip(new Uint8Array(this.response));
      var plain = gunzip.decompress();
      parseCCP4(plain.buffer, 0x5555AA);
   };
   xhr.open('GET', '/download/map/ty/'+ty+'/id/'+id);
   xhr.responseType = 'arraybuffer';
   xhr.send();
  
   /*if (ty == 'dimple') {
     var xhr2 = new XMLHttpRequest();
     xhr2.onload = function() {
       $('#loading').hide();
       var gunzip = new Zlib.Gunzip(new Uint8Array(this.response));
       var plain = gunzip.decompress();
       parseCCP4(plain.buffer, 0x33CC33, 3);
     };
     xhr2.open('GET', '/download/map/ty/'+ty+'/id/'+id+'/map/2');
     xhr2.responseType = 'arraybuffer';
     xhr2.send();
   }*/
}


function parseCCP4(data, color, sig) {
   var t = new Date();   
   header_int = new Int32Array(data, 0, 56);
   header_float = new Float32Array(data, 0, 56);
   map_header = {};
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

   var map = {}
  
   map.map_header = map_header;
   map.map_data = new Float32Array(data, 256 * 4 + map_header.NSYMBT, map_header.NC * map_header.NR * map_header.NS);

   map.mc = new THREE.MarchingCubes(map.map_data,
                  map_header.NC, map_header.NR, map_header.NS,
                  map_header.NCSTART, map_header.NRSTART, map_header.NSSTART);
   geo = map.mc.generateGeometry(0, 0, 0, 0, map_header.AMEAN + (sig ? sig : 1.5) * map_header.ARMS); // dummy

   mesh = new THREE.Line(geo, new THREE.LineBasicMaterial({color: (color ? color : 0x5555AA), linewidth: 1}));
   mesh.type = THREE.LinePieces;

   basis_a = [map_header.a, 0, 0];
   basis_b = [map_header.b * Math.cos(Math.PI / 180.0 * map_header.gamma),
              map_header.b * Math.sin(Math.PI / 180.0 * map_header.gamma),
              0];
   basis_c = [map_header.c * Math.cos(Math.PI / 180.0 * map_header.beta),
              map_header.c * (Math.cos(Math.PI / 180.0 * map_header.alpha)
               - Math.cos(Math.PI / 180.0 * map_header.gamma) 
               * Math.cos(Math.PI / 180.0 * map_header.beta))
               / Math.sin(Math.PI / 180.0 * map_header.gamma), 0];
   basis_c[2] = Math.sqrt(map_header.c * map_header.c * Math.sin(Math.PI / 180.0 * map_header.beta)
               * Math.sin(Math.PI / 180.0 * map_header.beta) - basis_c[1] * basis_c[1]);

   basis = [0, basis_a, basis_b, basis_c];
   nxyz = [0, map_header.NX, map_header.NY, map_header.NZ];
   mapcrs = [0, map_header.MAPC, map_header.MAPR, map_header.MAPS];

   mesh.matrix.set(basis[mapcrs[1]][0] / nxyz[mapcrs[1]], basis[mapcrs[2]][0] / nxyz[mapcrs[2]], basis[mapcrs[3]][0] / nxyz[mapcrs[3]], 0,
                   basis[mapcrs[1]][1] / nxyz[mapcrs[1]], basis[mapcrs[2]][1] / nxyz[mapcrs[2]], basis[mapcrs[3]][1] / nxyz[mapcrs[3]], 0,
                   basis[mapcrs[1]][2] / nxyz[mapcrs[1]],  basis[mapcrs[2]][2] / nxyz[mapcrs[2]], basis[mapcrs[3]][2] / nxyz[mapcrs[3]], 0,
                   0, 0, 0, 1);

  mesh.matrixAutoUpdate = false;

  map.mesh = mesh;
  console.log("Generate map mesh: ", +new Date() - t);

  glmol01.maps.push(map)
  
  glmol01.rebuildScene();
  glmol01.rotationGroup.position.z = -80; // dirty hack!
  gotoNext(1);
}

function saveImage() {
   glmol01.show(); // this is necessary for WebKit based browser. cf. preserveDrawingBuffer
   var imageURI = glmol01.renderer.domElement.toDataURL("image/png");
   window.open(imageURI);
}

$('#glmol01_reload').click(function(ev) {
   glmol01.rebuildScene();
   glmol01.show();
});

function defineRepFromController() {
   var idHeader = "#" + this.id + '_';

   var all = this.getAllAtoms();
   this.colorByAtom(all, {});  
   this.drawBondsAsLine(this.modelGroup, all, this.lineWidth * 2);
   this.drawUnitcell(this.modelGroup);

   var nonBonded = this.getNonbonded(all);
   this.drawAsCross(this.modelGroup, nonBonded, 0.3, true);

  
   $.each(glmol01.maps, function(i,m) {
     glmol01.modelGroup.add(m.mesh);
   })
  
  console.log('models', glmol01.modelGroup.children)
  // if (glmol01.mesh) glmol01.modelGroup.add(glmol01.mesh);

   this.slabNear = -8; this.slabFar = 8;
   updateMesh();
};

glmol01.defineRepresentation = defineRepFromController;

glmol01.current = 1;
glmol01.translate_callback = updateMesh

function updateMesh() {
  //if (!glmol01.mesh) return; // no map
  
  $.each(glmol01.maps, function(i,m) {
    var ortho_to_frac = new THREE.Matrix4().getInverse(m.mesh.matrix);
    var center = ortho_to_frac.multiplyVector3(glmol01.modelGroup.position.clone());
    var mc = m.mc;
    mc.generateGeometry(Math.floor(-center.x) - mc.ncstart,
                     Math.floor(-center.y) - mc.nrstart,
                     Math.floor(-center.z) - mc.nsstart, 10, mc.isol);
  })
}

function gotoNext(delta) {
   var resi = glmol01.atoms[glmol01.current].resi, i = glmol01.current, ilim = glmol01.atoms.length;

   while (true) {
      i += delta;
      if (i == 0) i = ilim - 1;
      if (i == ilim) i = 1;
      if (glmol01.atoms[i] == undefined) continue;
      if (glmol01.atoms[i].resi == resi) continue;
      break;
   }
   glmol01.current = i;
   glmol01.modelGroup.position.x = -glmol01.atoms[i].x;
   glmol01.modelGroup.position.y = -glmol01.atoms[i].y;
   glmol01.modelGroup.position.z = -glmol01.atoms[i].z;

   updateMesh();
   glmol01.show();
}

//$(glmol01.renderer.domElement).bind('keydown', function(ev) {
$('body').bind('keydown', function(ev) {
   var keyCode = ev.keyCode;
   var mc = glmol01.maps[0].mc;

   if (!mc) return;
   if (keyCode == 38) {
      mc.generateGeometry(mc.cc, mc.cr, mc.cs, 10, glmol01.mc.isol + 0.1 * glmol01.map_header.ARMS);
   } else if (keyCode == 40) {
      mc.generateGeometry(mc.cc, mc.cr, mc.cs, 10, glmol01.mc.isol - 0.1 * glmol01.map_header.ARMS);
   } else if (keyCode == 32) {
      gotoNext((ev.shiftKey) ? -1 : 1);
   }
   glmol01.show();

   return false;
});

download();
  
  
})