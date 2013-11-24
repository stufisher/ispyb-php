<?php

    class Samples extends Page {
        
        var $arg_list = array('visit' => '\w\w\d\d\d\d-\d+', 'page' => '\d+', 'mon' => '\w+', 'year' => '\d\d\d\d', 'id' => '\d+', 't' => '\w+');
        var $dispatch = array('samp' => '_samples', 'bl' => '_beamline', 'proposal' => '_prop_samples');
        var $def = 'samp';
        
        var $root = 'Container Allocation';
        var $root_link = '/samples';
        var $sidebar = True;
        
        
        function _prop_samples() {
            if (!$this->has_arg('prop')) $this->error('No proposal selected', 'You must select a proposal before viewing this page');
            
            $visits = $this->db->pq("SELECT s.beamlinename as bl, TO_CHAR(s.startdate, 'DD-MM-YYYY HH24:MI') as st, p.proposalcode||p.proposalnumber||'-'||s.visit_number as vis FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON s.proposalid = p.proposalid WHERE p.proposalcode||p.proposalnumber LIKE :1 ORDER BY s.startdate DESC", array($this->arg('prop')));
            
            $this->template('Container Allocation: ' . $this->arg('prop'), array($this->arg('prop')), array(''));
            $this->t->visits = $visits;
            $this->t->render('samp_select');
        }
        
        # Sample Creation & Allocation
        function _samples() {            
            if (!$this->has_arg('visit')) {
                $this->error('No Visit Specified', 'You must specify a visit number to view this page');
            }
            
            $info = $this->db->pq("SELECT s.sessionid, s.beamlinename as bl, vr.run, vr.runid, TO_CHAR(s.startdate, 'YYYY') as yr FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));
            
            if (sizeof($info) == 0) {
                $this->error('Visit doesnt exist', 'The selected visit doesnt exist');
            }
            
            $info = $info[0];
            
            $p = array($info['BL'], $this->arg('visit'), 'Sample Creation');
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
            $t = strtoupper(date('d-m-Y 08:59'));
            
            if (!$this->has_arg('visit')) {
                $visits = $this->db->pq('SELECT p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis, TO_CHAR(s.startdate, \'DD-MM-YYYY HH24:MI\') as st, TO_CHAR(s.enddate, \'DD-MM-YYYY HH24:MI\') as en,s.beamlinename as bl FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE s.startdate > TO_DATE(:1,\'dd-mm-yyyy HH24:MI\') AND s.enddate < TO_DATE(:2,\'dd-mm-yyyy HH24:MI\')+2 AND s.beamlinename LIKE :3 ORDER BY s.startdate', array($t, $t, $b));
                
                $this->template('Samples');
                $this->t->mobile();
                
                $this->t->bl = $b;
                $this->t->js_var('bl', $b);
                $this->t->visits = $visits;
                
                $this->render('samp_bl_visit');
            } else {
            
                $info = $this->db->pq("SELECT s.sessionid, s.beamlinename as bl, vr.run, vr.runid, TO_CHAR(s.startdate, 'YYYY') as yr FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1 AND s.startdate > TO_DATE(:2,'dd-mm-yyyy HH24:MI') AND s.enddate < TO_DATE(:3,'dd-mm-yyyy HH24:MI')+2 AND s.beamlinename LIKE :4", array($this->arg('visit'), $t, $t, $b));

                #$info = $this->db->pq("SELECT s.sessionid, s.beamlinename as bl, vr.run, vr.runid, TO_CHAR(s.startdate, 'YYYY') as yr FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1 AND s.beamlinename LIKE :2", array($this->arg('visit'), $b));
                
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