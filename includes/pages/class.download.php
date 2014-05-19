<?php

    class Download extends Page {
        
        var $arg_list = array('id' => '\d+',
                              'aid' => '\d+',
                              'run' => '\d+',
                              'visit' => '\w+\d+-\d+',
                              'u' => '\w+\d+',
                              's' => '\d',
                              'log' => '\d',
                              'LogFiles' => '([\w|\.])+',
                              'ty' => '\w+',
                              'pdb' => '\d',
                              'map' => '\d',
                              );
        
        var $dispatch = array('ap' => '_auto_processing',
                              'bl' => '_blend_mtz',
                              'ep' => '_ep_mtz',
                              'dimple' => '_dimple_mtz',
                              'csv' => '_csv_report',
                              'map' => '_map',
                              );
        var $def = 'ap';

        
        # ------------------------------------------------------------------------
        # Download mtz/log file for Fast DP / XIA2
        function _auto_processing() {
            if (!$this->has_arg('id')) $this->error('No data collection', 'No data collection id specified');
            if (!$this->has_arg('aid')) $this->error('No auto processing id', 'No auto processing id specified');
            
            $rows = $this->db->pq('SELECT appa.filename,appa.filepath,appa.filetype FROM ispyb4a_db.autoprocintegration api INNER JOIN ispyb4a_db.autoprocscaling_has_int aph ON api.autoprocintegrationid = aph.autoprocintegrationid INNER JOIN ispyb4a_db.autoprocscaling aps ON aph.autoprocscalingid = aps.autoprocscalingid INNER JOIN ispyb4a_db.autoproc ap ON aps.autoprocid = ap.autoprocid INNER JOIN ispyb4a_db.autoprocprogram app ON api.autoprocprogramid = app.autoprocprogramid INNER JOIN ispyb4a_db.autoprocprogramattachment appa ON appa.autoprocprogramid = app.autoprocprogramid WHERE api.datacollectionid = :1 AND api.autoprocprogramid=:2', array($this->arg('id'), $this->arg('aid')));
            
            foreach ($rows as $r) {
                if ($this->has_arg('log')) {
                    if ($r['FILETYPE'] == 'Log') {
                        if ($this->has_arg('LogFiles')) {
                            $f = $r['FILEPATH'].'/LogFiles/'.$this->arg('LogFiles');
                            header("Content-Type: text/plain");
                            
                        } else {
                        
                            $f = $r['FILEPATH'].'/'.$r['FILENAME'];
                            if ($r['FILENAME'] == 'fast_dp.log') header("Content-Type: text/plain");
                        }
                            
                        readfile($f);
                    }
                
                } else {
                    // XIA2
                    if ($r['FILETYPE'] == 'Result') {
                        $f = $r['FILEPATH'].'/'.$r['FILENAME'];
                        if (file_exists($f)) {
                            $this->_header($r['FILENAME']);
                            readfile($f);
                            
                        } $this->error('No such file', 'The specified auto processing file doesnt exist');
                        
                    // FastDP
                    } else if ($r['FILETYPE'] == 'Log' && $r['FILENAME'] == 'fast_dp.log') {
                        $f = $r['FILEPATH'].'/fast_dp.mtz';
                        if (file_exists($f)) {
                            $this->_header($this->arg('aid').'_fast_dp.mtz');
                            readfile($f);
                            
                        } $this->error('No such file', 'The specified auto processing file doesnt exist');
                    }
                }
            }
        }
        
        
        # ------------------------------------------------------------------------
        # Return a blended mtz file
        function _blend_mtz() {
            if (!$this->has_arg('visit')) $this->error('No visit specified', 'No visit was specified');
            if (!$this->has_arg('run')) $this->error('No run specified', 'No blend run number was specified');            
            
            $visit = $this->db->pq("SELECT TO_CHAR(startdate, 'YYYY') as y, s.beamlinename as bl FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON p.proposalid=s.proposalid WHERE p.proposalcode || p.proposalnumber || '-' ||s.visit_number LIKE :1", array($this->arg('visit')));

            if (!sizeof($visit)) $this->error('No such visit', 'The specified visit does not exist');
            else $visit = $visit[0];
            
            $u = $this->has_arg('u') ? $this->arg('u') : phpCAS::getUser();
            
            $root = '/dls/'.$visit['BL'].'/data/'.$visit['Y'].'/'.$this->arg('visit').'/processing/auto_mc/'.$u.'/blend/run_'.$this->arg('run');
            
            if (file_exists($root)) {
                $file = $this->has_arg('s') ? 'scaled_001.mtz' : 'merged_001.mtz';
                $f = $root.'/combined_files/'.$file;
                if (file_exists($f)) {
                    $this->_header('run_'.$this->arg('run').'_'.$file);
                    readfile($f);
                }
            }
        }
        
        
        # ------------------------------------------------------------------------
        # Return pdb/mtz/log file for fast_ep and dimple
        function _ep_mtz() {
            $info = $this->db->pq('SELECT dc.imageprefix as imp, dc.datacollectionnumber as run, dc.imagedirectory as dir, p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON s.sessionid=dc.sessionid INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE dc.datacollectionid=:1', array($this->arg('id')));
            
            if (!sizeof($info)) $this->error('No such data collection', 'The specified data collection does not exist');
            else $info = $info[0];
            
            $info['DIR'] = $this->ads($info['DIR']);

            $root = str_replace($info['VIS'], $info['VIS'] . '/processed', $info['DIR']).$info['IMP'].'_'.$info['RUN'].'_'.'/fast_ep/';
            $file = $root . 'sad.mtz';
            
            if (file_exists($file)) {
                if ($this->has_arg('log')) {
                    //$this->_header($this->arg('id').'_fast_ep.log');
                    header("Content-Type: text/plain");
                    readfile($root.'fast_ep.log');
                    
                } else {
                    if (!file_exists('/tmp/'.$this->arg('id').'_fast_ep.tar.gz')) {
                        $a = new PharData('/tmp/'.$this->arg('id').'_fast_ep.tar');

                        $a->addFile($file, 'sad.mtz');
                        $a->addFile($root.'sad_fa.pdb', 'sad_fa.pdb');
                        $a->compress(Phar::GZ);

                        unlink('/tmp/'.$this->arg('id').'_fast_ep.tar');
                    }
                    
                    $this->_header($this->arg('id').'_fast_ep.tar.gz');
                    readfile('/tmp/'.$this->arg('id').'_fast_ep.tar.gz');
                }
                
                
            } else $this->error('File not found', 'Fast EP files were not found');
        }
        
        
        function _dimple_mtz() {
            $info = $this->db->pq('SELECT dc.imageprefix as imp, dc.datacollectionnumber as run, dc.imagedirectory as dir, p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON s.sessionid=dc.sessionid INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE dc.datacollectionid=:1', array($this->arg('id')));
            
            if (!sizeof($info)) $this->error('No such data collection', 'The specified data collection does not exist');
            else $info = $info[0];
            
            $info['DIR'] = $this->ads($info['DIR']);

            $root = str_replace($info['VIS'], $info['VIS'] . '/processed', $info['DIR']).$info['IMP'].'_'.$info['RUN'].'_'.'/fast_dp/dimple/';
            $file = $root . 'final.pdb';
            
            if (file_exists($file)) {
                if ($this->has_arg('log')) {
                    //$this->_header($this->arg('id').'_dimple.log');
                    header("Content-Type: text/plain");
                    readfile($root.'08-refmac5_rigid.log');
                    
                } else {
                    if (!file_exists('/tmp/'.$this->arg('id').'_dimple.tar.gz')) {
                        $a = new PharData('/tmp/'.$this->arg('id').'_dimple.tar');

                        $a->addFile($file, 'final.pdb');
                        $a->addFile($root.'final.mtz', 'final.mtz');
                        $a->compress(Phar::GZ);

                        unlink('/tmp/'.$this->arg('id').'_dimple.tar');
                    }
                    
                    $this->_header($this->arg('id').'_dimple.tar.gz');
                    readfile('/tmp/'.$this->arg('id').'_dimple.tar.gz');
                }
                
                
            } else $this->error('File not found', 'Dimple files were not found');
        }
        
        
        # ------------------------------------------------------------------------
        # Return maps and pdbs for dimple / fast ep
        function _map() {
            if (!$this->has_arg('id')) $this->error('No id specified', 'No id was specified');
            if (!$this->has_arg('ty')) $this->error('No type specified', 'No type was specified');
            
            $info = $this->db->pq('SELECT dc.imageprefix as imp, dc.datacollectionnumber as run, dc.imagedirectory as dir, p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON s.sessionid=dc.sessionid INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE dc.datacollectionid=:1', array($this->arg('id')));
            
            if (!sizeof($info)) $this->error('No such data collection', 'The specified data collection does not exist');
            else $info = $info[0];
            
            $info['DIR'] = $this->ads($info['DIR']);

            if ($this->arg('ty') == 'ep') {
                $root = str_replace($info['VIS'], $info['VIS'] . '/processed', $info['DIR']).$info['IMP'].'_'.$info['RUN'].'_'.'/fast_ep/';
                $file_name = 'sad';
                $file = $root . $file_name;
                
            } else if ($this->arg('ty') == 'dimple') {
                $root = str_replace($info['VIS'], $info['VIS'] . '/processed', $info['DIR']).$info['IMP'].'_'.$info['RUN'].'_'.'/fast_dp/dimple/';
                $file_name = 'final';
                $file = $root . $file_name;
                
            } else $this->error('No file type specified');
            
            $ext = $this->has_arg('pdb') ? 'pdb' : 'mtz';
            
            if ($this->has_arg('pdb')) {
                $out = $file.'.'.$ext;
            } else {
                if ($this->arg('ty') == 'dimple') {
                    $map = $this->has_arg('map') ? 'fofc' : '2fofc';
                    $out = '/tmp/'.$this->arg('id').'_'.$this->arg('ty').'_'.$map.'.map.gz';
                    
                } else $out = '/tmp/'.$this->arg('id').'_'.$this->arg('ty').'.map.gz';
            }
 
            if ($ext == 'mtz') {
                # convert mtz to map
                if (!file_exists($out)) {
                    exec('./mtz2map.sh '.$file.'.'.$ext.' '.$this->arg('id').' '.$this->arg('ty').' '.$file.'.pdb');
                }
                
                $ext = 'map';
            }
            
            if (file_exists($out)) {
                if ($this->arg('ty') == 'ep' && $this->has_arg('pdb')) {
                    $lines = explode("\n", file_get_contents($out));
                    
                    foreach ($lines as $l) {
                        #$l = str_replace('PDB= PDB  ', ' S   ALA A', $l);
                        $l = str_replace('ATOM  ', 'HETATM', $l);
                        print $l."\n";
                    }
                    
                    
                    
                    
                } else {
                    $size = filesize($out);
                    header("Content-length: $size");
                    readfile($out);
                }
            } else $this->error('File not found', 'Fast EP / Dimple files were not found');
        }
        
        
        # ------------------------------------------------------------------------
        # CSV Report of Data Collections
        function _csv_report() {
            if (!$this->has_arg('visit')) $this->error('No visit specified', 'You must specify a visit to download a report for');
            
            $vis = $this->db->pq("SELECT s.sessionid,s.beamlinename,TO_CHAR(s.startdate, 'DD_MM_YYYY') as st FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE p.proposalcode||p.proposalnumber||'-'||s.visit_number LIKE :1", array($this->arg('visit')));
            
            if (!sizeof($vis)) $this->error('No such visit', 'The specified visit doesnt exist');
            else $vis = $vis[0];
            
            $rows = $this->db->pq("SELECT dc.imageprefix,s.beamlinename,dc.datacollectionnumber,TO_CHAR(dc.starttime, 'DD/MM/YYYY HH24:MI:SS'), sa.name, p.name as protein, dc.numberofimages, dc.wavelength, dc.detectordistance, dc.exposuretime, dc.axisstart, dc.axisrange, dc.xbeam, dc.ybeam, dc.resolution, dc.comments FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON s.sessionid = dc.sessionid LEFT OUTER JOIN ispyb4a_db.blsample sa ON dc.blsampleid = sa.blsampleid LEFT OUTER JOIN ispyb4a_db.crystal c ON sa.crystalid = c.crystalid LEFT OUTER JOIN ispyb4a_db.protein p ON c.proteinid = p.proteinid WHERE dc.sessionid=:1 ORDER BY dc.starttime", array($vis['SESSIONID']));
            
            header("Content-type: application/vnd.ms-excel");
            header("Content-disposition: attachment; filename=".$vis['ST']."_".$vis['BEAMLINENAME']."_".$this->arg('visit').".csv");
            print "Image prefix,Beamline,Run no,Start Time,Sample Name,Protein Acronym,# images, Wavelength (angstrom), Distance (mm), Exp. Time (sec), Phi start (deg), Phi range (deg), Xbeam (mm), Ybeam (mm), Detector resol. (angstrom), Comments\n";
            foreach ($rows as $r) {
                $r['COMMENTS'] = '"'.$r['COMMENTS'].'"';
                print implode(',', array_values($r))."\n";
            }
        }
        
        
        # ------------------------------------------------------------------------
        # Force browser to download file
        function _header($f) {
            header("Content-Type: application/octet-stream");
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"$f\"");
        }
        
    }
?>