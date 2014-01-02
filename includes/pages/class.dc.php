<?php

    class Dc extends Page {
        
        var $arg_list = array('visit' => '\w+\d+-\d+', 'page' => '\d+', 'id' => '\d+', 't' => '\w+', 'iframe' => '\d+', 'id' => '\d+', 'sid' => '\d+', 's' => '\w+');
        var $dispatch = array('dc' => '_data_collection', 'view' => '_viewer');
        var $def = 'dc';
        
        var $sidebar = True;
        
        var $root = 'Data Collections';
        var $root_link = '/dc';
        
        
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
            $this->template('Image Viewer: ' . $dc['VIS'] . ' - ' . $dc['DIR'].$dc['FT'], $p, $l, !$this->has_arg('iframe'));
            
            $this->t->d = $dc;
            
            $this->t->js_var('id', $this->arg('id'));
            $this->t->js_var('ni', floatval($dc['NUM']));
            $this->t->js_var('dc', $dc);
            
            $this->render('dc_viewer');
        }
        
        
        # List of data collections for a proposal / visit / sample
        function _data_collection() {
            if (!($this->has_arg('visit') || $this->has_arg('prop'))) $this->error('No visit /proposal specified', 'No visit or proposal specified');
            
            $start = 0;
            $end = 10;
            
            if ($this->has_arg('page')) {
                $pp = 10;
                $start = $this->arg('page')*$pp;
                $end = $this->arg('page')*$pp+$pp;
            }
            
            $active = False;
            $is_visit = False;
            $is_sample = False;
            
            if ($this->has_arg('visit')) {
                $info = $this->db->pq("SELECT case when sysdate between s.startdate and s.enddate then 1 else 0 end as active, s.sessionid, s.beamlinename as bl, vr.run, vr.runid, TO_CHAR(s.startdate, 'YYYY') as yr, p.proposalcode||p.proposalnumber as prop FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));
                
                if (!sizeof($info)) {
                    $this->msg('No such visit', 'That visit doesnt appear to exist');
                } else $info = $info[0];
                
                $this->cookie($info['PROP']);
                $active = $info['ACTIVE'];
                $is_visit = True;
                
                $p = array($info['BL'], $this->arg('visit'));
                $l = array('', '');

                $title = $this->arg('visit');
                
            } else if ($this->has_arg('sid')) {
                $info = $this->db->pq("SELECT s.name, s.code,s.comments,cr.spacegroup,pr.acronym,pr.proteinid FROM ispyb4a_db.blsample s INNER JOIN ispyb4a_db.crystal cr ON s.crystalid = cr.crystalid INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = cr.proteinid WHERE pr.proposalid=:1 and s.blsampleid=:2", array($this->proposalid, $this->arg('sid')));
                
                if (!sizeof($info)) {
                    $this->msg('No such sample', 'That sample doesnt appear to exist');
                } else $info = $info[0];
                
                $p = array($info['NAME']);
                $l = array('');
                $title = $info['NAME'];
                $is_sample = True;
                $sample_link = '<a href="/sample/sid/'.$this->arg('sid').'">'.$info['NAME'].'</a>';
                
            } else if ($this->has_arg('prop')) {
                $p = array($this->arg('prop'));
                $l = array('');
                $title = $this->arg('prop');
            }
            
            $this->template('Data Collections for ' . $title, $p, $l);

            if ($this->has_arg('visit')) {
                $this->t->bl = $info['BL'];
                $this->t->vis = $this->arg('visit');
            
                #$this->t->js_var('active', $info['ACTIVE']);
                $this->t->js_var('visit', $this->arg('visit'));
                $this->t->js_var('bl', $info['BL']);
                list($this->t->vid, $this->t->vno) = explode('-',$this->arg('visit'));
            }
            
            if ($this->has_arg('sid')) {
                $this->t->js_var('sid', $this->arg('sid'));
                $this->t->sl = $sample_link;
            }
                
            $this->t->active = $active;
            $this->t->is_visit = $is_visit;
            $this->t->is_sample = $is_sample;
            $this->t->js_var('is_visit', $is_visit);
            $this->t->js_var('is_sample', $is_sample);
            $this->t->js_var('prop', $this->has_arg('prop') ? $this->arg('prop') : '');
            
            $this->t->js_var('page', $this->has_arg('page') ? intval($this->arg('page')) : 1);
            #$this->t->js_var('year', $info['YR']);
            $this->t->js_var('type', $this->has_arg('t') ? $this->arg('t') : '');
            $this->t->js_var('search', $this->has_arg('s') ? $this->arg('s') : '');
            $this->t->js_var('dcid', $this->has_arg('id') ? $this->arg('id') : '');
            $this->t->dcid = $this->has_arg('id') ? $this->arg('id') : '';
            
            
            $this->render('dc_list');
        }
    }

?>