<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('time' => '\d+', 'bl' => '\w\d\d(-\d)?');
        var $dispatch = array('visits' => '_get_visits',
                              );
        var $def = 'visits';
        var $profile = True;
        
        # ------------------------------------------------------------------------
        # Return visits for a time on a beamline
        function _get_visits() {
            if (!$this->has_arg('time')) $this->_error('No time specified');
            if (!$this->has_arg('bl')) $this->_error('No bl specified');
            
            $st = $this->arg('t');
            $rows = $this->db->q("SELECT bl.startdate,bl.enddate,p.proposalcode || p.proposalnumber || '-' || bl.visit_number as visit, bl.sessionid FROM ispyb4a_db.blsession bl INNER JOIN ispyb4a_db.proposal p ON p.proposalid = bl.proposalid             WHERE ".$st." BETWEEN (bl.startdate - TO_DATE('1970-01-01','YYYY-MM-DD')) * 86400 AND (bl.enddate - TO_DATE('1970-01-01','YYYY-MM-DD')) * 86400 AND bl.beamlinename LIKE '".$this->arg('bl')."' AND bl.sessionid != 886");
            
            $this->_output($rows);
        }
        
    }

?>