<?php
    
    include_once('includes/class.sgs.php');
    
    class Page {
        var $root = '';
        var $root_link = '';
        var $require_staff = False;
        var $staff = False;
        var $visits = array();
        var $debug = False;
        var $explain = False;
        var $stats = False;
        var $profile = False;
        var $profiles = array();
        var $base;
        var $sidebar = false;
        
        var $lc_cache;
        
        var $sessionid;
        var $proposalid;
        
        function _base() {
            $rc = new ReflectionClass(get_class($this));
            return 'includes/pages/'.basename($rc->getFileName(), '.php');
        }

        
        function __construct($db, $args) {
            global $sgs;
            $this->sgs = $sgs;
            
            $this->last_profile = microtime(True);
            $this->db = $db;
            $this->db->set_debug($this->debug);
            $this->db->set_explain($this->explain);
            $this->db->set_stats($this->stats);
            
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
                        return;
                    }
                    
                # Normal page load
                } else if (array_key_exists($args[0], $this->dispatch)) {
                    $page = $args[0];
                    array_shift($args);
                }
            }
            
            $this->_parse_args($args);
            if (!$this->_auth()) return;
            
            #session_write_close();
            
            $this->log_action();
            $fn = $this->dispatch[$page];
            $this->$fn();
        }
        
        
        # ------------------------------------------------------------------------
        # Check that users have access to the pages they are trying to access
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
                
                # Make sure the visit is current (i.e. today)
                if ($this->has_arg('visit')) {
                    $rows = $this->db->pq("SELECT s.sessionid, s.beamlinename as bl, vr.run, vr.runid, TO_CHAR(s.startdate, 'YYYY') as yr FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1 AND s.startdate > TO_DATE(:2,'dd-mm-yyyy HH24:MI') AND s.enddate < TO_DATE(:3,'dd-mm-yyyy HH24:MI')+2 AND s.beamlinename LIKE :4", array($this->arg('visit'), $t, $t, $b));
                    
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
                    
                    if ($this->has_arg('prop')) {
                        $prop = $this->db->pq('SELECT p.proposalid FROM ispyb4a_db.proposal p WHERE p.proposalcode || p.proposalnumber LIKE :1', array($this->arg('prop')));
                        
                        if (sizeof($prop)) $this->proposalid = $prop[0]['PROPOSALID'];
                    }
                    
                // Normal users
                } else {
                    $rows = $this->db->pq("SELECT lower(i.visit_id) as vis from investigation@DICAT_RO i inner join investigationuser@DICAT_RO iu on i.id = iu.investigation_id inner join user_@DICAT_RO u on u.id = iu.user_id where u.name=:1", array($u));
                    
                    foreach ($rows as $row) {
                        array_push($this->visits, strtolower($row['VIS']));
                    }
                    
                    if ($this->has_arg('id') || $this->has_arg('visit') || $this->has_arg('prop')) {
                    
                        // Check user is in this visit
                        if ($this->has_arg('id')) {
                            $types = array('data' => array('datacollection', 'datacollectionid'),
                                           'edge' => array('energyscan', 'energyscanid'),
                                           'mca' => array('xfefluorescencespectrum', 'xfefluorescencespectrumid'),
                                           );
                            
                            $table = 'datacollection';
                            $col = 'datacollectionid';
                            if ($this->has_arg('t')) {
                                if (array_key_exists($this->arg('t'), $types)) {
                                    $table = $types[$this->arg('t')][0];
                                    $col = $types[$this->arg('t')][1];
                                }
                            }
                        
                            $vis = $this->db->pq('SELECT p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.'.$table.' dc ON s.sessionid = dc.sessionid WHERE dc.'.$col.' = :1', array($this->arg('id')));
                            
                            $vis = sizeof($vis) ? $vis[0]['VIS'] : '';
                        
                            
                        } else if ($this->has_arg('visit')) {
                            $vis = $this->arg('visit');
                            
                        // Check user is in this proposal
                        } else if ($this->has_arg('prop')) {
                            $viss = $this->db->pq('SELECT p.proposalid, p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE p.proposalcode || p.proposalnumber LIKE :1', array($this->arg('prop')));
                            
                            $vis = array();
                            foreach ($viss as $v) array_push($vis, $v['VIS']);
                            $this->proposalid = $viss[0]['PROPOSALID'];
                        }
                        
                        if ($this->has_arg('id') || $this->has_arg('visit')) {
                            if (in_array($vis, $this->visits)) $auth = True;
                        } else {
                            if (sizeof(array_intersect($vis, $this->visits))) $auth = True;
                        }
                        
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
                //exit();
            }
            
            return $auth;
            
        }
        
        
        # ------------------------------------------------------------------------
        # Convert input arg url to key / value pairs once checked against templates
        function _parse_args($args) {
            $temp = array();
            $len = sizeof($args);
            $len -= $len % 2;

            for ($i = 0; $i < $len; $i+= 2) {
                $temp[$args[$i]] = $args[$i+1];
            }
            
            $this->arg_list['sass'] = '\d';
            
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
            
            # Retrieve cookie args
            if (!$this->blsr() && class_exists('phpCAS')) {
                $u = phpCAS::getUser();
                if ($u && array_key_exists('ispyb_prop_'.$u, $_COOKIE) && !array_key_exists('prop', $parsed)) $parsed['prop'] = $_COOKIE['ispyb_prop_'.$u];
            }
            
            #$this->args = json_decode(json_encode($parsed), FALSE);
            $this->args = $parsed;
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
        
        
        # ------------------------------------------------------------------------
        # Templating
        
        # $this->template('page title', breadcrumb names, breadcrum links, header)
        # $this->t->variable = value; makes a variable available to template
        # $this->t->js_var('name', value); make variable available as javascript
        function template($title, $p=array(), $l=array(), $hf = 1) {
            $new = array();
            foreach ($l as $a) {
                array_push($new, $a ? ($this->root_link . $a) : $a);
            }
            
            $this->t = new Template($title, $this->nav($p, $new), $hf);
            if ($this->sidebar) $this->t->side();
            $this->t->prop = $this->has_arg('prop') ? $this->arg('prop') : '';
            $this->t->sass = $this->has_arg('sass');
            $this->t->staff = $this->staff;
        }
        
        # Shortcut to call template render
        # $this->render('templatefile')
        function render($template, $js=null) {
            $this->t->render($template, $js);
            
        }
        
        # Create navigation tree / links
        function nav($pages, $links) {
            array_unshift($pages, $this->root);
            array_unshift($links, $this->root_link);
            
            return array('p' => $pages, 'l' => $links);
        }
        
        # Template shortcut: Error page
        function error($title, $msg) {
            $this->template('Error');
            $this->t->title = 'Error: '.$title;
            $this->t->msg = $msg;
            $this->render('generic_msg');
            exit();
        }

        # Template shortcut: Message page
        function msg($title, $msg) {
            $this->template($title);
            $this->t->title = $title;
            $this->t->msg = $msg;
            $this->render('generic_msg');
            exit();
        }
        
        # ------------------------------------------------------------------------
        # Misc Helpers
        
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
            return strtotime($str.' GMT')*1000;# + ($plus ? (3600*1000) : 0);
        }
        
        
        # Get a PV
        function pv($pvs) {
            putenv('PATH=/dls_sw/epics/R3.14.11/base/bin/linux-x86_64/:$PATH');
            exec('caget ' . implode(' ', $pvs), $ret);
            $output = array();
            
            foreach ($ret as $i => $v) {
                $lis = preg_split('/\s+/', $v);
                $output[$lis[0]] = sizeof($lis) > 1 ? $lis[1] : '';
            }
            
            return $output;
        }
        
        
        # Check for trailing slash on path
        function ads($var) {
            if (!(substr($var, -1, 1) == '/')) $var .= '/';
            return $var;
        }
        
        
        function dirs($root) {
            $d = array();
            
            if (file_exists($root)) {
                foreach (scandir($root) as $f) {
                    if ($f === '.' or $f === '..') continue;
                    if (is_dir($root.'/'.$f)) array_push($d,$f);
                }
            }
            
            return $d;
        }
        
        
        # ------------------------------------------------------------------------
        # Spacegroup list in various formats
        function sg_opts() {
            $ops = '';
            foreach ($this->sgs as $s) {
                $ops .= '<option value="'.$s.'">'.$s.'</option>';
            }
            return $ops;
        }
        
        function sg_hash() {
            $hash = array();
            foreach ($this->sgs as $s) $hash[$s] = $s;
            return $hash;
        }
        
        
        # ------------------------------------------------------------------------
        # Page profiling, call with a message to log the time taken between calls
        function profile($msg) {
            if ($this->profile)
                array_push($this->profiles, $msg.': '.(microtime(True) - $this->last_profile));
            $this->last_profile = microtime(True);
        }
        
        function pro() {
            return $this->profiles;
        }
        
        
        # ------------------------------------------------------------------------
        # Beamline sample registration: Get Beamline from IP
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
        # LDAP: Return a name for a fedid
        function _get_name($fedid) {
            $src = $this->_ldap_search('uid='.$fedid);
            return $src[$fedid];
        }
        
        function _get_email($fedid) {
            $src = $this->_ldap_search('uid='.$fedid, True);
            return $src[$fedid];
        }
              

        # Run an ldap search
        function _ldap_search($search,$email=False) {
            $ret = array();
            $ds=ldap_connect("ldap.diamond.ac.uk");
            if ($ds) {
                $r=ldap_bind($ds);
                $sr=ldap_search($ds, "ou=People,dc=diamond,dc=ac,dc=uk", $search);
                $info = ldap_get_entries($ds, $sr);
                
                for ($i=0; $i<$info["count"]; $i++) {
                    if ($email) {
                        $ret[$info[$i]['uid'][0]] = array_key_exists('mail', $info[$i]) ? $info[$i]['mail'][0] : '';
                    } else $ret[$info[$i]['uid'][0]] = $info[$i]['cn'][0];
                }
                
                ldap_close($ds);                                  
            }
            return $ret;
        }
        
        
        # ------------------------------------------------------------------------
        # Set cookie for current proposal
        function cookie($val) {
            $u = phpCAS::getUser();
            if ($u) {
                setcookie('ispyb_prop_'.$u, $val, time()+31536000, '/');
            }
        }
        
        
        # ------------------------------------------------------------------------
        # Local Contact Lookup
        # Hopefully we'll get db access to this information at some point...
        function lc_lookup($sid) {
            if (!$this->lc_cache) {
                $this->lc_cache = json_decode(file_get_contents('lc_lookup.json'));
            }

            if (property_exists($this->lc_cache,$sid)) {
                return $this->lc_cache->$sid;
            }
        }
        

        # ------------------------------------------------------------------------
        # Log Action
        function log_action($act=1,$com='') {
            if(get_class($this) == 'Image') return;
            
            $action = $act ? 'LOGON' : 'LOGOFF';
            $u = class_exists('phpCAS') ? phpCAS::getUser() : '';
            
            if ($u) {
                $com = $com ? $com : 'ISPyB2: '.$_SERVER['REQUEST_URI'];
                $chk = $this->db->pq("SELECT comments FROM ispyb4a_db.adminactivity WHERE username LIKE :1", array($u));
                
                if (sizeof($chk)) {
                    $this->db->pq("UPDATE ispyb4a_db.adminactivity SET action=:1, comments=:2, datetime=SYSDATE WHERE username=:3", array($action, $com, $u));
                    
                    
                } else {
                    $this->db->pq("INSERT INTO ispyb4a_db.adminactivity (adminactivityid, username, action, comments, datetime) VALUES (s_adminactivity.nextval, :1, :2, :3, SYSDATE)", array($u, $action, $com));
                }
            }
            
            return true;
        }
        
    }


?>