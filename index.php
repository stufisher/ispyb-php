<?php
    
    require_once('config.php');
    
    # Url is parsed into a series of arguments, arguments take the form /name/value
    $parts = explode('/', $_SERVER['REQUEST_URI']);
    array_shift($parts);
    
    
    # Work around to allow beamline sample registration without CAS authentication
    # For use on the touchscreen computers in the hutch
    if (sizeof($parts) >= 2) {
        if (($parts[0] == 'samples' && ($parts[1] == 'bl' || $parts[1] == 'ajax') && in_array($_SERVER["REMOTE_ADDR"], $blsr))) {
            
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
    
    
    # New pages need to be added to this array in order for them to be
    # parsed
    $pages = array(
                   'image',
                   'download',
                   'pdf',
                   'robot',
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
