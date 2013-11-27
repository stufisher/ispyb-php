<?php

    class Download extends Page {
        
        var $arg_list = array('id' => '\d+',
                              'aid' => '\d+',
                              'run' => '\d+',
                              'visit' => '\w\w\d+-\d+',
                              'u' => '\w+\d+',
                              's' => '\d',
                              );
        
        var $dispatch = array('ap' => '_auto_processing',
                              'bl' => '_blend_mtz',
                              );
        var $def = 'ap';

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
        
        function _header($f) {
            header("Content-Type: application/octet-stream");
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"$f\"");
        }
        
    }
?>