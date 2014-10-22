<?php

class ProposalType {
    
    var $table;
    var $col;
    
    var $generic_pages = array('proposal', 'fault', 'cal', 'feedback');
    
    var $visit_table;
    var $session_column;
    
    var $pages = array();
    var $prop_menu = array();
    var $ext_menu =  array();
    var $ext_admin = array();
    
    var $staff = False;
    var $visits = array();
    var $sessionids = array();
    var $proposalid;
    
    var $default = '';
    var $dir = '';
    
    function __construct($db, $parts) {
        $this->db = $db;
        $this->parts = $parts;
    }
    
    
    // Work out what type of proposal we are in
    function get_type() {
        global $prop_types, $bl_types, $blsr, $bcr;
        
        
        // default to use (none)
        $ty = '';
        
        
        // check if there is a visit in the address args
        if (in_array('visit', $this->parts)) {
            $idx = array_search('visit', $this->parts);
            if ($idx+1 < sizeof($this->parts)) {
                $vis = $this->parts[$idx+1];

                if (preg_match('/([A-z]+)\d+-\d+/', $vis, $m)) {
                    $bl = $this->db->pq("SELECT s.beamlinename FROM blsession s INNER JOIN proposal p ON p.proposalid = s.proposalid WHERE p.proposalcode||p.proposalnumber||'-'||s.visit_number LIKE :1", array($m[0]));
                    
                    if (sizeof($bl)) {
                        $bl = $bl[0]['BEAMLINENAME'];
                        foreach ($bl_types as $tty => $bls) {
                            if (in_array($bl, $bls)) {
                                $ty = $tty;
                                break;
                            }
                        }
                    }
                }
            }
            
            
        // check if its blsr or bcr machine for mx
        } else if (in_array($_SERVER['REMOTE_ADDR'], $blsr) || in_array($_SERVER['REMOTE_ADDR'], $bcr)) {
            $ty = 'mx';
            
            
        // check cookie
        } else {
            if (class_exists('phpCAS')) {
                $u = phpCAS::getUser();
                if (array_key_exists('ispyb_prop_'.$u, $_COOKIE)) {
                    $prop = $_COOKIE['ispyb_prop_'.$u];
                    if (preg_match('/([A-z]+)\d+/', $prop, $m)) {
                        $prop_code = $m[1];
                        
                        // See if proposal code matches list in config
                        $found = False;
                        foreach ($prop_types as $pty) {
                            if ($prop_code == $pty) {
                                $ty = $pty;
                                $found = True;
                            }
                        }
                        
                        // Proposal code didnt match, work out what beamline the visits are on
                        if (!$found) {
                            $bls = $this->db->pq("SELECT s.beamlinename FROM blsession s INNER JOIN proposal p ON p.proposalid = s.proposalid WHERE p.proposalcode||p.proposalnumber LIKE :1", array($m[0]));
                            
                            if (sizeof($bls)) {
                                foreach ($bls as $bl) {
                                    $b = $bl['BEAMLINENAME'];
                                    foreach ($bl_types as $tty => $bls) {
                                        if (in_array($b, $bls)) {
                                            $ty = $tty;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }


        
        // Load specific proposal type
        if ($ty && file_exists('includes/class.type.'.$ty.'.php')) {
            include_once('includes/class.type.'.$ty.'.php');
            $type_class = strtoupper($ty);
            $tyc = new $type_class($this->db, $this->parts);
            $tyc->dispatch();
            
            
        // Generic Pages
        } else if (in_array($this->parts[0], $this->generic_pages)) {
            $this->dispatch();
            
            
        // Default to show proposals list
        } else {
            $class = 'includes/pages/class.proposal.php';
            array_shift($this->parts);
            include_once($class);
            $pg = new Proposal($this->db, $this->parts, $this);
        }
    }
    
    
    
    // Dispatch to correct class based on args
    function dispatch() {
        if (in_array($this->parts[0], array_merge($this->pages, $this->generic_pages))) {
            $page = $this->parts[0];
            array_shift($this->parts);
        } else {
            $page = $this->default;
        }
        
        // Exception for proposal / visits page which is global to all proposals
        if (in_array($page, $this->generic_pages)) {
            $class = 'includes/pages/class.'.$page.'.php';
            
        // Otherwise load proposal specific files
        } else {
            $class = 'includes/pages/'.$this->dir.'/class.'.$page.'.php';
        }
        

        
        if (in_array($page, array_merge($this->pages, $this->generic_pages)) && file_exists($class)) {
            include_once($class);
            $cn = ucfirst($page);
            $pg = new $cn($this->db, $this->parts, $this);
            
        } else {
            # 404 here
            
        }
    }
    
    
    function auth($require_staff, $parent) {
        $u = class_exists('phpCAS') ? phpCAS::getUser() : '';
        $groups = explode(' ', exec('groups ' . $u));
        $this->staff = in_array('mx_staff', $groups) ? True : False;
        if (!$this->staff && in_array('dls_dasc', $groups)) $this->staff = True;
        //if (!$this->staff && in_array('b21_staff', $groups)) $this->staff = True;
        //if (!$this->staff && in_array('i11_staff', $groups)) $this->staff = True;
        
        return True;
    }
    
    
    function msg($title, $msg) {
        $this->t = new Template('Access Denied', array('l' => array(), 'p' => array()), True);
        $this->t->set_type($this);
        $this->t->side();
        $this->t->prop = '';
        $this->t->staff = $this->staff;
        $this->t->title = $title;
        $this->t->msg = $msg;
        $this->t->render('generic_msg');
    }
    
    
    # ------------------------------------------------------------------------
    # Nice interface to args
    function has_arg($key) {
        return array_key_exists($key, $this->args);
    }
    
    function arg($key) {
        if (!$this->has_arg($key)) new Exception();
        return $this->args[$key];
    }
    
    function set_args($args) {
        $this->args = $args;
    }
    
    function is_staff() {
        return $this->staff;
    }
    
    function pid() {
        return $this->proposalid;
    }
}
    
?>
