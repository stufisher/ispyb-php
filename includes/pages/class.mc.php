<?php

    class Mc extends Page {
        
        var $arg_list = array('visit' => '\w+\d+-\d+', 'user' => '\d+');
        var $dispatch = array('mc' => '_data_collections',
                              'blend' => '_blend');
        var $def = 'mc';
        
        var $root = 'Multi-Crystal Integration';
        var $root_link = '/mc';
        
        
        # Main page for multicrystal integration
        function _data_collections() {
            if (!$this->has_arg('visit')) $this->error('No visit specified');
            
            $info = $this->db->pq("SELECT case when sysdate between s.startdate and s.enddate then 1 else 0 end as active, s.sessionid, s.beamlinename as bl, vr.run, vr.runid, TO_CHAR(s.startdate, 'YYYY') as yr FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));
            
            if (!sizeof($info)) {
                $this->msg('No such visit', 'That visit doesnt appear to exist');
            } else $info = $info[0];            
            
            $this->template('Multi-Crystal Integration', array($this->arg('visit')), array('/visit/'.$this->arg('visit')));
            
            $this->t->visit = $this->arg('visit');
            $this->t->js_var('visit', $this->arg('visit'));
            
            $this->t->render('mc_list');
            
        }
        
        
        # List of integrated data sets to blend
        function _blend() {
            if (!$this->has_arg('visit')) $this->error('No visit specified');
            
            $info = $this->db->pq("SELECT case when sysdate between s.startdate and s.enddate then 1 else 0 end as active, s.sessionid, s.beamlinename as bl, vr.run, vr.runid, TO_CHAR(s.startdate, 'YYYY') as yr FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));
            
            if (!sizeof($info)) {
                $this->msg('No such visit', 'That visit doesnt appear to exist');
            } else $info = $info[0];            
            
            $root = '/dls/'.$info['BL'].'/data/'.$info['YR'].'/'.$this->arg('visit').'/processing/auto_mc';
            $users = $this->dirs($root);
            
            $this->template('Multi-Crystal Integration - Blend', array($this->arg('visit'), 'Blend'), array('/visit/'.$this->arg('visit'), ''));
            
            
            if ($this->has_arg('user')) {
                $us = $this->dirs($root);
                $u = $us[$this->arg('user')];
            } else $u = phpCAS::getUser();
            
            $this->t->visit = $this->arg('visit');
            $this->t->js_var('visit', $this->arg('visit'));
            $this->t->js_var('user', $this->has_arg('user') ? $this->arg('user') : '');
            $this->t->js_var('owner', $u == phpCAS::getUser());
            $this->t->js_var('cas', phpCAS::getUser());
            
            $this->t->users = $users;
            
            $this->t->render('mc_blend');
        }
        
        
    }

?>