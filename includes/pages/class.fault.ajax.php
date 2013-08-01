<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('time' => '\d+', 'bl' => '\d+', 'sid' => '\d+', 'cid' => '\d+', 'scid' => '\d+', 'pp' => '\d+', 'page' => '\d+');
        var $dispatch = array('list' => '_get_faults',
                              
                              'visits' => '_get_visits',
                              'bl' => '_get_beamlines',
                              'sys' => '_get_systems',
                              'com' => '_get_components',
                              'scom' => '_get_subcomponents',
                              
                              );
        var $def = 'list';
        var $profile = True;
        
        # ------------------------------------------------------------------------
        # Return faults based on search terms / filters
        function _get_faults() {
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
            
            $tot = 80;//$this->db->q('SELECT count(faultid) as tot FROM ispyb4a_db.bf_faults '.$where)[0]['TOT'];
            
            
            $pgs = intval($tot/$pp);
            if ($tot % $pp != 0) $pgs++;
            
            $this->_output(array(4, array(
                array('FAULTID' => 1, 'BLSESSIONID' => 12, 'BEAMLINEID' => 1, 'BEAMLINE' => 'i03', 'OWNER' => 'vxn01537', 'SYSTEMID' => 1, 'SYSTEM' => 'EPICS', 'COMPONENTID' => 1, 'COMPONENT' => 'Scintilator', 'SUBCOMPONENTID' => 1, 'SUBCOMPONENT' => 'x', 'STARTTIME' => '01-08-2013 11:08:07', 'BEAMTIMELOST' => 1, 'LOST' => 1.3, 'TITLE' => 'Scintilator lost home position', 'RESOLVED' => 1),
                ))
            );
            return;
            
            
            $rows = $this->db->q('SELECT outer.*
             FROM (SELECT ROWNUM rn, inner.*
               FROM (
                SELECT f.faultid, f.blsessionid, f.beamlineid, bl.name as beamline f.owner, f.systemid, s.name as system f.componentid, c.name as component, f.subcomponentid, sc.name as subcomponent, f.starttime, f.endtime, f.beamtimelost, (f.beamtimelost_endtime-f.beamtimelost_starttime) as lost, f.title, f.resolved
                FROM ispyb4a_db.bf_faults f
                INNER JOIN bf_beamline bl ON f.beamlineid = bl.beamlineid
                INNER JOIN bf_system s ON f.systemid = s.systemid
                INNER JOIN bf_component c ON f.systemid = c.componentid
                LEFT JOIN bf_subcomponent sc ON f.subcomponentid = sc.subcomponentid
                '.$where.'
                ORDER BY f.faultid DESC
             
               ) inner) outer
             WHERE outer.rn > '.$start.' AND outer.rn <= '.$end);
                                 
            $this->_output(array($pgs, $rows));
        }
        
        
        # ------------------------------------------------------------------------
        # Return visits for a time on a beamline
        function _get_visits() {
            if (!$this->has_arg('time')) $this->_error('No time specified');
            if (!$this->has_arg('bl')) $this->_error('No beamline specified');
            
            $bl = $this->arg('bl') == 1 ? 'i02' : 'i03';
            
            $st = $this->arg('time');
            $rows = $this->db->q("SELECT bl.startdate,bl.enddate,p.proposalcode || p.proposalnumber || '-' || bl.visit_number as visit, bl.sessionid FROM ispyb4a_db.blsession bl INNER JOIN ispyb4a_db.proposal p ON p.proposalid = bl.proposalid WHERE ".$st." BETWEEN (bl.startdate - TO_DATE('1970-01-01','YYYY-MM-DD')) * 86400 AND (bl.enddate - TO_DATE('1970-01-01','YYYY-MM-DD')) * 86400 AND bl.beamlinename LIKE '".$bl."' AND bl.sessionid != 886");
            
            $this->_output($rows);
        }
        
        # ------------------------------------------------------------------------
        # Return a list of beamlines with ids
        function _get_beamlines() {
            $this->_output(array(array('BEAMLINEID' => 1, 'NAME' => 'i02'),
                                 array('BEAMLINEID' => 2, 'NAME' => 'i03')
                                 ));
            return;
            
            $rows = $this->db->q('SELECT beamlineid, name FROM ispyb4a_db.bf_beamline');
            $this->_output($rows);
        }
        
        # ------------------------------------------------------------------------
        # Return a list of sytems for a beamline
        function _get_systems() {
            if (!$this->has_arg('bl')) $this->_error('No beamlineid specified');
            
            $this->_output(array(array('SYSTEMID' => 1, 'NAME' => 'EPICS'),
                                 array('SYSTEMID' => 2, 'NAME' => 'GDA'),
                                 ));
            return;
            
            $rows = $this->db->q('SELECT s.systemid, s.name FROM ispyb4a_db.bf_systems s INNER JOIN ispyb4a_db.bf_has_system hs ON s.hassystemid = hs.hassystemid WHERE hs.beamlineid='.$this->arg('bl'));
            $this->_output($rows);
        }
        
        # ------------------------------------------------------------------------
        # Return a list of components for a system on a beamline
        function _get_components() {
            if (!$this->has_arg('bl')) $this->_error('No beamlineid specified');
            if (!$this->has_arg('sid')) $this->_error('No systemid specified');            
            
            if ($this->arg('sid') == 1)
                $this->_output(array(array('COMPONENTID' => 1, 'NAME' => 'S4Slit'),
                                     array('COMPONENTID' => 2, 'NAME' => 'Scintilator'),
                                     ));
            else
                $this->_output(array(array('COMPONENTID' => 3, 'NAME' => 'Server'),
                                     array('COMPONENTID' => 4, 'NAME' => 'Client'),
                                     ));
            return;
            
            $rows = $this->db->q('SELECT c.componentid, c.name FROM ispyb4a_db.bf_components c INNER JOIN ispyb4a_db.bf_has_component hc ON c.hascomponentid = hc.hascomponentid WHERE hc.beamlineid='.$this->arg('bl').' AND c.systemid='.$this->arg('sid'));
            $this->_output($rows);
        }
        
        # ------------------------------------------------------------------------
        # Return a list of subcomponents for a component on a beamline
        function _get_subcomponents() {
            if (!$this->has_arg('bl')) $this->_error('No beamlineid specified');
            if (!$this->has_arg('cid')) $this->_error('No componentid specified');
            
            if ($this->arg('cid') == 1)
                $this->_output(array(array('SUBCOMPONENTID' => 1, 'NAME' => 'xpos'),
                                     array('SUBCOMPONENTID' => 2, 'NAME' => 'ypos'),
                                     ));
            else if ($this->arg('cid') == 2)
                $this->_output(array(array('SUBCOMPONENTID' => 1, 'NAME' => 'x'),
                                     array('SUBCOMPONENTID' => 2, 'NAME' => 'y'),
                                     ));
            else if ($this->arg('cid') == 3)
                $this->_output(array());
            else
                $this->_output(array());
            
        
            return;
            
            $rows = $this->db->q('SELECT s.subcomponentid, s.name FROM ispyb4a_db.bf_subcomponents s INNER JOIN ispyb4a_db.bf_has_subcomponent hs ON s.hassubcomponentid = hs.hassubcomponentid WHERE hs.beamlineid='.$this->arg('bl').' AND s.componentid='.$this->arg('cid'));
            $this->_output($rows);
        }
        
    }

?>