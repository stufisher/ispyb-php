<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('visit' => '\w\w\d\d\d\d-\d+', 's' => '\w+', 'd' => '\w+', 'id' => '\d+', 'sg' => '\w+', 'a' => '\d+(.\d+)?', 'b' => '\d+(.\d+)?', 'c' => '\d+(.\d+)?', 'alpha' => '\d+(.\d+)?', 'beta' => '\d+(.\d+)?', 'gamma' => '\d+(.\d+)?', 'res' => '\d+(.\d+)?', 'rfrad' => '\d+(.\d+)?', 'isigi' => '\d+(.\d+)?');
        var $dispatch = array('list' => '_data_collections',
                              'dirs' => '_get_dirs',
                              'integrate' => '_integrate',
                              'blend' => '_blend',
                              'blended' => '_blended',
                              'status' => '_get_status',
                              );
        
        var $def = 'list';
        var $profile = True;
        //var $debug = True;
        
        # ------------------------------------------------------------------------
        # Get directories for visit
        function _get_dirs() {
            session_write_close();
            
            $info = $this->db->pq("SELECT s.sessionid, s.beamlinename as bl, vr.run, vr.runid FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));

            if (!sizeof($info)) $this->_error('No such visit');
            $info = $info[0];
            
            $rows = $this->db->pq("SELECT distinct dc.imagedirectory as dir FROM ispyb4a_db.datacollection dc WHERE dc.sessionid=:1", array($info['SESSIONID']));
                               
            $dirs = array();
            foreach ($rows as &$r) {
                $r['DIR'] = $this->ads($r['DIR']);
                $r['DIR'] = substr($r['DIR'], strpos($r['DIR'], $this->arg('visit'))+strlen($this->arg('visit'))+1);                                  
                array_push($dirs, $r['DIR']);
            }
                                  
            $this->_output($dirs);
        }
                                  

        # ------------------------------------------------------------------------
        # Get unit cells for any autoprocessed data sets
        function _get_cells() {
            
        }
        
        
        # ------------------------------------------------------------------------
        # Find out how many jobs are running
        function _get_status() {
            $jobs = preg_split('/\s+/', exec('module load global/cluster;qstat -u vxn01537 | wc'))[1];
            
            if ($jobs > 0) $jobs -= 2;
            $this->_output($jobs);
        }
        
        
        # ------------------------------------------------------------------------
        # Multicrystal data collection list
        function _data_collections() {
            session_write_close();
            
            $info = $this->db->pq("SELECT s.sessionid, s.beamlinename as bl, vr.run, vr.runid FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));

            if (!sizeof($info)) $this->_error('No such visit');
            $info = $info[0];
            
            $where = '';
            $args = array($info['SESSIONID']);
            
            if ($this->has_arg('s')) {
                array_push($args, $this->arg('s'));
                $where = " AND (dc.imagedirectory LIKE '%".$this->arg('s')."%' OR dc.filetemplate LIKE '%".$this->arg('s')."%')";
            }

            if ($this->has_arg('d')) {
                array_push($args, $this->arg('d'));
                $where = " AND dc.imagedirectory LIKE '%".$this->arg('d')."/'";
            }
            
            $rows = $this->db->pq("SELECT TO_CHAR(dc.starttime, 'DD-MM-YYYY HH24:MI:SS') as st, dc.filetemplate as prefix, dc.imagedirectory as dir, dc.datacollectionid as did, dc.numberofimages as ni, dc.axisstart as ost, dc.axisrange as oos FROM ispyb4a_db.datacollection dc WHERE dc.sessionid=:1 $where ORDER BY dc.imagedirectory, dc.starttime", $args);
            
            foreach ($rows as &$r) {
                $r['INT'] = 0;
                $root = str_replace($this->arg('visit'), $this->arg('visit').'/processing/auto_mc', $r['DIR']).str_replace('####.cbf', '', $r['PREFIX']);
                
                if (file_exists($root)) $r['INT'] = 1;
                if (file_exists($root.'/xia2-summary.dat')) {
                    $log = explode("\n", file_get_contents($root.'/xia2-summary.dat'));
                    $stats = array();
                    $r['INT'] = 2;
                    
                    foreach ($log as $l) {
                        if (strpos($l, 'High resolution limit') !== false) $stats['RESH'] = preg_split('/\t/', $l)[1];
                        if (strpos($l, 'Completeness') !== false) $stats['C'] = preg_split('/\t/', $l)[1];
                        if (strpos($l, 'Rmerge') !== false) $stats['R'] = preg_split('/\t/', $l)[1];
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
            
            $ranges = array();
            foreach ($_POST['int'] as $d) {
                if (is_numeric($d[0]) && is_numeric($d[1]) && is_numeric($d[2])) {
                    array_push($args, $d[0]);
                    array_push($where, 'dc.datacollectionid=:'.sizeof($args));
                    $ranges[$d[0]] = array($d[1], $d[2]);
                }
            }
            
            if (sizeof($where)) {
                $where = implode(' OR ', $where);
                
                $rows = $this->db->pq("SELECT dc.datacollectionid as id, dc.wavelength,dc.filetemplate as prefix, dc.imagedirectory as dir, p.proposalcode || p.proposalnumber || '-' || s.visit_number as visit FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON dc.sessionid = s.sessionid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE $where", $args);
                                      
                foreach ($rows as $i => $r) {
                    $root = str_replace($r['VISIT'], $r['VISIT'].'/processing/auto_mc',$r['DIR']) . str_replace('####.cbf', '', $r['PREFIX']);
                    
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
                    
                    $remote = "module load xia2\nxia2 -failover -3daii $sg $cell $res -xinfo xia.xinfo";
                    file_put_contents($root.'/remote.sh', $remote);
                    
                    $ret = exec("module load global/cluster;qsub remote.sh");
                    
                    
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
                $where = implode(' OR ', $where);
                
                $rows = $this->db->pq("SELECT dc.datacollectionid as id, dc.wavelength,dc.filetemplate as prefix, dc.imagedirectory as dir, p.proposalcode || p.proposalnumber || '-' || s.visit_number as visit FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON dc.sessionid = s.sessionid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE $where", $args);
                
                $files = array();
                $blend = '';
                foreach ($rows as $i => $r) {
                    if (!$blend) {
                        $blend = substr($r['DIR'], 0, strpos($r['DIR'], $r['VISIT'])).$r['VISIT'].'/processing/auto_mc/blend';
                    }
                    
                    $root = str_replace($r['VISIT'], $r['VISIT'].'/processing/auto_mc',$r['DIR']) . str_replace('####.cbf', '', $r['PREFIX']);
                
                    $hkl = $root.'/DEFAULT/NATIVE/SWEEP1/integrate/INTEGRATE.HKL';
                    if (file_exists($hkl)) {
                        array_push($files, $hkl);
                    }
                    
                }
                
                $last = 0;
                foreach (glob($blend.'/run_*') as $d) {
                    if (preg_match('/run_(\d+)$/', $d, $m)) {
                        if ($m[1] > $last) $last = $m[1];
                    }
                }
                $last++;
                $blend .= '/run_'.$last;
                
                if (!file_exists($blend)) mkdir($blend, 0777, true);
                chdir($blend);
                #foreach (glob($blend.'/*') as $f) @unlink($f);
                
                $radfrac = $this->has_arg('rfrac') ? $this->arg('rfrac') : 0.25;
                $isigi = $this->has_arg('isigi') ? $this->arg('isigi') : 1.5;
                $res = $this->has_arg('res') ? ('RESOLUTION HIGH '.$this->arg('res')) : '';
                
                $sets = array();
                for ($i = 0; $i < sizeof($args); $i++) array_push($sets, $i+1);
                
                file_put_contents($blend.'/files.dat', implode("\n", $files));
                file_put_contents($blend.'/blend.sh', "#!/bin/sh\nmodule load blend\nblend -a files.dat 2> blend.elog\nblend -c ".implode(' ', $sets)." 2>> blend.elog");
                file_put_contents($blend.'/BLEND_KEYWORDS.dat', "BLEND KEYWORDS\nNBIN	  20\nRADFRAC   $radfrac\nISIGI     $isigi\nCPARWT    1.000\nPOINTLESS KEYWORDS\nAIMLESS KEYWORDS\n$res");
                
                # no x11 on cluster
                #$ret = exec("module load global/cluster;qsub blend.sh");
                $ret = exec("chmod +x blend.sh;./blend.sh");
                
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
            $root = $vis.'/processing/auto_mc/blend';
            
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
                    
                    $aim = $r.'/combined_files/aimless_001.log';
                    if (file_exists($aim)) {
                        $stats = array();
                        $run['STATE'] = 1;
                        foreach (explode("\n", file_get_contents($aim)) as $l) {
                            if (strpos($l, 'Rmerge  (within I+/I-)') !== false) $stats['RMERGE'] = array_slice(preg_split('/\s\s\s+/', $l), 0);
                            if (strpos($l, 'Mean((I)/sd(I))') !== false) $stats['ISIGI'] = array_slice(preg_split('/\s\s\s+/', $l), 0);
                            if (strpos($l, 'Completeness   ') !== false) $stats['C'] = array_slice(preg_split('/\s\s\s+/', $l), 0);
                            if (strpos($l, 'Multiplicity   ') !== false) $stats['M'] = array_slice(preg_split('/\s\s\s+/', $l), 0);
                            if (strpos($l, 'Low resolution limit') !== false) $stats['RESL'] = array_slice(preg_split('/\s\s\s+/', $l), 0);
                            if (strpos($l, 'High resolution limit') !== false) $stats['RESH'] = array_slice(preg_split('/\s\s\s+/', $l), 0);
                        }
                        
                        $run['STATS'] = $stats;
                    }
                    
                    $files = array();
                    if (file_exists($r.'/files.dat')) {
                        foreach (explode("\n", file_get_contents($r.'/files.dat')) as $f) {
                            array_push($files, str_replace('/DEFAULT/NATIVE/SWEEP1/integrate/INTEGRATE.HKL', '####.cbf', str_replace($vis.'/processing/auto_mc/', '', $f)));
                        }
                    }
                    
                    $run['FILES'] = $files;
                    
                    array_push($runs, $run);
                }
            }
            
            $this->_output($runs);
        }
        
        
    }

?>