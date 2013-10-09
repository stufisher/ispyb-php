<?php

    class Robot extends Page {
        
        var $arg_list = array('bl' => '\w\d\d(-\d)?', 'run' => '\d+', 'visit' => '\w\w\d\d\d\d-\d+');
        var $dispatch = array('index' => '_index');
        var $def = 'index';
        
        var $root = 'Robot Statistics';
        var $root_link = '/robot/';
        
        var $require_staff = True;
        #var $debug = True;
        
        
        # Internal dispatcher based on passed arguments
        function _index() {
            if ($this->has_arg('visit')) $this->_get_visit();
            elseif (sizeof($this->args) > 0) $this->_get_list();
            else $this->_get_root();
        }
        
        
        # Show list of beamlines & runs
        function _get_root() {
            $rows = $this->db->q("SELECT vr.run || '-' || s.beamlinename as rbl, min(vr.run) as run, min(vr.runid) as runid, min(s.beamlinename) as bl, count(r.robotactionid) as num, AVG(CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*86400 as avgt FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = s.sessionid) WHERE r.robotactionid > 1 AND p.proposalcode <> 'cm' AND r.status='SUCCESS' GROUP BY vr.run || '-' || s.beamlinename ORDER BY min(s.beamlinename), min(vr.runid)");
            
            $tvs = $this->db->q("SELECT distinct vr.run,vr.runid FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession bl ON (bl.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = bl.sessionid) WHERE robotactionid != 1 ORDER BY vr.runid");
            
            $rids = array();$rvl = array();
            $ticks = array();
            foreach ($tvs as $i => $t) {
                array_push($ticks, array($i, $t['RUN']));
                $rids[$t['RUN']] = $t['RUNID'];
                $rvl[$t['RUN']] = $i;
            }
                                 
            $bls = array();
            foreach ($rows as $r) {
                if (!array_key_exists($r['BL'], $bls)) $bls[$r['BL']] = array();
                                 
                array_push($bls[$r['BL']], array('id' => $r['RUNID'], 'n' => $r['RUN'], 'avg' => $r['AVGT'], 'c' => $r['NUM'], 'rid' => $r['RUNID']));
            }
                                 
            $bld = array();
            foreach ($bls as $bl => $d) {
                $rd = array();
                foreach ($d as $i => $dat) {
                    array_push($rd, array($rvl[$dat['n']], floatval($dat['avg'])));
                }
                
                $bld[$bl] = $rd;
            }

            $this->template($this->root);
             
            $this->t->row_titles = sizeof($rows) > 0 ? array_keys($rows[0]) : array();
            $this->t->bls = $bls;
            $this->t->js_var('bld', $bld);
            $this->t->js_var('rids', $rids);
            $this->t->js_var('ticks', $ticks);
            $this->t->js_var('url', 1);
            
             
            $this->render('robot');
        }
        
        
        # Show list of visits for beamline/run combinations
        function _get_list() {
            $where = array();
            $args = array();
            
            if ($this->has_arg('bl')) {
                array_push($where, 's.beamlinename LIKE :'. (sizeof($args)+1));
                array_push($args, $this->arg('bl'));
            }
            if ($this->has_arg('run')) {
                array_push($where, 'vr.runid = :' . (sizeof($args)+1));
                array_push($args, $this->arg('run'));
            }
            $where = implode(' AND ', $where);
            
            $q = "SELECT TO_CHAR(min(r.starttimestamp), 'DD-MM-YYYY HH24:MI:SS') as st, p.proposalcode || p.proposalnumber || '-' || s.visit_number as vis, s.beamlinename as bl, r.status, count(r.robotactionid) as num, AVG(CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*86400 as avgt FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = s.sessionid) WHERE p.proposalcode <> 'cm' AND $where GROUP BY p.proposalcode || p.proposalnumber || '-' || s.visit_number, r.status, s.beamlinename ORDER BY min(r.starttimestamp)";
            
            $ticks = array();
            $avts = array();
            $totals = array();
            $visits = array();
            $totalt = 0;
            
            $id = 0;
            $rows = $this->db->pq($q, $args);
            
            if (!$rows) {
                $this->_no_data();
                return;
            }
            
            $id = 0; //sizeof($rows)-1;
            foreach ($rows as $r) {
                if (!array_key_exists($r['VIS'], $visits)) $visits[$r['VIS']] = array('bl' => $r['BL'], 'st' => $r['ST'], 'tot' => 0, 'avgt' => 0);
                
                $visits[$r['VIS']][$r['STATUS']] = $r['NUM'];
                
                if (array_key_exists($r['STATUS'], $totals)) $totals[$r['STATUS']] += $r['NUM'];
                else $totals[$r['STATUS']] = $r['NUM'];
                
                if ($r['STATUS'] == 'SUCCESS') {
                    $visits[$r['VIS']]['avgt'] = round($r['AVGT'], 1);
                    $totalt += $r['AVGT'];
                    array_push($avts, array($id, round($r['AVGT'],1)));
                    array_push($ticks, array($id, $r['VIS']));
                    #$id -= 1;
                    $id++;
                }
                
            }
            
            $types = array('SUCCESS', 'ERROR', 'CRITICAL', 'WARNING', 'EPICSFAIL', 'COMMANDNOTSENT');
            foreach ($visits as $n => $v) {
                foreach ($types as $t) {
                    if (!array_key_exists($t, $v)) $visits[$n][$t] = 0;
                    $visits[$n]['tot'] += $visits[$n][$t];
                }
            }
            
            $total = array('st' => 'Total', 'avgt' => round($totalt/sizeof($avts),1), 'tot' => array_sum($totals), 'id' => '', 'bl' => '');
            foreach ($types as $t) $total[$t] = array_key_exists($t, $totals) ? $totals[$t] : 0;
            $visits[''] =  $total;
            
            if ($this->has_arg('run'))
                $run = $this->db->pq('SELECT run FROM ispyb4a_db.v_run WHERE runid=:1', array($this->args['run']))[0]['RUN'];

            
            # Get breakdown of dewar usage for last 7 / 30 days
            $seven = $this->db->pq("SELECT count(case when r.status='CRITICAL' then 1 end) as ccount, count(case when r.status!='SUCCESS' then 1 end) as ecount, count(case when r.status!='SUCCESS' then 1 end)/count(r.status)*100 as fpc, count(r.status), r.dewarlocation from robotaction r  inner join blsession s on r.blsessionid=s.sessionid WHERE  $where AND r.dewarlocation != 99 AND r.starttimestamp > SYSDATE-7 GROUP BY r.dewarlocation ORDER BY r.dewarlocation", $args);

            $thirty = $this->db->pq("SELECT count(case when r.status='CRITICAL' then 1 end) as ccount, count(case when r.status!='SUCCESS' then 1 end) as ecount, count(case when r.status!='SUCCESS' then 1 end)/count(r.status)*100 as fpc, count(r.status), r.dewarlocation from robotaction r  inner join blsession s on r.blsessionid=s.sessionid WHERE  $where AND r.dewarlocation != 99 AND r.starttimestamp > SYSDATE-30 GROUP BY r.dewarlocation ORDER BY r.dewarlocation", $args);
            
            # Get latest errors for run / beamline
            $errors = $this->db->pq("SELECT * FROM (SELECT ROWNUM,r.samplebarcode, r.actiontype, r.dewarlocation, r.containerlocation, r.message, TO_CHAR(r.starttimestamp, 'DD-MM-YYYY HH24:MI:SS') as st, p.proposalcode || p.proposalnumber || '-' || s.visit_number as vis, s.beamlinename as bl, r.status, (CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*86400 as time FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = s.sessionid) WHERE r.status != 'SUCCESS' AND $where ORDER BY r.starttimestamp DESC) WHERE rownum <= 100", $args);
            
            $p = array();
            $l = array();
            
            if ($this->has_arg('bl')) {
                array_push($p, $this->arg('bl'));
                array_push($l, 'bl/' . $this->arg('bl'));
            }
            
            if ($this->has_arg('run')) {
                array_push($p, $run);
                array_push($l, 'run/' . $this->arg('run'));
            }
            
            
            $this->template(join(' > ', $p), $p, $l);
            
            $this->t->errors = $errors;
            $this->t->js_var('avg_time', $avts);
            $this->t->js_var('avg_ticks', $ticks);
            $this->t->js_var('url', 1);
            $this->t->visits = $visits;
            
            $this->render('robot_list');
            
        }
        
        
        # Show list of actions for visit
        function _get_visit() {
            $rows = $this->db->pq("SELECT TO_CHAR(r.starttimestamp, 'DD-MM-YYYY HH24:MI:SS') as st, r.status, r.actiontype, r.containerlocation, r.dewarlocation, r.samplebarcode, r.message, (CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*86400 as time FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = s.sessionid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1 ORDER BY r.starttimestamp DESC", array($this->arg('visit')));
            
            if (!$rows) {
                $this->_no_data();
                return;
            }
            
            $times = array();
            $ticks = array();
            foreach ($rows as $i => $r) {
                array_push($times, array(sizeof($rows) - 1 - $i, round($r['TIME'], 1)));
                array_push($ticks, array(sizeof($rows) - 1 - $i, $r['ST']));
            }

            $info = $this->db->pq("SELECT s.beamlinename as bl, vr.run, vr.runid FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')))[0];
            
            $p = array($info['BL'], $info['RUN'], $this->arg('visit'));
            $l = array('bl/' . $info['BL'], 'run/' .$info['RUNID'], '');
            
            $this->template('Visit: ' . $this->args['visit'], $p, $l);

            $this->t->js_var('avg_time', $times);
            $this->t->js_var('avg_ticks', $ticks);
            $this->t->js_var('url', 0);
            
            $this->t->rows = $rows;
            $this->t->visit = $this->arg('visit');
            
            $this->render('robot_visit', 'robot_list');

        }
        
        
        # No data for selected beamline / run combination
        function _no_data() {
            $this->template('Robot Statistics > No data for beamline/run', array('No Data'), array(''));
            $this->render('robot_no_data');
        }
        
    }
    
    

?>
