<?php

    class DC extends Page {
        
        var $arg_list = array('visit' => '\w\w\d\d\d\d-\d+', 'page' => '\d+', 'mon' => '\w+', 'year' => '\d\d\d\d', 'id' => '\d+', 't' => '\w+');
        var $dispatch = array('dc' => '_dispatch', 'view' => '_viewer');
        var $def = 'dc';
        
        var $root = 'Data Collections';
        var $root_link = '/dc';
        
        
        # Dispatch to either calendar or list view as needed
        function _dispatch() {
            if ($this->has_arg('visit')) $this->_data_collection();
            else $this->_index();
        }
        
        
        # Diffraction image viewer
        function _viewer() {
            if (!$this->has_arg('id')) {
                $this->error('No data collection id specified', 'You need to specify a data collection id in order to view diffraction images');
            }
            
            $dc = $this->db->pq('SELECT dc.transmission, dc.axisrange, dc.exposuretime, dc.resolution as res, dc.ybeam as y, dc.xbeam as x,dc.wavelength as lam, dc.detectordistance as det, dc.numberofimages as num, dc.filetemplate as ft, dc.imageprefix as imp, dc.datacollectionnumber as run, dc.imagedirectory as dir, p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON s.sessionid=dc.sessionid INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE dc.datacollectionid=:1', array($this->arg('id')));
            
            if (!sizeof($dc)) {
                $this->_index();
                return;
            }
            
            $dc = $dc[0];
            $dc['DIR'] = $this->ads($dc['DIR']);
            $dc['DIR'] = substr($dc['DIR'], strpos($dc['DIR'], $dc['VIS'])+strlen($dc['VIS'])+1);
            foreach (array('X', 'Y', 'DET', 'LAM', 'RES') as $k) $dc[$k] = floatval($dc[$k]);            
            
            $p = array($dc['VIS'], $dc['DIR'].$dc['FT']);
            $l = array('/visit/'.$dc['VIS'], '');
            $this->template('Image Viewer: ' . $dc['VIS'] . ' - ' . $dc['DIR'].$dc['FT'], $p, $l);
            
            $this->t->d = $dc;
            
            $this->t->js_var('id', $this->arg('id'));
            $this->t->js_var('ni', floatval($dc['NUM']));
            $this->t->js_var('dc', $dc);
            
            $this->render('dc_viewer');
        }
        
        
        # List of visits by date / beamline
        function _index() {
            $this->template('Visits');            
            
            $c_year = date('Y');
            $c_month = date('n');
            $c_day = date('j');
            
            $this->t->days = array(1=>'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
            $this->t->months = array(1=>'January','February','March','April','May','June','July','August','September','October','November','December');
            
            if ($this->has_arg('mon')) {
                $id = array_search($this->arg('mon'), $this->t->months);
                if ($id !== False) $c_month = $id;
            }
            
            if ($this->has_arg('year')) {
                $c_year = $this->arg('year');
            }
            
            $this->t->first = (date('w',mktime(0,0,0,$c_month,1,$c_year)) - 1) % 7;
            $this->t->dim = date('t', mktime (0,0,0,$c_month,1,$c_year));
            $this->t->rem = ($this->t->first+$this->t->dim) % 7;
            
            $day = mktime(0,0,0,$c_month,1,$c_year);
            $den = mktime(23,59,59,$c_month,$this->t->dim,$c_year);
            
            $visits = $this->db->pq("SELECT p.proposalcode || p.proposalnumber || '-' || s.visit_number as vis, s.beamlinename as bl, TO_CHAR(s.startdate, 'DD-MM-YYYY HH24:MI') as st FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE s.startdate BETWEEN TO_DATE(:1,'dd-mm-yyyy') AND TO_DATE(:2,'dd-mm-yyyy') AND (s.beamlinename LIKE 'i02' OR s.beamlinename LIKE 'i03' OR s.beamlinename LIKE 'i04' OR s.beamlinename LIKE 'i04-1' OR s.beamlinename LIKE 'i24') ORDER BY s.beamlinename, s.startdate", array(strtoupper(date('d-m-Y', $day)), strtoupper(date('d-m-Y', $den))));
            
            $vbd = array();
            foreach ($visits as $v) {
                if (!$this->staff)
                    if (!in_array($v['VIS'], $this->visits)) continue;
                
                $t = strtotime($v['ST']);
                $k = date('j', $t);
                $k2 = date('H:i', $t);
                $v['TIME'] = $k2;
                
                if (!array_key_exists($k, $vbd)) $vbd[$k] = array();
                if (!array_key_exists($k2, $vbd[$k])) $vbd[$k][$k2] = array();
                
                array_push($vbd[$k][$k2], $v);
            }
            
            $this->t->next_mon = $this->t->months[($c_month + 1) % 12];
            $this->t->prev_mon = $this->t->months[($c_month - 1) % 12];
            
            $this->t->vbd = $vbd;
            $this->t->c_day = $c_day;
            $this->t->c_month = $c_month;
            $this->t->c_year = $c_year;
            
            $this->render('dc');
        }
        
        
        # List of data collections for a visit
        function _data_collection() {
            $start = 0;
            $end = 10;
            
            if ($this->has_arg('page')) {
                $pp = 10;
                $start = $this->arg('page')*$pp;
                $end = $this->arg('page')*$pp+$pp;
            }
            
            $info = $this->db->pq("SELECT case when sysdate between s.startdate and s.enddate then 1 else 0 end as active, s.sessionid, s.beamlinename as bl, vr.run, vr.runid, TO_CHAR(s.startdate, 'YYYY') as yr FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));
            
            if (!sizeof($info)) {
                $this->msg('No such visit', 'That visit doesnt appear to exist');
            } else $info = $info[0];

            
            $p = array($info['BL'], $this->arg('visit'));
            $l = array('', '');
            
            $this->template('Data Collections for ' . $this->arg('visit'), $p, $l);
            
            $this->t->bl = $info['BL'];
            $this->t->vis = $this->arg('visit');
            $this->t->active = 1;#$info['ACTIVE'];
            
            $this->t->js_var('visit', $this->arg('visit'));
            $this->t->js_var('page', $this->has_arg('page') ? intval($this->arg('page')) : 1);
            $this->t->js_var('bl', $info['BL']);
            $this->t->js_var('year', $info['YR']);
            $this->t->js_var('type', $this->has_arg('t') ? $this->arg('t') : '');
            
            list($this->t->vid, $this->t->vno) = explode('-',$this->arg('visit'));
            
            $this->render('dc_list');
        }
    }

?>