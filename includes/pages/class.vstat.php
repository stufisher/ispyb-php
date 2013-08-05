<?php
    require_once('xmlrpc/xmlrpc.inc');
    require_once('xmlrpc/xmlrpcs.inc');
    require_once('xmlrpc/xmlrpc_wrappers.inc');
    
    
    class Visit extends Page {
        
        var $arg_list = array('bag' => '\w\w\d+', 'visit' => '\d+');
        var $dispatch = array('index' => '_index');
        var $def = 'index';
        
        var $root = 'Visit Statistics';
        var $root_link = '/vstat';
        
        var $require_staff = True;
        #var $debug = True;
        
        
        # Internal dispatcher based on passed arguments
        function _index() {
            if ($this->has_arg('bag') && $this->has_arg('visit')) $this->_get_visit();
            else if ($this->has_arg('bag')) $this->_get_bag();
            else $this->_get_root();
        }
        
        
        # Show averages for bag
        function _get_root() {
            $where = "(s.enddate - s.startdate) > 0 AND p.proposalcode NOT LIKE 'cm' AND  p.proposalcode NOT LIKE 'nt' AND p.proposalnumber > 0 AND s.startdate > to_date('2012-10-01','YYYY-MM-DD')";
            #$where = "(s.enddate - s.startdate) > 0 AND p.proposalnumber > 0 AND s.startdate > to_date('2012-10-01','YYYY-MM-DD') AND s.enddate < SYSDATE";
            
            $dc = $this->db->q("SELECT AVG(sup) as avgsup, TO_CHAR(MAX(last), 'DD-MM-YYYY HH24:MI:SS') as last, AVG(len) as avglen, AVG(dctime) as avgdc, count(visit) as num_vis, bag, AVG(rem) as avgrem FROM (SELECT MAX(s.enddate) as last, SUM(dc.endtime - dc.starttime)*24 as dctime, GREATEST((max(s.enddate)-max(dc.endtime))*24,0) as rem, GREATEST((min(dc.starttime)-min(s.startdate))*24,0) as sup, p.proposalcode || p.proposalnumber || '-' || s.visit_number as visit, max(p.proposalcode || p.proposalnumber) as bag, min(s.startdate) as st, max(s.enddate) en, (max(s.enddate) - min(s.startdate))*24 as len FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.datacollection dc ON (dc.sessionid = s.sessionid) WHERE $where GROUP BY p.proposalcode || p.proposalnumber || '-' || s.visit_number, p.proposalnumber ORDER BY max(s.enddate) DESC) GROUP BY bag ORDER BY bag");
            
            $robot = $this->db->q("SELECT AVG(dctime) as avgdc, bag FROM (SELECT SUM(CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*24 as dctime, max(p.proposalcode || p.proposalnumber) as bag FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = s.sessionid) WHERE $where GROUP BY p.proposalcode || p.proposalnumber || '-' || s.visit_number, p.proposalnumber) GROUP BY bag");

            $edge = $this->db->q("SELECT AVG(dctime) as avgdc, bag FROM (SELECT SUM(ed.endtime-ed.starttime)*24 as dctime, max(p.proposalcode || p.proposalnumber) as bag FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.energyscan ed ON (ed.sessionid = s.sessionid) WHERE $where GROUP BY p.proposalcode || p.proposalnumber || '-' || s.visit_number, p.proposalnumber) GROUP BY bag");
            
            $data = array();
            foreach ($dc as $d) {
                if (array_key_exists('AVGDC', $d)) {                
                    if (!array_key_exists($d['BAG'], $data)) $data[$d['BAG']] = array();
                    #if ($d['AVGREM'] < 0) $d['AVGREM'] = 0;
                    $data[$d['BAG']] = $d;
                }
            }
            
            foreach ($robot as $d)if (array_key_exists($d['BAG'], $data)) $data[$d['BAG']]['R'] = $d['AVGDC'];
            foreach ($edge as $d) if (array_key_exists($d['BAG'], $data)) $data[$d['BAG']]['ED'] = $d['AVGDC'];
            
            $plot = array(array(), array(), array(), array(), array(), array());
            $plot_ticks = array();
            $bids = array();
            
            $i = 0;
            foreach ($data as $b => &$d) {
                if (!array_key_exists('R', $d)) $d['R'] = 0;
                if (!array_key_exists('ED', $d)) $d['ED'] = 0;
                
                #foreach (array('AVGSUP', 'AVGREM') as $k) if ($d[$k] < 0) $d[$k] = 0;
                
                $d['T'] = max($d['AVGLEN'] - $d['AVGDC'] - $d['R'] - $d['AVGREM'] - $d['AVGSUP'] - $d['ED'],0);
                #if ($d['T'] < 0) $d['T'] = 0;
                
                array_push($plot_ticks, array($i, $b));
                array_push($bids, $b);
                
                array_push($plot[0], array($i, $d['AVGSUP']));
                array_push($plot[1], array($i, $d['AVGDC']));
                array_push($plot[2], array($i, $d['ED']));
                array_push($plot[3], array($i, $d['R']));
                array_push($plot[4], array($i, $d['AVGREM']));
                array_push($plot[5], array($i, $d['T']));
                
                foreach(array('AVGLEN', 'AVGREM', 'AVGDC', 'AVGSUP', 'R', 'ED', 'T') as $f) $d[$f] = number_format($d[$f], 1);
                
                $i++;
            }
            
            
            $dc = $this->db->q("SELECT AVG(sup) as avgsup, AVG(len) as avglen, AVG(dctime) as avgdc, count(ty) as num_vis, ty, AVG(rem) as avgrem FROM (SELECT max(p.proposalcode) as ty, SUM(dc.endtime - dc.starttime)*24 as dctime, GREATEST((max(s.enddate)-max(dc.endtime))*24,0) as rem, GREATEST((min(dc.starttime)-min(s.startdate))*24,0) as sup, (max(s.enddate) - min(s.startdate))*24 as len FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.datacollection dc ON (dc.sessionid = s.sessionid) WHERE $where GROUP BY p.proposalcode || p.proposalnumber || '-' || s.visit_number) GROUP BY ty");
            
            $robot = $this->db->q("SELECT AVG(dctime) as avgdc, ty FROM (SELECT SUM(CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*24 as dctime, max(p.proposalcode) as ty FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = s.sessionid) WHERE $where GROUP BY p.proposalcode || p.proposalnumber || '-' || s.visit_number) GROUP BY ty");
            
            $edge = $this->db->q("SELECT AVG(dctime) as avgdc, ty FROM (SELECT SUM(ed.endtime-ed.starttime)*24 as dctime, max(p.proposalcode) as ty FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.energyscan ed ON (ed.sessionid = s.sessionid) WHERE $where GROUP BY p.proposalcode || p.proposalnumber || '-' || s.visit_number, p.proposalnumber) GROUP BY ty");
            
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
            $this->p($this->arg('bag'));
            $where = "WHERE p.proposalcode || p.proposalnumber LIKE '".$this->arg('bag')."'";
            
            $dc = $this->db->q("SELECT max(p.title) as title, TO_CHAR(MAX(dc.endtime), 'DD-MM-YYYY HH24:MI') as last, SUM(dc.endtime - dc.starttime)*24 as dctime, GREATEST((min(dc.starttime)-min(s.startdate))*24,0) as sup, GREATEST((max(s.enddate)-max(dc.endtime))*24,0) as rem, s.visit_number as visit, TO_CHAR(min(s.startdate), 'DD-MM-YYYY HH24:MI') as st, TO_CHAR(max(s.enddate), 'DD-MM-YYYY HH24:MI') as en, (max(s.enddate) - min(s.startdate))*24 as len FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.datacollection dc ON (dc.sessionid = s.sessionid) $where GROUP BY s.visit_number ORDER BY min(s.startdate) DESC");
            
            $robot = $this->db->q("SELECT SUM(CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*24 as dctime, s.visit_number as visit FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.robotaction r ON (r.blsessionid = s.sessionid) $where GROUP BY s.visit_number");

            $edge = $this->db->q("SELECT SUM(e.endtime-e.starttime)*24 as dctime, s.visit_number as visit FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.energyscan e ON (e.sessionid = s.sessionid) $where GROUP BY s.visit_number");
            
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
            

            $this->template('Visit: ' . $this->arg('bag'), array('Bag: '.$this->arg('bag')), array(''));
            $this->t->bag = $this->arg('bag');
            $this->t->data = $dc;
            
            $this->t->js_var('vids', $vids);
            $this->t->js_var('visit_ticks', $plot_ticks);
            $this->t->js_var('visit_data', $plot);
            $this->t->js_var('bag', $this->arg('bag'));
            
            $this->render('vstat_list');

        }
        
        
        # List of actions for a particular visit
        function _get_visit() {
            $where = "WHERE p.proposalcode || p.proposalnumber LIKE '".$this->arg('bag')."' AND s.visit_number=".$this->arg('visit');
            
            $info = $this->db->q("SELECT s.beamlinename as bl, s.sessionid as sid, TO_CHAR(s.startdate, 'DD-MM-YYYY HH24:MI') as st, TO_CHAR(s.enddate, 'DD-MM-YYYY HH24:MI') as en, (s.enddate - s.startdate)*24 as len FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) $where");
            
            if (!sizeof($info)) {
                $this->msg('No such visit', 'That visit doesnt seem to exist');
            } else $info = $info[0];
            
            
            # Visit breakdown
            $dc = $this->db->q("SELECT TO_CHAR(dc.starttime, 'DD-MM-YYYY HH24:MI:SS') as st, TO_CHAR(dc.endtime, 'DD-MM-YYYY HH24:MI:SS') as en, (dc.endtime - dc.starttime)*86400 as dctime FROM ispyb4a_db.datacollection dc WHERE dc.sessionid=".$info['SID']." ORDER BY dc.endtime DESC");
            
            $robot = $this->db->q("SELECT r.status, r.actiontype, TO_CHAR(r.starttimestamp, 'DD-MM-YYYY HH24:MI:SS') as st, TO_CHAR(r.endtimestamp, 'DD-MM-YYYY HH24:MI:SS') as en, (CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*86400 as dctime FROM ispyb4a_db.robotaction r WHERE r.blsessionid=".$info['SID']." ORDER BY r.endtimestamp DESC");

            $edge = $this->db->q("SELECT TO_CHAR(e.starttime, 'DD-MM-YYYY HH24:MI:SS') as st, TO_CHAR(e.endtime, 'DD-MM-YYYY HH24:MI:SS') as en, (e.endtime - e.starttime)*86400 as dctime FROM ispyb4a_db.energyscan e WHERE e.sessionid=".$info['SID']." ORDER BY e.endtime DESC");
            
            $info['DC_TOT'] = sizeof($dc);
            $info['E_TOT'] = sizeof($edge);
            $info['R_TOT'] = sizeof($robot);
            
            if ($info['DC_TOT'] + $info['E_TOT'] + $info['R_TOT'] == 0) $this->msg('No Data', 'There is no data associated with that visit');
            
            $data = array();
            foreach ($dc as $d) {
                if ($d['ST'] && $d['EN'])
                    array_push($data, array('data' => array(
                        array($this->jst($d['ST']), 1, $this->jst($d['ST'])),
                        array($this->jst($d['EN']), 1, $this->jst($d['ST']))), 'color' => 'green'));
            }
            
            foreach ($robot as $r) {
                array_push($data, array('data' => array(
                        array($this->jst($r['ST']), 2, $this->jst($r['ST'])),
                        array($this->jst($r['EN']), 2, $this->jst($r['ST']))), 'color' => 'blue',             'status' => ' ' . $r['ACTIONTYPE'] . ' (' . $r['STATUS'] . ')'));
            }
            
            foreach ($edge as $e) {
                array_push($data, array('data' => array(
                        array($this->jst($e['ST']), 3, $this->jst($e['ST'])),
                        array($this->jst($e['EN']), 3, $this->jst($e['ST']))), 'color' => 'orange'));
            }

            // Beam status
            $bs = $this->_get_archive('SR-DI-DCCT-01:SIGNAL', strtotime($info['ST'])+3600, strtotime($info['EN'])+3600, 200);
            #$bs = $this->_get_archive('CS-CS-MSTAT-01:MODE', strtotime($info['ST'])+3600, strtotime($info['EN'])+3600, 200);
            
            #print_r($bs);
            
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
                            array($c+$ex, 4, $st+$ex)), 'color' => 'black'));
                    $bd = False;
                    $total_no_beam += ($c - $st) / 1000;
                }
                
                $lastv = $v;
            }
            
            # Data collection time histogramming
            $bs = 20;
            $bs2 = 50;
            $bc = 10;
            
            $dchist = $this->db->q("SELECT count(1) as c, bin FROM (SELECT width_bucket((dc.endtime - dc.starttime)*86400, 0, ".($bc*$bs).", ".$bc.") as bin FROM ispyb4a_db.datacollection dc WHERE dc.sessionid=".$info['SID'].") GROUP BY bin ORDER BY bin");
            
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
            
            
            
            $dchist = $this->db->q("SELECT count(1) as c, bin FROM (SELECT width_bucket(numberofimages, 0, ".($bc*$bs2).", ".$bc.") as bin FROM ispyb4a_db.datacollection dc WHERE dc.sessionid=".$info['SID'].") GROUP BY bin ORDER BY bin");

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
            $dc = $this->db->q("SELECT TO_CHAR(MAX(dc.endtime), 'DD-MM-YYYY HH24:MI') as last, SUM(dc.endtime - dc.starttime)*24 as dctime, GREATEST((max(s.enddate)-max(dc.endtime))*24,0) as rem, GREATEST((min(dc.starttime)-min(s.startdate))*24,0) as sup  FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON dc.sessionid=s.sessionid WHERE dc.sessionid=".$info['SID']." ORDER BY min(s.startdate)")[0];
            
            $rb = $this->db->q("SELECT SUM(CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*24 as dctime FROM ispyb4a_db.robotaction r WHERE r.blsessionid=".$info['SID'])[0];
            
            $ed = $this->db->q("SELECT SUM(e.endtime-e.starttime)*24 as dctime FROM ispyb4a_db.energyscan e WHERE e.sessionid=".$info['SID'])[0];
            
            $rb = array_key_exists('DCTIME', $rb) ? $rb['DCTIME'] : 0;
            $ed = array_key_exists('DCTIME', $ed) ? $ed['DCTIME'] : 0;
            $t = max($info['LEN'] - $dc['SUP'] - $dc['DCTIME'] - $dc['REM'] - $rb - $ed,0);
            
            $pie = array();
            array_push($pie, array('label'=>'Startup', 'color'=>'grey', 'data'=>$dc['SUP']));
            array_push($pie, array('label'=>'Data Collection', 'color'=> 'green', 'data'=>$dc['DCTIME']));
            array_push($pie, array('label'=>'Energy Scans', 'color'=> 'orange', 'data'=>$ed));
            array_push($pie, array('label'=>'Robot Actions', 'color'=> 'blue', 'data'=>$rb));
            array_push($pie, array('label'=>'Thinking', 'color'=> 'purple', 'data'=>$t));
            array_push($pie, array('label'=>'Remaining', 'color'=> 'red', 'data'=>$dc['REM']));
            array_push($pie, array('label'=>'Beam Dump', 'color'=> 'black', 'data'=>$total_no_beam/3600));
            
            
            # Get Robot Errors
            $robotl = $this->db->q("SELECT TO_CHAR(r.starttimestamp, 'DD-MM-YYYY HH24:MI:SS') as st, r.status, r.actiontype, r.containerlocation, r.dewarlocation, r.samplebarcode, r.message, (CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*86400 as time FROM ispyb4a_db.robotaction r WHERE r.status != 'SUCCESS' AND r.blsessionid=".$info['SID']." ORDER BY r.starttimestamp DESC");
            
            
            $this->template('Visit: ' . $this->arg('bag'), array('Bag: '.$this->arg('bag'), 'Visit: ' . $this->arg('visit')), array('/bag/'.$this->arg('bag'), ''));
            $this->t->bag = $this->arg('bag');
            $this->t->visit = $this->arg('visit');
            $this->t->info = $info;
            $this->t->last = $dc['LAST'];
            
            $this->t->robot = $robotl;
            $this->t->js_var('visit_info', $data);
            $this->t->js_var('start', $this->jst($info['ST']));
            $this->t->js_var('end', $this->jst(strtotime($info['EN']) < strtotime($dc['LAST']) ? $dc['LAST'] : $info['EN']));
            $this->t->js_var('dc_hist', $dcht);
            $this->t->js_var('dc_hist2', $dcht2);
            $this->t->js_var('pie', $pie);
            

            
            $this->render('vstat_visit');
        }
        
        
        // Talk to channel archiver to get a pv
        function _get_archive($pv, $s, $e, $n=100) {
            $m = new xmlrpcmsg('archiver.values', array(
                    new xmlrpcval(1000, 'int'),
                    new xmlrpcval(array(new xmlrpcval($pv,'string')), 'array'),
                    new xmlrpcval($s,'int'),
                    new xmlrpcval(0,'int'),
                    new xmlrpcval($e,'int'),
                    new xmlrpcval(0,'int'),
                    new xmlrpcval($n,'int'),
                    new xmlrpcval(2,'int'),
                    ));
            $c = new xmlrpc_client("/archive/cgi/ArchiveDataServer.cgi", "archiver.pri.diamond.ac.uk", 80);
            
            $r = $c->send($m);
            $val = $r->value();
            
            if ($val) {
                $str = $val->arrayMem(0);
                $vals = $str->structMem('values');
                
                $ret = array();
                for ($i = 0; $i < $vals->arraySize(); $i++) {
                    $vs = $vals->arrayMem($i);
                    $v = $vs->structMem('value')->arrayMem(0)->scalarVal();
                    $t = $vs->structMem('secs')->scalarVal();
                    
                    array_push($ret, array($t,$v));
                }
            
                return $ret;
            }
        }
    

    }

?>
