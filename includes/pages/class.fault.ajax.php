<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('time' => '\d+', 'bl' => '\w\d\d(-\d)?', 'sid' => '\d+', 'cid' => '\d+', 'scid' => '\d+', 'pp' => '\d+', 'page' => '\d+', 'array' => '\d', 'ty' => '\w+', 'fid' => '\d+', 'name' => '[A-Za-z0-9_\- ]+', 'desc' => '[A-Za-z0-9_\- ]+', 's' => '\w+');
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
                              
                              );
        var $def = 'list';
        var $profile = True;
        #var $debug = True;
        
        # ------------------------------------------------------------------------
        # Return faults based on search terms / filters
        function _get_faults() {
            $args = array();
            $where = array();
            
            if ($this->has_arg('s')) {
                $st = sizeof($args) + 1;
                array_push($where, "(f.title LIKE '%'||:".$st."||'%' OR f.description LIKE '%'||:".($st+1)."||'%')");
                array_push($args, $this->arg('s'));
                array_push($args, $this->arg('s'));
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
                array_push($where, 'bl.beamlinename LIKE :'.(sizeof($args) + 1));
                array_push($args, $this->arg('bl'));
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
                '.$where, $args)[0]['TOT'];
            
            $pgs = intval($tot/$pp);
            if ($tot % $pp != 0) $pgs++;
            
            $st = sizeof($args) + 1;
            array_push($args, $start);
            array_push($args, $end);
            
            $rows = $this->db->pq("SELECT outer.*
             FROM (SELECT ROWNUM rn, inner.*
               FROM (
                SELECT f.faultid, f.sessionid, bl.visit_number as visit, p.proposalcode || p.proposalnumber as bag, bl.beamlinename as beamline, f.owner, s.systemid, s.name as system, c.componentid, c.name as component, f.subcomponentid, sc.name as subcomponent, TO_CHAR(f.starttime, 'DD-MM-YYYY HH24:MI') as starttime, f.endtime, f.beamtimelost, round((f.beamtimelost_endtime-f.beamtimelost_starttime)*24,2) as lost, f.title, f.resolved
                FROM ispyb4a_db.bf_fault f
                INNER JOIN bf_subcomponent sc ON f.subcomponentid = sc.subcomponentid
                INNER JOIN bf_component c ON sc.componentid = c.componentid
                INNER JOIN bf_system s ON c.systemid = s.systemid
                INNER JOIN blsession bl ON f.sessionid = bl.sessionid
                INNER JOIN proposal p on p.proposalid = bl.proposalid
                $where
                ORDER BY f.faultid DESC
             
               ) inner) outer
             WHERE outer.rn > :".$st." AND outer.rn <= :".($st+1), $args);
                                 
            $this->_output(array($pgs, $rows));
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
                           );
                    
            // Check we have a fault id
            if (!$this->has_arg('fid')) $this->_error('No fault id specified');
                                
            // Check that the fault exists
            $check = $this->db->pq('SELECT faultid FROM bf_fault WHERE faultid=:1', array($this->arg('fid')));
            if (!sizeof($check)) $this->_error('A fault with that id doesnt exists');
                                
            if (array_key_exists($this->arg('ty'), $types)) {
                $t = $types[$this->arg('ty')];
                $v = $_POST['value'];
                                
                // Check the value matches the template
                if (preg_match('/^'.$t[0].'$/', $v)) {
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
                    print $ret;
                }
                                 
            }
        }
                                 
                                 
        # ------------------------------------------------------------------------
        # Return visits for a time on a beamline
        function _get_visits() {
            if (!$this->has_arg('time')) $this->_error('No time specified');
            if (!$this->has_arg('bl')) $this->_error('No beamline specified');
            
            #$bls = $this->db->q('SELECT name FROM bf_beamlines WHERE beamlineid=:1', array($this->arg('bl')));
            #if (sizeof($bls)) $bl = $bls[0]['NAME'];
            #else $this->_error('No beamline with that id');
            
            $st = $this->arg('time');
            $rows = $this->db->pq("SELECT bl.startdate,bl.enddate,p.proposalcode || p.proposalnumber || '-' || bl.visit_number as visit, bl.sessionid FROM ispyb4a_db.blsession bl INNER JOIN ispyb4a_db.proposal p ON p.proposalid = bl.proposalid WHERE :1 BETWEEN (bl.startdate - TO_DATE('1970-01-01','YYYY-MM-DD')) * 86400 AND (bl.enddate - TO_DATE('1970-01-01','YYYY-MM-DD')) * 86400 AND bl.beamlinename LIKE :2 AND p.proposalid != 0", array($this->arg('time'), $this->arg('bl')));

            $rows = array_merge($rows,$this->db->pq("SELECT * FROM (SELECT bl.startdate,bl.enddate,p.proposalcode || p.proposalnumber || '-' || bl.visit_number as visit, bl.sessionid FROM ispyb4a_db.blsession bl INNER JOIN ispyb4a_db.proposal p ON p.proposalid = bl.proposalid WHERE bl.startdate < SYSDATE AND (p.proposalcode LIKE 'cm' OR p.proposalcode LIKE 'nt') AND bl.beamlinename LIKE :1 ORDER BY bl.startdate DESC) WHERE ROWNUM <= 10", array($this->arg('bl'))));
                                  
            //array_push($rows, array('VISIT' => 'N/A', 'SESSIONID' => -1));
                                 
            $vis = array();
            foreach ($rows as $v) $vis[$v['SESSIONID']] = $v['VISIT'];
                                 
            $this->_output($this->has_arg('array') ? $vis : $rows);
        }
        
        # ------------------------------------------------------------------------
        # Return a list of beamlines with ids
        function _get_beamlines() {            
            #$rows = $this->db->q("SELECT distinct beamlinename as name FROM ispyb4a_db.blsession WHERE beamlinename NOT LIKE 'i04 1' ORDER BY beamlinename");
                                  
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
                $where = ' WHERE hs.beamlinename=:1';
                array_push($args, $this->arg('bl'));
                                  
            } else $where = '';
            
            $rows = $this->db->pq("SELECT s.systemid, s.name, s.description, string_agg(hs.beamlinename) as beamlines FROM ispyb4a_db.bf_system s INNER JOIN ispyb4a_db.bf_system_beamline hs ON s.systemid = hs.systemid ".$where." GROUP BY s.systemid, s.name, s.description", $args);
                                 
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
                $where = ' AND hc.beamlinename=:2';
                array_push($args, $this->arg('bl'));
            } else $where = '';
            
            $rows = $this->db->pq('SELECT c.componentid, c.name, c.description, string_agg(hc.beamlinename) as beamlines FROM ispyb4a_db.bf_component c INNER JOIN ispyb4a_db.bf_component_beamline hc ON c.componentid = hc.componentid WHERE c.systemid=:1'.$where.' GROUP BY c.componentid, c.name, c.description', $args);
                                 
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
                $where = ' AND hs.beamlinename=:2';
                array_push($args, $this->arg('bl'));
            } else $where = '';
            
            $rows = $this->db->pq('SELECT s.subcomponentid, s.name, s.description, string_agg(hs.beamlinename) as beamlines FROM ispyb4a_db.bf_subcomponent s INNER JOIN ispyb4a_db.bf_subcomponent_beamline hs ON s.subcomponentid = hs.subcomponentid WHERE s.componentid=:1'.$where.' GROUP BY s.subcomponentid, s.name, s.description', $args);
            
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
            $this->_output($_POST);                                 
        }
                                 
    }

?>