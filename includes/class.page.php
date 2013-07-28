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

        
        function __construct($db, $args) {
            $this->last_profile = microtime(True);
            $this->db = $db;
            $this->db->set_debug($this->debug);
            
            $page = $this->def;
            if (sizeof($args) > 0) {
                if (array_key_exists($args[0], $this->dispatch)) {
                    $page = $args[0];
                    array_shift($args);
                }
            }
            
            $this->_parse_args($args);
            $this->_auth();
            
            $fn = $this->dispatch[$page];
            $this->$fn();
        }
        
        
        function profile($msg) {
            if ($this->profile)
                array_push($this->profiles, $msg.': '.(microtime(True) - $this->last_profile));
            $this->last_profile = microtime(True);
        }
        
        
        function _auth() {
            global $icat;
            
            $u = phpCAS::getUser();
            $groups = explode(' ', exec('groups ' . $u));
            $this->staff = in_array('mx_staff', $groups) ? True : False;
            if (!$this->staff && in_array('dls_dasc', $groups)) $this->staff = True;

        
            // Staff only pages
            if ($this->require_staff) {
                $auth = $this->staff;
            } else {
                $auth = False;
                
                // Registered visit or staff
                if ($this->staff) {
                    $auth = True;
                } else {
                    $q = 'SELECT inv.visit_id as vis FROM investigation inv INNER JOIN investigator ir ON (ir.investigation_id=inv.id) INNER JOIN facility_user fu ON (fu.facility_user_id = ir.facility_user_id)  WHERE fu.federal_id LIKE \''.$u.'\'';
                    
                    $db = new Oracle($icat['user'], $icat['pass'], $icat['db']);
                    $rows = $db->q($q);

                    #$rows = $this->db->q('SELECT lower(i.visit_id) from investigation@dicat i inner join investigationuser@dicat iu on i.id = iu.investigation_id inner join user_@dicat u on u.id = iu.user_id where u.name='.$u);
                    
                    foreach ($rows as $row) {
                        array_push($this->visits, strtolower($row['VIS']));
                    }
                    
                    #print_r($this->visits);
                    
                    if ($this->has_arg('id') || $this->has_arg('visit')) {
                    
                        // Check user is in this visit
                        if ($this->has_arg('id')) {
                            $vis = strtoupper($this->db->q('SELECT p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.datacollection dc ON s.sessionid = dc.sessionid WHERE dc.datacollectionid ='.$this->arg('id'))[0]['VIS']);
                            
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
            
            # End execution, show not authed page template
            if (!$auth) {
                $this->template('Access Denied');
                $this->render('no_access');
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
                    if (preg_match('/^'.$v.'$/', $temp[$k])) {
                        $parsed[$k] = $temp[$k];
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
        
        
        function template($title, $p=array(), $l=array()) {
            $new = array();
            foreach ($l as $a) {
                array_push($new, $a ? ($this->root_link . $a) : $a);
            }
            
            $this->t = new Template($title, $this->nav($p, $new));
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
        
        // Unix time to javascript timestamp
        function jst($str, $plus=True) {
            return strtotime($str)*1000 + ($plus ? (3600*1000) : 0);
        }
        
        function pro() {
            return $this->profiles;
        }
        
        // Get a PV
        function pv($pvid) {
            $ret = exec('caget ' . $pvid);
            return preg_split('/\s+/', $ret)[1];
        }
        
        
        // Check for trailing slash on path
        function ads($var) {
            if (!(substr($var, -1, 1) == '/')) $var .= '/';
            return $var;
        }
        
        
    }


?>