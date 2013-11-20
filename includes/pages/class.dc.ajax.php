<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('id' => '\d+', 'visit' => '\w\w\d\d\d\d-\d+', 'page' => '\d+', 's' => '\w+', 'pp' => '\d+', 't' => '\w+', 'bl' => '\w\d\d(-\d)?', 'value' => '.*');
        var $dispatch = array('strat' => '_dc_strategies',
                              'ap' => '_dc_auto_processing',
                              'dp' => '_dc_downstream',
                              'dc' => '_data_collections',
                              'ed' => '_edge',
                              'mca' => '_mca',
                              'aps' => '_ap_status',
                              'imq' => '_image_qi',
                              'flag' => '_flag',
                              'comment' => '_set_comment',
                              );
        
        var $def = 'dc';
        var $profile = True;
        #var $debug = True;
        
        # ------------------------------------------------------------------------
        # Data Collection AJAX Requests
        function _data_collections() {
            session_write_close();
            
            $this->profile('starting dc page');
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            
            $args = array();
            
            $where = '';
            $where2 = '';
            $where3 = '';
            $where4 = '';
            
            if ($this->has_arg('t')) {
                if ($this->arg('t') == 'dc') {
                    $where2 .= ' AND es.energyscanid < 0';
                    $where3 .= ' AND r.robotactionid < 0';
                    $where4 .= ' AND xrf.xfefluorescencespectrumid < 0';
                    
                } else if ($this->arg('t') == 'ed') {
                    $where .= ' AND dc.datacollectionid < 0';
                    $where3 .= ' AND r.robotactionid < 0';
                    $where4 .= ' AND xrf.xfefluorescencespectrumid < 0';
                    
                } else if ($this->arg('t') == 'fl') {
                    $where .= ' AND dc.datacollectionid < 0';
                    $where2 .= ' AND es.energyscanid < 0';
                    $where3 .= ' AND r.robotactionid < 0';
                    
                } else if ($this->arg('t') == 'rb') {
                    $where .= ' AND dc.datacollectionid < 0';
                    $where2 .= ' AND es.energyscanid < 0';
                    $where4 .= ' AND xrf.xfefluorescencespectrumid < 0';
                    
                } else if ($this->arg('t') == 'flag') {
                    $where .= " AND dc.comments LIKE '%_FLAG_%'";
                    $where2 .= " AND es.comments LIKE '%_FLAG_%'";
                    $where3 .= " AND r.robotactionid < 0";
                    $where4 .= " AND xrf.comments LIKE '%_FLAG_%'";
                }
            }
            
            $start = 0;
            $end = 10;
            $pp = $this->has_arg('pp') ? $this->arg('pp') : 15;
            
            if ($this->has_arg('page')) {
                $pg = $this->arg('page') - 1;
                $start = $pg*$pp;
                $end = $pg*$pp+$pp;
            }
            
            $info = $this->db->pq("SELECT s.sessionid, s.beamlinename as bl, vr.run, vr.runid FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')))[0];
            
            for ($i = 0; $i < 4; $i++) array_push($args, $info['SESSIONID']);
        
            if ($this->has_arg('id')) {
                $st = sizeof($args)+1;
                $where .= ' AND dc.datacollectionid=:'.$st;
                $where3 .= ' AND r.robotactionid=:'.($st+1);
                $where2 .= ' AND es.energyscanid=:'.($st+2);
                $where4 .= ' AND xrf.xfefluorescencespectrumid=:'.($st+3);
                for ($i = 0; $i < 4; $i++) array_push($args, $this->arg('id'));
            }
            
            
            if ($this->has_arg('s')) {
                $st = sizeof($args) + 1;
                $where .= " AND (lower(dc.filetemplate) LIKE lower('%'||:$st||'%') OR lower(dc.imagedirectory) LIKE lower('%'||:".($st+1)."||'%'))";
                $where2 .= " AND (lower(es.comments) LIKE lower('%'||:".($st+2)."||'%') OR lower(es.element) LIKE lower('%'||:".($st+3)."||'%'))";
                $where3 .= ' AND r.robotactionid < 0';
                $where4 .= " AND lower(xrf.filename) LIKE lower('%'||:".($st+4)."||'%')";
                
                for ($i = 0; $i < 5; $i++) array_push($args, $this->arg('s'));
            }
            
            $tot = $this->db->pq('SELECT sum(tot) as t FROM (SELECT count(dc.datacollectionid) as tot FROM ispyb4a_db.datacollection dc  WHERE dc.sessionid=:1'.$where.'
                
                UNION SELECT count(es.energyscanid) as tot FROM ispyb4a_db.energyscan es WHERE es.sessionid=:2'.$where2.'
                                
                UNION SELECT count(xrf.xfefluorescencespectrumid) as tot from ispyb4a_db.xfefluorescencespectrum xrf WHERE xrf.sessionid=:3'.$where4.'
                                
                UNION SELECT count(r.robotactionid) as tot FROM ispyb4a_db.robotaction r WHERE r.blsessionid=:4'.$where3.')', $args)[0]['T'];
    
            $this->profile('after page count');
            
            $pgs = intval($tot/$pp);
            if ($tot % $pp != 0) $pgs++;

            $st = sizeof($args) + 1;
            array_push($args, $start);
            array_push($args, $end);

            $q = 'SELECT outer.*
             FROM (SELECT ROWNUM rn, inner.*
             FROM (
             SELECT dc.overlap, 1 as flux, 1 as scon, \'a\' as spos, \'a\' as san, \'data\' as type, dc.imageprefix as imp, dc.datacollectionnumber as run, dc.filetemplate, dc.datacollectionid as id, dc.numberofimages as ni, dc.imagedirectory as dir, dc.resolution, dc.exposuretime, dc.axisstart, dc.numberofimages as numimg, TO_CHAR(dc.starttime, \'DD-MM-YYYY HH24:MI:SS\') as st, dc.transmission, dc.axisrange, dc.wavelength, dc.comments, 1 as epk, 1 as ein, dc.xtalsnapshotfullpath1 as x1, dc.xtalsnapshotfullpath2 as x2, dc.xtalsnapshotfullpath3 as x3, dc.xtalsnapshotfullpath4 as x4, dc.starttime as sta FROM ispyb4a_db.datacollection dc
             

             WHERE dc.sessionid=:1'.$where.'
             UNION
             SELECT 1, 1, 1 as scon, \'A\' as spos, \'A\' as sn, \'edge\' as type, es.jpegchoochfilefullpath, 1, \'A\', es.energyscanid, 1, es.element, es.peakfprime, es.exposuretime, es.peakfdoubleprime, 1, TO_CHAR(es.starttime, \'DD-MM-YYYY HH24:MI:SS\') as st, es.transmissionfactor, es.inflectionfprime, es.inflectionfdoubleprime, es.comments, es.peakenergy, es.inflectionenergy, \'A\', \'A\', \'A\', \'A\', es.starttime as sta FROM ispyb4a_db.energyscan es WHERE es.sessionid=:2'.$where2.'

            UNION
            SELECT 1, 1, 1, \'A\', \'A\', \'mca\' as type, \'A\', 1, \'A\', xrf.xfefluorescencespectrumid, 1, xrf.filename, 1, xrf.exposuretime, 1, 1, TO_CHAR(xrf.starttime, \'DD-MM-YYYY HH24:MI:SS\') as st, xrf.beamtransmission, 1, xrf.energy, xrf.comments, 1, 1, \'A\', \'A\', \'A\', \'A\', xrf.starttime as sta FROM ispyb4a_db.xfefluorescencespectrum xrf WHERE xrf.sessionid=:3'.$where4.'
                   
             UNION
             SELECT 1, 1, 1, r.status, r.message, \'load\' as type, r.actiontype, 1, \'A\', r.robotactionid, 1,  r.samplebarcode, r.containerlocation, r.dewarlocation, 1, 1, TO_CHAR(r.starttimestamp, \'DD-MM-YYYY HH24:MI:SS\') as st, 1, 1, 1, \'A\', 1, 1, \'A\', \'A\', \'A\', \'A\', r.starttimestamp as sta FROM ispyb4a_db.robotaction r WHERE r.blsessionid=:4'.$where3.'
             
             
             ORDER BY sta DESC
             
             ) inner) outer
             WHERE outer.rn > :'.$st.' AND outer.rn <= :'.($st+1);
            
            $dcs = $this->db->pq($q, $args);
            $this->profile('main query');            
            
            foreach ($dcs as $i => &$dc) {
                $dc['SN'] = 0;
                $dc['DI'] = 0;
                
                
                // Data collections
                if ($dc['TYPE'] == 'data') {
                    $nf = array(1 => array('AXISSTART', 'AXISRANGE'), 2 => array('RESOLUTION', 'TRANSMISSION'), 3 => array('EXPOSURETIME'), 4 => array('WAVELENGTH'));
 
                    $dc['DIR'] = $this->ads($dc['DIR']);
                    $dc['DIR'] = substr($dc['DIR'], strpos($dc['DIR'], $this->arg('visit'))+strlen($this->arg('visit'))+1);
                    //$this->profile('dc');
                    
                    //$dc['FLUX'] = $dc['FLUX'] ? sprintf('%.2e', $dc['FLUX']) : 'N/A';
                    
                // Edge Scans
                } else if ($dc['TYPE'] == 'edge') {
                    $dc['EPK'] = floatVal($dc['EPK']);
                    $dc['EIN'] = floatVal($dc['EIN']);
                    
                    $nf = array(2 => array('EXPOSURETIME'), 2 => array('AXISSTART', 'RESOLUTION'), 3 => array('TRANSMISSION'));
                    $this->profile('edge');  
                
                // MCA Scans
                } else if ($dc['TYPE'] == 'mca') {
                    $results = str_replace('.mca', '.results.dat', str_replace($this->arg('visit'), $this->arg('visit').'/processed/pymca', $dc['DIR']));
                    
                    $elements = array();
                    if (file_exists($results)) {
                        $dat = explode("\n",file_get_contents($results));
                        foreach ($dat as $i => $d) {
                            if ($i < 5) array_push($elements, $d);
                        }
                    }
                    
                    $dc['ELEMENTS'] = $elements;
                    $nf = array(2 => array('EXPOSURETIME', 'WAVELENGTH'), 3 => array('TRANSMISSION'));
                    
                // Robot loads
                } else if ($dc['TYPE'] == 'load') $nf = array();
                
                
                $dc['AP'] = array(0,0,0,0,0,0,0,0);
                
                foreach ($nf as $nff => $cols) {
                    foreach ($cols as $c) {
                        $dc[$c] = number_format($dc[$c], $nff);
                    }
                }
            
            }
            
            $this->profile('processing');
            $this->_output(array($pgs, $dcs));
        }
        
        
        # ------------------------------------------------------------------------
        # Autoprocessing Status
        function _ap_status() {
            session_write_close();
            
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            
            $ids = array();
            if (array_key_exists('ids', $_POST)) {
                foreach ($_POST['ids'] as $i) {
                    if (preg_match('/^\d+$/', $i)) array_push($ids,$i);
                }
            }
            
            $aps = array(
                         array('simple_strategy/', 'strategy_native.log', 'Phi start'),
                         array('edna/', 'summary.html', 'Selected spacegroup'),
                         
                         array('fast_dp/', 'fast_dp.log', 'dF/F'),
                         
                         array('xia2/2da-run/', 'xia2.txt' , 'dF/F'),
                         array('xia2/3da-run/', 'xia2.txt' , 'dF/F'),
                         array('xia2/3daii-run/', 'xia2.txt' , 'dF/F'),
                         
                         array('fast_ep/', 'fast_ep.log', 'Best spacegroup'),
                         array('fast_dp/dimple/', '09-refmac5_restr.log', 'Cruickshanks'),
                         );
            
            $out = array();
            
            foreach ($ids as $i) {
                $dc = $this->db->pq("SELECT c.samplechangerlocation as scon, bls.location as spos, bls.name as san, im.measuredintensity as flux, dc.filetemplate, dc.xtalsnapshotfullpath1 as x1, dc.xtalsnapshotfullpath2 as x2, dc.xtalsnapshotfullpath3 as x3, dc.xtalsnapshotfullpath4 as x4,dc.imageprefix as imp, dc.datacollectionnumber as run, dc.imagedirectory as dir, p.proposalcode || p.proposalnumber || '-' || s.visit_number as vis FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON s.sessionid=dc.sessionid INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) LEFT OUTER JOIN ispyb4a_db.blsample bls ON bls.blsampleid = dc.blsampleid LEFT OUTER JOIN ispyb4a_db.container c ON bls.containerid = c.containerid LEFT OUTER JOIN ispyb4a_db.image im ON (im.datacollectionid = dc.datacollectionid AND im.imagenumber = 1) WHERE dc.datacollectionid=:1 AND p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :2", array($i,$this->arg('visit')))[0];
                
                $dc['DIR'] = $this->ads($dc['DIR']);
                $root = str_replace($dc['VIS'], $dc['VIS'].'/processed', $dc['DIR']).$dc['IMP'].'_'.$dc['RUN'].'_'.'/';
            
                $apr = array();
                foreach ($aps as $ap) {
                    # 0: didnt run, 1: running, 2: success, 3: failed
                    $val = 0;
                    //$rts = glob($root.$ap[0]);
                    $rt = $root.$ap[0];
                    
                    //if (sizeof($rts) > 0) {
                    if (file_exists($rt)) {
                        $val = 1;
                        
                        $log = $root.$ap[0].$ap[1];
                        //$logs = glob($root.$ap[0].$ap[1]);
                        //if (sizeof($logs) > 0) {
                        //print $log;
                        if (is_readable($log)) {
                            $file = file_get_contents($log);
                            $val = 3;
                            if (strpos($file, $ap[2]) !== False) $val = 2;
                        }
                        //}
                    } //else $val = 3;
                    
                    array_push($apr, $val);
                    
                }
                
                $sn = 0;
                $images = array();
                foreach (array('X1', 'X2', 'X3', 'X4') as $j => $im) {
                    if (file_exists($dc[$im])) {
                        array_push($images, $j);
                        if ($im == 'X1') {
                            if (file_exists(str_replace('.png', 't.png', $dc[$im]))) $sn = 1;
                        }
                    }
                    unset($dc[$im]);
                }

                $dc['DIR'] = $this->ads($dc['DIR']);
                $dc['X'] = $images;
                
                $di = str_replace($this->arg('visit'), $this->arg('visit') . '/jpegs', $dc['DIR']).str_replace('.cbf', '.jpeg',preg_replace('/#+/', sprintf('%0'.substr_count($dc['FILETEMPLATE'], '#').'d', 1),$dc['FILETEMPLATE']));
                
                $die = 0;
                if (file_exists($di)) $die = 1;
                
                array_push($out, array($i, $apr, array($die,$images,$sn), array('FLUX' => $dc['FLUX'] ? sprintf('%.2e', $dc['FLUX']) : 'N/A', 'SCON' => $dc['SCON'], 'SPOS' => $dc['SPOS'], 'SAN' => $dc['SAN'])));
                
            }
            
            $this->_output($out);
        }
        
        
        
        # ------------------------------------------------------------------------
        # Edge Scan Data
        function _edge() {
            session_write_close();
            if (!$this->has_arg('id')) {
                $this->_error('No data collection id specified');
                return;
            }
            
            $info = $this->db->pq('SELECT jpegchoochfilefullpath as pth FROM ispyb4a_db.energyscan WHERE energyscanid=:1', array($this->arg('id')));
            if (sizeof($info) == 0) {
                $this->_error('No data for that collection id');
                return;
            }
            
            $ch = str_replace('.png', '', $info[0]['PTH']);
            
            $data = array(array(), array(), array());
            if (file_exists($ch)) {
                $dat = explode("\n",file_get_contents($ch));
                
                foreach ($dat as $i => $d) {
                    if ($d) {
                        list($x, $y) = explode(' ', $d);
                        array_push($data[0], array(floatval($x), intval($y)));
                    }
                }
                
                $dat = explode("\n",file_get_contents($ch.'.efs'));
                foreach ($dat as $i => $d) {
                    if ($d) {
                        list($x, $y, $y2) = preg_split('/\s+/', trim($d));
                        array_push($data[1], array(floatval($x), intval($y)));
                        array_push($data[2], array(floatval($x), intval($y2)));
                    }
                }
            }
            
            $this->_output($data);
        }
        
        
        # ------------------------------------------------------------------------
        # MCA Scan Data
        function _mca() {
            session_write_close();
            if (!$this->has_arg('id')) {
                $this->_error('No data collection id specified');
                return;
            }
            
            $info = $this->db->pq('SELECT scanfilefullpath as dat FROM ispyb4a_db.xfefluorescencespectrum WHERE xfefluorescencespectrumid=:1', array($this->arg('id')));
            if (sizeof($info) == 0) {
                $this->_error('No data for that spectrum id');
                return;
            }
            
            $info = $info[0];
            
            $data = array();
            if (file_exists($info['DAT'])) {
                $dat = explode("\n",file_get_contents($info['DAT']));
                
                foreach ($dat as $i => $d) {
                    if ($i >2 && $d) {
                        list($e, $v) = preg_split('/\s+/', trim($d));
                        if ($i % 2 == 1) array_push($data, array(floatval($e), floatval($v)));
                    }
                }
                
            }
            
            $this->_output($data);
        }
        
        
        # ------------------------------------------------------------------------        
        # Strategies for a data collection
        function _dc_strategies() {
            if (!$this->has_arg('id')) {
                $this->_error('No data collection id specified');
                return;
            }
            
            $rows = $this->db->pq('SELECT dc.transmission as dctrn, dc.wavelength as lam, dc.imagedirectory imd, dc.imageprefix as imp, dc.comments as dcc, dc.blsampleid as sid, sl.spacegroup as sg, sl.unitcell_a as a, sl.unitcell_b as b, sl.unitcell_c as c, sl.unitcell_alpha as al, sl.unitcell_beta as be, sl.unitcell_gamma as ga, s.shortcomments as com, sssw.axisstart as st, sssw.exposuretime as time, sssw.transmission as tran, sssw.oscillationrange as oscran, sssw.resolution as res, sssw.numberofimages as nimg FROM ispyb4a_db.screeningstrategy st INNER JOIN ispyb4a_db.screeningoutput so on st.screeningoutputid = so.screeningoutputid INNER JOIN ispyb4a_db.screening s on so.screeningid = s.screeningid INNER JOIN ispyb4a_db.screeningstrategywedge ssw ON ssw.screeningstrategyid = st.screeningstrategyid INNER JOIN ispyb4a_db.screeningstrategysubwedge sssw ON sssw.screeningstrategywedgeid = ssw.screeningstrategywedgeid INNER JOIN ispyb4a_db.screeningoutputlattice sl ON sl.screeningoutputid = st.screeningoutputid INNER JOIN ispyb4a_db.datacollection dc on s.datacollectionid = dc.datacollectionid WHERE s.datacollectionid = :1', array($this->arg('id')));
        
            $nf = array('A', 'B', 'C', 'AL', 'BE', 'GA');
            foreach ($rows as &$r) {
                foreach ($r as $k => &$v) {
                    if (in_array($k, $nf)) $v = number_format(floatval($v), 2);
                    if ($k == 'TRAN') $v = number_format($v, 1);
                    if ($k == 'TIME') $v = number_format($v, 3);
                    if ($k == 'OSCRAN') $v = number_format($v, 2);
                    if ($k == 'RES') $v = number_format($v, 2);
                }
                $r['VPATH'] = join('/', array_slice(explode('/', $r['IMD']),0,6));
                $r['BL'] = explode('/', $r['IMD'])[2];
                $r['DIST'] = $this->_r_to_dist($r['BL'], $r['LAM'], $r['RES']);
                $r['ATRAN'] = round($r['TRAN']/100.0*$r['DCTRN'],1);
                list($r['NTRAN'], $r['NEXP']) = $this->_norm_et($r['ATRAN'], $r['TIME']);
                $r['AP'] = $this->_get_ap($r['DCC']);
            }
                
            $this->_output($rows);
        }
        
        # ------------------------------------------------------------------------        
        # Normalise transmission fo 25hz data collection
        function _norm_et($t, $e) {
            if ($t < 100 && $e > 0.04) {
                $f = $e / 0.04;
                $maxe = 0.04;
                $maxt = ($e / 0.04) * $t;
                
                if ($maxt > 100) {
                    $maxe *= $maxt/100;
                    $maxt = 100;
                }
                return array(number_format($maxt,1), number_format($maxe,3));
            } else {
                return array($t, $e);
            }
        
        }
        
        # ------------------------------------------------------------------------        
        # Convert resolution to detector distance
        function _r_to_dist($bl, $lambda, $r) {
            $diam = $bl == 'i04-1' ? 252.5 : 415;
            $b=$lambda/(2*$r);
            $d=2*asin($b);
            $f=2*tan($d);
            
            return number_format($diam/$f, 2);
        }
        
        # ------------------------------------------------------------------------        
        # Work out which aperture is selected
        function _get_ap($com) {
            $aps = array('Aperture: Large'=>'LARGE_APERTURE',
                         'Aperture: Medium'=>'MEDIUM_APERTURE',
                         'Aperture: Small'=>'SMALL_APERTURE',
                         'Aperture: 10'=>'In_10',
                         'Aperture: 20'=>'In_20',
                         'Aperture: 30'=>'In_30',
                         'Aperture: 50'=>'In_50',
                         'Aperture: 70'=>'In_70');
            
            $app = '';
            foreach ($aps as $k => $v) {
                if (strpos($com, $k) !== False) $app = $v;
            }
            
            return $app;
        }
        
        
        # ------------------------------------------------------------------------
        # Auto processing for a data collection
        function _dc_auto_processing() {
            if (!$this->has_arg('id')) {
                $this->_error('No data collection id specified');
                return;
            }
        
            $rows = $this->db->pq('SELECT app.processingcommandline as type, apss.ntotalobservations as ntobs, apss.ntotaluniqueobservations as nuobs, apss.resolutionlimitlow as rlow, apss.resolutionlimithigh as rhigh, apss.scalingstatisticstype as shell, apss.rmerge, apss.completeness, apss.multiplicity, apss.meanioversigi as isigi, ap.spacegroup as sg, ap.refinedcell_a as cell_a, ap.refinedcell_b as cell_b, ap.refinedcell_c as cell_c, ap.refinedcell_alpha as cell_al, ap.refinedcell_beta as cell_be, ap.refinedcell_gamma as cell_ga FROM ispyb4a_db.autoprocintegration api INNER JOIN ispyb4a_db.autoprocscaling_has_int aph ON api.autoprocintegrationid = aph.autoprocintegrationid INNER JOIN ispyb4a_db.autoprocscaling aps ON aph.autoprocscalingid = aps.autoprocscalingid INNER JOIN ispyb4a_db.autoproc ap ON aps.autoprocid = ap.autoprocid INNER JOIN ispyb4a_db.autoprocscalingstatistics apss ON apss.autoprocscalingid = aph.autoprocscalingid INNER JOIN ispyb4a_db.autoprocprogram app ON api.autoprocprogramid = app.autoprocprogramid WHERE api.datacollectionid = :1', array($this->arg('id')));
            
            $types = array('fast_dp' => 'Fast DP', '-3da ' => 'XIA2 3da', '-2da ' => 'XIA2 2da', '-3daii ' => 'XIA2 3daii');
            
            $dts = array('rlow', 'rhigh', 'cell_a', 'cell_b', 'cell_c', 'cell_al', 'cell_be', 'cell_ga');
            

            foreach ($rows as &$r) {
                foreach ($r as $k => &$v) {
                    if ($k == 'TYPE') {
                        foreach ($types as $id => $name) {
                            if (strpos($v, $id)) {
                                $v = $name;
                                break;
                            }
                        }
                    }
                    
                    if (in_array(strtolower($k), $dts)) $v = number_format($v, 2);
                    
                    if ($k == 'RMERGE') $v = number_format($v, 3);
                    if ($k == 'COMPLETENESS') $v = number_format($v, 1);
                    if ($k == 'MULTIPLICITY') $v = number_format($v, 1);
                }
            }
                  
            $this->_output($rows);
        }
        
        
        # ------------------------------------------------------------------------
        # Results from downstream processing
        function _dc_downstream() {
            $ap = array('Fast EP' => array('fast_ep', array('sad.mtz', 'sad_fa.pdb')),
                        'MrBUMP' => array('auto_mrbump', array('PostMRRefine.pdb', 'PostMRRefine.mtz')),
                        #'Dimple' => array('fast_dp', array('dimple_refined.pdb', 'dimple_map.mtz')),
                        'Dimple' => array('fast_dp', array('dimple/final.pdb', 'dimple/final.mtz'))
                        );
            
            $info = $this->db->pq('SELECT dc.imageprefix as imp, dc.datacollectionnumber as run, dc.imagedirectory as dir, p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON s.sessionid=dc.sessionid INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE dc.datacollectionid=:1', array($this->arg('id')))[0];
            
            $info['DIR'] = $this->ads($info['DIR']);
            $data = array();
            
            foreach($ap as $n => $p) {
                $dat = array();
                
                $root = str_replace($info['VIS'], $info['VIS'] . '/processed', $info['DIR']).$info['IMP'].'_'.$info['RUN'].'_'.'/'.$p[0].'/';
                
                $file = $root . $p[1][0];
                if (file_exists($file)) {
                    $dat['TYPE'] = $n;
                    
                    # Fast EP
                    if ($n == 'Fast EP') {
                        # parse pdb file
                        
                        $ats = array();
                        $p1 = array();
                        $p2 = array();
                        
                        $pdb = file_get_contents($root . $p[1][1]);
                        foreach (explode("\n", $pdb) as $l) {
                            if (strpos($l,'HETATM') !== False) {
                                $parts = preg_split('/\s+/', $l);
                                array_push($ats, array($parts[1], $parts[5], $parts[6], $parts[7], $parts[8]));
                            }
                            
                        }
                        
                        $dat['ATOMS'] = array_slice($ats, 0, 5);
                        
                        if (file_exists($root.'/sad.lst')) {
                            $lst = file_get_contents($root.'/sad.lst');
                            $graph_vals = 0;
                            $gvals = array();
                            foreach (explode("\n", $lst) as $l) {
                                if (strpos($l, 'Estimated mean FOM and mapCC as a function of resolution') !== False) {
                                    $graph_vals = 1;
                                }
                                
                                if ($graph_vals && $graph_vals < 5) {
                                    array_push($gvals, $l);
                                    $graph_vals++;
                                }
                                
                                if (preg_match('/ Estimated mean FOM = (\d+.\d+)\s+Pseudo-free CC = (\d+.\d+)/', $l, $mat)) {
                                    $dat['FOM'] = floatval($mat[1]);
                                    $dat['CC'] = floatval($mat[2]);
                                }
                            }
                            
                            
                            if (sizeof($gvals) > 0) {
                                $x = array_map('floatval', array_slice(explode(' - ', $gvals[1]), 1));
                                $y = array_map('floatval', array_slice(preg_split('/\s+/', $gvals[2]), 1));
                                $y2 = array_map('floatval', array_slice(preg_split('/\s+/', $gvals[3]), 1));

                                foreach ($x as $i => $v) {
                                    array_push($p1, array($v, $y[$i]));
                                    array_push($p2, array($v, $y2[$i]));
                                }
                            }
                            
                        }
                        $dat['PLOTS']['FOM'] = $p1;
                        $dat['PLOTS']['CC'] = $p2;
                        array_push($data, $dat);
                        
                    # Dimple
                    } else if ($n == 'Dimple') {
                        //$pth = glob($root.'/EDApplication_*.log');
                        $lf = $root . '/dimple/09-refmac5_restr.log';
                        //if (sizeof($pth) > 0) {
                        if (file_exists($lf)) {
                            $log = file_get_contents($lf);
                         
                            $refmac = 0;
                            $stats = array();
                            $plot = 0;
                            $plots = array();
                            foreach (explode("\n", $log) as $l) {
                                if ($plot == 1) {
                                    $plot++;
                                    continue;
                                }
                                
                                if (strpos(trim($l), '$TEXT:Result: $$ Final results $$') !== False) {
                                    $refmac = 1;
                                    $stats = array();
                                    continue;
                                }
                                if (strpos(trim($l), '$$') !== False) $refmac = 0;
                                
                                if ($refmac) {
                                    array_push($stats, preg_split('/\s\s+/', trim($l)));
                                }
                                
                                if (strpos(trim($l), 'Ncyc    Rfact    Rfree') !== False) {
                                    $plot = 1;
                                    $plots = array();
                                    continue;
                                }
                                
                                if (strpos(trim($l), '$$') !== False) $plot = 0;
                                    
                                if ($plot) {
                                    array_push($plots, preg_split('/\s+/', trim($l)));
                                }
                            }
                            
                            $plts = array('RVC'=>array(), 'FVC'=>array(), 'RVR'=>array());
                            foreach ($plots as $p) {
                                $p = array_map('floatval', $p);
                                array_push($plts['RVC'], array($p[0], $p[1]));
                                array_push($plts['FVC'], array($p[0], $p[2]));
                            }
                            
                            array_unshift($stats[0], 'Parameter');
                            $dat['STATS'] = $stats;
                            $dat['PLOTS'] = $plts;
                            
                            $blobs = glob($root .'/dimple/blob*v*.png');
                            $dat['BLOBS'] = sizeof($blobs)/3;
                            
                            array_push($data, $dat);
                        }
                    }
                    
                    
                }
                
            }
            
            $this->_output($data);
        }
        
        
        # ------------------------------------------------------------------------
        # Image quality indicators from distl
        function _image_qi() {
            if (!$this->has_arg('id')) $this->_error('No data collection id specified');
            
            session_write_close();
            $iqs = array(array(), array(), array());
            $imqs = $this->db->pq('SELECT im.imagenumber as nim, imq.method1res as res, imq.spottotal as s, imq.goodbraggcandidates as b FROM ispyb4a_db.image im INNER JOIN ispyb4a_db.imagequalityindicators imq ON imq.imageid = im.imageid WHERE im.datacollectionid=:1 ORDER BY imagenumber', array($this->arg('id')));
            
            foreach ($imqs as $imq) {
                array_push($iqs[0], array(intval($imq['NIM']), intval($imq['S'])));
                array_push($iqs[1], array(intval($imq['NIM']), intval($imq['B'])));
                array_push($iqs[2], array(intval($imq['NIM']), floatval($imq['RES'])));
            }

            $this->_output($iqs);
        }

                
        # ------------------------------------------------------------------------
        # Flag a data collection
        function _flag() {
            if (!$this->has_arg('t')) $this->_error('No data type specified');
            if (!$this->arg('id')) $this->_error('No data collection id specified');
            
            $types = array('data' => ['datacollection', 'datacollectionid'],
                           'edge' => ['energyscan', 'energyscanid'],
                           'mca' => ['xfefluorescencespectrum', 'xfefluorescencespectrumid'],
                           );
            
            if (!array_key_exists($this->arg('t'), $types)) $this->_error('No such data type');
            $t = $types[$this->arg('t')];
            
            $com = $this->db->pq('SELECT comments from ispyb4a_db.'.$t[0].' WHERE '.$t[1].'=:1', array($this->arg('id')));
            
            if (!sizeof($com)) $this->_error('No such data collection');
            else $com = $com[0]['COMMENTS'];
            
            if (strpos($com, '_FLAG_') === false) {
                $this->db->pq("UPDATE ispyb4a_db.$t[0] set comments=comments||' _FLAG_' where $t[1]=:1", array($this->arg('id')));
                $this->_output(1);
            } else {
                $com = str_replace(' _FLAG_', '', $com);
                $this->db->pq("UPDATE ispyb4a_db.$t[0] set comments=:1 where $t[1]=:2", array($com, $this->arg('id')));
                
                $this->_output(0);
            }
        }
        
        
        # ------------------------------------------------------------------------
        # Update comment for a data collection
        function _set_comment() {
            if (!$this->has_arg('t')) $this->_error('No data type specified');
            if (!$this->arg('id')) $this->_error('No data collection id specified');
            if (!$this->arg('value')) $this->_error('No comment specified');
            
            $types = array('data' => ['datacollection', 'datacollectionid'],
                           'edge' => ['energyscan', 'energyscanid'],
                           'mca' => ['xfefluorescencespectrum', 'xfefluorescencespectrumid'],
                           );
            
            if (!array_key_exists($this->arg('t'), $types)) $this->_error('No such data type');
            $t = $types[$this->arg('t')];
            
            $com = $this->db->pq('SELECT comments from ispyb4a_db.'.$t[0].' WHERE '.$t[1].'=:1', array($this->arg('id')));
            
            if (!sizeof($com)) $this->_error('No such data collection');
            
            $this->db->pq("UPDATE ispyb4a_db.$t[0] set comments=:1 where $t[1]=:2", array($this->arg('value'), $this->arg('id')));
            
            print $this->arg('value');
        }
        
        
    }

?>