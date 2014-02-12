<?php
    
    class Vstat extends Page {
        
        var $arg_list = array('prop' => '\w\w\d+', 'visit' => '\w+\d+-\d+');
        var $dispatch = array('index' => '_index',
                              'proposal' => '_show_proposal',
                              'all' => '_get_root',
                              );
        var $def = 'index';
        
        var $sidebar = True;
        
        var $root = 'Visit Statistics';
        var $root_link = '/vstat';
        
        //var $require_staff = True;
        //var $debug = True;
        
        # Internal dispatcher based on passed arguments
        function _index() {
            if ($this->has_arg('visit')) $this->_get_visit();
            else if ($this->has_arg('prop')) $this->_get_bag();
            else $this->_get_root();
        }
        
        
        
        function _show_proposal() {
            if (!$this->has_arg('prop')) $this->error('No proposal', 'No proposal specified');
            $this->args['bag'] = $this->arg('prop');
            $this->_get_bag();
        }
        
        
        # Show averages for bag
        function _get_root() {
            if (!$this->staff) $this->error('Access Denied', 'You dont have access to view that page');
            
            $where = "(s.enddate - s.startdate) > 0 AND p.proposalcode NOT LIKE 'cm' AND  p.proposalcode NOT LIKE 'nt' AND p.proposalnumber > 0 AND s.startdate > to_date('2012-10-01','YYYY-MM-DD')";
            #$where = "(s.enddate - s.startdate) > 0 AND p.proposalnumber > 0 AND s.startdate > to_date('2012-10-01','YYYY-MM-DD') AND s.enddate < SYSDATE";
            
            $dc = $this->db->pq("SELECT AVG(sup) as avgsup, TO_CHAR(MAX(last), 'DD-MM-YYYY HH24:MI:SS') as last, AVG(len) as avglen, AVG(dctime) as avgdc, count(visit) as num_vis, bag, AVG(rem) as avgrem, SUM(rem) as totrem FROM (SELECT MAX(s.enddate) as last, SUM(dc.endtime - dc.starttime)*24 as dctime, GREATEST((max(s.enddate)-max(dc.endtime))*24,0) as rem, GREATEST((min(dc.starttime)-min(s.startdate))*24,0) as sup, p.proposalcode || p.proposalnumber || '-' || s.visit_number as visit, max(p.proposalcode || p.proposalnumber) as bag, min(s.startdate) as st, max(s.enddate) en, (max(s.enddate) - min(s.startdate))*24 as len FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.datacollection dc ON (dc.sessionid = s.sessionid) WHERE $where GROUP BY p.proposalcode || p.proposalnumber || '-' || s.visit_number, p.proposalnumber ORDER BY max(s.enddate) DESC) GROUP BY bag ORDER BY bag");
            
            $robot = $this->db->pq("SELECT AVG(dctime) as avgdc, bag FROM (SELECT SUM(CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*24 as dctime, max(p.proposalcode || p.proposalnumber) as bag FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = s.sessionid) WHERE $where GROUP BY p.proposalcode || p.proposalnumber || '-' || s.visit_number, p.proposalnumber) GROUP BY bag");

            $edge = $this->db->pq("SELECT AVG(dctime) as avgdc, bag FROM (SELECT SUM(ed.endtime-ed.starttime)*24 as dctime, max(p.proposalcode || p.proposalnumber) as bag FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.energyscan ed ON (ed.sessionid = s.sessionid) WHERE $where GROUP BY p.proposalcode || p.proposalnumber || '-' || s.visit_number, p.proposalnumber) GROUP BY bag");
            
            $data = array();
            foreach ($dc as $d) {
                if (array_key_exists('AVGDC', $d)) {                
                    if (!array_key_exists($d['BAG'], $data)) $data[$d['BAG']] = array();
                    $data[$d['BAG']] = $d;
                }
            }
            
            foreach ($robot as $d)if (array_key_exists($d['BAG'], $data)) $data[$d['BAG']]['R'] = $d['AVGDC'];
            foreach ($edge as $d) if (array_key_exists($d['BAG'], $data)) $data[$d['BAG']]['ED'] = $d['AVGDC'];
            
            $plot = array(array(), array(), array(), array(), array(), array());
            $plot_ticks = array();
            $bids = array();
            
            $i = 0;
            foreach ($data as $b => &$r) {
                if (!array_key_exists('R', $r)) $r['R'] = 0;
                if (!array_key_exists('ED', $r)) $r['ED'] = 0;

                $r['T'] = max($r['AVGLEN'] - $r['AVGDC'] - $r['R'] - $r['AVGREM'] - $r['AVGSUP'] - $r['ED'],0);
                
                array_push($plot_ticks, array($i, $b));
                array_push($bids, $b);
                
                array_push($plot[0], array($i, $r['AVGSUP']));
                array_push($plot[1], array($i, $r['AVGDC']));
                array_push($plot[2], array($i, $r['ED']));
                array_push($plot[3], array($i, $r['R']));
                array_push($plot[4], array($i, $r['AVGREM']));
                array_push($plot[5], array($i, $r['T']));
                
                foreach(array('AVGLEN', 'AVGREM', 'AVGDC', 'AVGSUP', 'R', 'ED', 'T', 'TOTREM') as $f) $r[$f] = number_format($r[$f], 1);
                
                $i++;
            }
            
            
            $dc = $this->db->pq("SELECT AVG(sup) as avgsup, AVG(len) as avglen, AVG(dctime) as avgdc, count(ty) as num_vis, ty, AVG(rem) as avgrem FROM (SELECT max(p.proposalcode) as ty, SUM(dc.endtime - dc.starttime)*24 as dctime, GREATEST((max(s.enddate)-max(dc.endtime))*24,0) as rem, GREATEST((min(dc.starttime)-min(s.startdate))*24,0) as sup, (max(s.enddate) - min(s.startdate))*24 as len FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.datacollection dc ON (dc.sessionid = s.sessionid) WHERE $where GROUP BY p.proposalcode || p.proposalnumber || '-' || s.visit_number) GROUP BY ty");
            
            $robot = $this->db->pq("SELECT AVG(dctime) as avgdc, ty FROM (SELECT SUM(CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*24 as dctime, max(p.proposalcode) as ty FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = s.sessionid) WHERE $where GROUP BY p.proposalcode || p.proposalnumber || '-' || s.visit_number) GROUP BY ty");
            
            $edge = $this->db->pq("SELECT AVG(dctime) as avgdc, ty FROM (SELECT SUM(ed.endtime-ed.starttime)*24 as dctime, max(p.proposalcode) as ty FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.energyscan ed ON (ed.sessionid = s.sessionid) WHERE $where GROUP BY p.proposalcode || p.proposalnumber || '-' || s.visit_number, p.proposalnumber) GROUP BY ty");
            
            $data2 = array();
            foreach ($dc as $d) {
                if (array_key_exists('AVGDC', $d)) $data2[$d['TY']] = $d;
            }
            
            foreach ($robot as $d)if (array_key_exists($d['TY'], $data2)) $data2[$d['TY']]['R'] = $d['AVGDC'];
            foreach ($edge as $d) if (array_key_exists($d['TY'], $data2)) $data2[$d['TY']]['ED'] = $d['AVGDC'];
            
            $this->p($data2);
            
            $c = array();
            $pie = array();
            foreach ($data2 as $t => $d) {
                if (!array_key_exists('ED', $d)) $d['ED'] = 0;
                
                if (!array_key_exists($t, $pie)) $pie[$t] = array();

                $c[$t] = $d['NUM_VIS'];
                
                $d['T'] = max($d['AVGLEN'] - $d['AVGSUP'] - $d['AVGDC'] - $d['ED'] - $d['R'], 0);
                
                array_push($pie[$t], array('label'=>'Startup', 'color'=>'grey', 'data'=>$d['AVGSUP']));
                array_push($pie[$t], array('label'=>'Data Collection', 'color'=> 'green', 'data'=>$d['AVGDC']));
                array_push($pie[$t], array('label'=>'Energy Scans', 'color'=> 'orange', 'data'=>$d['ED']));
                array_push($pie[$t], array('label'=>'Robot Actions', 'color'=> 'blue', 'data'=>$d['R']));
                array_push($pie[$t], array('label'=>'Thinking', 'color'=> 'purple', 'data'=>$d['T']));
                array_push($pie[$t], array('label'=>'Remaining', 'color'=> 'red', 'data'=>$d['AVGREM']));
            }
            
            $this->template($this->root);
            $this->t->data = $data;
            $this->t->c = $c;
            
            $this->t->js_var('vids', $bids);
            $this->t->js_var('visit_ticks', $plot_ticks);
            $this->t->js_var('visit_data', $plot);
            $this->t->js_var('pie_data', $pie);
            
            $this->render('vstat');
        }
        
        

        
        
        # Show list of visits for a BAG
        function _get_bag() {
            if (!$this->has_arg('prop')) $this->error('No proposal', 'No proposal was specified');
            
            $args = array($this->proposalid);
            $where = ' WHERE p.proposalid=:1';
            
            $dc = $this->db->pq("SELECT max(p.title) as title, TO_CHAR(MAX(dc.endtime), 'DD-MM-YYYY HH24:MI') as last, SUM(dc.endtime - dc.starttime)*24 as dctime, GREATEST((min(dc.starttime)-min(s.startdate))*24,0) as sup, GREATEST((max(s.enddate)-max(dc.endtime))*24,0) as rem, s.visit_number as visit, TO_CHAR(min(s.startdate), 'DD-MM-YYYY HH24:MI') as st, TO_CHAR(max(s.enddate), 'DD-MM-YYYY HH24:MI') as en, (max(s.enddate) - min(s.startdate))*24 as len FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.datacollection dc ON (dc.sessionid = s.sessionid) $where GROUP BY s.visit_number ORDER BY min(s.startdate) DESC", $args);
            
            $robot = $this->db->pq("SELECT SUM(CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*24 as dctime, s.visit_number as visit FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = s.sessionid) $where GROUP BY s.visit_number", $args);

            $edge = $this->db->pq("SELECT SUM(e.endtime-e.starttime)*24 as dctime, s.visit_number as visit FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.energyscan e ON (e.sessionid = s.sessionid) $where GROUP BY s.visit_number", $args);
            
            foreach ($robot as $r) {
                foreach ($dc as &$d) {
                    if ($r['VISIT'] == $d['VISIT']) $d['R'] = $r['DCTIME'];
                }
            }

            foreach ($edge as $e) {
                foreach ($dc as &$d) {
                    if ($e['VISIT'] == $d['VISIT']) $d['EDGE'] = $e['DCTIME'];
                }
            }
            
            $plot = array(array(), array(), array(), array(), array(), array());
            $plot_ticks = array();
            $vids = array();
            
            $this->p($dc);
            
            $i = 0;
            foreach ($dc as &$d) {
                if (!array_key_exists('R', $d)) $d['R'] = 0;
                if (!array_key_exists('EDGE', $d)) $d['EDGE'] = 0;
                
                #if ($d['REM'] < 0) $d['REM'] = 0;
                #if ($d['SUP'] < 0) $d['SUP'] = 0;
                
                $d['T'] = max($d['LEN'] - $d['SUP'] - $d['DCTIME'] - $d['R'] - $d['REM'] - $d['EDGE'],0);
                
                #if ($d['T'] < 0) $d['T'] = 0;

                array_push($vids, $d['VISIT']);
                array_push($plot_ticks, array($i, $d['VISIT'] . ': ' . $d['ST']));
                
                array_push($plot[0], array($i, $d['SUP']));
                array_push($plot[1], array($i, $d['DCTIME']));
                array_push($plot[2], array($i, $d['EDGE']));
                array_push($plot[3], array($i, $d['R']));
                array_push($plot[4], array($i, $d['REM']));
                array_push($plot[5], array($i, $d['T']));
                
                foreach (array('SUP', 'DCTIME', 'LEN', 'R', 'REM', 'T', 'EDGE') as $nf) $d[$nf] = number_format($d[$nf], 2);
                
                $d['ID'] = $i;
                
                $i++;
            }
            

            $this->template('Visit: ' . $this->arg('prop'), array('Proposal: '.$this->arg('prop')), array(''));
            $this->t->prop = $this->arg('prop');
            $this->t->data = $dc;
            
            $this->t->js_var('vids', $vids);
            $this->t->js_var('visit_ticks', $plot_ticks);
            $this->t->js_var('visit_data', $plot);
            $this->t->js_var('prop', $this->arg('prop'));
            
            $this->render('vstat_list');
        }
        
        
        # List of actions for a particular visit
        function _get_visit() {
            $args = array($this->arg('visit'));
            $where = "WHERE p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1";
            
            if (!$this->staff) {
                if (!$this->has_arg('prop')) $this->error('No proposal selected', 'You need to select a proposal before viewing this page');
                
                $where .= ' AND p.proposalid LIKE :2';
                array_push($args, $this->proposalid);
            }
            
            $info = $this->db->pq("SELECT p.proposalcode || p.proposalnumber as prop, s.beamlinename as bl, s.sessionid as sid, TO_CHAR(s.startdate, 'DD-MM-YYYY HH24:MI') as st, TO_CHAR(s.enddate, 'DD-MM-YYYY HH24:MI') as en, (s.enddate - s.startdate)*24 as len FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) $where", $args);
            
            if (!sizeof($info)) {
                $this->msg('No such visit', 'That visit doesnt seem to exist');
            } else $info = $info[0];
            
            
            # Visit breakdown
            $dc = $this->db->pq("SELECT TO_CHAR(dc.starttime, 'DD-MM-YYYY HH24:MI:SS') as st, TO_CHAR(dc.endtime, 'DD-MM-YYYY HH24:MI:SS') as en, (dc.endtime - dc.starttime)*86400 as dctime, dc.runstatus FROM ispyb4a_db.datacollection dc WHERE dc.sessionid=:1 ORDER BY dc.endtime DESC", array($info['SID']));
            
            $robot = $this->db->pq("SELECT r.status, r.actiontype, TO_CHAR(r.starttimestamp, 'DD-MM-YYYY HH24:MI:SS') as st, TO_CHAR(r.endtimestamp, 'DD-MM-YYYY HH24:MI:SS') as en, (CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*86400 as dctime FROM ispyb4a_db.robotaction r WHERE r.blsessionid=:1 ORDER BY r.endtimestamp DESC", array($info['SID']));

            $edge = $this->db->pq("SELECT TO_CHAR(e.starttime, 'DD-MM-YYYY HH24:MI:SS') as st, TO_CHAR(e.endtime, 'DD-MM-YYYY HH24:MI:SS') as en, (e.endtime - e.starttime)*86400 as dctime FROM ispyb4a_db.energyscan e WHERE e.sessionid=:1 ORDER BY e.endtime DESC", array($info['SID']));

            $fl = $this->db->pq("SELECT TO_CHAR(f.starttime, 'DD-MM-YYYY HH24:MI:SS') as st, TO_CHAR(f.endtime, 'DD-MM-YYYY HH24:MI:SS') as en, (f.endtime - f.starttime)*86400 as dctime FROM ispyb4a_db.xfefluorescencespectrum f WHERE f.sessionid=:1 ORDER BY f.endtime DESC", array($info['SID']));
            
            # Get Faults
            $faultl = $this->db->pq("SELECT f.faultid, bl.beamlinename as beamline, f.owner, s.name as system, c.name as component, sc.name as subcomponent, TO_CHAR(f.starttime, 'DD-MM-YYYY HH24:MI') as starttime, f.beamtimelost, round((f.beamtimelost_endtime-f.beamtimelost_starttime)*24,2) as lost, f.title, f.resolved, TO_CHAR(f.beamtimelost_starttime, 'DD-MM-YYYY HH24:MI:SS') as st, TO_CHAR(f.beamtimelost_endtime, 'DD-MM-YYYY HH24:MI:SS') as en
                FROM ispyb4a_db.bf_fault f INNER JOIN blsession bl ON f.sessionid = bl.sessionid
                INNER JOIN bf_subcomponent sc ON f.subcomponentid = sc.subcomponentid
                INNER JOIN bf_component c ON sc.componentid = c.componentid
                INNER JOIN bf_system s ON c.systemid = s.systemid
                WHERE f.sessionid = :1", array($info['SID']));
            
            
            $info['DC_TOT'] = sizeof($dc);
            $info['DC_STOPPED'] = 0;
            $info['E_TOT'] = sizeof($edge);
            $info['FL_TOT'] = sizeof($fl);
            $info['R_TOT'] = sizeof($robot);
            $info['F_TOT'] = sizeof($faultl);
            
            if ($info['DC_TOT'] + $info['E_TOT'] + $info['R_TOT'] == 0) $this->msg('No Data', 'There is no data associated with that visit');
            
            $data = array();
            foreach ($dc as $d) {
                if (strpos($d['RUNSTATUS'], 'Successful') === false) $info['DC_STOPPED']++;
                                    
                if ($d['ST'] && $d['EN'])
                    array_push($data, array('data' => array(
                        array($this->jst($d['ST']), 1, $this->jst($d['ST'])),
                        array($this->jst($d['EN']), 1, $this->jst($d['ST']))), 'color' => 'green'));
            }
            
            foreach ($robot as $r) {
                array_push($data, array('data' => array(
                        array($this->jst($r['ST']), 2, $this->jst($r['ST'])),
                        array($this->jst($r['EN']), 2, $this->jst($r['ST']))), 'color' => 'blue', 'status' => ' ' . $r['ACTIONTYPE'] . ' (' . $r['STATUS'] . ')'));
            }
            
            foreach ($edge as $e) {
                array_push($data, array('data' => array(
                        array($this->jst($e['ST']), 3, $this->jst($e['ST'])),
                        array($this->jst($e['EN']), 3, $this->jst($e['ST']))), 'color' => 'orange'));
            }

            foreach ($fl as $e) {
                array_push($data, array('data' => array(
                        array($this->jst($e['ST']), 3, $this->jst($e['ST'])),
                        array($this->jst($e['EN']), 3, $this->jst($e['ST']))), 'color' => 'red', 'type' => 'mca'));
            }
                                    
            foreach ($faultl as $f) {
                if ($f['BEAMTIMELOST']) {
                    array_push($data, array('data' => array(
                        array($this->jst($f['ST']), 4, $this->jst($f['ST'])),
                        array($this->jst($f['EN']), 4, $this->jst($f['ST']))), 'color' => 'grey', 'status' => ' Fault: '.$f['TITLE']));
                    
                }
            }
            
            // Beam status
            $bs = $this->_get_archive('SR-DI-DCCT-01:SIGNAL', strtotime($info['ST']), strtotime($info['EN']), 200);
            #$bs = $this->_get_archive('CS-CS-MSTAT-01:MODE', strtotime($info['ST'])+3600, strtotime($info['EN'])+3600, 200);
                                    
            if (!sizeof($bs)) $bs = array();
            
            $lastv = 0;
            $ex = 3600*1000;
            $bd = False;
            $total_no_beam = 0;
            foreach ($bs as $i => $b) {
                $v = $b[1] < 5 ? 1 : 0;
                #$v = ($b[1] >= 4 && $b[1] < 7) ? 1 : 0;
                $c = $b[0]*1000;
                
                if (($v != $lastv) && $v) {
                    $bd = True;
                    $st = $c;
                }
                
                if ($lastv && ($v != $lastv)) {
                    array_push($data, array('data' => array(
                            array($st+$ex, 4, $st+$ex),
                            array($c+$ex, 4, $st+$ex)), 'color' => 'black', 'status' => ' Beam Dump'));
                    $bd = False;
                    $total_no_beam += ($c - $st) / 1000;
                }
                
                $lastv = $v;
            }
            
            # Data collection time histogramming
            $bs = 20;
            $bs2 = 50;
            $bc = 10;
            
            $dchist = $this->db->pq("SELECT count(1) as c, bin FROM (SELECT width_bucket((dc.endtime - dc.starttime)*86400, 0, :1, :2) as bin FROM ispyb4a_db.datacollection dc WHERE dc.sessionid=:3) GROUP BY bin ORDER BY bin", array($bc*$bs, $bc, $info['SID']));
            
            $dch = array();
            $max = 0;
            foreach ($dchist as $d) {
                if ($d['BIN'] > $max) $max = $d['BIN'];
                $dch[$d['BIN']] = intval($d['C']);
            }
            
            $dcht = array(array(), array());
            for ($i = 0; $i < max($max,$bc); $i++) {
                array_push($dcht[0], array($i, ($i*$bs)));
                array_push($dcht[1], array($i, array_key_exists($i+1, $dch) ? $dch[$i+1] : 0));
            }
            
            
            
            $dchist = $this->db->pq("SELECT count(1) as c, bin FROM (SELECT width_bucket(numberofimages, 0, :1, :2) as bin FROM ispyb4a_db.datacollection dc WHERE dc.sessionid=:3) GROUP BY bin ORDER BY bin", array($bc*$bs2, $bc, $info['SID']));

            $dch = array();
            $max = 0;
            foreach ($dchist as $d) {
                if ($d['BIN'] > $max) $max = $d['BIN'];
                $dch[$d['BIN']] = intval($d['C']);
            }
            
            $dcht2 = array(array(), array());
            for ($i = 0; $i < max($max,$bc); $i++) {
                array_push($dcht2[0], array($i, ($i*$bs2)));
                array_push($dcht2[1], array($i, array_key_exists($i+1, $dch) ? $dch[$i+1] : 0));
            }
            
            
            # Percentage breakdown of time used
            list($dc) = $this->db->pq("SELECT TO_CHAR(MAX(dc.endtime), 'DD-MM-YYYY HH24:MI') as last, SUM(dc.endtime - dc.starttime)*24 as dctime, GREATEST((max(s.enddate)-max(dc.endtime))*24,0) as rem, GREATEST((min(dc.starttime)-min(s.startdate))*24,0) as sup  FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON dc.sessionid=s.sessionid WHERE dc.sessionid=:1 ORDER BY min(s.startdate)", array($info['SID']));
            
            list($rb) = $this->db->pq("SELECT SUM(CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*24 as dctime FROM ispyb4a_db.robotaction r WHERE r.blsessionid=:1", array($info['SID']));
            
            list($ed) = $this->db->pq("SELECT SUM(e.endtime-e.starttime)*24 as dctime FROM ispyb4a_db.energyscan e WHERE e.sessionid=:1", array($info['SID']));
            
            list($fa) = $this->db->pq("SELECT SUM(f.beamtimelost_endtime-f.beamtimelost_starttime)*24 as dctime FROM ispyb4a_db.bf_fault f WHERE f.sessionid=:1", array($info['SID']));
            
            $rb = array_key_exists('DCTIME', $rb) ? $rb['DCTIME'] : 0;
            $ed = array_key_exists('DCTIME', $ed) ? $ed['DCTIME'] : 0;
            $fa = array_key_exists('DCTIME', $fa) ? $fa['DCTIME'] : 0;
            $t = max($info['LEN'] - $dc['SUP'] - $dc['DCTIME'] - $dc['REM'] - $rb - $ed,0);
            
            $pie = array();
            array_push($pie, array('label'=>'Startup', 'color'=>'grey', 'data'=>$dc['SUP']));
            array_push($pie, array('label'=>'Data Collection', 'color'=> 'green', 'data'=>$dc['DCTIME']));
            array_push($pie, array('label'=>'Energy Scans', 'color'=> 'orange', 'data'=>$ed));
            array_push($pie, array('label'=>'Robot Actions', 'color'=> 'blue', 'data'=>$rb));
            array_push($pie, array('label'=>'Thinking', 'color'=> 'purple', 'data'=>$t));
            array_push($pie, array('label'=>'Remaining', 'color'=> 'red', 'data'=>$dc['REM']));
            array_push($pie, array('label'=>'Beam Dump', 'color'=> 'black', 'data'=>$total_no_beam/3600));
            array_push($pie, array('label'=>'Faults', 'color'=> 'black', 'data'=>$fa));
            
            
            # Get Robot Errors
            $robotl = $this->db->pq("SELECT TO_CHAR(r.starttimestamp, 'DD-MM-YYYY HH24:MI:SS') as st, r.status, r.actiontype, r.containerlocation, r.dewarlocation, r.samplebarcode, r.message, (CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*86400 as time FROM ispyb4a_db.robotaction r WHERE r.status != 'SUCCESS' AND r.blsessionid=:1 ORDER BY r.starttimestamp DESC", array($info['SID']));
        
            $st = strtotime($info['ST']);
            $en = strtotime($info['EN']);
                                    
            # Call out log
            libxml_use_internal_errors(true);
            $calls = simplexml_load_file('https://rdb.pri.diamond.ac.uk/php/elog/cs_logwscalloutinfo.php?startdate='.date('d/m/Y', $st).'&enddate='.date('d/m/Y', $en));
            if (!$calls) $calls = array();
                                    
                                    
            //print_r($calls);

            # EHC log
            $ehc_tmp = simplexml_load_file('https://rdb.pri.diamond.ac.uk/php/elog/cs_logwscontentinfo.php?startdate='.date('d/m/Y', $en));
            if (!$ehc_tmp) $ehc_tmp = array();
                     
            $ehcs = array();
            foreach ($ehc_tmp as $e) {
                if (strpos($e->title, 'shift') !== False) array_push($ehcs, $e);
            }
                                    
            //print_r($ehcs);
                                          
                                          
            $this->template('Visit: ' . $this->arg('visit'), array('Proposal: '.$info['PROP'], 'Visit: ' . $this->arg('visit')), array('/prop/'.$info['PROP'], ''));
            //$this->t->bag = $this->arg('bag');
            $this->t->visit = $this->arg('visit');
            $this->t->info = $info;
            $this->t->last = $dc['LAST'];
            
            $this->t->robot = $robotl;
            $this->t->fault = $faultl;
            $this->t->calls = $calls;
            $this->t->ehcs = $ehcs;
                                    
            $this->t->js_var('visit_info', $data);
            $this->t->js_var('start', $this->jst($info['ST']));
            $this->t->js_var('end', $this->jst(strtotime($info['EN']) < strtotime($dc['LAST']) ? $dc['LAST'] : $info['EN']));
            $this->t->js_var('dc_hist', $dcht);
            $this->t->js_var('dc_hist2', $dcht2);
            $this->t->js_var('pie', $pie);
            
            $this->render('vstat_visit');
        }
    
    }

?>
