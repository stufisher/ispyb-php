<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('bl' => '\w\d\d(-\d)?',
                              'p' => '\d+',
                              'st' => '\d\d-\d\d-\d\d\d\d',
                              'en' => '\d\d-\d\d-\d\d\d\d',
                              );
        
        
        var $dispatch = array('pvs' => '_get_pvs',
                              'log' => '_get_server_log',
                              'sch' => '_schedule',
                              );
        
        var $def = 'pvs';
        #var $profile = True;
        //var $debug = True;
        
        
        # ------------------------------------------------------------------------
        # Return beam / ring status pvs for a beamline
        function _get_pvs() {
            session_write_close();
            
            if (!$this->has_arg('bl')) $this->_error('No beamline specified');
            
            $ring_pvs = array('Ring Current' => 'SR-DI-DCCT-01:SIGNAL',
                              'Ring State' => 'CS-CS-MSTAT-01:MODE',
                              'Refill' => 'SR-CS-FILL-01:COUNTDOWN'
                              );
            
            $bl_pvs = array(
                            'i02' => array('Hutch' => 'BL02I-PS-IOC-01:M14:LOP',
                                           'Port Shutter' => 'FE02I-PS-SHTR-01:STA',
                                           'Expt Shutter' => 'BL02I-PS-SHTR-01:STA',
                                           'Fast Shutter' => 'BL02I-EA-SHTR-01:SHUTTER_STATE',
                                           ),
                            'i03' => array('Hutch' => 'BL03I-PS-IOC-01:M14:LOP',
                                           'Port Shutter' => 'FE03I-PS-SHTR-01:STA',
                                           'Expt Shutter' => 'BL03I-PS-SHTR-01:STA',
                                           'Fast Shutter' => 'BL03I-EA-SHTR-01:SHUTTER_STATE',
                                           ),
                            'i04' => array('Hutch' => 'BL04I-PS-IOC-01:M14:LOP',
                                           'Port Shutter' => 'FE04I-PS-SHTR-01:STA',
                                           'Expt Shutter' => 'BL04I-PS-SHTR-01:STA',
                                           'Fast Shutter' => 'BL04I-EA-SHTR-01:SHUTTER_STATE',
                                           ),
                            'i04-1' => array('Hutch' => 'BL04J-PS-IOC-01:M14:LOP',
                                           'Port Shutter' => 'FE04I-PS-SHTR-01:STA',
                                           'Expt Shutter' => 'BL04J-PS-SHTR-02:STA',
                                           'Fast Shutter' => 'BL04J-EA-SHTR-01:STA',
                                           ),
                            'i24' => array('Hutch' => 'BL24I-PS-IOC-01:M14:LOP',
                                           'Port Shutter' => 'FE24I-PS-SHTR-01:STA',
                                           'Expt Shutter' => 'BL24I-PS-SHTR-01:STA',
                                           'Fast Shutter' => 'BL24I-EA-SHTR-01:EQU:POSN',
                                           ),
                            );
            
            if (!array_key_exists($this->arg('bl'), $bl_pvs)) $this->_error('No such beamline');
            
            $return = array();
            foreach (array_merge($ring_pvs, $bl_pvs[$this->arg('bl')]) as $k => $pv) {
                $return[$k] = $this->pv($pv);
                if ($k == 'Hutch') $return[$k] = $return[$k] == 7 ? 'Open' : 'Locked';
            }
            
            $this->_output($return);
            
        }
        
        
        
        # ------------------------------------------------------------------------
        # Return last n lines of gda log file
        function _get_server_log() {
            session_write_close();
            
            $pp = 100;
            
            if ($this->has_arg('p')) $num_lines = $this->arg('p') * $pp;
            else $num_lines = 100;
            
            if (!$this->has_arg('bl')) $this->_error('No beamline specified');
            
            $file = fopen('/dls_sw/'.$this->arg('bl').'/logs/gda_server.log', 'r');
            fseek($file, -1, SEEK_END);

            for ($line = 0, $lines = array(); $line < $num_lines && false !== ($char = fgetc($file));) {
                if ($char === "\n"){
                    if(isset($lines[$line])){
                        //$lines[$line][] = $char;
                        $lines[$line] = implode('', array_reverse($lines[$line]));
                        $line++;
                    }
                } else
                    $lines[$line][] = $char;
                fseek($file, -2, SEEK_CUR);
            }

            if($line < $num_lines)
                $lines[$line] = implode('', array_reverse($lines[$line]));
            
            if ($this->has_arg('p')) $lines = array_slice($lines,$pp*($this->arg('p')-1),$pp);
            
            foreach ($lines as &$l) $l = htmlentities($l, ENT_QUOTES);
            
            $this->_output(array_reverse($lines));
        }
        
        
        # ------------------------------------------------------------------------
        # Local Contact Schedule
        function _schedule() {
            if (!$this->has_arg('bl')) $this->_error('No beamline specified');
            
            $st = $this->has_arg('st') ? $this->arg('st') : date('d-m-Y');
            $en = $this->has_arg('st') ? $this->arg('st') : date('d-m-Y', mktime(0,0,0,date('m'),date('d')+28,date('Y')));
            
            $visits = $this->db->pq("SELECT TO_CHAR(s.startdate, 'DY DD-MM-YYYY HH24:MI') as st, TO_CHAR(s.enddate, 'DY DD-MM-YYYY HH24:MI') as en, s.sessionid, p.proposalcode||p.proposalnumber||'-'||s.visit_number as vis FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON s.proposalid = p.proposalid WHERE s.startdate BETWEEN TO_DATE(:1,'dd-mm-yyyy') AND TO_DATE(:2,'dd-mm-yyyy') AND s.beamlinename LIKE :3 ORDER BY s.startdate", array($st, $en, $this->arg('bl')));
            
            $rows = array();
            foreach ($visits as &$v) {
                $lc = $this->lc_lookup($v['SESSIONID']);
                $v['LC'] = $lc ? $lc->name : '';
                $v['TY'] = $lc ? $lc->type : '';
                $v['OC'] = $lc ? $lc->oc : '';
                
                array_push($rows, array($v['ST'], $v['EN'], '<a href="/dc/visit/'.$v['VIS'].'">'.$v['VIS'].'</a>', $v['LC'], $v['OC'], $v['TY']));
            }
            
            $this->_output(array('iTotalRecords' => sizeof($rows),
                                 'iTotalDisplayRecords' => sizeof($rows),
                                 'aaData' => $rows,
                           ));
        }

    }

?>