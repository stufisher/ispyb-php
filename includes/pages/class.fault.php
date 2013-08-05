<?php

    class Fault extends Page {
        
        var $arg_list = array('bl' => '\w\d\d', 'page' => '\d+', 'fid' => '\d+');
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
            $this->render('fault');
        }
        
        # View a particular fault
        function _view() {
            if (!$this->has_arg('fid')) $this->error('No fault id specified', 'You must specify a fault id to view');
            
            
            /*$info = $this->db->pq('SELECT f.faultid, f.blsessionid, f.beamlineid, bl.name as beamline, f.owner, f.systemid, s.name as system, f.componentid, c.name as component, f.subcomponentid, sc.name as subcomponent, f.starttime, f.endtime, f.beamtimelost, (f.beamtimelost_endtime-f.beamtimelost_starttime) as lost, f.title, f.resolved, f.description, f.beamtimelost_endtime, f.beamtimelost_starttime
                FROM ispyb4a_db.bf_faults f
                INNER JOIN bf_beamline bl ON f.beamlineid = bl.beamlineid
                INNER JOIN bf_system s ON f.systemid = s.systemid
                INNER JOIN bf_component c ON f.systemid = c.componentid
                LEFT JOIN bf_subcomponent sc ON f.subcomponentid = sc.subcomponentid
                WHERE f.faultid=:1', array($this->arg('fid')));
                                 
            );*/
            
            $info = array(array('FAULTID' => 1, 'BLSESSIONID' => 12, 'BEAMLINEID' => 1, 'BEAMLINE' => 'i03', 'OWNER' => 'vxn01537', 'SYSTEMID' => 1, 'SYSTEM' => 'EPICS', 'COMPONENTID' => 1, 'COMPONENT' => 'Scintilator', 'SUBCOMPONENTID' => 1, 'SUBCOMPONENT' => 'x', 'STARTTIME' => '01-08-2013 11:08', 'ENDTIME' => '01-08-2013 11:08','BEAMTIMELOST' => 0, 'BEAMTIMELOST_STARTTIME' => '01-08-2013 11:08', 'BEAMTIMELOST_ENDTIME' => '01-08-2013 11:08', 'LOST' => 1.3, 'TITLE' => 'Scintilator lost home position', 'RESOLVED' => 1, 'DESCRIPTION' => 'skjdksd fkjs kflsjd fkjs lkfjs ldkfj lksjd flksdj lfksjd lfksj lfk', 'RESOLUTION' => 'sdf skjd fksj dfkjs dkjf skdj fksjd f', 'VISIT' => 'mx5677-32'));
            
            if (sizeof($info)) {
                $info = $info[0];
            } else {
                $this->error('Fault id doesnt exists', 'There is not fault recorded with that id');
            }
            
            
            $this->template('Fault: '.$info['TITLE']);
            $this->t->f = $info;
            
            $this->t->js_var('fid', $info['FAULTID']);
            
            $this->t->js_var('owner', $info['OWNER'] == phpCAS::getUser());
            $this->t->js_var('blid', $info['BEAMLINEID']);
            
            $this->t->js_var('sid', $info['SYSTEMID']);
            $this->t->js_var('cid', $info['COMPONENTID']);
            $this->t->js_var('scid', $info['SUBCOMPONENTID']);
            
            $this->t->js_var('resolved', $info['RESOLVED']);
            $this->t->js_var('btl', $info['BEAMTIMELOST']);
            
            $this->render('fault_view');
        }
        
        
        
        
        
        # Add new fault report
        function _add_fault() {
            if (array_key_exists('submit', $_POST)) {
                
                $id = 1;
                
                $this->msg('New Fault Added', 'Your fault report was sucessfully submitted. Click <a href="/fault/fid/'.$id.'">here</a> to see to the fault listing');
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