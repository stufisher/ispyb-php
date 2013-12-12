<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('visit' => '\w+\d+-\d+', 's' => '\w+', 'd' => '([\w\/])+', 'id' => '\d+', 'sg' => '\w+', 'a' => '\d+(.\d+)?', 'b' => '\d+(.\d+)?', 'c' => '\d+(.\d+)?', 'alpha' => '\d+(.\d+)?', 'beta' => '\d+(.\d+)?', 'gamma' => '\d+(.\d+)?', 'res' => '\d+(.\d+)?', 'rfrac' => '\d+(.\d+)?', 'isigi' => '\d+(.\d+)?', 'run' => '\d+', 'type' => '\d+', 'local' => '\d+', 'user' => '\d+');
        var $dispatch = array('list' => '_data_collections',
                              'dirs' => '_get_dirs',
                              'cells' => '_get_cells',
                              'integrate' => '_integrate',
                              'blend' => '_blend',
                              'blended' => '_blended',
                              'delete' => '_delete',
                              'dend' => '_dendrogram',
                              
                              'status' => '_get_status',
                              
                              'xscale' => '_xscale',
                              'cluster' => '_cluster',
                              );
        
        var $def = 'list';
        var $profile = True;
        //var $debug = True;
        
        # ------------------------------------------------------------------------
        # Get directories for visit
        function _get_dirs() {
            session_write_close();
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            
            $info = $this->db->pq("SELECT s.sessionid, s.beamlinename as bl, vr.run, vr.runid FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));

            if (!sizeof($info)) $this->_error('No such visit');
            $info = $info[0];
            
            $rows = $this->db->pq("SELECT distinct dc.imagedirectory as dir FROM ispyb4a_db.datacollection dc WHERE dc.sessionid=:1 ORDER BY dc.imagedirectory", array($info['SESSIONID']));
                               
            $dirs = array();
            foreach ($rows as &$r) {
                $r['DIR'] = $this->ads($r['DIR']);
                $r['DIR'] = substr($r['DIR'], strpos($r['DIR'], $this->arg('visit'))+strlen($this->arg('visit'))+1);                                  
                array_push($dirs, preg_replace('/\/$/', '', $r['DIR']));
            }
                                  
            $this->_output($dirs);
        }
                                  

        # ------------------------------------------------------------------------
        # Get unit cells for any autoprocessed data sets
        function _get_cells() {
            if (!$this->has_arg('visit')) {
                $this->_error('No visit specified');
            }
            
            $args = array($this->arg('visit'));
            $where = '';
            
            if ($this->has_arg('d')) {
                array_push($args, $this->arg('d'));
                $where = " AND dc.imagedirectory LIKE '%".$this->arg('d')."/'";
            }
            
            if ($this->has_arg('id')) {
                array_push($args, $this->arg('id'));
                $where .= " AND dc.datacollectionid=:".sizeof($args);
            }
            
            $rows = $this->db->pq("SELECT dc.imagedirectory as dir, dc.filetemplate as prefix, dc.datacollectionid as id, app.processingcommandline as type,  ap.spacegroup as sg, ap.refinedcell_a as cell_a, ap.refinedcell_b as cell_b, ap.refinedcell_c as cell_c, ap.refinedcell_alpha as cell_al, ap.refinedcell_beta as cell_be, ap.refinedcell_gamma as cell_ga FROM ispyb4a_db.autoprocintegration api INNER JOIN ispyb4a_db.autoprocscaling_has_int aph ON api.autoprocintegrationid = aph.autoprocintegrationid INNER JOIN ispyb4a_db.autoprocscaling aps ON aph.autoprocscalingid = aps.autoprocscalingid INNER JOIN ispyb4a_db.autoproc ap ON aps.autoprocid = ap.autoprocid INNER JOIN ispyb4a_db.autoprocprogram app ON api.autoprocprogramid = app.autoprocprogramid INNER JOIN ispyb4a_db.datacollection dc on api.datacollectionid = dc.datacollectionid INNER JOIN ispyb4a_db.blsession s ON s.sessionid = dc.sessionid INNER JOIN ispyb4a_db.v_run vr ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1 $where", $args);
            
            $types = array('fast_dp' => 'Fast DP', '-3da ' => 'XIA2 3da', '-2da ' => 'XIA2 2da', '-3daii ' => 'XIA2 3daii');
            
            $dts = array('rlow', 'rhigh', 'cell_a', 'cell_b', 'cell_c', 'cell_al', 'cell_be', 'cell_ga');
            

            foreach ($rows as &$r) {
                $r['DIR'] = $this->ads($r['DIR']);
                $r['DIR'] = substr($r['DIR'], strpos($r['DIR'], $this->arg('visit'))+strlen($this->arg('visit'))+1);
                                  
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
        # Find out how many jobs are running
        function _get_status() {
            if ($this->has_arg('local')) {
                $jobs = exec('ps aux | grep blend.sh | wc -l') - 2;
                
            } else {
                $jobs = exec('module load global/cluster;qstat -u vxn01537 | grep x2 | wc -l') - 0;
            }

            $this->_output($jobs);
        }
        
        
        # ------------------------------------------------------------------------
        # Multicrystal data collection list
        function _data_collections() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            session_write_close();
            
            $info = $this->db->pq("SELECT s.sessionid, s.beamlinename as bl, TO_CHAR(s.startdate, 'YYYY') as yr, vr.run, vr.runid FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));

            if (!sizeof($info)) $this->_error('No such visit');
            $info = $info[0];
            
            $vis = '/dls/'.$info['BL'].'/data/'.$info['YR'].'/'.$this->arg('visit');
            if ($this->has_arg('user')) {
                $us = $this->dirs($vis.'/processing/auto_mc');
                $u = $us[$this->arg('user')];
            } else $u = phpCAS::getUser();
            
            $where = '';
            $args = array($info['SESSIONID']);
            
            if ($this->has_arg('s')) {
                array_push($args, $this->arg('s'));
                $where .= " AND (dc.imagedirectory LIKE '%".$this->arg('s')."%' OR dc.filetemplate LIKE '%".$this->arg('s')."%')";
            }

            if ($this->has_arg('d')) {
                array_push($args, $this->arg('d'));
                $where .= " AND dc.imagedirectory LIKE '%".$this->arg('d')."/'";
            }
            
            $rows = $this->db->pq("SELECT TO_CHAR(dc.starttime, 'DD-MM-YYYY HH24:MI:SS') as st, dc.filetemplate as prefix, dc.imagedirectory as dir, dc.datacollectionid as did, dc.numberofimages as ni, dc.axisstart as ost, dc.axisrange as oos FROM ispyb4a_db.datacollection dc WHERE dc.sessionid=:1 AND dc.axisrange > 0 $where ORDER BY dc.imagedirectory, dc.starttime", $args);
            
            foreach ($rows as $i => &$r) {
                $r['INT'] = 0;
                $root = str_replace($this->arg('visit'), $this->arg('visit').'/processing/auto_mc/'.$u, $r['DIR']).str_replace('####.cbf', '', $r['PREFIX']);
                
                if (file_exists($root)) $r['INT'] = 1;
                if (file_exists($root.'/xia2-summary.dat')) {
                    $log = explode("\n", file_get_contents($root.'/xia2-summary.dat'));
                    $stats = array();
                    $r['INT'] = 2;
                    
                    foreach ($log as $l) {
                        if (strpos($l, 'High resolution limit') !== false) $stats['RESH'] = number_format(preg_split('/\t/', $l)[1],2);
                        if (strpos($l, 'Completeness') !== false) $stats['C'] = number_format(preg_split('/\t/', $l)[1],1);
                        if (strpos($l, 'Rmerge') !== false) $stats['R'] = number_format(preg_split('/\t/', $l)[1],3);
                        if (strpos($l, 'Cell:') !== false) $stats['CELL'] = array_slice(preg_split('/\s+/', $l), 1);
                        if (strpos($l, 'Spacegroup:') !== false) $stats['SG'] = preg_split('/\s+/', $l)[1];
                    }
                    
                    $r['STATS'] = $stats;
                }
                
                if (file_exists($root.'/xia2-xinfo.error') || file_exists($root.'/xia2.error')) $r['INT'] = 3;
                
                
                $r['DIR'] = $this->ads($r['DIR']);
                $r['DIR'] = substr($r['DIR'], strpos($r['DIR'], $this->arg('visit'))+strlen($this->arg('visit'))+1);
            }
                                  
            $this->_output(array(sizeof($rows), $rows));
        }
                                  
                                  
                                  
        # ------------------------------------------------------------------------
        # Integrate multiple data sets
        function _integrate() {
            $ret = '';
            $args = array();
            $where = array();
            
            $running = array_map(function($i) { return intval(preg_replace('/.*x2(\d+).*/', '$1', $i)); }, explode("\n", exec('module load global/cluster;qstat -u vxn01537 | grep x2')));
            
            $ranges = array();
            foreach ($_POST['int'] as $d) {
                if (is_numeric($d[0]) && is_numeric($d[1]) && is_numeric($d[2]) && !in_array($d[0], $running)) {
                    array_push($args, $d[0]);
                    array_push($where, 'dc.datacollectionid=:'.sizeof($args));
                    $ranges[$d[0]] = array($d[1], $d[2]);
                }
            }
                                 
            if (sizeof($where)) {
                $where = implode(' OR ', $where);
                
                $rows = $this->db->pq("SELECT dc.datacollectionid as id, dc.wavelength,dc.filetemplate as prefix, dc.imagedirectory as dir, p.proposalcode || p.proposalnumber || '-' || s.visit_number as visit FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON dc.sessionid = s.sessionid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE $where", $args);
                                      
                foreach ($rows as $i => $r) {
                    $root = str_replace($r['VISIT'], $r['VISIT'].'/processing/auto_mc/'.phpCAS::getUser(),$r['DIR']) . str_replace('####.cbf', '', $r['PREFIX']);
                    
                    $st = $ranges[$r['ID']][0] + 1;
                    $en = $ranges[$r['ID']][1];

                    if (!file_exists($root)) mkdir($root, 0777, true);
                    chdir($root);
                    
                    foreach (glob($root.'/xia*') as $f) unlink($f);
                    
                    $xinfo ="BEGIN PROJECT AUTOMATIC\nBEGIN CRYSTAL DEFAULT\nBEGIN WAVELENGTH NATIVE\nWAVELENGTH ".$r['WAVELENGTH']."\nEND WAVELENGTH NATIVE\n\nBEGIN SWEEP SWEEP1\nWAVELENGTH NATIVE\nDIRECTORY ".$r['DIR']."\nIMAGE ".str_replace('####.cbf', '0001.cbf', $r['PREFIX'])."\nSTART_END ".$st." ".$en."\nEND SWEEP SWEEP1\n\nEND CRYSTAL DEFAULT\nEND PROJECT AUTOMATIC";
                    
                    file_put_contents($root.'/xia.xinfo', $xinfo);
                    
                    $cell = $this->arg('a') ? "-cell ".$this->arg('a').",".$this->arg('b').",".$this->arg('c').",".$this->arg('alpha').",".$this->arg('beta').",".$this->arg('gamma') : '';
                    $res = $this->arg('res') ? "-resolution ".$this->arg('res') : '';
                    $sg = $this->arg('sg') ? "-spacegroup ".$this->arg('sg') : '';
                    
                    $remote = "module load xia2/4479\necho 'xds.colspot.minimum_pixels_per_spot=3' > spot.phil\nxia2 -failover -3daii $sg $cell $res -xinfo xia.xinfo -phil spot.phil";
                    file_put_contents($root.'/x2'.$r['ID'].'.sh', $remote);
                    
                    $ret = exec('module load global/cluster;qsub x2'.$r['ID'].'.sh');
                    
                    
                }
            } else $ret = 'No data sets specified';
            
            $this->_output($ret);
        }
        
        # ------------------------------------------------------------------------
        # Blend analyse selected integrated data sets
        function _blend() {
            session_write_close();
            
            $ret = '';
            
            $args = array();
            $where = array();
            
            if (!array_key_exists('dcs', $_POST)) $this->_error('No data collections specified');
            
            foreach ($_POST['dcs'] as $d) {
                if (is_numeric($d)) {
                    array_push($args, $d);
                    array_push($where, 'dc.datacollectionid=:'.sizeof($args));
                }
            }
            
            if (sizeof($where)) {
                if ($this->has_arg('user')) {
                    $us = $this->dirs($vis.'/processing/auto_mc');
                    $u = $us[$this->arg('user')];
                } else $u = phpCAS::getUser();
                
                $where = implode(' OR ', $where);
                
                $rows = $this->db->pq("SELECT dc.datacollectionid as id, dc.wavelength,dc.filetemplate as prefix, dc.imagedirectory as dir, p.proposalcode || p.proposalnumber || '-' || s.visit_number as visit FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON dc.sessionid = s.sessionid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE $where", $args);
                
                $files = array();
                $ids = array();
                $blend = '';
                foreach ($rows as $i => $r) {
                    if (!$blend) {
                        $blend = substr($r['DIR'], 0, strpos($r['DIR'], $r['VISIT'])).$r['VISIT'].'/processing/auto_mc/'.$u.'/blend';
                    }
                    
                    $root = str_replace($r['VISIT'], $r['VISIT'].'/processing/auto_mc/'.phpCAS::getUser(),$r['DIR']) . str_replace('####.cbf', '', $r['PREFIX']);
                
                    #$hkl = $root.'/DEFAULT/NATIVE/SWEEP1/integrate/INTEGRATE.HKL';
                    $hkl = $root.'/DEFAULT/scale/NATIVE_SWEEP1.HKL';
                    if (file_exists($hkl)) {
                        array_push($files, $hkl);
                        array_push($ids, $r['ID']);
                    }
                    
                }
                
                if ($this->arg('type') == 1) {
                    $blend .= '/analyse';
                    if (file_exists($blend)) $this->rrmdir($blend);
                    $radfrac = 0.25;
                    
                } else {
                    $last = 0;
                    foreach (glob($blend.'/run_*') as $d) {
                        if (preg_match('/run_(\d+)$/', $d, $m)) {
                            if ($m[1] > $last) $last = $m[1];
                        }
                    }
                    $last++;
                    $blend .= '/run_'.$last;
                    $radfrac = $this->has_arg('rfrac') ? $this->arg('rfrac') : 0.25;
                }
                
                if (!file_exists($blend)) mkdir($blend, 0777, true);
                chdir($blend);
                
                $isigi = $this->has_arg('isigi') ? $this->arg('isigi') : 1.5;
                $res = $this->has_arg('res') ? ('RESOLUTION HIGH '.$this->arg('res')) : '';
                
                $sets = array();
                for ($i = 0; $i < sizeof($args); $i++) array_push($sets, $i+1);
                
                file_put_contents($blend.'/files.dat', implode("\n", $files));
                file_put_contents($blend.'/ids.dat', implode("\n", $ids));
                
                $cmd = "blend -a files.dat 2> blend.elog";
                if ($this->arg('type') == 0) $cmd .= "\nblend -c ".implode(' ', $sets)." 2>> blend.elog";
                
                file_put_contents($blend.'/blend.sh', "#!/bin/sh\nmodule load blend\n".$cmd);
                
                $sg = $this->has_arg('sg')  ? ("CHOOSE SPACEGROUP ".$this->arg('sg')) : '';
                file_put_contents($blend.'/BLEND_KEYWORDS.dat', "BLEND KEYWORDS\nNBIN	  20\nRADFRAC   $radfrac\nISIGI     $isigi\nCPARWT    1.000\nPOINTLESS KEYWORDS\n$sg\nAIMLESS KEYWORDS\n$res");
                
                # no x11 on cluster
                #$ret = exec("module load global/cluster;qsub blend.sh");
                $ret = exec("chmod +x blend.sh;./blend.sh &");
                
            } else $ret = 'No data sets specified';
            
            $this->_output($ret);
            
        }
                                  
                                      
        # ------------------------------------------------------------------------
        # List of blended data sets
        function _blended() {
            session_write_close();
            
            $info = $this->db->pq("SELECT TO_CHAR(s.startdate, 'YYYY') as yr, s.sessionid, s.beamlinename as bl, vr.run, vr.runid FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));
            
            if (!sizeof($info)) $this->_error('No such visit');
            $info = $info[0];
            
            $vis = '/dls/'.$info['BL'].'/data/'.$info['YR'].'/'.$this->arg('visit');
            
            if ($this->has_arg('user')) {
                $us = $this->dirs($vis.'/processing/auto_mc');
                $u = $us[$this->arg('user')];
            } else $u = phpCAS::getUser();
            
            $root = $vis.'/processing/auto_mc/'.$u.'/blend';
            
            $runs = array();
            if (file_exists($root)) {
                foreach (glob($root.'/run_*') as $r) {
                    $id = preg_match('/run_(\d+)$/', $r, $m) ? $m[1] : 0;
                    $run = array('ID' => $id, 'STATE' => 0);
                    
                    $log = $r.'/blend.elog';
                    if (file_exists($log)) {
                        foreach (explode("\n", file_get_contents($log)) as $l) {
                            if (strpos($l, 'Execution halted') !== false) $run['STATE'] = 2;
                        }
                    }
                    
                    $prs = $r.'/BLEND_KEYWORDS.dat';
                    if (file_exists($prs)) {
                        foreach (explode("\n", file_get_contents($prs)) as $l) {
                            if (strpos($l, 'RADFRAC') !== false) $run['RFRAC'] = preg_split('/\s+/', $l)[1];
                            if (strpos($l, 'ISIGI') !== false) $run['ISIGI'] = preg_split('/\s+/', $l)[1];
                        }
                    }
                    
                    $aim = $r.'/combined_files/aimless_001.log';
                    if (file_exists($aim)) {
                        $stats = array();
                        $run['STATE'] = 1;
                        foreach (explode("\n", file_get_contents($aim)) as $l) {
                            if (strpos($l, 'Rmerge  (all I+ and I-)') !== false) $stats['RMERGE'] = array_slice(preg_split('/\s\s\s+/', $l), 0);
                            if (strpos($l, 'Rpim (all I+ & I-)') !== false) $stats['RPIM'] = array_slice(preg_split('/\s\s\s+/', $l), 0);
                            if (strpos($l, 'Mean((I)/sd(I))') !== false) $stats['ISIGI'] = array_slice(preg_split('/\s\s\s+/', $l), 0);
                            if (strpos($l, 'Completeness   ') !== false) $stats['C'] = array_slice(preg_split('/\s\s\s+/', $l), 0);
                            if (strpos($l, 'Multiplicity   ') !== false) $stats['M'] = array_slice(preg_split('/\s\s\s+/', $l), 0);
                            if (strpos($l, 'Low resolution limit') !== false) $stats['RESL'] = array_slice(preg_split('/\s\s\s+/', $l), 0);
                            if (strpos($l, 'High resolution limit') !== false) $stats['RESH'] = array_slice(preg_split('/\s\s\s+/', $l), 0);
                            if (strpos($l, 'Space group: ') !== false) $run['SG'] = implode(' ',array_slice(preg_split('/\s/', $l), 2));
                            
                        }
                        
                        $run['STATS'] = $stats;
                    }
                    
                    $ids = array();
                    if (file_exists($r.'/ids.dat')) {
                        $ids = explode("\n", file_get_contents($r.'/ids.dat'));
                    }
                    
                    $run['IDS'] = $ids;
                    
                    array_push($runs, $run);
                }
            }
            
            usort($runs, function($a, $b) { return $a['ID'] - $b['ID'];});
                                  
            $this->_output($runs);
        }
                                  
        
        # ------------------------------------------------------------------------
        # Delete a blend run
        function _delete() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            if (!$this->has_arg('run')) $this->_error('No run specified');
                                  
            $info = $this->db->pq("SELECT TO_CHAR(s.startdate, 'YYYY') as yr, s.sessionid, s.beamlinename as bl, vr.run, vr.runid FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));
            
            if (!sizeof($info)) $this->_error('No such visit');
            $info = $info[0];
            
            $vis = '/dls/'.$info['BL'].'/data/'.$info['YR'].'/'.$this->arg('visit');
            $root = $vis.'/processing/auto_mc/'.phpCAS::getUser().'/blend/run_'.$this->arg('run');
            $this->rrmdir($root);
                                  
            $this->_output(1);    
        }
                                  
        function rrmdir($dir) {
            foreach(glob($dir . '/*') as $file) {
                if(is_dir($file))
                    $this->rrmdir($file);
                else
                    unlink($file);
            }
            rmdir($dir);
        }
        

        # ------------------------------------------------------------------------
        # Get data for blend dendrogram
        function _dendrogram() {
            session_write_close();
            
            $info = $this->db->pq("SELECT TO_CHAR(s.startdate, 'YYYY') as yr, s.sessionid, s.beamlinename as bl, vr.run, vr.runid FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));
            
            if (!sizeof($info)) $this->_error('No such visit');
            $info = $info[0];
            
            $vis = '/dls/'.$info['BL'].'/data/'.$info['YR'].'/'.$this->arg('visit');
            
            if ($this->has_arg('user')) {
                $us = $this->dirs($vis.'/processing/auto_mc');
                $u = $us[$this->arg('user')];
            } else $u = phpCAS::getUser();
            
            $root = $vis.'/processing/auto_mc/'.$u.'/blend/analyse';
            $cf = $root.'/CLUSTERS.txt';
            
            $data = array();
            $raw = array();
            if (file_exists($cf)) {
                $log = explode("\n", file_get_contents($cf));
                
                foreach (array_slice($log,4) as $i => $l) {
                    if ($l) {
                        $parts = preg_split("/\s+/", $l);
                        array_push($raw, array($parts[3], array_slice($parts,4)));
                    }
                }
                
                $all = @array_map(intval, end($raw)[1]);
                array_push($data, array($all, 0));
                foreach ($raw as $r) {
                    
                    foreach ($r[1] as $e) {
                        $idx = array_search($e, $all);
                        if ($idx > -1) unset($all[$idx]);
                        
                        foreach ($all as $i => $a) {
                            if (preg_match('/\+'.$e.'\+/', $a) || preg_match('/^'.$e.'\+/', $a)) {
                                unset($all[$i]);
                            }
                        }
                    }
                    
                    array_push($all, implode('+',$r[1]));
                    array_push($data, array(array_values($all), floatval($r[0])));
                }
                
                $ids = explode("\n", file_get_contents($root.'/ids.dat'));
                $this->_output(array($ids, array_reverse($data)));
            }
            
        }
    
    
        # ------------------------------------------------------------------------
        # Get blend aimloss log output
        function _blend_log() {
            
        }
        
        # ------------------------------------------------------------------------
        # Get blend mtz
        function _get_mtz() {
            
        }
    
        
        
    
        # ------------------------------------------------------------------------
        # Code for manual clustering and scaling with XSCALE
        # Not good enough yet!
    
        
        # ------------------------------------------------------------------------
        # Calculate CCI(i,j) for data sets with XSCALE
        function _xscale() {
            session_write_close();
            
            $ret = '';
            
            $args = array();
            $where = array();
            
            if (!array_key_exists('dcs', $_POST)) $this->_error('No data collections specified');
            
            foreach ($_POST['dcs'] as $d) {
                if (is_numeric($d)) {
                    array_push($args, $d);
                    array_push($where, 'dc.datacollectionid=:'.sizeof($args));
                }
            }
            
            if (sizeof($where)) {
                $where = implode(' OR ', $where);
                
                $rows = $this->db->pq("SELECT dc.datacollectionid as id, dc.wavelength,dc.filetemplate as prefix, dc.imagedirectory as dir, p.proposalcode || p.proposalnumber || '-' || s.visit_number as visit FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON dc.sessionid = s.sessionid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE $where", $args);
                
                $files = array();
                $xscale = '';
                $ids = array();
                foreach ($rows as $i => $r) {
                    if (!$xscale) {
                        $xscale = substr($r['DIR'], 0, strpos($r['DIR'], $r['VISIT'])).$r['VISIT'].'/processing/auto_mc/xscale/analyse';
                    }
                    
                    $root = str_replace($r['VISIT'], $r['VISIT'].'/processing/auto_mc',$r['DIR']) . str_replace('####.cbf', '', $r['PREFIX']);
                
                    $file = 'NATIVE_SWEEP1.HKL';
                    $file2 = 'SWEEP1.HKL';
                    
                    #$hkl = $root.'/DEFAULT/NATIVE/SWEEP1/integrate/XDS_ASCII.HKL';
                    $hkl = $root.'/DEFAULT/scale/'.$file;
                    if (file_exists($hkl)) {
                        if (!file_exists($root.'/'.$file2)) symlink($hkl, $root.'/'.$file2);
                        $rel = substr($root, strpos($root, 'auto_mc')+8);
                        array_push($files, '../../'.$rel.'/'.$file2);
                        array_push($ids, $r['ID']);
                    }
                    
                }
                
                #$last = 0;
                #foreach (glob($xscale.'/run_*') as $d) {
                #    if (preg_match('/run_(\d+)$/', $d, $m)) {
                #        if ($m[1] > $last) $last = $m[1];
                #    }
                #}
                #$last++;
                #$xscale .= '/run_'.$last;
                
                if (!file_exists($xscale)) mkdir($xscale, 0777, true);
                chdir($xscale);
                
                file_put_contents($xscale.'/ids.txt', implode("\n", $ids));
                
                file_put_contents($xscale.'/XSCALE.INP', "OUTPUT_FILE=temp.ahkl\nINPUT_FILE=".implode("\nINPUT_FILE=", $files));
                file_put_contents($xscale.'/xscale.sh', "#!/bin/sh\nmodule load xdsme\nxscale");
                
                # no x11 on cluster
                $ret = exec("module load global/cluster;qsub xscale.sh");
                #$ret = exec("chmod +x blend.sh;./blend.sh &");
                
            } else $ret = 'No data sets specified';
            
            $this->_output($ret);
            
        }
                                  
                                  
                                  
        function _cluster() {
            session_write_close();
            
            $info = $this->db->pq("SELECT TO_CHAR(s.startdate, 'YYYY') as yr, s.sessionid, s.beamlinename as bl, vr.run, vr.runid FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));
            
            if (!sizeof($info)) $this->_error('No such visit');
            $info = $info[0];
            
            $vis = '/dls/'.$info['BL'].'/data/'.$info['YR'].'/'.$this->arg('visit');
            $root = $vis.'/processing/auto_mc/xscale/analyse';
            $xsl = $root.'/XSCALE.LP';
            
            $data = array();
            $start = 99999999;
            $stop = false;
            if (file_exists($xsl)) {
                $log = explode("\n", file_get_contents($xsl));
                foreach ($log as $i => $l) {
                    if (strpos($l, 'CORRELATIONS BETWEEN INPUT DATA SETS AFTER CORRECTIONS') !== false) $start = $i + 4;

                    if ($i > $start && !$stop) {
                        if ($l) {
                            $row = preg_split('/\s+/', $l);

                            $a = (string)$row[1];
                            $b = (string)$row[2];
                            
                            if (!array_key_exists($a, $data)) $data[$a] = array();
                            if (!array_key_exists($b, $data)) $data[$b] = array();
                            
                            $data[$a][$b] = sqrt(1-pow($row[4],2));
                            $data[$b][$a] = sqrt(1-pow($row[4],2));
                            
                        } else $stop = true;
                    }
                }
                
                $init = $data;
                $ca = array(array(array_keys($data), 0));
                while (sizeof($data) > 1) {
                    list($data, $cluster) = $this->_iterate($data, $init);
                    array_push($ca, $cluster);
                }

                $ids = explode("\n", file_get_contents($root.'/ids.txt'));
                $this->_output(array($ids, array_reverse($ca)));
                
            } else {
                $this->_output('No xscale run');
            }
            
            /*
            $clusters = array();
            $children = &$clusters;
            $allocated = array();
            foreach (array_reverse($ca) as $c) {
                if (sizeof($children) == 0) {
                    array_push($children, array('children'=>array(), 'height'=>$c[1]));
                }
                
                foreach ($c[0] as $e) {
                    if (strlen($e) == 1 && !in_array($e, $allocated)) {
                        array_push($children, array('name' => $e, 'height' => 0));
                        array_push($allocated, $e);
                    }
                }
                
                $children = &$children[0]['children'];
            }
            
            print_r($clusters);
            $this->_output($clusters);
            */
        }
        
        
        
        function _iterate($data, $init) {
            $min = 2;
            $mk = array();
            foreach ($data as $i => $d) {
                $tmp = min($d);
                if ($tmp < $min) {
                    $min = $tmp;
                    $mk = array($i, array_keys($d, $tmp)[0]);
                }
            }
            
            $k = $mk[0].'+'.$mk[1];
            $data[$k] = array();
            foreach ($data as $i => $d) {
                if ($i != $k && $mk[0] != $i && $mk[1] != $i) {
                    #$val = ($data[$mk[0]][$i]+$data[$mk[1]][$i])/2;
                    $val = $this->avg($k, $i, $init);
                    #$val = min($data[$mk[0]][$i],$data[$mk[1]][$i]);
                    
                    $data[$k][$i] = $val;
                    $data[$i][$k] = $val;
                }
            }
            
            unset($data[$mk[0]]);
            unset($data[$mk[1]]);
            
            foreach ($data as $i => $d) {
                unset($data[$i][$mk[0]]);
                unset($data[$i][$mk[1]]);
            }
            
            return array($data, array(array_keys($data), $min));
        }
        
        # average linkage
        function avg($a, $b, $data) {
            $aa = explode('+', $a);
            $ba = explode('+', $b);

            $tot = 0;
            foreach ($aa as $as) {
                foreach ($ba as $bs) {
                    $tot += $data[$as][$bs];
                }
            }
            
            return $tot / (sizeof($aa)*sizeof($ba));
            
        }
    }
    
    class Cluster {
        
        function __construct($k, $v) {
            $this->sequence = array($k);
            $this->data[$k] = $v;
        }
        
        function size() {
            return sizeof(array_keys($this->data));
        }
        
        function add($k, $v) {
            $this->data[$k] = $v;
            array_push($this->sequence, $k);
        }
        
        function avg() {
            return array_sum(array_values($this->data))/$this->size();
        }
        
        function key() {
            return implode('+', $this->sequence);
        }
        
    }
?>