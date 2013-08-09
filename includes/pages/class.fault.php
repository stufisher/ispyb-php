<?php

    class Fault extends Page {
        
        var $arg_list = array('bl' => '\w\d\d(-\d)?', 'page' => '\d+', 'fid' => '\d+',
                              
                              'sid' => '\d+',
                              'cid' => '\d+',
                              'scid' => '\d+',
                              
                              'start' => '\d\d-\d\d-\d\d\d\d \d\d:\d\d',
                              'end' => '\d\d-\d\d-\d\d\d\d \d\d:\d\d',
                              'blstart' => '\d\d-\d\d-\d\d\d\d \d\d:\d\d',
                              'blend' => '\d\d-\d\d-\d\d\d\d \d\d:\d\d',
                              'sub_component' => '\d+',
                              'beamtime_lost' => '\d',
                              'resolved' => '\d',
                              'visit' => '\d+',
                              
                              'title' => '.*',
                              'desc' => '.*',
                              'resolution' => '.*',
                              'submit' => '\d',
                              );
        
        var $dispatch = array('list' => '_dispatch',
                              'new' => '_add_fault',
                              'edit' => '_editor',
                              'stats' => '_stats'
                              );
        var $def = 'list';
        
        var $root = 'Fault Logging';
        var $root_link = '/fault/';
    
        
        # dispatch based on passed args
        function _dispatch() {
            if ($this->has_arg('fid')) $this->_view();
            else $this->_index();
        }
        
        
        # List of faults by beamline / time
        function _index() {
            
            $this->template('Fault List');
            
            $this->t->js_var('bl', $this->has_arg('bl') ? $this->arg('bl') : '');
            $this->t->js_var('sid', $this->has_arg('sid') ? $this->arg('sid') : '');
            $this->t->js_var('cid', $this->has_arg('cid') ? $this->arg('cid') : '');
            $this->t->js_var('scid', $this->has_arg('scid') ? $this->arg('scid') : '');
            $this->t->js_var('page', $this->has_arg('page') ? $this->arg('page') : 1);
            
            $this->render('fault');
        }
        
        # View a particular fault
        function _view() {
            if (!$this->has_arg('fid')) $this->error('No fault id specified', 'You must specify a fault id to view');
            
            
            $info = $this->db->pq("SELECT p.proposalcode || p.proposalnumber || '-' || bl.visit_number as visit, f.faultid, f.sessionid, bl.beamlinename as beamline, f.owner, s.systemid, s.name as system, c.componentid, c.name as component, sc.subcomponentid, sc.name as subcomponent, TO_CHAR(f.starttime, 'DD-MM-YYYY HH24:MI') as starttime, TO_CHAR(f.endtime, 'DD-MM-YYYY HH24:MI') as endtime, f.beamtimelost, round((f.beamtimelost_endtime-f.beamtimelost_starttime)*24,2) as lost, f.title, f.resolved, f.resolution, f.description, TO_CHAR(f.beamtimelost_endtime, 'DD-MM-YYYY HH24:MI') as beamtimelost_endtime, TO_CHAR(f.beamtimelost_starttime, 'DD-MM-YYYY HH24:MI') as beamtimelost_starttime
                FROM ispyb4a_db.bf_fault f
                INNER JOIN bf_subcomponent sc ON f.subcomponentid = sc.subcomponentid
                INNER JOIN bf_component c ON sc.componentid = c.componentid
                INNER JOIN bf_system s ON c.systemid = s.systemid
                INNER JOIN blsession bl ON f.sessionid = bl.sessionid
                INNER JOIN proposal p ON bl.proposalid = p.proposalid

                WHERE f.faultid=:1", array($this->arg('fid')));
            
            if (sizeof($info)) {
                $info = $info[0];
            } else {
                $this->error('Fault id doesnt exists', 'There is not fault recorded with that id');
            }
            
            
            $this->template('Fault: '.$info['TITLE']);
            $this->t->f = $info;
            
            $this->t->js_var('fid', $info['FAULTID']);
            
            $this->t->js_var('owner', $info['OWNER'] == phpCAS::getUser());
            $this->t->js_var('bl', $info['BEAMLINE']);
            
            $this->t->js_var('sid', $info['SYSTEMID']);
            $this->t->js_var('cid', $info['COMPONENTID']);
            $this->t->js_var('scid', $info['SUBCOMPONENTID']);
            
            $this->t->js_var('resolved', $info['RESOLVED']);
            $this->t->js_var('btl', $info['BEAMTIMELOST']);
            
            $this->render('fault_view');
        }
        
        
        
        
        
        # Add new fault report
        function _add_fault() {
            if ($this->has_arg('submit')) {
                
                $valid = True;
                foreach (array('title', 'desc', 'visit', 'start', 'beamtime_lost', 'resolved') as $f) {
                    if (!$this->has_arg($f)) $valid = False;
                }
                
                if (!$valid) $this->error('Missing Fields', 'Some fields were missing from the submitted fault report');
                
                $btlstart = $this->has_arg('blstart') ? $this->arg('blstart') : '';
                $btlend = $this->has_arg('blend') ? $this->arg('blend') : '';
                $end = $this->has_arg('end') ? $this->arg('end') : '';
                
                $this->db->pq("INSERT INTO bf_fault (faultid, sessionid, owner, subcomponentid, starttime, endtime, beamtimelost, beamtimelost_starttime, beamtimelost_endtime, title, description, resolved, resolution) VALUES (s_bf_fault.nextval, :1, :2, :3, TO_DATE(:4, 'DD-MM-YYYY HH24:MI'), TO_DATE(:5, 'DD-MM-YYYY HH24:MI'), :6, TO_DATE(:7, 'DD-MM-YYYY HH24:MI'), TO_DATE(:8, 'DD-MM-YYYY HH24:MI'), :9, :10, :11, :12) RETURNING faultid INTO :id", array($this->arg('visit'), phpCAS::getUser(), $this->arg('sub_component'), $this->arg('start'), $end, $this->arg('beamtime_lost'), $btlstart, $btlend, $this->arg('title'), $this->arg('desc'), $this->arg('resolved'), $this->arg('resolution')));
                
                $this->msg('New Fault Added', 'Your fault report was sucessfully submitted. Click <a href="/fault/fid/'.$this->db->id().'">here</a> to see to the fault listing');
            } else {
                $this->template('Add New Fault Report', array('New'), array(''));
                $this->render('fault_new');
            }
        }
        
        
        # Editor for systems, components, and subcomponents
        function _editor() {
            $this->template('System Editor', array('Editor'), array(''));
            $this->render('fault_editor');
            
        }
        
        # View fault stats
        function _stats() {
            $this->template('Fault Statistics', array('Statistics'), array(''));
            $this->render('fault_stats');
        }
    }

?>