<?php

    require_once('Michelf/Markdown.php');
    use \Michelf\Markdown;
    
    class Fault extends Page {
        
        var $arg_list = array('bl' => '\w\d\d(-\d)?', 'page' => '\d+', 'fid' => '\d+',
                              
                              'sid' => '\d+',
                              'cid' => '\d+',
                              'scid' => '\d+',
                              
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
                              'submit' => '\d',
                              
                              'assignee' => '\w+\d+',
                              
                              );
        
        var $dispatch = array('list' => '_dispatch',
                              'new' => '_add_fault',
                              'edit' => '_editor',
                              'stats' => '_stats'
                              );
        var $def = 'list';
        
        var $root = 'Fault Logging';
        var $root_link = '/fault';
    
        
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
            
            
            $info = $this->db->pq("SELECT p.proposalcode || p.proposalnumber || '-' || bl.visit_number as visit, f.attachment, f.elogid, f.faultid, f.sessionid, bl.beamlinename as beamline, f.owner, f.assignee, s.systemid, s.name as system, c.componentid, c.name as component, sc.subcomponentid, sc.name as subcomponent, TO_CHAR(f.starttime, 'DD-MM-YYYY HH24:MI') as starttime, TO_CHAR(f.endtime, 'DD-MM-YYYY HH24:MI') as endtime, f.beamtimelost, round((f.beamtimelost_endtime-f.beamtimelost_starttime)*24,2) as lost, f.title, f.resolved, f.resolution, f.description, TO_CHAR(f.beamtimelost_endtime, 'DD-MM-YYYY HH24:MI') as beamtimelost_endtime, TO_CHAR(f.beamtimelost_starttime, 'DD-MM-YYYY HH24:MI') as beamtimelost_starttime
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
                                  
            foreach (array('DESCRIPTION', 'RESOLUTION') as $k) {
                if ($info[$k]) {
                    $info[$k] = Markdown::defaultTransform($info[$k]->read($info[$k]->size()));
                }
            }
                
            $info['ATTACH_IMAGE'] = false;
            if ($info['ATTACHMENT']) {
                $info['ATTACHMENT'] = basename($info['ATTACHMENT']);
                $ext = pathinfo($info['ATTACHMENT'], PATHINFO_EXTENSION);
                                  
                if (in_array($ext, array('png', 'jpg', 'jpeg', 'gif'))) $info['ATTACH_IMAGE'] = true;
            }
                                  
            
            $this->template('Fault: '.$info['TITLE']);
            
            $this->t->js_var('fid', $info['FAULTID']);
                                  
            $this->t->js_var('owner', ($info['OWNER'] == phpCAS::getUser()) || $info['ASSIGNEE'] == phpCAS::getUser() || phpCAS::getUser() == 'vxn01537' || phpCAS::getUser() == 'ndg63276');
                                  
            $info['REPORTER'] = $this->_get_name($info['OWNER']);
            if ($info['ASSIGNEE']) $info['ASSIGNEE'] = $this->_get_name($info['ASSIGNEE']);
            $this->t->f = $info;
                                  
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
                foreach (array('title', 'desc', 'session', 'start', 'beamtime_lost', 'resolved') as $f) {
                    if (!$this->has_arg($f)) $valid = False;
                }
                
                if (!$valid) $this->error('Missing Fields', 'Some fields were missing from the submitted fault report');
                
                $btlstart = $this->has_arg('blstart') ? $this->arg('blstart') : '';
                $btlend = $this->has_arg('blend') ? $this->arg('blend') : '';
                $end = $this->has_arg('end') ? $this->arg('end') : '';
                $as = $this->has_arg('assignee') ? $this->arg('assignee') : '';
                
                $this->db->pq("INSERT INTO bf_fault (faultid, sessionid, owner, subcomponentid, starttime, endtime, beamtimelost, beamtimelost_starttime, beamtimelost_endtime, title, description, resolved, resolution, assignee) VALUES (s_bf_fault.nextval, :1, :2, :3, TO_DATE(:4, 'DD-MM-YYYY HH24:MI'), TO_DATE(:5, 'DD-MM-YYYY HH24:MI'), :6, TO_DATE(:7, 'DD-MM-YYYY HH24:MI'), TO_DATE(:8, 'DD-MM-YYYY HH24:MI'), :9, :10, :11, :12, :13) RETURNING faultid INTO :id", array($this->arg('session'), phpCAS::getUser(), $this->arg('sub_component'), $this->arg('start'), $end, $this->arg('beamtime_lost'), $btlstart, $btlend, $this->arg('title'), $this->arg('desc'), $this->arg('resolved'), $this->arg('resolution'), $as));
                        
                $newid = $this->db->id();

                $info = $this->db->pq("SELECT p.proposalcode || p.proposalnumber || '-' || bl.visit_number as visit, s.name as system, c.name as component, sc.name as subcomponent, TO_CHAR(f.starttime, 'DD-MM-YYYY HH24:MI') as starttime, TO_CHAR(f.endtime, 'DD-MM-YYYY HH24:MI') as endtime, f.beamtimelost, round((f.beamtimelost_endtime-f.beamtimelost_starttime)*24,2) as lost, f.title, f.resolved, f.resolution, f.description, TO_CHAR(f.beamtimelost_endtime, 'DD-MM-YYYY HH24:MI') as beamtimelost_endtime, TO_CHAR(f.beamtimelost_starttime, 'DD-MM-YYYY HH24:MI') as beamtimelost_starttime
                    FROM ispyb4a_db.bf_fault f
                    INNER JOIN bf_subcomponent sc ON f.subcomponentid = sc.subcomponentid
                    INNER JOIN bf_component c ON sc.componentid = c.componentid
                    INNER JOIN bf_system s ON c.systemid = s.systemid
                    INNER JOIN blsession bl ON f.sessionid = bl.sessionid
                    INNER JOIN proposal p ON bl.proposalid = p.proposalid

                    WHERE f.faultid=:1", array($newid));
                
                $info = $info[0];
                                    
                foreach (array('DESCRIPTION', 'RESOLUTION') as $k) {
                    if ($info[$k]) {
                        $info[$k] = Markdown::defaultTransform($info[$k]->read($info[$k]->size()));
                    }
                }
                                      
                $report = '<b>'.$info['TITLE'].'</b><br/><br/>System: '.$info['SYSTEM'].'<br/>Component: '.$info['COMPONENT'].' &raquo; '.$info['SUBCOMPONENT'].'<br/><br/>Start: '.$info['STARTTIME'].' End: '.($info['RESOLVED'] == 1 ? $info['ENDTIME'] : 'N/A') .'<br/>Resolved: '.($info['RESOLVED']  == 2 ? 'Partial' : ($info['RESOLVED'] ? 'Yes' : 'No')).'<br/>Beamtime Lost: '.($info['BEAMTIMELOST'] ? ('Yes ('.$info['LOST'].'h between '.$info['BEAMTIMELOST_STARTTIME'].' and '.$info['BEAMTIMELOST_ENDTIME'].')') : 'No').'<br/><br/><b>Description</b><br/>'.$info['DESCRIPTION'].'<br/><br/>'.($info['RESOLVED'] ? ('<b>Resolution</b><br/>'.$info['RESOLUTION']):'').'<br/><br/><a href="http://i03-ws006:5000/fault/fid/'.$this->db->id().'">Fault Report Link</a>';
                                      
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
                 
                $this->msg('New Fault Added', 'Your fault report was sucessfully submitted. Click <a href="/fault/fid/'.$newid.'">here</a> to see to the fault listing');
                                  
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
            $overall = $this->db->pq('SELECT sum((f.beamtimelost_endtime-f.beamtimelost_starttime)*24) as lost, max(s.name) as system,s.systemid
            FROM ispyb4a_db.bf_fault f
            INNER JOIN bf_subcomponent sc ON f.subcomponentid = sc.subcomponentid
            INNER JOIN bf_component c ON sc.componentid = c.componentid
            INNER JOIN bf_system s ON c.systemid = s.systemid
            WHERE f.beamtimelost=1
            GROUP BY s.systemid
            ');
               
            $cols = array('red', 'green', 'blue', 'orange', 'turquoise', 'purple');
            $ovr_pie = array();
            foreach ($overall as $i => $ovr) {
                array_push($ovr_pie, array('label'=>$ovr['SYSTEM'], 'color'=>$cols[$i], 'data'=>$ovr['LOST'], 'sid'=>$ovr['SYSTEMID']));
            }
                                     
            $sys = $this->db->pq('SELECT count(f.faultid) as count, max(c.description) as dc, sum((f.beamtimelost_endtime-f.beamtimelost_starttime)*24) as lost, max(c.name) as component,c.componentid, s.systemid
            FROM ispyb4a_db.bf_fault f
            INNER JOIN bf_subcomponent sc ON f.subcomponentid = sc.subcomponentid
            INNER JOIN bf_component c ON sc.componentid = c.componentid
            INNER JOIN bf_system s ON c.systemid = s.systemid
            WHERE f.beamtimelost=1
            GROUP BY c.componentid, s.systemid
            ');
                    
            $sys_pie = array();
            foreach ($sys as $i=>$s) {
                if (!array_key_exists($s['SYSTEMID'], $sys_pie)) $sys_pie[$s['SYSTEMID']] = array();
                array_push($sys_pie[$s['SYSTEMID']], array('label'=>$s['COMPONENT'], 'color'=>$cols[sizeof($sys_pie[$s['SYSTEMID']])], 'data'=>$s['LOST'], 'cid'=>$s['COMPONENTID']));
            }

                                     
            $sc = $this->db->pq('SELECT count(faultid) as count, max(sc.name) as sc, f.subcomponentid
            FROM ispyb4a_db.bf_fault f
            INNER JOIN bf_subcomponent sc ON f.subcomponentid = sc.subcomponentid
            INNER JOIN bf_component c ON sc.componentid = c.componentid
            INNER JOIN bf_system s ON c.systemid = s.systemid
            GROUP BY f.subcomponentid
            ORDER BY count DESC
            ');
                                
            print_r($sc);
                                      
            $this->template('Fault Statistics', array('Statistics'), array(''));
                                     
            $this->t->js_var('ovr_pie', $ovr_pie);
            $this->t->js_var('sys_pie', $sys_pie);
                                
            $this->render('fault_stats');
        }
    }

?>