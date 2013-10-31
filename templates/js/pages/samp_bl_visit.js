var ref = null

$(window).blur(function () {
  if (ref) {
    console.log('clearning interval', ref)
    clearInterval(ref)
    ref = null
  }
}).focus(function () {
    _start()
})

$(document).on('pageinit', '#visits', function() {
    _start()
})

function _start() {
    console.log('starting refresh')
    if (ref) clearInterval(ref)
    ref = setInterval(function() { $.mobile.changePage(window.location.href, { reloadPage: true }) }, 300000)
}