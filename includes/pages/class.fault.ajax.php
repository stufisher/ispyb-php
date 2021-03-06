<?php

    require_once('Michelf/Markdown.php');
    use \Michelf\Markdown;
    
    class Ajax extends AjaxBase {
        
        var $arg_list = array('time' => '\d+',
                              'bl' => '\w\d\d(-\d)?',
                              'sid' => '\d+',
                              'cid' => '\d+',
                              'scid' => '\d+',
                              'pp' => '\d+',
                              'page' => '\d+',
                              'array' => '\d',
                              'ty' => '\w+',
                              'fid' => '\d+',
                              'name' => '[A-Za-z0-9_\- ]+',
                              'desc' => '[A-Za-z0-9_\- ]+',
                              's' => '\w+', 'id' => '\d+',
                              'term' => '\w+',
                              'visit' => '\w+\d+-\d+',
                              
                              'beamline' => '\w\d\d(-\d)?',
                              'start' => '\d\d-\d\d-\d\d\d\d \d\d:\d\d',
                              'end' => '\d\d-\d\d-\d\d\d\d \d\d:\d\d',
                              'blstart' => '\d\d-\d\d-\d\d\d\d \d\d:\d\d',
                              'blend' => '\d\d-\d\d-\d\d\d\d \d\d:\d\d',
                              'sub_component' => '\d+',
                              'beamtime_lost' => '\d',
                              'resolved' => '\d',
                              'session' => '\d+',
                              
                              'title' => '.*',
                              'desc' => '.*',
                              'resolution' => '.*',
                              
                              'assignee' => '\w+\d+',
                              );
        
        var $dispatch = array('list' => '_get_faults',
                              
                              'visits' => '_get_visits',
                              'bls' => '_get_beamlines',
                              'sys' => '_get_systems',
                              'com' => '_get_components',
                              'scom' => '_get_subcomponents',
                              
                              'add' => '_add_fault',
                              
                              'bladd' => '_add_beamline',
                              'sysadd' => '_add_system',
                              'comadd' => '_add_component',
                              'scomadd' => '_add_subcomponent',

                              'ec' => '_edit_component',
                              'dc' => '_delete_component',
                              
                              'update' => '_update_fault',
                              'load' => '_load_field',
                              
                              'names' => '_name_lookup',
                              
                              );
        var $def = 'list';
        #var $profile = True;
        #var $debug = True;
        
        # ------------------------------------------------------------------------
        # Return faults based on search terms / filters
        function _get_faults() {
            $args = array();
            $where = array();
            
            if ($this->has_arg('s')) {
                $st = sizeof($args) + 1;
                array_push($where, "(lower(f.title) LIKE lower('%'||:".$st."||'%') OR lower(f.description) LIKE lower('%'||:".($st+1)."||'%') OR lower(s.name) LIKE lower('%'||:".($st+2)."||'%') OR lower(c.name) LIKE lower('%'||:".($st+3)."||'%') OR lower(sc.name) LIKE lower('%'||:".($st+4)."||'%'))");
                for ($i = 0; $i < 5; $i++) array_push($args, $this->arg('s'));
            }
            
            $ext_columns = '';
            if ($this->has_arg('fid')) {
                array_push($where, 'f.faultid=:'.(sizeof($args) +1));
                array_push($args, $this->arg('fid'));
                $ext_columns = 'f.description, f.resolution,';
            }

            if ($this->has_arg('visit')) {
                array_push($where, "p.proposalcode||p.proposalnumber||'-'||bl.visit_number LIKE :".(sizeof($args)+1));
                array_push($args, $this->arg('visit'));
            }
            
            
            if ($this->has_arg('sid')) {
                array_push($where, 's.systemid=:'.(sizeof($args) + 1));
                array_push($args, $this->arg('sid'));
            }

            if ($this->has_arg('cid')) {
                array_push($where, 'c.componentid=:'.(sizeof($args) + 1));
                array_push($args, $this->arg('cid'));
            }
            
            if ($this->has_arg('scid')) {
                array_push($where, 'sc.subcomponentid=:'.(sizeof($args) + 1));
                array_push($args, $this->arg('scid'));
            }

            if ($this->has_arg('bl')) {
                if ($this->arg('bl') == 'P01') {
                    $bls = array();
                    foreach (array('i02', 'i03', 'i04') as $b) {
                        array_push($bls, 'bl.beamlinename LIKE :'.(sizeof($args) + 1));
                        array_push($args, $b);
                    }
                    array_push($where, '('.implode($bls, ' OR ').')');
                } else {
                    array_push($where, 'bl.beamlinename LIKE :'.(sizeof($args) + 1));
                    array_push($args, $this->arg('bl'));
                }
            }
            
            $where = implode($where, ' AND ');
            if ($where) $where = ' WHERE ' . $where;
            
            $start = 0;
            $end = 10;
            $pp = $this->has_arg('pp') ? $this->arg('pp') : 20;
            
            if ($this->has_arg('page')) {
                $pg = $this->arg('page') - 1;
                $start = $pg*$pp;
                $end = $pg*$pp+$pp;
            }
            
            $tot = $this->db->pq('SELECT count(faultid) as tot
                FROM ispyb4a_db.bf_fault f
                INNER JOIN bf_subcomponent sc ON f.subcomponentid = sc.subcomponentid
                INNER JOIN bf_component c ON sc.componentid = c.componentid
                INNER JOIN bf_system s ON c.systemid = s.systemid
                INNER JOIN blsession bl ON f.sessionid = bl.sessionid
                INNER JOIN proposal p on p.proposalid = bl.proposalid
                '.$where, $args);
            $tot = $tot[0]['TOT'];
            
            $pgs = intval($tot/$pp);
            if ($tot % $pp != 0) $pgs++;
            
            $st = sizeof($args) + 1;
            array_push($args, $start);
            array_push($args, $end);
            
            $rows = $this->db->pq("SELECT outer.*
             FROM (SELECT ROWNUM rn, inner.*
               FROM (
                SELECT $ext_columns f.faultid, f.sessionid, f.elogid, f.assignee, f.attachment, p.proposalcode || p.proposalnumber || '-' || bl.visit_number as visit, bl.beamlinename as beamline, f.owner, s.systemid, s.name as system, c.componentid, c.name as component, f.subcomponentid, sc.name as subcomponent, TO_CHAR(f.starttime, 'DD-MM-YYYY HH24:MI') as starttime, TO_CHAR(f.endtime, 'DD-MM-YYYY HH24:MI') as endtime, f.beamtimelost, round((f.beamtimelost_endtime-f.beamtimelost_starttime)*24,2) as lost, f.title, f.resolved, TO_CHAR(f.beamtimelost_endtime, 'DD-MM-YYYY HH24:MI') as beamtimelost_endtime, TO_CHAR(f.beamtimelost_starttime, 'DD-MM-YYYY HH24:MI') as beamtimelost_starttime
                FROM ispyb4a_db.bf_fault f
                INNER JOIN bf_subcomponent sc ON f.subcomponentid = sc.subcomponentid
                INNER JOIN bf_component c ON sc.componentid = c.componentid
                INNER JOIN bf_system s ON c.systemid = s.systemid
                INNER JOIN blsession bl ON f.sessionid = bl.sessionid
                INNER JOIN proposal p on p.proposalid = bl.proposalid
                $where
                ORDER BY f.starttime DESC
             
               ) inner) outer
             WHERE outer.rn > :".$st." AND outer.rn <= :".($st+1), $args);
               
            foreach ($rows as &$r) {
                $r['NAME'] = $this->_get_name($r['OWNER']);
                if ($r['ASSIGNEE']) $r['ASSIGNEENAME'] = $this->_get_name($r['ASSIGNEE']);
                foreach (array('DESCRIPTION', 'RESOLUTION') as $k) {
                    if (array_key_exists($k, $r)) {
                        #if ($r[$k]) {
                        $r[$k] = $this->db->read($r[$k]);
                        #}
                    }
                }
            }
                                  
            if ($this->has_arg('fid')) {
                if (sizeof($rows)) $this->_output($rows[0]);
                else $this->_error('No such fault');
                                  
            } else $this->_output(array($pgs, $rows));
        }

        # ------------------------------------------------------------------------
        # Return an unformatted field from the db
        function _load_field() {
            $types = array('desc' => 'description',
                           'resolution' => 'resolution',
                           );
                                  
                                  
            // Check we have a fault id
            if (!$this->has_arg('fid')) $this->_error('No fault id specified');
                                
            // Check that the fault exists
            $check = $this->db->pq('SELECT owner FROM bf_fault WHERE faultid=:1', array($this->arg('fid')));
            if (!sizeof($check)) $this->_error('A fault with that id doesnt exists');
            $check = $check[0];
                                
            //if (phpCAS::getUser() != $check['OWNER']) $this->_error('You dont own that fault report');
                                  
            if (array_key_exists($this->arg('ty'), $types)) {
                $f = $types[$this->arg('ty')];
                                  
                $rows = $this->db->pq('SELECT '.$f.' FROM ispyb4a_db.bf_fault WHERE faultid=:1', array($this->arg('fid')));
                                  
                if (!sizeof($rows)) $this->_error('No such fault id');
                                  
                $fld = $rows[0][strtoupper($f)];
                    
                print $this->db->read($fld);
                                  
            } else $this->_error('No such field type');
        }
                                  

        # ------------------------------------------------------------------------
        # Update fields for a fault
        function _update_fault() {
            $types = array( 'title' => array('\w+', 'title', '', 0),
                           
                            'starttime' => array('\d+-\d+-\d+ \d+:\d+', 'starttime', '', 1),
                            'endtime' => array('\d+-\d+-\d+ \d+:\d+', 'endtime', '', 1),
                            'btlstart' => array('\d+-\d+-\d+ \d+:\d+', 'beamtimelost_starttime', '', 1),
                            'btlend' => array('\d+-\d+-\d+ \d+:\d+', 'beamtimelost_endtime', '', 1),

                            'visit' => array('\d+', 'sessionid', "SELECT p.proposalcode || p.proposalnumber || '-' || bl.visit_number as value FROM ispyb4a_db.blsession bl INNER JOIN ispyb4a_db.proposal p ON bl.proposalid = p.proposalid WHERE bl.sessionid=:1", 0),
                           
                            'scom' => array('\d+', 'subcomponentid', 'SELECT name as value FROM bf_subcomponent WHERE subcomponentid=:1', 0),
                           
                            'btl' => array('\d+', 'beamtimelost', '', 0),
                            'res' => array('\d+', 'resolved', '', 0),
                           
                            'resolution' => array('.*', 'resolution', '', 0),
                            'desc' => array('.*', 'description', '', 0),
                            'title' => array('.*', 'title', '', 0),
                           );
                    
            // Check we have a fault id
            if (!$this->has_arg('fid')) $this->_error('No fault id specified');
                                
            // Check that the fault exists
            $check = $this->db->pq('SELECT owner,assignee FROM bf_fault WHERE faultid=:1', array($this->arg('fid')));
            if (!sizeof($check)) $this->_error('A fault with that id doesnt exists');
            $check = $check[0];
                                
            if (phpCAS::getUser() != $check['OWNER'] && phpCAS::getUser() != $check['ASSIGNEE'] && phpCAS::getUser() != 'vxn01537' && phpCAS::getUser() != 'ndg63276') $this->_error('You dont own that fault report');
                                  
            if (array_key_exists($this->arg('ty'), $types)) {
                $t = $types[$this->arg('ty')];
                $v = $_POST['value'];
                                
                // Check the value matches the template
                if (preg_match('/^'.$t[0].'$/m', $v)) {
                    $pp = array('','');

                    if ($t[3]) {
                        $pp[0] = 'TO_DATE(';
                        $pp[1] = ", 'DD-MM-YYYY HH24:MI')";
                    }
                                  
                    $this->db->pq('UPDATE bf_fault SET '.$t[1].'='.$pp[0].':1'.$pp[1].' WHERE faultid=:2', array($v, $this->arg('fid')));
                    
                    $ret = $v;
                                  
                    if ($this->arg('ty') == 'res') $ret = $v == 2 ? 'Partial' : ($v == 1 ? 'Yes' : 'No');
                    else if ($this->arg('ty') == 'btl') $ret = $v == 1 ? 'Yes' : 'No';
                                  
                    else if ($t[2]) {
                        $rets = $this->db->pq($t[2], array($v));
                        if (sizeof($rets)) $ret = $rets[0]['VALUE'];
                    }
                                  
                    if ($this->arg('ty') == 'desc' || $this->arg('ty') == 'resolution') $ret = Markdown::defaultTransform($ret);
                                  
                    print $ret;
                }
                                 
            }
        }
                                 
                                 
        # ------------------------------------------------------------------------
        # Return visits for a time on a beamline
        function _get_visits() {
            if (!$this->has_arg('time')) $this->_error('No time specified');
            if (!$this->has_arg('bl')) $this->_error('No beamline specified');
            
            #$bls = $this->db->pq('SELECT name FROM bf_beamlines WHERE beamlineid=:1', array($this->arg('bl')));
            #if (sizeof($bls)) $bl = $bls[0]['NAME'];
            #else $this->_error('No beamline with that id');
            
            $st = $this->arg('time');
            $rows = $this->db->pq("SELECT TO_CHAR(bl.startdate, 'DD-MM-YYYY HH24:MI') as stdt, bl.startdate,bl.enddate,p.proposalcode || p.proposalnumber || '-' || bl.visit_number as visit, bl.sessionid FROM ispyb4a_db.blsession bl INNER JOIN ispyb4a_db.proposal p ON p.proposalid = bl.proposalid WHERE :1 BETWEEN ((bl.startdate - TO_DATE('1970-01-01','YYYY-MM-DD')) * 86400) - 86400 AND ((bl.enddate - TO_DATE('1970-01-01','YYYY-MM-DD')) * 86400) + 86400 AND bl.beamlinename LIKE :2 AND p.proposalid != 0", array($this->arg('time'), $this->arg('bl')));

            $rows = array_merge($rows,$this->db->pq("SELECT * FROM (SELECT TO_CHAR(bl.startdate, 'DD-MM-YYYY HH24:MI') as stdt, bl.startdate,bl.enddate,p.proposalcode,p.proposalcode || p.proposalnumber || '-' || bl.visit_number as visit, bl.sessionid FROM ispyb4a_db.blsession bl INNER JOIN ispyb4a_db.proposal p ON p.proposalid = bl.proposalid WHERE bl.startdate < SYSDATE AND (p.proposalcode LIKE 'cm' OR p.proposalcode LIKE 'nt') AND bl.beamlinename LIKE :1 ORDER BY bl.startdate DESC) WHERE ROWNUM <= 10", array($this->arg('bl'))));
                                  
            //array_push($rows, array('VISIT' => 'N/A', 'SESSIONID' => -1));
                                 
            $vis = array();
            foreach ($rows as $v) $vis[$v['SESSIONID']] = $v['VISIT'] . ' ('.$v['STDT'].')';
                                 
            $this->_output($this->has_arg('array') ? $vis : $rows);
        }
        
        # ------------------------------------------------------------------------
        # Return a list of beamlines with ids
        function _get_beamlines() {            
            #$rows = $this->db->pq("SELECT distinct beamlinename as name FROM ispyb4a_db.blsession WHERE beamlinename NOT LIKE 'i04 1' ORDER BY beamlinename");
                                  
            $rows = array(array('NAME' => 'i02'), array('NAME' => 'i03'), array('NAME' => 'i04'), array('NAME' => 'i04-1'), array('NAME' => 'i24'));
                                 
            $bls = array();
            foreach ($rows as $r) $bls[$r['NAME']] = $r['NAME'];
            $this->_output($this->has_arg('array') ? $bls : $rows);
        }
        
        # ------------------------------------------------------------------------
        # Return a list of sytems for a beamline
        function _get_systems() {
            $args = array();
                                  
            if ($this->has_arg('bl')) {
                $bls = $this->arg('bl') == 'P01' ? array('i02', 'i03', 'i04') : array($this->arg('bl'));

                $blw = array();
                foreach ($bls as $b) {
                    array_push($blw, 'hs.beamlinename LIKE :'.(sizeof($args) + 1));
                    array_push($args, $b);
                }
                                  
                $where = ' WHERE ('.implode($blw, ' OR ').')';
                                  
            } else $where = '';
            
            $rows = $this->db->pq("SELECT s.systemid, s.name, s.description, string_agg(hs.beamlinename) as beamlines FROM ispyb4a_db.bf_system s INNER JOIN ispyb4a_db.bf_system_beamline hs ON s.systemid = hs.systemid ".$where." GROUP BY s.systemid, s.name, s.description ORDER BY s.name", $args);
                                 
            $sys = array();
            foreach ($rows as $s) $sys[$s['SYSTEMID']] = $s['NAME'];
                                 
            $this->_output($this->has_arg('array') ? $sys : $rows);
        }
        
        # ------------------------------------------------------------------------
        # Return a list of components for a system on a beamline
        function _get_components() {
            if (!$this->has_arg('sid')) $this->_error('No systemid specified');
            $args = array($this->arg('sid'));
            
            if ($this->has_arg('bl')) {
                $bls = $this->arg('bl') == 'P01' ? array('i02', 'i03', 'i04') : array($this->arg('bl'));

                $blw = array();
                foreach ($bls as $b) {
                    array_push($blw, 'hc.beamlinename LIKE :'.(sizeof($args) + 1));
                    array_push($args, $b);
                }
                                  
                $where = ' AND ('.implode($blw, ' OR ').')';
            } else $where = '';
            
            $rows = $this->db->pq('SELECT c.componentid, c.name, c.description, string_agg(hc.beamlinename) as beamlines FROM ispyb4a_db.bf_component c INNER JOIN ispyb4a_db.bf_component_beamline hc ON c.componentid = hc.componentid WHERE c.systemid=:1'.$where.' GROUP BY c.componentid, c.name, c.description ORDER BY beamlines,c.name', $args);
                                 
            $com = array();
            foreach ($rows as $c) $com[$c['COMPONENTID']] = $c['NAME'];
                                
            $this->_output($this->has_arg('array') ? $com : $rows);
        }
        
        # ------------------------------------------------------------------------
        # Return a list of subcomponents for a component on a beamline
        function _get_subcomponents() {
            if (!$this->has_arg('cid')) $this->_error('No componentid specified');
            $args = array($this->arg('cid'));
            
            if ($this->has_arg('bl')) {
                $bls = $this->arg('bl') == 'P01' ? array('i02', 'i03', 'i04') : array($this->arg('bl'));
                                  
                $blw = array();
                foreach ($bls as $b) {
                    array_push($blw, 'hs.beamlinename LIKE :'.(sizeof($args) + 1));
                    array_push($args, $b);
                }
                                  
                $where = ' AND ('.implode($blw, ' OR ').')';
            } else $where = '';
            
            $rows = $this->db->pq('SELECT s.subcomponentid, s.name, s.description, string_agg(hs.beamlinename) as beamlines FROM ispyb4a_db.bf_subcomponent s INNER JOIN ispyb4a_db.bf_subcomponent_beamline hs ON s.subcomponentid = hs.subcomponentid WHERE s.componentid=:1'.$where.' GROUP BY s.subcomponentid, s.name, s.description ORDER BY s.name', $args);
            
            $scom = array();
            foreach ($rows as $s) $scom[$s['SUBCOMPONENTID']] = $s['NAME'];
                                 
            $this->_output($this->has_arg('array') ? $scom : $rows);
        }
        
                       
        # Check beamline array is valid
        function check_bls($bls) {
            $br = array();
            foreach ($bls as $b) {
                if (preg_match('/^'.$this->arg_list['bl'].'$/', $b)) array_push($br, $b);
            }
                                  
            return $br;
        }

        # ------------------------------------------------------------------------
        # Add a new system
        function _add_system() {

            if (!$this->has_arg('name')) $this->_error('Please specify a system name');
                                  
            $this->db->pq('INSERT INTO ispyb4a_db.bf_system (systemid,name,description) VALUES (s_bf_system.nextval, :1, :2) RETURNING systemid INTO :id', array($this->arg('name'), $this->has_arg('desc') ? $this->arg('desc') : ''));
                            
            $sysid = $this->db->id();
            $bls = $this->check_bls($_POST['bls']);
                                  
            foreach ($bls as $b) {
                $this->db->pq('INSERT INTO ispyb4a_db.bf_system_beamline (system_beamlineid, systemid, beamlinename) VALUES (s_bf_system_beamline.nextval,:1, :2)', array($sysid, $b));
            }
                                  
            $this->_output(1);
        }
                                 
        # ------------------------------------------------------------------------
        # Add a new component
        function _add_component() {
            if (!$this->has_arg('name')) $this->_error('Please specify a component name');
            if (!$this->has_arg('sid')) $this->_error('Please specify a system id');
                                  
            $this->db->pq('INSERT INTO ispyb4a_db.bf_component (componentid, systemid,name,description) VALUES (s_bf_component.nextval, :1, :2, :3) RETURNING componentid INTO :id', array($this->arg('sid'), $this->arg('name'), $this->has_arg('desc') ? $this->arg('desc') : ''));
                            
            $sysid = $this->db->id();
            $bls = $this->check_bls($_POST['bls']);
                                  
            foreach ($bls as $b) {
                $this->db->pq('INSERT INTO ispyb4a_db.bf_component_beamline (component_beamlineid, componentid, beamlinename) VALUES (s_bf_component_beamline.nextval,:1, :2)', array($sysid, $b));
            }
                                  
            $this->_output(1);                                  
        }
                                 
        # ------------------------------------------------------------------------
        # Add a new subcomponent
        function _add_subcomponent() {
            if (!$this->has_arg('name')) $this->_error('Please specify a subcomponent name');
            if (!$this->has_arg('cid')) $this->_error('Please specify a component id');
                                  
            $this->db->pq('INSERT INTO ispyb4a_db.bf_subcomponent (subcomponentid, componentid,name,description) VALUES (s_bf_subcomponent.nextval, :1, :2, :3) RETURNING subcomponentid INTO :id', array($this->arg('cid'), $this->arg('name'), $this->has_arg('desc') ? $this->arg('desc') : ''));
                            
            $sysid = $this->db->id();
            $bls = $this->check_bls($_POST['bls']);
                                  
            foreach ($bls as $b) {
                $this->db->pq('INSERT INTO ispyb4a_db.bf_subcomponent_beamline (subcomponent_beamlineid, subcomponentid, beamlinename) VALUES (s_bf_subcomponent_beamline.nextval,:1, :2)', array($sysid, $b));
            }
                                  
            $this->_output(1);    
        }
                                 
                                 
        # ------------------------------------------------------------------------
        # Delete a row
        function _delete_component() {
            $this->_output($_POST);
        
        }

        # ------------------------------------------------------------------------
        # Edit a row
        function _edit_component() {  
            if (!$this->has_arg('id')) $this->_error('No id specified');
            if (!$this->has_arg('ty')) $this->_error('No type specified');
            if (!$this->has_arg('name')) $this->_error('No name specified');
                                  
            $types = array('systems' => array('system'), 'components' => array('component'), 'subcomponents' => array('subcomponent'));
            if (!array_key_exists($this->arg('ty'), $types)) $this->_error('That type doesnt exists');
                                 
            $ty = $types[$this->arg('ty')];
                                  
            $check = $this->db->pq('SELECT '.$ty[0].'id as id FROM bf_'.$ty[0].' WHERE '.$ty[0].'id=:1', array($this->arg('id')));
            if (!sizeof($check)) $this->_error('That id doesnt exist');
                                  
            $desc = $this->has_arg('desc') ? $this->arg('desc') : '';
                                  
            $bls = $this->check_bls($_POST['bls']);
            $bl_temp = $this->db->pq('SELECT '.$ty[0].'_beamlineid as id,beamlinename FROM bf_'.$ty[0].'_beamline WHERE '.$ty[0].'id=:1', array($this->arg('id')));
            $bl_old = array();
            foreach ($bl_temp as $b) $bl_old[$b['BEAMLINENAME']] = $b['ID'];
                                  
            $rem = array_values(array_diff(array_keys($bl_old), $bls));
            $add = array_values(array_diff($bls, array_keys($bl_old)));
                            
                                  
            foreach ($rem as $r) {
                $this->db->pq('DELETE FROM bf_'.$ty[0].'_beamline WHERE '.$ty[0].'_beamlineid=:1', array($bl_old[$r]));
            }
                                  
            foreach ($add as $a) {
                $this->db->pq('INSERT INTO bf_'.$ty[0].'_beamline ('.$ty[0].'_beamlineid, '.$ty[0].'id, beamlinename) VALUES (s_bf_'.$ty[0].'_beamline.nextval, :1, :2)', array($this->arg('id'), $a));
            }
                                  
            $this->db->pq('UPDATE bf_'.$ty[0].' SET name=:1, description=:2 WHERE '.$ty[0].'id=:3', array($this->arg('name'), $desc, $this->arg('id')));
                                  
            $this->_output(1);
        }
                                  
                                  
        # ------------------------------------------------------------------------
        # Return feed of fedid / name combinations for search
        function _name_lookup() {
            if (!$this->has_arg('term')) $this->_error('No name specified');
                                  
            $vals = array();
            # |(cn=*'.$this->arg('term').'*)(uid=*'.$this->arg('term').'*)
            foreach ($this->_ldap_search('cn=*'.$this->arg('term').'*') as $fid => $n) {
                array_push($vals, array('label' => $n, 'value' => $fid));
            }
            $this->_output($vals);
                                  
        }

                                  
        # ------------------------------------------------------------------------
        # Add fault via ajax
        function _add_fault() {
            $valid = True;
            foreach (array('title', 'desc', 'session', 'start', 'beamtime_lost', 'resolved') as $f) {
                if (!$this->has_arg($f)) $valid = False;
            }
            
            if (!$valid) $this->_error('Missing Fields');
            
            $btlstart = $this->has_arg('blstart') ? $this->arg('blstart') : '';
            $btlend = $this->has_arg('blend') ? $this->arg('blend') : '';
            $end = $this->has_arg('end') ? $this->arg('end') : '';
            $as = $this->has_arg('assignee') ? $this->arg('assignee') : '';
            
            $this->db->pq("INSERT INTO bf_fault (faultid, sessionid, owner, subcomponentid, starttime, endtime, beamtimelost, beamtimelost_starttime, beamtimelost_endtime, title, description, resolved, resolution, assignee) VALUES (s_bf_fault.nextval, :1, :2, :3, TO_DATE(:4, 'DD-MM-YYYY HH24:MI'), TO_DATE(:5, 'DD-MM-YYYY HH24:MI'), :6, TO_DATE(:7, 'DD-MM-YYYY HH24:MI'), TO_DATE(:8, 'DD-MM-YYYY HH24:MI'), :9, :10, :11, :12, :13) RETURNING faultid INTO :id", array($this->arg('session'), phpCAS::getUser(), $this->arg('sub_component'), $this->arg('start'), $end, $this->arg('beamtime_lost'), $btlstart, $btlend, $this->arg('title'), $this->arg('desc'), $this->arg('resolved'), $this->arg('resolution'), $as));
                    
            $newid = $this->db->id();

            $info = $this->db->pq("SELECT p.proposalcode || p.proposalnumber || '-' || bl.visit_number as visit, s.name as system, c.name as component, sc.name as subcomponent, TO_CHAR(f.starttime, 'DD-MM-YYYY HH24:MI') as starttime, TO_CHAR(f.endtime, 'DD-MM-YYYY HH24:MI') as endtime, f.beamtimelost, round((f.beamtimelost_endtime-f.beamtimelost_starttime)*24,2) as lost, f.title, f.resolved, f.resolution, f.description, TO_CHAR(f.beamtimelost_endtime, 'DD-MM-YYYY HH24:MI') as beamtimelost_endtime, TO_CHAR(f.beamtimelost_starttime, 'DD-MM-YYYY HH24:MI') as beamtimelost_starttime, f.owner
                FROM ispyb4a_db.bf_fault f
                INNER JOIN bf_subcomponent sc ON f.subcomponentid = sc.subcomponentid
                INNER JOIN bf_component c ON sc.componentid = c.componentid
                INNER JOIN bf_system s ON c.systemid = s.systemid
                INNER JOIN blsession bl ON f.sessionid = bl.sessionid
                INNER JOIN proposal p ON bl.proposalid = p.proposalid

                WHERE f.faultid=:1", array($newid));
            
            $info = $info[0];
                                
            foreach (array('DESCRIPTION', 'RESOLUTION') as $k) {
                #if ($info[$k]) {
                    #$info[$k] = Markdown::defaultTransform($info[$k]->read($info[$k]->size()));
                $info[$k] = $this->db->read($info[$k]);
                #}
            }
                                  
            $report = '<b>'.$info['TITLE'].'</b><br/><br/>Reported By: '.$this->_get_name($info['OWNER']).'<br/><br/>System: '.$info['SYSTEM'].'<br/>Component: '.$info['COMPONENT'].' &raquo; '.$info['SUBCOMPONENT'].'<br/><br/>Start: '.$info['STARTTIME'].' End: '.($info['RESOLVED'] == 1 ? $info['ENDTIME'] : 'N/A') .'<br/>Resolved: '.($info['RESOLVED']  == 2 ? 'Partial' : ($info['RESOLVED'] ? 'Yes' : 'No')).'<br/>Beamtime Lost: '.($info['BEAMTIMELOST'] ? ('Yes ('.$info['LOST'].'h between '.$info['BEAMTIMELOST_STARTTIME'].' and '.$info['BEAMTIMELOST_ENDTIME'].')') : 'No').'<br/><br/><b>Description</b><br/>'.$info['DESCRIPTION'].'<br/><br/>'.($info['RESOLVED'] ? ('<b>Resolution</b><br/>'.$info['RESOLUTION']):'').'<br/><br/><a href="http://ispyb.diamond.ac.uk/fault/fid/'.$this->db->id().'">Fault Report Link</a>';
                                  
            $data = array('txtTITLE'      => 'Fault Report: '. $info['TITLE'],
                          'txtCONTENT'    => $report,
                          'txtLOGBOOKID'  =>'BL'.strtoupper
($this->arg('beamline')),
                          'txtGROUPID'    => 'GEN',
                          'txtENTRYTYPEID'=> '41',
                          'txtUSERID'     => phpCAS::getUser(),
                          'txtMANUALAUTO' => 'M',
                          );
            
            
            if ($_FILES['userfile1']['name']) {
                move_uploaded_file($_FILES['userfile1']['tmp_name'], '/tmp/fault_'.strtolower($_FILES['userfile1']['name']));
                $data['userfile1'] = '@/tmp/fault_'.strtolower($_FILES['userfile1']['name']);
            }
            
            $ch = curl_init('http://rdb.pri.diamond.ac.uk/php/elog/cs_logentryext_bl.php');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            
            if (preg_match('/New Log Entry ID:(\d+)/', $response, $eid)) {
                $this->db->pq('UPDATE ispyb4a_db.bf_fault SET elogid=:1 WHERE faultid=:2', array($eid[1], $newid));
            }
                                  
            if (preg_match('/Attachment Id:(\d+)/', $response, $aid)) {
                $this->db->pq('UPDATE ispyb4a_db.bf_fault SET attachment=:1 WHERE faultid=:2', array($aid[1].'-fault_'.strtolower($_FILES['userfile1']['name']), $newid));
            }

                                  
            $this->_output(array('FAULTID' => $newid));
        }
                                  
    }

?>