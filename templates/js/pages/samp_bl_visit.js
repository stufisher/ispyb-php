$(document).on('pageinit', '#visits', function() {
    setTimeout(function() { $.mobile.changePage(window.location.href, { reloadPage: true }) }, 300000)
})