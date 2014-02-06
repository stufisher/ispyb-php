<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('page' => '\d+',
                              'a' => '\d+(.\d+)?',
                              'b' => '\d+(.\d+)?',
                              'c' => '\d+(.\d+)?',
                              'al' => '\d+(.\d+)?',
                              'be' => '\d+(.\d+)?',
                              'ga' => '\d+(.\d+)?',
                              'tol' => '\d+(.\d+)?',
                              'res' => '\d+(.\d+)?',
                              'sg' => '\w+',
                              'id' => '\d+',
                              'pdb' => '\w+',
                              'title' => '.*',
                              'author' => '.*',
                              'bl' => '.*',
                              'year' => '.*',
                              'iDisplayStart' => '\d+',
                              'iDisplayLength' => '\d+',
                              'iSortCol_0' => '\d+',
                              'sSortDir_0' => '\w+',
                              'sSearch' => '\w+',
                              'pp' => '\d+',
                              'page' => '\d+',
                              );
        
        var $dispatch = array('cells' => '_cells',
                              'analysed' => '_analysed',
                              'dc' => '_lookup_dc',
                              );
        
        var $def = 'cells';
        #var $profile = True;
        #var $debug = True;
        
        # ------------------------------------------------------------------------
        # List data collections / visits for a particular cell
        function _cells() {
            session_write_close();
            
            $tol = $this->has_arg('tol') ? $this->arg('tol') : 0.01;
            
            $args = array();
            foreach (array('a', 'b', 'c', 'al', 'be', 'ga') as $p) {
                if (!$this->has_arg($p)) $this->_error('One or more unit cell parameters are missing');
                
                array_push($args, $this->arg($p)*(1-$tol));
                array_push($args, $this->arg($p)*(1+$tol));
            }
            
            $tot_args = $args;
            foreach (array('a', 'b', 'c', 'al', 'be', 'ga') as $p) array_push($args, $this->arg($p));
            
            if ($this->has_arg('year')) {
                array_push($args, $this->arg('year'));
            } else {
                array_push($args, strftime('%Y-%m-%d'));
            }
            array_push($tot_args, $args[sizeof($args)-1]);
            
            $rest = '';
            $sgt = '';
            
            if ($this->has_arg('res')) {
                $res = 'AND apss.resolutionlimithigh <= :'.(sizeof($args)+1);
                array_push($args, $this->arg('res'));
                $rest = 'AND apss.resolutionlimithigh <= :'.(sizeof($tot_args)+1);
                array_push($tot_args, $this->arg('res'));
            } else $res = '';
            
            if ($this->has_arg('sg')) {
                $sg = 'AND ap.spacegroup LIKE :'.(sizeof($args)+1);
                array_push($args, $this->arg('sg'));
                $sgt = 'AND ap.spacegroup LIKE :'.(sizeof($tot_args)+1);
                array_push($tot_args, $this->arg('sg'));
            } else $sg = '';
            
            //print_r($args);
            
            $nostafft = '';
            if (!$this->staff) {
                $nostaff = "INNER JOIN investigation@DICAT_RO i ON lower(i.visit_id) = p.proposalcode || p.proposalnumber || '-' || s.visit_number INNER JOIN investigationuser@DICAT_RO iu on i.id = iu.investigation_id INNER JOIN user_@DICAT_RO u on (u.id = iu.user_id AND u.name=:".(sizeof($args)+1).")";
                array_push($args, phpCAS::getUser());
                
                $nostafft = "INNER JOIN investigation@DICAT_RO i ON lower(i.visit_id) = p.proposalcode || p.proposalnumber || '-' || s.visit_number INNER JOIN investigationuser@DICAT_RO iu on i.id = iu.investigation_id INNER JOIN user_@DICAT_RO u on (u.id = iu.user_id AND u.name=:".(sizeof($tot_args)+1).")";
                array_push($tot_args, phpCAS::getUser());
            } else $nostaff = '';
            
            
            $tot = $this->db->pq("SELECT count(ap.refinedcell_a) as tot FROM ispyb4a_db.autoprocintegration api INNER JOIN ispyb4a_db.autoprocscaling_has_int aph ON api.autoprocintegrationid = aph.autoprocintegrationid INNER JOIN ispyb4a_db.autoprocscaling aps ON aph.autoprocscalingid = aps.autoprocscalingid INNER JOIN ispyb4a_db.autoproc ap ON aps.autoprocid = ap.autoprocid INNER JOIN ispyb4a_db.autoprocscalingstatistics apss ON apss.autoprocscalingid = aph.autoprocscalingid INNER JOIN ispyb4a_db.autoprocprogram app ON api.autoprocprogramid = app.autoprocprogramid INNER JOIN ispyb4a_db.datacollection dc ON api.datacollectionid = dc.datacollectionid INNER JOIN ispyb4a_db.blsession s ON s.sessionid = dc.sessionid INNER JOIN ispyb4a_db.proposal p ON s.proposalid = p.proposalid $nostafft WHERE p.proposalcode != 'in' AND apss.scalingstatisticstype LIKE 'overall' AND (ap.refinedcell_a BETWEEN :1 AND :2) AND (ap.refinedcell_b BETWEEN :3 AND :4) AND (ap.refinedcell_c BETWEEN :5 AND :6) AND (ap.refinedcell_alpha BETWEEN :7 AND :8) AND (ap.refinedcell_beta BETWEEN :9 AND :10) AND (ap.refinedcell_gamma BETWEEN :11 AND :12) AND to_date(:13, 'YYYY-MM-DD') >= dc.starttime $rest $sgt", $tot_args);
            
            if (sizeof($tot)) $tot = $tot[0]['TOT'];
            else $tot = 0;
            
            $start = 0;
            $end = 10;
            $pp = $this->has_arg('pp') ? $this->arg('pp') : 15;
            
            if ($this->has_arg('page')) {
                $pg = $this->arg('page') - 1;
                $start = $pg*$pp;
                $end = $pg*$pp+$pp;
            }
            
            $st = sizeof($args)+1;
            $en = $st + 1;
            array_push($args, $start);
            array_push($args, $end);
            
            $pgs = intval($tot/$pp);
            if ($tot % $pp != 0) $pgs++;
            
            $rows = $this->db->pq("SELECT outer.* FROM (SELECT ROWNUM rn, inner.* FROM (SELECT sqrt(power(ap.refinedcell_a-:13,2)+power(ap.refinedcell_b-:14,2)+power(ap.refinedcell_c-:15,2)+power(ap.refinedcell_alpha-:16,2)+power(ap.refinedcell_beta-:17,2)+power(ap.refinedcell_gamma-:18,2)) as dist, s.beamlinename as bl, app.processingcommandline as type, apss.ntotalobservations as ntobs, apss.ntotaluniqueobservations as nuobs, apss.resolutionlimitlow as rlow, apss.resolutionlimithigh as rhigh, apss.scalingstatisticstype as shell, apss.rmerge, apss.completeness, apss.multiplicity, apss.meanioversigi as isigi, ap.spacegroup as sg, ap.refinedcell_a as cell_a, ap.refinedcell_b as cell_b, ap.refinedcell_c as cell_c, ap.refinedcell_alpha as cell_al, ap.refinedcell_beta as cell_be, ap.refinedcell_gamma as cell_ga, dc.datacollectionid as id, TO_CHAR(dc.starttime, 'DD-MM-YYYY HH24:MI:SS') as st, dc.imagedirectory as dir, dc.filetemplate, p.proposalcode || p.proposalnumber || '-' || s.visit_number as visit, dc.numberofimages as numimg, dc.axisrange, dc.axisstart, dc.wavelength, dc.transmission, dc.exposuretime FROM ispyb4a_db.autoprocintegration api INNER JOIN ispyb4a_db.autoprocscaling_has_int aph ON api.autoprocintegrationid = aph.autoprocintegrationid INNER JOIN ispyb4a_db.autoprocscaling aps ON aph.autoprocscalingid = aps.autoprocscalingid INNER JOIN ispyb4a_db.autoproc ap ON aps.autoprocid = ap.autoprocid INNER JOIN ispyb4a_db.autoprocscalingstatistics apss ON apss.autoprocscalingid = aph.autoprocscalingid INNER JOIN ispyb4a_db.autoprocprogram app ON api.autoprocprogramid = app.autoprocprogramid INNER JOIN ispyb4a_db.datacollection dc ON api.datacollectionid = dc.datacollectionid INNER JOIN ispyb4a_db.blsession s ON s.sessionid = dc.sessionid INNER JOIN ispyb4a_db.proposal p ON s.proposalid = p.proposalid $nostaff WHERE p.proposalcode != 'in' AND apss.scalingstatisticstype LIKE 'overall' AND (ap.refinedcell_a BETWEEN :1 AND :2) AND (ap.refinedcell_b BETWEEN :3 AND :4) AND (ap.refinedcell_c BETWEEN :5 AND :6) AND (ap.refinedcell_alpha BETWEEN :7 AND :8) AND (ap.refinedcell_beta BETWEEN :9 AND :10) AND (ap.refinedcell_gamma BETWEEN :11 AND :12) AND to_date(:19, 'YYYY-MM-DD') >= dc.starttime $res $sg ORDER BY dist) inner) outer WHERE outer.rn > :$st AND outer.rn <= :$en", $args);
                        
            $types = array('fast_dp' => 'Fast DP', '-3d' => 'XIA2 3d', '-3dii' => 'XIA2 3dii', '-3da ' => 'XIA2 3da', '-2da ' => 'XIA2 2da', '-2d' => 'XIA2 2d', '-2dr' => 'XIA2 2dr', '-3daii ' => 'XIA2 3daii', '-blend' => 'MultiXIA2');
                                  
            foreach ($rows as &$dc) {
                foreach ($types as $id => $name) {
                    if (strpos($dc['TYPE'], $id) !== false) {
                        $dc['TYPE'] = $name;
                        break;
                    }
                }
                
                $users = $this->db->pq("SELECT u.name,u.fullname FROM investigation@DICAT_RO i INNER JOIN investigationuser@DICAT_RO iu on i.id = iu.investigation_id INNER JOIN user_@DICAT_RO u on u.id = iu.user_id WHERE lower(i.visit_id)=:1", array($dc['VISIT']));
                
                $dc['USERS'] = array();
                foreach ($users as $u) {
                    array_push($dc['USERS'], $u['FULLNAME']);
                }
                
                $dc['DIR'] = $this->ads($dc['DIR']);
                $dc['DIR'] = substr($dc['DIR'], strpos($dc['DIR'], $dc['VISIT'])+strlen($dc['VISIT'])+1);
                
                $dc['WAVELENGTH'] = number_format($dc['WAVELENGTH'], 3);
                $dc['TRANSMISSION'] = number_format($dc['TRANSMISSION'], 3);
            }
            
            if ($this->has_arg('pdb')) {
                if (file_exists('tables/pdbs.json')) {
                    $pdbs = json_decode(file_get_contents('tables/pdbs.json'));
                } else $pdbs = new stdClass();

                //if (!property_exists($pdbs,$this->arg('pdb'))) {
                if (1) {
                    $d = array();
                    foreach (array('a', 'b', 'c', 'al', 'be', 'ga', 'res', 'title', 'author', 'bl', 'pdb', 'year') as $e) $d[strtoupper($e)] = $this->arg($e);
                    
                    $blmatch = false;
                    $umatch = false;
                    $umatchl = array();
                    $dcids = array();
                    $bls = array();
                    
                    foreach ($rows as $r) {
                        $dcids[$r['ID']] = 1;
                        $bls[$r['BL']] = 1;
                        
                        if (str_replace('DIAMOND BEAMLINE ', '', $this->arg('bl')) ==  strtoupper($r['BL'])) $blmatch = true;
                        
                        foreach ($r['USERS'] as $u) {
                            $parts = explode(' ', $u);
                            
                            if (strpos($this->arg('author'), end($parts)) !== false) {
                                $umatch = true;
                                if (!in_array($u, $umatchl)) array_push($umatchl, $u);
                            }
                        }
                    }
                    
                    $d['CLOSEST'] = sizeof($rows) ? $rows[0]['BL'] : '';
                    $d['DIST'] = sizeof($rows) ? $rows[0]['DIST'] : '';
                    $d['RESULTS'] = $tot;
                    
                    $d['BLS'] = array_keys($bls);
                    $d['DCIDS'] = array_keys($dcids);
                    $d['UMATCH'] = $umatch;
                    $d['UMATCHL'] = $umatchl;
                    $d['BLMATCH'] = $blmatch;
                    
                    $p = $this->arg('pdb');
                    $pdbs->$p = $d;
                    file_put_contents('tables/pdbs.json', json_encode($pdbs));
                }
            }
            
            $this->_output(array($tot, $pgs, $rows));
        }
                                                        
           
        # ------------------------------------------------------------------------
        # List pdb codes that have been analysed
        function _analysed() {
            $rows = array();
            $processed = array();
            $stats = array();
            $perbl = array();
            $perbl_old = array();
            $blns = array('i02', 'i03', 'i04', 'i04-1', 'i24');
            
            $st = $this->has_arg('iDisplayStart') ? $this->arg('iDisplayStart') : 0;
            $len = $this->has_arg('iDisplayLength') ? $this->arg('iDisplayLength') : 10;
            
            foreach (array('No Results', 'No Match', 'Mismatch', 'Matched') as $k) $stats[$k] = 0;
            
            if (file_exists('tables/pdbs.json')) {
                $pdbs = json_decode(file_get_contents('tables/pdbs.json'));
                $tot = sizeof(get_object_vars($pdbs));
                
                foreach ($pdbs as $pdb => $d) {
                    if (strtotime($d->YEAR) < strtotime('2010-05-01')) continue;
                    
                    $s = '';
                    if ($d->RESULTS == 0) $s = 'No Results';
                    if ($d->RESULTS > 0 && !$d->BLMATCH) $s = 'No Match';
                    if ($d->RESULTS > 0 && !$d->BLMATCH && $d->UMATCH) $s = 'Mismatch';
                    if ($d->BLMATCH) $s = 'Matched';
                    
                    #if ($s == 'Matched') array_push($processed, $d->PDB);
                    if ($s != 'No Results') array_push($processed, $d->PDB);
                      
                    $stats[$s]++;
                    
                    $row = array('<a href="/cell/pdb/'.$d->PDB.'">'.$d->PDB.'</a> <a class="ext" href="http://www.rcsb.org/pdb/explore/explore.do?structureId='.$d->PDB.'">PDB</a>', $d->YEAR, strtolower(str_replace('DIAMOND BEAMLINE ', '', $d->BL)), ($d->BLMATCH ? 'Yes' : 'No'), ($d->UMATCH ? 'Yes' : 'No'), $d->CLOSEST.($d->CLOSEST ? (' ('.number_format($d->DIST,2).')'):''), implode(', ',$d->BLS), $d->RESULTS, $s);
                    
                    if ($this->has_arg('sSearch')) {
                        if (strpos(strtolower($d->PDB), strtolower($this->arg('sSearch'))) !== false || strpos(strtolower($s), strtolower($this->arg('sSearch'))) !== false) array_push($rows, $row);
                    } else array_push($rows, $row);
                    
                    if ($d->BLMATCH || $d->UMATCH) {
                        list($y) = explode('-', $d->YEAR);

                        if (preg_match('/(\w\d\d(-\d)?)/', $d->BL, $m)) {
                            $blid = array_search(strtolower($m[0]), $blns);
                            
                            #print_r(array($d->BL, $blid, $m));
                            
                            if ($blid !== false) {
                                if (!array_key_exists($y, $perbl_old)) $perbl_old[$y] = array();
                                if (!array_key_exists($blid, $perbl_old[$y])) $perbl_old[$y][$blid] = 0;
                                $perbl_old[$y][$blid]++;
                            }
                        }
                        
                        if ($d->CLOSEST) {
                            $b = $d->CLOSEST;
                            $blid = array_search($b, $blns);
                          
                            if (!array_key_exists($y, $perbl)) $perbl[$y] = array();
                            if (!array_key_exists($blid, $perbl[$y])) $perbl[$y][$blid] = 0;
                          $perbl[$y][$blid]++;
                        }
                    }
                }
            } else $tot = 0;
            
            if ($this->has_arg('iSortCol_0')) {
                $v = $this;
                usort($rows, function($a, $b) use ($v) {
                    $c = $v->arg('iSortCol_0');
                    $d = $v->has_arg('sSortDir_0') ? ($v->arg('sSortDir_0') == 'asc' ? 1 : 0) : 0;
                      
                    if (gettype($a[$c]) == 'string') return $d ? strcmp($a[$c],$b[$c]) : strcmp($b[$c], $a[$c]);
                    else return $d ? ($a[$c] - $b[$c]) : ($b[$c] - $a[$c]);
                });
            }
            
            $this->_output(array('iTotalRecords' => $tot,
                                 'iTotalDisplayRecords' => sizeof($rows),
                                 'stats' => $stats,
                                 'perbl' => $perbl,
                                 'perbl_old' => $perbl_old,
                                 'processed' => $processed,
                                 'aaData' => array_slice($rows, $st, $len),
                           ));
        }

    }

?>