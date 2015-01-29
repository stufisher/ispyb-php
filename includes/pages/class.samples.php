<?php

    class Samples extends Page {
        
        var $arg_list = array('visit' => '\w+\d+-\d+', 'page' => '\d+', 'mon' => '\w+', 'year' => '\d\d\d\d', 'id' => '\d+', 't' => '\w+');
        var $dispatch = array('samp' => '_samples', 'bl' => '_beamline', 'proposal' => '_prop_samples');
        var $def = 'samp';
        
        var $root = 'Container Allocation';
        var $root_link = '/samples';
        var $sidebar = True;
        
        
        function _prop_samples() {
            if (!$this->has_arg('prop')) $this->error('No proposal selected', 'You must select a proposal before viewing this page');
            
            $re = preg_match('/([a-zA-Z]+)\d+/', $this->arg('prop'), $mat);
            
            if ($mat[1] == 'cm' || $mat[1] == 'nr') {
                $where = "startdate > TO_DATE('01-01-".date('Y')."', 'DD-MM-YYYY') AND enddate < TO_DATE('31-12-".(date('Y')+1)."', 'DD-MM-YYYY')";
            } else {
                $where = 'startdate > SYSDATE-1 AND startdate < SYSDATE+14';
            }
            
            
            $visits = $this->db->pq("SELECT rownum,inner.* FROM (SELECT case when sysdate between s.startdate and s.enddate then 1 else 0 end as active, s.beamlinename as bl, TO_CHAR(s.startdate, 'DD-MM-YYYY HH24:MI') as st, p.proposalcode||p.proposalnumber||'-'||s.visit_number as vis FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON s.proposalid = p.proposalid WHERE p.proposalcode||p.proposalnumber LIKE :1 AND $where ORDER BY s.startdate) inner WHERE rownum < 10", array($this->arg('prop')));
            
            $this->template('Container Allocation: ' . $this->arg('prop'), array($this->arg('prop')), array(''));
            $this->t->visits = $visits;
            $this->t->render('samp_select');
        }
        
        # Sample Creation & Allocation
        function _samples() {            
            if (!$this->has_arg('visit')) {
                $this->error('No Visit Specified', 'You must specify a visit number to view this page');
            }
            
            $info = $this->db->pq("SELECT s.sessionid, s.beamlinename as bl, TO_CHAR(s.startdate, 'YYYY') as yr FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));
            
            if (sizeof($info) == 0) {
                $this->error('Visit doesnt exist', 'The selected visit doesnt exist');
            }
            
            $info = $info[0];
            
            $p = array($info['BL'], $this->arg('visit'), 'Sample Allocation');
            $l = array('', '/dc/visit/'.$this->arg('visit'), '');
            $this->template('Container Allocation: ' . $this->arg('visit'), $p, $l);
            $this->t->vis = $this->arg('visit');
            $this->t->bl = $info['BL'];
            $this->t->js_var('bl', $info['BL']);
            $this->t->js_var('visit', $this->arg('visit'));
            
            $this->render('samp');
        }
        
        
        # Full screen unauthenticated sample editor for use on beamline
        function _beamline() {
            if (!$this->blsr()) {
                $this->error('Access Denied', 'You need to be on a beamline machine to access this page');
            }
            
            $b = $this->ip2bl();
            
            if (!$this->has_arg('visit')) {
                $visits = $this->blsr_visits();
                
                $this->template('Samples');
                $this->t->mobile();
                
                $this->t->bl = $b;
                $this->t->js_var('bl', $b);
                $this->t->visits = $visits;
                
                $this->render('samp_bl_visit');
            } else {
            
                $info = $this->db->pq("SELECT s.sessionid, s.beamlinename as bl, TO_CHAR(s.startdate, 'YYYY') as yr FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1 AND s.beamlinename LIKE :2", array($this->arg('visit'), $b));
                
                if (sizeof($info) == 0) {
                    $this->error('Visit doesnt exist', 'The selected visit doesnt exist');
                }
                
                $info = $info[0];
            
                $this->template('Samples: ' . $this->arg('visit'));
                $this->t->mobile();
                
                $this->t->js_var('visit', $this->arg('visit'));
                $this->t->js_var('bl', $b);
                $this->t->bl = $b;
                
                $this->render('samp_bl');
            }
        }
    }

?>