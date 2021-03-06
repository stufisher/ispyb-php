<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('bl' => '\w\d\d(-\d)?',
                              'run' => '\d+',
                              'visit' => '\w+\d+-\d+',
                              'year' => '\d\d\d\d',
                              'page' => '\d+',
                              'pp' => '\d+',
                              's' => '[\w\d-\s]+'
                              );
        
        var $dispatch = array('averages' => '_averages',
                              'errors' => '_errors',
                              'profile' => '_visit_profile',
                              );
        var $def = 'averages';
        
        var $require_staff = True;

        
        # Show list of beamlines & runs
        function _averages() {
            $rows = $this->db->pq("SELECT vr.run || '-' || s.beamlinename as rbl, min(vr.run) as run, min(vr.runid) as runid, min(s.beamlinename) as bl, count(r.robotactionid) as num, MEDIAN(CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*86400 as avgt FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = s.sessionid) WHERE r.robotactionid > 1 AND p.proposalcode <> 'cm' AND r.status='SUCCESS' AND (r.actiontype = 'LOAD') GROUP BY vr.run || '-' || s.beamlinename ORDER BY min(s.beamlinename), min(vr.runid)");
            
            $tvs = $this->db->pq("SELECT distinct vr.run,vr.runid FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession bl ON (bl.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = bl.sessionid) WHERE robotactionid != 1 ORDER BY vr.runid");
            
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
                                 
                array_push($bls[$r['BL']], $r);
            }
                                 
            $bld = array();
            foreach ($bls as $bl => $d) {
                $rd = array();
                foreach ($d as $i => $dat) {
                    array_push($rd, array($rvl[$dat['RUN']], floatval($dat['AVGT'])));
                }
                
                $bld[$bl] = $rd;
            }

            $this->_output(array('details' => $bls, 'data' => $bld, 'ticks' => $ticks));
        }
        
        
        # List of robot errors for beamline / run / visit
        function _errors() {
            $args = array();
            $where = array();
            
            if ($this->has_arg('bl')) {
                array_push($where, 's.beamlinename LIKE :'. (sizeof($args)+1));
                array_push($args, $this->arg('bl'));
            }
            if ($this->has_arg('run')) {
                array_push($where, 'vr.runid = :' . (sizeof($args)+1));
                array_push($args, $this->arg('run'));
            }
            if ($this->has_arg('visit')) {
                array_push($where, "p.proposalcode||p.proposalnumber||'-'||s.visit_number LIKE :" . (sizeof($args)+1));
                array_push($args, $this->arg('visit'));
            }
            if ($this->has_arg('s')) {
                array_push($where, "(lower(r.status) LIKE lower('%'||:".(sizeof($args)+1)."||'%') OR lower(r.message) LIKE lower('%'||:".(sizeof($args)+2)."||'%'))");
                array_push($args, $this->arg('s'));
                array_push($args, $this->arg('s'));
            }
            
            $where = implode(' AND ', $where);
            if ($where) $where = 'AND '.$where;

            $tot = $this->db->pq("SELECT count(r.robotactionid) as tot FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = s.sessionid) WHERE r.status != 'SUCCESS' AND  (r.actiontype = 'LOAD' OR r.actiontype='UNLOAD') $where ORDER BY r.starttimestamp DESC", $args);
            
            $start = 0;
            $end = 10;
            $pp = $this->has_arg('pp') ? $this->arg('pp') : 15;
            
            if ($this->has_arg('page')) {
                $pg = $this->arg('page') - 1;
                $start = $pg*$pp;
                $end = $pg*$pp+$pp;
            }
            
            array_push($args, $start);
            array_push($args, $end);
            
            $errors = $this->db->pq("SELECT outer.* FROM (SELECT rownum rn, inner.* FROM (SELECT r.samplebarcode, r.actiontype, r.dewarlocation, r.containerlocation, r.message, TO_CHAR(r.starttimestamp, 'DD-MM-YYYY HH24:MI:SS') as st, p.proposalcode || p.proposalnumber || '-' || s.visit_number as vis, s.beamlinename as bl, r.status, (CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*86400 as time FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = s.sessionid) WHERE r.status != 'SUCCESS' AND (r.actiontype = 'LOAD' OR r.actiontype='UNLOAD') $where ORDER BY r.starttimestamp DESC) inner) outer WHERE outer.rn > :".(sizeof($args)-1)." AND outer.rn <= :".sizeof($args), $args);
            
            foreach ($errors as $i => &$e) {
                $e['TIME'] = number_format($e['TIME'], 1);
            }
            
            $this->_output(array(intval($tot[0]['TOT']), $errors));
            
        }
        
        
        # Dewar profile for visit
        function _visit_profile() {
            $dp = $this->db->pq("SELECT count(case when r.status='CRITICAL' then 1 end) as ccount, count(case when r.status!='SUCCESS' then 1 end) as ecount, count(case when r.status!='SUCCESS' then 1 end)/count(r.status)*100 as epc, count(case when r.status='CRITICAL' then 1 end)/count(r.status)*100 as cpc, count(r.status) as total, r.dewarlocation FROM robotaction r INNER JOIN blsession s on r.blsessionid=s.sessionid INNER JOIN proposal p ON p.proposalid = s.proposalid WHERE p.proposalcode||p.proposalnumber||'-'||s.visit_number LIKE :1 AND r.actiontype LIKE 'LOAD' AND r.dewarlocation != 99 GROUP BY r.dewarlocation ORDER BY r.dewarlocation", array($this->arg('visit')));
            
            
            $profile = array(array(
                                   array('label' => 'Total',  'data' => array()),
                                   array('label' => '% Errors',  'data' => array(), 'yaxis' => 2),
                                   array('label' => '% Critical',  'data' => array(), 'yaxis' => 2),
                                   ),
                             array());
            
            foreach ($dp as $e) {
                array_push($profile[0][0]['data'], array($e['DEWARLOCATION'], $e['TOTAL']));
                array_push($profile[0][2]['data'], array($e['DEWARLOCATION'], $e['CPC']));
                array_push($profile[0][1]['data'], array($e['DEWARLOCATION'], $e['EPC']));
            }
            
            $this->_output($profile);
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
            
            if ($this->has_arg('year')) {
                array_push($where, "r.starttimestamp > TO_DATE(:".(sizeof($args)+1).", 'HH24:MI DD-MM-YYYY')");
                array_push($where, "r.starttimestamp < TO_DATE(:".(sizeof($args)+2).", 'HH24:MI DD-MM-YYYY')");
                array_push($args, '00:01 01-01-'.$this->arg('year'));
                array_push($args, '23:59 31-12-'.($this->arg('year')));
            }
            
            $where = implode(' AND ', $where);
            
            $q = "SELECT TO_CHAR(min(r.starttimestamp), 'DD-MM-YYYY HH24:MI:SS') as st, p.proposalcode || p.proposalnumber || '-' || s.visit_number as vis, s.beamlinename as bl, r.status, count(r.robotactionid) as num, MEDIAN(CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*86400 as avgt FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = s.sessionid) WHERE p.proposalcode <> 'cm' AND $where AND (r.actiontype = 'LOAD') GROUP BY p.proposalcode || p.proposalnumber || '-' || s.visit_number, r.status, s.beamlinename ORDER BY min(r.starttimestamp)";
            
            $ticks = array();
            $avts = array();
            $totals = array();
            $visits = array();
            $totalt = 0;
            
            $id = 0;
            $rows = $this->db->pq($q, $args);
            
            if (!$rows) $this->error('No Data', 'No data associated with that beamline/run');
            
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
            
            if ($this->has_arg('run')) {
                $run = $this->db->pq('SELECT run FROM ispyb4a_db.v_run WHERE runid=:1', array($this->args['run']));
                $run = $run[0]['RUN'];
            }

            
            # Get breakdown of dewar usage for last 7 / 30 days
            $seven = $this->db->pq("SELECT count(case when r.status='CRITICAL' then 1 end) as ccount, count(case when r.status!='SUCCESS' then 1 end) as ecount, count(case when r.status!='SUCCESS' then 1 end)/count(r.status)*100 as epc, count(case when r.status='CRITICAL' then 1 end)/count(r.status)*100 as cpc, count(r.status) as total, r.dewarlocation from robotaction r INNER JOIN blsession s on r.blsessionid=s.sessionid INNER JOIN ispyb4a_db.v_run vr ON (s.startdate BETWEEN vr.startdate AND vr.enddate) WHERE  $where AND r.actiontype LIKE 'LOAD' AND r.dewarlocation != 99 AND r.starttimestamp > SYSDATE-7 GROUP BY r.dewarlocation ORDER BY r.dewarlocation", $args);

            
            $profile = array(array(
                    array('label' => 'Total',  'data' => array()),
                    array('label' => '% Errors',  'data' => array(), 'yaxis' => 2),
                    array('label' => '% Critical',  'data' => array(), 'yaxis' => 2)),
                array(
                    array('label' => 'Total',  'data' => array()),
                    array('label' => '% Errors',  'data' => array(), 'yaxis' => 2),
                    array('label' => '% Critical',  'data' => array(), 'yaxis' => 2),
                ));
                             
            foreach ($seven as $e) {
                array_push($profile[0][0]['data'], array($e['DEWARLOCATION'], $e['TOTAL']));
                array_push($profile[0][2]['data'], array($e['DEWARLOCATION'], $e['CPC']));
                array_push($profile[0][1]['data'], array($e['DEWARLOCATION'], $e['EPC']));
            }
            
            $thirty = $this->db->pq("SELECT count(case when r.status='CRITICAL' then 1 end) as ccount, count(case when r.status!='SUCCESS' then 1 end) as ecount, count(case when r.status!='SUCCESS' then 1 end)/count(r.status)*100 as epc, count(case when r.status='CRITICAL' then 1 end)/count(r.status)*100 as cpc, count(r.status) as total, r.dewarlocation from robotaction r  inner join blsession s on r.blsessionid=s.sessionid INNER JOIN ispyb4a_db.v_run vr ON (s.startdate BETWEEN vr.startdate AND vr.enddate) WHERE $where AND r.actiontype LIKE 'LOAD' AND r.dewarlocation != 99 AND r.starttimestamp > SYSDATE-30 GROUP BY r.dewarlocation ORDER BY r.dewarlocation", $args);
                           
            foreach ($thirty as $e) {
                array_push($profile[1][0]['data'], array($e['DEWARLOCATION'], $e['TOTAL']));
                array_push($profile[1][2]['data'], array($e['DEWARLOCATION'], $e['CPC']));
                array_push($profile[1][1]['data'], array($e['DEWARLOCATION'], $e['EPC']));
            }
            
            # Get latest errors for run / beamline
            $errors = $this->db->pq("SELECT * FROM (SELECT ROWNUM,r.samplebarcode, r.actiontype, r.dewarlocation, r.containerlocation, r.message, TO_CHAR(r.starttimestamp, 'DD-MM-YYYY HH24:MI:SS') as st, p.proposalcode || p.proposalnumber || '-' || s.visit_number as vis, s.beamlinename as bl, r.status, (CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*86400 as time FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = s.sessionid) WHERE r.status != 'SUCCESS' AND $where AND (r.actiontype = 'LOAD' OR r.actiontype='UNLOAD') ORDER BY r.starttimestamp DESC) WHERE rownum <= 100", $args);
            
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

            if ($this->has_arg('year')) {
                array_push($p, $this->arg('year'));
                array_push($l, 'year/' . $this->arg('year'));
            }
            
            $this->template(join(' > ', $p), $p, $l);
            
            $this->t->errors = $errors;
            $this->t->js_var('avg_time', $avts);
            $this->t->js_var('avg_ticks', $ticks);
            $this->t->js_var('url', 1);
            $this->t->js_var('dewar', $profile);
            $this->t->visits = $visits;
            
            $this->render('robot_list');
            
        }
        
        
        # Show list of actions for visit
        function _get_visit() {
            $rows = $this->db->pq("SELECT TO_CHAR(r.starttimestamp, 'DD-MM-YYYY HH24:MI:SS') as st, r.status, r.actiontype, r.containerlocation, r.dewarlocation, r.samplebarcode, r.message, (CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*86400 as time FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = s.sessionid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1 AND (r.actiontype = 'LOAD' OR r.actiontype='UNLOAD') ORDER BY r.starttimestamp DESC", array($this->arg('visit')));
            
            if (!$rows) $this->error('No Data', 'No data associated with that visit');
            
            $times = array();
            $ticks = array();
            foreach ($rows as $i => $r) {
                array_push($times, array(sizeof($rows) - 1 - $i, round($r['TIME'], 1)));
                array_push($ticks, array(sizeof($rows) - 1 - $i, $r['ST']));
            }

            
            list($info) = $this->db->pq("SELECT s.sessionid, s.beamlinename as bl, vr.run, vr.runid FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));
            
            # Get breakdown of dewar usage for visit
            $dp = $this->db->pq("SELECT count(case when r.status='CRITICAL' then 1 end) as ccount, count(case when r.status!='SUCCESS' then 1 end) as ecount, count(case when r.status!='SUCCESS' then 1 end)/count(r.status)*100 as epc, count(case when r.status='CRITICAL' then 1 end)/count(r.status)*100 as cpc, count(r.status) as total, r.dewarlocation from robotaction r INNER JOIN blsession s on r.blsessionid=s.sessionid INNER JOIN ispyb4a_db.v_run vr ON (s.startdate BETWEEN vr.startdate AND vr.enddate) WHERE s.sessionid=:1 AND r.actiontype LIKE 'LOAD' AND r.dewarlocation != 99 GROUP BY r.dewarlocation ORDER BY r.dewarlocation", array($info['SESSIONID']));
            
            
            $profile = array(array(
                                   array('label' => 'Total',  'data' => array()),
                                   array('label' => '% Errors',  'data' => array(), 'yaxis' => 2),
                                   array('label' => '% Critical',  'data' => array(), 'yaxis' => 2),
                                   ),
                             array());
            
            foreach ($dp as $e) {
                array_push($profile[0][0]['data'], array($e['DEWARLOCATION'], $e['TOTAL']));
                array_push($profile[0][2]['data'], array($e['DEWARLOCATION'], $e['CPC']));
                array_push($profile[0][1]['data'], array($e['DEWARLOCATION'], $e['EPC']));
            }
            
            
            $p = array($info['BL'], $info['RUN'], $this->arg('visit'));
            $l = array('bl/' . $info['BL'], 'run/' .$info['RUNID'], '');
            
            $this->template('Visit: ' . $this->args['visit'], $p, $l);

            $this->t->js_var('dewar', $profile);
            $this->t->js_var('avg_time', $times);
            $this->t->js_var('avg_ticks', $ticks);
            $this->t->js_var('url', 0);
            
            $this->t->rows = $rows;
            $this->t->visit = $this->arg('visit');
            
            $this->render('robot_visit', 'robot_list');

        }
        
    }
    
    

?>
