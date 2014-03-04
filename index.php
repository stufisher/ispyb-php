<?php
    
    //if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) ob_start("ob_gzhandler");
    //else ob_start();
    
    require_once('OracleSession.php');
    $handler = new OracleSessionHandler();
    session_set_save_handler(
                             array($handler, '_open'),
                             array($handler, '_close'),
                             array($handler, '_read'),
                             array($handler, '_write'),
                             array($handler, '_destroy'),
                             array($handler, '_gc')
                             );
    
    // the following prevents unexpected effects when using objects as save handlers
    register_shutdown_function('session_write_close');
    
    require_once('config.php');
    
    # Url is parsed into a series of arguments, arguments take the form /name/value
    $parts = explode('/', $_SERVER['REQUEST_URI']);
    array_shift($parts);
    
    
    # Work around to allow beamline sample registration without CAS authentication
    # For use on the touchscreen computers in the hutch
    if (sizeof($parts) >= 2) {
        if (($parts[0] == 'samples' && ($parts[1] == 'bl' || $parts[1] == 'ajax') && in_array($_SERVER["REMOTE_ADDR"], $blsr)) || ($parts[0] == 'cal' && $parts[1] == 'ics' && $parts[2] == 'h')) {
            
        } else {
            require_once 'CAS/CAS.php';
            phpCAS::client(CAS_VERSION_2_0, 'auth.diamond.ac.uk', 443, '/cas');
            phpCAS::setNoCasServerValidation();
            phpCAS::forceAuthentication();
        }
        
        
    # Allow barcode reader unauthorised access, same as above, certain IPs only
    } else if (sizeof($parts) == 1) {
        if ($parts[0] == 'tracking' && in_array($_SERVER["REMOTE_ADDR"], $bcr)) {
            
        } else {
            require_once 'CAS/CAS.php';
            phpCAS::client(CAS_VERSION_2_0, 'auth.diamond.ac.uk', 443, '/cas');
            phpCAS::setNoCasServerValidation();
            phpCAS::forceAuthentication();
        }
        
    } else {
        require_once 'CAS/CAS.php';
        phpCAS::client(CAS_VERSION_2_0, 'auth.diamond.ac.uk', 443, '/cas');
        phpCAS::setNoCasServerValidation();
        phpCAS::forceAuthentication();
    }

    date_default_timezone_set('Europe/London');
    
    include_once('includes/class.page.php');
    include_once('includes/class.db.php');
    include_once('includes/class.template.php');
    
    $db = new Oracle($isb['user'], $isb['pass'], $isb['db']);
    register_shutdown_function(array(&$db, '__destruct'));
    
    if ($parts[0] == 'logout') phpCAS::logout();
    
    # New pages need to be added to this array in order for them to be
    # parsed
    $pages = array(
                   'image',
                   'download',
                   'pdf',
                   'robot',
                   'cal',
                   'dc',
                   'mc',
                   'samples',
                   'fault',
                   'vstat',
                   'log',
                   'status',
                   'cell',
                   'proposal',
                   'shipment',
                   'sample',
                   'contact',
                   'feedback',
                   'projects',
                   'tracking',
                   'stats',
                   );
    
    # Classes for each page, file is all lower case, the actual class
    # name is the same with the first character capitialised.
    if (in_array($parts[0], $pages)) {
        $page = $parts[0];
        array_shift($parts);
    } else {
        $page = 'log';
    }
    
    $class = 'includes/pages/class.'.$page.'.php';
    if (in_array($page, $pages) && file_exists($class)) {
        include_once($class);
        $cn = ucfirst($page);
        $pg = new $cn($db, $parts);

    } else {
        # 404 here
        
    }
    
?>
