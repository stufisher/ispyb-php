<?php
    
    class Page {
        var $root = '';
        var $root_link = '';
        var $require_staff = False;
        var $staff = False;
        var $visits = array();
        var $debug = False;
        var $profile = False;
        var $profiles = [];
        var $base;
        
        function _base() {
            $rc = new ReflectionClass(get_class($this));
            return 'includes/pages/'.basename($rc->getFileName(), '.php');
        }

        
        function __construct($db, $args) {
            $this->last_profile = microtime(True);
            $this->db = $db;
            $this->db->set_debug($this->debug);
            
            $page = $this->def;
            if (sizeof($args) > 0) {
                # Redirect Ajax Requests to relevent file
                if ($args[0] == 'ajax') {
                    $aj = $this->_base().'.ajax.php';
                    if (file_exists($aj)) {
                        array_shift($args);
                        
                        include_once('class.ajax.php');
                        include_once($aj);
                        
                        $ajax = new Ajax($db, $args);
                        exit(1);
                    }
                    
                # Normal page load
                } else if (array_key_exists($args[0], $this->dispatch)) {
                    $page = $args[0];
                    array_shift($args);
                }
            }
            
            $this->_parse_args($args);
            $this->_auth();
            
            session_write_close();
            
            $fn = $this->dispatch[$page];
            $this->$fn();
        }
        
        
        function profile($msg) {
            if ($this->profile)
                array_push($this->profiles, $msg.': '.(microtime(True) - $this->last_profile));
            $this->last_profile = microtime(True);
        }
        
        
        function _auth() {
            $u = class_exists('phpCAS') ? phpCAS::getUser() : '';
            
            $groups = explode(' ', exec('groups ' . $u));
            $this->staff = in_array('mx_staff', $groups) ? True : False;
            if (!$this->staff && in_array('dls_dasc', $groups)) $this->staff = True;
            #if (!$this->staff && in_array('dls_sysadmin', $groups)) $this->staff = True;
            
            // Staff only pages
            if ($this->require_staff) {
                $auth = $this->staff;

                
            // Beamline Sample Registration
            } else if ($this->blsr() && !$u) {                
                $auth = false;
                $b = $this->ip2bl();
                $t = strtoupper(date('d-m-Y 08:59'));
                
                if ($this->has_arg('visit')) {
                    $rows = $this->db->pq("SELECT s.sessionid, s.beamlinename as bl, vr.run, vr.runid, TO_CHAR(s.startdate, 'YYYY') as yr FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1 AND s.startdate > TO_DATE(:2,'dd-mm-yyyy HH24:MI') AND s.enddate < TO_DATE(:3,'dd-mm-yyyy HH24:MI')+2 AND s.beamlinename LIKE :4", array($this->arg('visit'), $t, $t, $b));

                    #$rows = $this->db->pq("SELECT s.sessionid, s.beamlinename as bl, vr.run, vr.runid, TO_CHAR(s.startdate, 'YYYY') as yr FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1 AND s.beamlinename LIKE :2", array($this->arg('visit'), $b));
                    
                    if (sizeof($rows)) $auth = true;
                    
                } else {
                    $auth = true;
                }
            
            // Normal validation
            } else {
                $auth = False;
                
                // Registered visit or staff
                if ($this->staff) {
                    $auth = True;
                    
                // Normal users
                } else {
                    $rows = $this->db->pq("SELECT lower(i.visit_id) as vis from investigation@DICAT_RO i inner join investigationuser@DICAT_RO iu on i.id = iu.investigation_id inner join user_@DICAT_RO u on u.id = iu.user_id where u.name=:1", array($u));
                    
                    foreach ($rows as $row) {
                        array_push($this->visits, strtolower($row['VIS']));
                    }
                    
                    if ($this->has_arg('id') || $this->has_arg('visit')) {
                    
                        // Check user is in this visit
                        if ($this->has_arg('id')) {
                            $vis = strtoupper($this->db->pq('SELECT p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.datacollection dc ON s.sessionid = dc.sessionid WHERE dc.datacollectionid = :1', array($this->arg('id')))[0]['VIS']);
                            
                        } else if ($this->has_arg('visit')) {
                            $vis = $this->arg('visit');
                        }
                        
                        if (in_array($vis, $this->visits)) $auth = True;
                        
                    // No id or visit, anyone ok to view
                    } else {
                        $auth = True;
                    }
                }
            }
            
            // End execution, show not authed page template
            if (!$auth) {
                $this->template('Access Denied');
                $this->t->title = 'Access Denied';
                $this->t->msg = 'You dont have access to that page';
                $this->render('generic_msg');
                exit();
            }
            
        }
        
        
        # Convert input arg url to key / value pairs once checked against templates
        function _parse_args($args) {
            $temp = array();
            $len = sizeof($args);
            $len -= $len % 2;

            for ($i = 0; $i < $len; $i+= 2) {
                $temp[$args[$i]] = $args[$i+1];
            }
            
            $parsed = array();
            foreach ($this->arg_list as $k => $v) {
                if (array_key_exists($k, $temp)) {
                    if (preg_match('/^'.$v.'$/m', $temp[$k])) {
                        $parsed[$k] = $temp[$k];
                    }
                }
                
            }
            
            # Append _GET args when not on url
            $pg = array_merge($_GET, $_POST);
            foreach ($this->arg_list as $k => $v) {
                if (!array_key_exists($k, $parsed)) {
                    if (array_key_exists($k, $pg)) {
                    
                        if (preg_match('/^'.$v.'$/m', $pg[$k])) {
                            $parsed[$k] = $pg[$k];
                        }
                    }
                }
            }
            
            #$this->args = json_decode(json_encode($parsed), FALSE);
            $this->args = $parsed;
        }
        
        # Nice interface to args
        function has_arg($key) {
            return array_key_exists($key, $this->args);
        }
        
        function arg($key) {
            if (!$this->has_arg($key)) new Exception();
            return $this->args[$key];
        }
        
        
        # Create navigation tree / links
        function nav($pages, $links) {
            array_unshift($pages, $this->root);
            array_unshift($links, $this->root_link);
            
            return array('p' => $pages, 'l' => $links);
        }
        
        
        function template($title, $p=array(), $l=array(), $hf = 1) {
            $new = array();
            foreach ($l as $a) {
                array_push($new, $a ? ($this->root_link . $a) : $a);
            }
            
            $this->t = new Template($title, $this->nav($p, $new), $hf);
            $this->t->staff = $this->staff;
        }
        
        function render($template, $js=null) {
            $this->t->render($template, $js);
            
        }
        
        
        # Pretty-ish printer
        function p($array) {
            if ($this->debug) {
                print '<h1 class="debug">Debug</h1><pre>';
                print_r($array);
                print '</pre>';
            }
        }
        
        # Unix time to javascript timestamp
        function jst($str, $plus=True) {
            return strtotime($str)*1000;# + ($plus ? (3600*1000) : 0);
        }
        
        function pro() {
            return $this->profiles;
        }
        
        # Get a PV
        function pv($pvid) {
            $ret = exec('caget ' . $pvid);
            return preg_split('/\s+/', $ret)[1];
        }
        
        
        # Check for trailing slash on path
        function ads($var) {
            if (!(substr($var, -1, 1) == '/')) $var .= '/';
            return $var;
        }
        
        
        # Error page
        function error($title, $msg) {
            $this->template('Error');
            $this->t->title = 'Error: '.$title;
            $this->t->msg = $msg;
            $this->render('generic_msg');
            exit();
        }

        # Message page
        function msg($title, $msg) {
            $this->template($title);
            $this->t->title = $title;
            $this->t->msg = $msg;
            $this->render('generic_msg');
            exit();
        }
        
        function dirs($root) {
            $d = array();
            foreach (scandir($root) as $f) {
                if ($f === '.' or $f === '..') continue;
                if (is_dir($root.'/'.$f)) array_push($d,$f);
            }
            
            return $d;
        }
        
        
        # Get Beamline from IP
        function ip2bl() {
            $parts = explode('.', $_SERVER['REMOTE_ADDR']);
            $bls = array(103 => 'i03',
                         146 => 'i03',
                         104 => 'i04',
                         102 => 'i02',
                         73 => 'i04-1',
                         124 => 'i24');
            
            if (array_key_exists($parts[2], $bls)) {
                return $bls[$parts[2]];
            }
        }
        
        # Beamline Sample Registration Machine
        function blsr() {
            global $blsr;
            
            return in_array($_SERVER['REMOTE_ADDR'], $blsr);
        }
        
        # ------------------------------------------------------------------------
        # Return a name for a fedid
        function _get_name($fedid) {
            return $this->_ldap_search('uid='.$fedid)[$fedid];
        }
              
        # ------------------------------------------------------------------------
        # Run an ldap search
        function _ldap_search($search) {
            $ret = array();
            $ds=ldap_connect("ldap.diamond.ac.uk");
            if ($ds) {
                $r=ldap_bind($ds);
                $sr=ldap_search($ds, "ou=People,dc=diamond,dc=ac,dc=uk", $search);
                $info = ldap_get_entries($ds, $sr);
                                  
                for ($i=0; $i<$info["count"]; $i++) {
                    $ret[$info[$i]['uid'][0]] = $info[$i]['cn'][0];
                }
                
                ldap_close($ds);                                  
            }
            return $ret;
        }        
        
    }


?>