<?php

    class Samples extends Page {
        
        var $arg_list = array('visit' => '\w\w\d\d\d\d-\d+', 'page' => '\d+', 'mon' => '\w+', 'year' => '\d\d\d\d', 'id' => '\d+', 't' => '\w+');
        var $dispatch = array('samp' => '_samples');
        var $def = 'samp';
        
        var $root = 'Sample Creation';
        var $root_link = '/samples';
        
        
        # Sample Creation & Allocation
        function _samples() {
            if (!$this->has_arg('visit')) {
                $this->error('No Visit Specified', 'You must specify a visit number to view this page');
            }
            
            $info = $this->db->pq("SELECT s.sessionid, s.beamlinename as bl, vr.run, vr.runid, TO_CHAR(s.startdate, 'YYYY') as yr FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));
            
            if (sizeof($info) == 0) {
                $this->_index();
                return;
            }
            
            $info = $info[0];
            
            $p = array($info['BL'], $this->arg('visit'), 'Sample Creation');
            $l = array('', '/dc/visit/'.$this->arg('visit'), '');
            $this->template('Sample Creation: ' . $this->arg('visit'), $p, $l);
            $this->t->bl = $info['BL'];
            $this->t->js_var('bl', $info['BL']);
            $this->t->js_var('visit', $this->arg('visit'));
            
            $this->render('samp');
        }
    }

?>