<?php

    class Download extends Page {
        
        var $arg_list = array('id' => '\d+',
                              'aid' => '\d+',
                              'run' => '\d+',
                              'visit' => '\w+\d+-\d+',
                              'u' => '\w+\d+',
                              's' => '\d',
                              );
        
        var $dispatch = array('ap' => '_auto_processing',
                              'bl' => '_blend_mtz',
                              'ep' => '_ep_mtz',
                              'dimple' => '_dimple_mtz',
                              'csv' => '_csv_report',
                              );
        var $def = 'ap';

        
        # ------------------------------------------------------------------------
        # Download mtz file for Fast DP / XIA2
        function _auto_processing() {
            if (!$this->has_arg('id')) $this->error('No data collection', 'No data collection id specified');
            if (!$this->has_arg('aid')) $this->error('No auto processing id', 'No auto processing id specified');
            
            $rows = $this->db->pq('SELECT appa.filename,appa.filepath,appa.filetype FROM ispyb4a_db.autoprocintegration api INNER JOIN ispyb4a_db.autoprocscaling_has_int aph ON api.autoprocintegrationid = aph.autoprocintegrationid INNER JOIN ispyb4a_db.autoprocscaling aps ON aph.autoprocscalingid = aps.autoprocscalingid INNER JOIN ispyb4a_db.autoproc ap ON aps.autoprocid = ap.autoprocid INNER JOIN ispyb4a_db.autoprocprogram app ON api.autoprocprogramid = app.autoprocprogramid INNER JOIN ispyb4a_db.autoprocprogramattachment appa ON appa.autoprocprogramid = app.autoprocprogramid WHERE api.datacollectionid = :1 AND api.autoprocprogramid=:2', array($this->arg('id'), $this->arg('aid')));
            
            foreach ($rows as $r) {
                // XIA2
                if ($r['FILETYPE'] == 'Result') {
                    $f = $r['FILEPATH'].'/'.$r['FILENAME'];
                    if (file_exists($f)) {
                        $this->_header($r['FILENAME']);
                        readfile($f);
                        
                    } $this->error('No such file', 'The specified auto processing file doesnt exist');
                    
                } else if ($r['FILETYPE'] == 'Log' && $r['FILENAME'] == 'fast_dp.log') {
                    $f = $r['FILEPATH'].'/fast_dp.mtz';
                    if (file_exists($f)) {
                        $this->_header($this->arg('aid').'_fast_dp.mtz');
                        readfile();
                        
                    } $this->error('No such file', 'The specified auto processing file doesnt exist');
                }
            }
            
            print_r($rows);
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
        # Return mtz file for fast_ep and dimple
        function _ep_mtz() {
            
        }
        
        function _dimple_mtz() {
            
        }
        
        
        # ------------------------------------------------------------------------
        # CSV Report of Data Collections
        function _csv_report() {
            if (!$this->has_arg('visit')) $this->error('No visit specified', 'You must specify a visit to download a report for');
            
            $vis = $this->db->pq("SELECT s.sessionid,s.beamlinename,TO_CHAR(s.startdate, 'DD_MM_YYYY') as st FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE p.proposalcode||p.proposalnumber||'-'||s.visit_number LIKE :1", array($this->arg('visit')));
            
            if (!sizeof($vis)) $this->error('No such visit', 'The specified visit doesnt exist');
            else $vis = $vis[0];
            
            $rows = $this->db->pq("SELECT dc.imageprefix,s.beamlinename,dc.datacollectionnumber,TO_CHAR(dc.starttime, 'DD/MM/YYYY HH24:II:SS'), sa.name, p.name as protein, dc.numberofimages, dc.wavelength, dc.detectordistance, dc.exposuretime, dc.axisstart, dc.axisrange, dc.xbeam, dc.ybeam, dc.resolution, dc.comments FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON s.sessionid = dc.sessionid LEFT OUTER JOIN ispyb4a_db.blsample sa ON dc.blsampleid = sa.blsampleid LEFT OUTER JOIN ispyb4a_db.crystal c ON sa.crystalid = c.crystalid LEFT OUTER JOIN ispyb4a_db.protein p ON c.proteinid = p.proteinid WHERE dc.sessionid=:1 ORDER BY dc.starttime", array($vis['SESSIONID']));
            
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