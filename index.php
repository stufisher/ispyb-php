<?php
    
    require_once('config.php');
    
    require_once 'CAS/CAS.php';
    phpCAS::client(CAS_VERSION_2_0, 'auth.diamond.ac.uk', 443, '/cas');
    phpCAS::setNoCasServerValidation();
    phpCAS::forceAuthentication();
    
    $parts = explode('/', $_SERVER['REQUEST_URI']);
    array_shift($parts);

    date_default_timezone_set('Europe/London');
    
    include_once('includes/class.page.php');
    include_once('includes/class.db.php');
    include_once('includes/class.template.php');
    
    $db = new Oracle($isb['user'], $isb['pass'], $isb['db']);
    
    $pages = array(
                   #'ajax' => array('Ajax', ''),
                   'image' => array('Image', ''),
                   'robot' => array('Robot', 'Robot Statistics'),
                   'dc' => array('DC', 'Data Collections'),
                   'samples' => array('Samples', 'Sample Creation'),
                   'fault' => array('Fault', 'Fault Logging'),
                   'vstat' => array('Visit', 'Visit Statistics'),
                   'log' => array('Log', 'Visit Summary'),
                   'status' => array('Status', 'Beamline Status'),
                   );
    
    if (array_key_exists($parts[0], $pages)) {
        $page = $parts[0];
        array_shift($parts);
    } else {
        $page = 'log';
    }
    
    $class = 'includes/pages/class.'.$page.'.php';
    if (array_key_exists($page, $pages) && file_exists($class)) {
        include_once($class);
        $pg = new $pages[$page][0]($db, $parts);
        
    } else {
        # 404 here
        
    }
    
?>
