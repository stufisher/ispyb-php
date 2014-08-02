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
        }
        
        
        
        function _show_proposal() {
            if (!$this->has_arg('prop')) $this->error('No proposal', 'No proposal specified');
            $this->args['bag'] = $this->arg('prop');
            $this->_get_bag();
        }

        
        
        # Show list of visits for a BAG
        function _get_bag() {
            if (!$this->has_arg('prop')) $this->error('No proposal', 'No proposal was specified');
            
            $args = array($this->proposalid);
            $where = ' WHERE p.proposalid=:1';
            
            $dc = $this->db->pq("SELECT max(p.title) as title, TO_CHAR(MAX(dc.endtime), 'DD-MM-YYYY HH24:MI') as last, SUM(dc.endtime - dc.starttime)*24 as dctime, GREATEST((min(dc.starttime)-min(s.startdate))*24,0) as sup, GREATEST((max(s.enddate)-max(dc.endtime))*24,0) as rem, s.visit_number as visit, TO_CHAR(min(s.startdate), 'DD-MM-YYYY HH24:MI') as st, TO_CHAR(max(s.enddate), 'DD-MM-YYYY HH24:MI') as en, (max(s.enddate) - min(s.startdate))*24 as len FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN ispyb4a_db.datacollection dc ON (dc.sessionid = s.sessionid) $where GROUP BY s.visit_number ORDER BY min(s.startdate) DESC", $args);
            
            
            $plot = array(array(), array(), array(), array());
            $plot_ticks = array();
            $vids = array();
            
            $this->p($dc);
            
            $i = 0;
            foreach ($dc as &$d) {
                $d['T'] = max($d['LEN'] - $d['SUP'] - $d['DCTIME'] - $d['REM'],0);
                
                array_push($vids, $d['VISIT']);
                array_push($plot_ticks, array($i, $d['VISIT'] . ': ' . $d['ST']));
                
                array_push($plot[0], array($i, $d['SUP']));
                array_push($plot[1], array($i, $d['DCTIME']));
                array_push($plot[2], array($i, $d['REM']));
                array_push($plot[3], array($i, $d['T']));
                
                foreach (array('SUP', 'DCTIME', 'LEN', 'REM', 'T') as $nf) $d[$nf] = number_format($d[$nf], 2);
                
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
            $dc = $this->db->pq("SELECT dc.datacollectionid as id, TO_CHAR(dc.starttime, 'DD-MM-YYYY HH24:MI:SS') as st, TO_CHAR(dc.endtime, 'DD-MM-YYYY HH24:MI:SS') as en, (dc.endtime - dc.starttime)*86400 as dctime, dc.runstatus FROM ispyb4a_db.datacollection dc WHERE dc.sessionid=:1 ORDER BY dc.endtime DESC", array($info['SID']));
            
            
            # Get Faults
            $faultl = $this->db->pq("SELECT f.faultid, bl.beamlinename as beamline, f.owner, s.name as system, c.name as component, sc.name as subcomponent, TO_CHAR(f.starttime, 'DD-MM-YYYY HH24:MI') as starttime, f.beamtimelost, round((f.beamtimelost_endtime-f.beamtimelost_starttime)*24,2) as lost, f.title, f.resolved, TO_CHAR(f.beamtimelost_starttime, 'DD-MM-YYYY HH24:MI:SS') as st, TO_CHAR(f.beamtimelost_endtime, 'DD-MM-YYYY HH24:MI:SS') as en
                FROM ispyb4a_db.bf_fault f INNER JOIN blsession bl ON f.sessionid = bl.sessionid
                INNER JOIN bf_subcomponent sc ON f.subcomponentid = sc.subcomponentid
                INNER JOIN bf_component c ON sc.componentid = c.componentid
                INNER JOIN bf_system s ON c.systemid = s.systemid
                WHERE f.sessionid = :1", array($info['SID']));
            
            
            $info['DC_TOT'] = sizeof($dc);
            $info['DC_STOPPED'] = 0;
            
            if ($info['DC_TOT'] == 0) $this->msg('No Data', 'There is no data associated with that visit');
            
            $data = array();
            foreach ($dc as $d) {
                if (strpos($d['RUNSTATUS'], 'Successful') === false) $info['DC_STOPPED']++;
                                    
                if ($d['ST'] && $d['EN'])
                    array_push($data, array('data' => array(
                        array($this->jst($d['ST']), 1, $this->jst($d['ST'])),
                        array($this->jst($d['EN']), 1, $this->jst($d['ST']))), 'color' => 'green', 'id' => intval($d['ID']), 'type' => 'dc'));
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
            
            
            # Percentage breakdown of time used
            list($dc) = $this->db->pq("SELECT TO_CHAR(MAX(dc.endtime), 'DD-MM-YYYY HH24:MI') as last, TO_CHAR(MIN(dc.starttime), 'DD-MM-YYYY HH24:MI') as first, SUM(dc.endtime - dc.starttime)*24 as dctime, GREATEST((max(s.enddate)-max(dc.endtime))*24,0) as rem, GREATEST((min(dc.starttime)-min(s.startdate))*24,0) as sup  FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON dc.sessionid=s.sessionid WHERE dc.sessionid=:1 ORDER BY min(s.startdate)", array($info['SID']));
            
            
            list($fa) = $this->db->pq("SELECT SUM(f.beamtimelost_endtime-f.beamtimelost_starttime)*24 as dctime FROM ispyb4a_db.bf_fault f WHERE f.sessionid=:1", array($info['SID']));
            
            $dc['SUP'] = max(0,(strtotime($dc['FIRST']) - strtotime($info['ST'])) / 3600);
            $dc['REM'] = max(0,(strtotime($info['EN']) - strtotime($dc['LAST'])) / 3600);
                                    
            $fa = array_key_exists('DCTIME', $fa) ? $fa['DCTIME'] : 0;
            $t = max($info['LEN'] - $dc['SUP'] - $dc['DCTIME'] - $dc['REM'],0);
            
            $pie = array();
            array_push($pie, array('label'=>'Startup', 'color'=>'grey', 'data'=>$dc['SUP']));
            array_push($pie, array('label'=>'Data Collection', 'color'=> 'green', 'data'=>$dc['DCTIME']));
            array_push($pie, array('label'=>'Thinking', 'color'=> 'purple', 'data'=>$t));
            array_push($pie, array('label'=>'Remaining', 'color'=> 'red', 'data'=>$dc['REM']));
            array_push($pie, array('label'=>'Beam Dump', 'color'=> 'black', 'data'=>$total_no_beam/3600));
            array_push($pie, array('label'=>'Faults', 'color'=> 'black', 'data'=>$fa));
            
            
            $st = strtotime($info['ST']);
            $en = strtotime($info['EN']);                                    
                                    
            # Call out log
            $bls =  array('b21' => 'BLI21', 'i11' => 'BLI11');
            $calls = array();
            if (array_key_exists($info['BL'], $bls)) {
                $calls = $this->_get_remote_xml('https://rdb.pri.diamond.ac.uk/php/elog/cs_logwscalloutinfo.php?startdate='.date('d/m/Y', $st).'&enddate='.date('d/m/Y', $en).'selgroupid='.$bls[$info['BL']]);
                if (!$calls) $calls = array();
            }


            # EHC log
            $ehc_tmp = $this->_get_remote_xml('https://rdb.pri.diamond.ac.uk/php/elog/cs_logwscontentinfo.php?startdate='.date('d/m/Y', $en));
            if (!$ehc_tmp) $ehc_tmp = array();
                     
            $ehcs = array();
            foreach ($ehc_tmp as $e) {
                if (strpos($e->title, 'shift') !== False) array_push($ehcs, $e);
            }

                                          
            $this->template('Visit: ' . $this->arg('visit'), array('Proposal: '.$info['PROP'], 'Visit: ' . $this->arg('visit')), array('prop/'.$info['PROP'], ''));
            $this->t->visit = $this->arg('visit');
            $this->t->info = $info;
            $this->t->last = $dc['LAST'];
            
            $this->t->fault = $faultl;
            $this->t->calls = $calls;
            $this->t->ehcs = $ehcs;

            $this->t->js_var('visit_info', $data);
            $this->t->js_var('start', $this->jst(strtotime($info['ST']) > strtotime($dc['FIRST']) ? $dc['FIRST'] : $info['ST']));
            $this->t->js_var('end', $this->jst(strtotime($info['EN']) < strtotime($dc['LAST']) ? $dc['LAST'] : $info['EN']));
            $this->t->js_var('pie', $pie);
            $this->t->js_var('visit', $this->arg('visit'));
            
            $this->render('gen/vstat_visit');
        }
                 
                                    
        // Return xml from external link without using url_fopen
        function _get_remote_xml($url) {
            libxml_use_internal_errors(true);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $xml = curl_exec($ch);
            curl_close($ch);
                                    
            return simplexml_load_string($xml);
        }
    
    }

?>
