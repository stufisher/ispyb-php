<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('time' => '\d+', 'bl' => '\d+', 'sid' => '\d+', 'cid' => '\d+', 'scid' => '\d+', 'pp' => '\d+', 'page' => '\d+', 'array' => '\d', 'ty' => '\w+', 'fid' => '\d+');
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
        //var $debug = True;
        
        # ------------------------------------------------------------------------
        # Return faults based on search terms / filters
        function _get_faults() {
            $args = array();
            $where = array();
            $where = implode($where, ' AND ');
            
            $start = 0;
            $end = 10;
            $pp = $this->has_arg('pp') ? $this->arg('pp') : 20;
            
            if ($this->has_arg('page')) {
                $pg = $this->arg('page') - 1;
                $start = $pg*$pp;
                $end = $pg*$pp+$pp;
            }
            
            $tot = 80;//$this->db->q('SELECT count(faultid) as tot FROM ispyb4a_db.bf_faults '.$where, $args)[0]['TOT'];
            
            
            $pgs = intval($tot/$pp);
            if ($tot % $pp != 0) $pgs++;
            
            $this->_output(array(4, array(
                array('FAULTID' => 1, 'BLSESSIONID' => 12, 'BEAMLINEID' => 1, 'BEAMLINE' => 'i03', 'OWNER' => 'vxn01537', 'SYSTEMID' => 1, 'SYSTEM' => 'EPICS', 'COMPONENTID' => 1, 'COMPONENT' => 'Scintilator', 'SUBCOMPONENTID' => 1, 'SUBCOMPONENT' => 'x', 'STARTTIME' => '01-08-2013 11:08', 'BEAMTIMELOST' => 1, 'LOST' => 1.3, 'TITLE' => 'Scintilator lost home position', 'RESOLVED' => 1),
                ))
            );
            return;
            
            array_unshift($args, $start);
            array_unshift($args, $end);
            
            $rows = $this->db->pq("SELECT outer.*
             FROM (SELECT ROWNUM rn, inner.*
               FROM (
                SELECT f.faultid, f.blsessionid, f.beamlineid, bl.name as beamline f.owner, f.systemid, s.name as system f.componentid, c.name as component, f.subcomponentid, sc.name as subcomponent, f.starttime, f.endtime, f.beamtimelost, (f.beamtimelost_endtime-f.beamtimelost_starttime)*24 as lost, f.title, f.resolved
                FROM ispyb4a_db.bf_faults f
                INNER JOIN bf_beamline bl ON f.beamlineid = bl.beamlineid
                INNER JOIN bf_system s ON f.systemid = s.systemid
                INNER JOIN bf_component c ON f.systemid = c.componentid
                LEFT JOIN bf_subcomponent sc ON f.subcomponentid = sc.subcomponentid
                $where
                ORDER BY f.faultid DESC
             
               ) inner) outer
             WHERE outer.rn > :1 AND outer.rn <= :2", $args);
                                 
            $this->_output(array($pgs, $rows));
        }
        

        # ------------------------------------------------------------------------
        # Update fields for a fault
        function _update_fault() {
            $types = array( 'title' => array('\w+', 'title', ''),
                           
                            'starttime' => array('\d+-\d+-\d+ \d+:\d+', 'starttime', ''),
                            'endtime' => array('\d+-\d+-\d+ \d+:\d+', 'endtime', ''),
                           
                            'bl' => array('\d+', 'beamlineid', 'SELECT name as value FROM bf_beamlines WHERE beamlineid='),
                            'sys' => array('\d+', 'systemid', 'SELECT name as value FROM bf_systems WHERE systemid='),
                            'com' => array('\d+', 'componentid', 'SELECT name as value FROM bf_components WHERE componentid='),
                            'scom' => array('\d+', 'subcomponentid', 'SELECT name as value FROM bf_subcomponents WHERE subcomponentid='),
                           
                            'btl' => array('\d+', 'beamtimelost', 'SELECT name as value FROM bf_subcomponents WHERE subcomponentid='),
                            'res' => array('\d+', 'resolved', 'SELECT name as value FROM bf_subcomponents WHERE subcomponentid='),
                           );
                    
            // Check we have a fault id
            if (!$this->has_arg('fid')) $this->_error('No fault id specified');
                                
            // Check that the fault exists
            $check = $this->db->pq('SELECT faultid FROM bf_faults WHERE faultid=:1', array($this->arg('fid')));
            if (!sizeof($check)) $this->_error('A fault with that id doesnt exists');
                                
            if (array_key_exists($this->arg('ty'))) {
                $t = $types[$this->arg('ty')];
                $v = $_POST['value'];
                                
                // Check the value matches the template
                if (preg_match('/^'.$t[0].'$/', $v)) {
                    $this->db->pq('UPDATE bf_faults SET :1=:2 WHERE faultid=:3', array($t[1], $v, $this->arg('fid')));
                    
                    $ret = $v;
                                 
                    if ($t[2]) {
                        $rets = $this->db->q($t[2].$v);
                        if (sizeof($rets)) $ret = $rets[0]['VALUE'];
                    }
                    print $ret;
                }
                                 
            }
                                 
            print $_POST['value'];
        }
                                 
                                 
        # ------------------------------------------------------------------------
        # Return visits for a time on a beamline
        function _get_visits() {
            if (!$this->has_arg('time')) $this->_error('No time specified');
            if (!$this->has_arg('bl')) $this->_error('No beamline specified');
            
            #$bls = $this->db->q('SELECT name FROM bf_beamlines WHERE beamlineid=:1', array($this->arg('bl')));
            #if (sizeof($bls)) $bl = $bls[0]['NAME'];
            #else $this->_error('No beamline with that id');
                                 
            $bl = $this->arg('bl') == 1 ? 'i02' : 'i03';
            
            $st = $this->arg('time');
            $rows = $this->db->pq("SELECT bl.startdate,bl.enddate,p.proposalcode || p.proposalnumber || '-' || bl.visit_number as visit, bl.sessionid FROM ispyb4a_db.blsession bl INNER JOIN ispyb4a_db.proposal p ON p.proposalid = bl.proposalid WHERE :1 BETWEEN (bl.startdate - TO_DATE('1970-01-01','YYYY-MM-DD')) * 86400 AND (bl.enddate - TO_DATE('1970-01-01','YYYY-MM-DD')) * 86400 AND bl.beamlinename LIKE :2", array($st, $bl));
            
            array_push($rows, array('VISIT' => 'N/A', 'SESSIONID' => -1));
                                 
            $vis = array();
            foreach ($rows as $v) $vis[$v['SESSIONID']] = $v['VISIT'];
                                 
            $this->_output($this->has_arg('array') ? $vis : $rows);
        }
        
        # ------------------------------------------------------------------------
        # Return a list of beamlines with ids
        function _get_beamlines() {
            if ($this->has_arg('array')) {
                $this->_output(array(1=>'i02', 2=>'i03'));
                return;
            }
                                 
            $this->_output(array(array('BEAMLINEID' => 1, 'NAME' => 'i02'),
                                 array('BEAMLINEID' => 2, 'NAME' => 'i03')
                                 ));
            return;
            
            $rows = $this->db->q('SELECT beamlineid, name FROM ispyb4a_db.bf_beamline');
                                 
            $bls = array();
            foreach ($rows as $r) $bls[$r['BEAMLINEID']] = $r['NAME'];
            $this->_output($this->has_arg('array') ? $bls : $rows);
        }
        
        # ------------------------------------------------------------------------
        # Return a list of sytems for a beamline
        function _get_systems() {
            $args = array();
                                  
            if ($this->has_arg('bl')) {
                $where = ' WHERE hs.beamlineid=:1';
                array_push($args, $this->arg('bl'));
                                  
            } else $where = '';
                    
            if ($this->has_arg('array')) {
                $this->_output(array(1=>'EPICS', 2=>'GDA'));
                return;
            }
                                 
            $this->_output(array(array('SYSTEMID' => 1, 'NAME' => 'EPICS', 'BEAMLINES' => 'i03,i02'),
                                 array('SYSTEMID' => 2, 'NAME' => 'GDA', 'BEAMLINES' => 'i03'),
                                 ));
            return;
            
            $rows = $this->db->pq('SELECT s.systemid, s.name FROM ispyb4a_db.bf_systems s INNER JOIN ispyb4a_db.bf_has_system hs ON s.hassystemid = hs.hassystemid '.$where, $args);
                                 
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
                $where = ' AND hc.beamlineid=:2';
                array_push($args, $this->arg('bl'));
            } else $where = '';
                          
            if ($this->has_arg('array')) {
                if ($this->arg('sid') == 1) $this->_output(array(1=>'S4Slit', 2=>'Scintilator'));
                else $this->_output(array(3=>'Server', 4=>'Client'));
                return;
            }
                                 
            if ($this->arg('sid') == 1)
                $this->_output(array(array('COMPONENTID' => 1, 'NAME' => 'S4Slit', 'DESCRIPTION' => 'Phase 1 Slits'),
                                     array('COMPONENTID' => 2, 'NAME' => 'Scintilator', 'DESCRIPTION' => 'Phase II Scintilator'),
                                     ));
            else
                $this->_output(array(array('COMPONENTID' => 3, 'NAME' => 'Server'),
                                     array('COMPONENTID' => 4, 'NAME' => 'Client'),
                                     ));
            return;
            
            $rows = $this->db->q('SELECT c.componentid, c.name FROM ispyb4a_db.bf_components c INNER JOIN ispyb4a_db.bf_has_component hc ON c.hascomponentid = hc.hascomponentid WHERE c.systemid=:1'.$where, $args);
                                 
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
                $where = ' AND hs.beamlineid=:2';
                array_push($args, $this->arg('bl'));
            }else $where = '';
                         
            if ($this->has_arg('array')) {
                if ($this->arg('cid') == 1) $this->_output(array(1=>'xpos', 2=>'ypos'));
                else $this->_output(array(3=>'x', 4=>'y'));
                return;
            }
                                 
            if ($this->arg('cid') == 1)
                $this->_output(array(array('SUBCOMPONENTID' => 1, 'NAME' => 'xpos'),
                                     array('SUBCOMPONENTID' => 2, 'NAME' => 'ypos'),
                                     ));
            else if ($this->arg('cid') == 2)
                $this->_output(array(array('SUBCOMPONENTID' => 3, 'NAME' => 'x'),
                                     array('SUBCOMPONENTID' => 4, 'NAME' => 'y'),
                                     ));
            else if ($this->arg('cid') == 3)
                $this->_output(array());
            else
                $this->_output(array());
            
        
            return;
            
            $rows = $this->db->pq('SELECT s.subcomponentid, s.name FROM ispyb4a_db.bf_subcomponents s INNER JOIN ispyb4a_db.bf_has_subcomponent hs ON s.hassubcomponentid = hs.hassubcomponentid WHERE s.componentid=:1'.$where, $args);
            
            $scom = array();
            foreach ($rows as $s) $scom[$s['SUBCOMPONENTID']] = $s['NAME'];
                                 
            $this->_output($this->has_arg('array') ? $scom : $rows);
        }
         
                                 
        # ------------------------------------------------------------------------
        # Add a new beamline
        function _add_beamline() {
            $this->_output($_POST);
        }

        # ------------------------------------------------------------------------
        # Add a new system
        function _add_system() {
            $this->_output($_POST);
        }
                                 
        # ------------------------------------------------------------------------
        # Add a new component
        function _add_component() {
            $this->_output($_POST);
        }
                                 
        # ------------------------------------------------------------------------
        # Add a new subcomponent
        function _add_subcomponent() {
            $this->_output($_POST);
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