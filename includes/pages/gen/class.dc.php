<?php

    class Dc extends Page {
        
        var $arg_list = array('visit' => '\w+\d+-\d+', 'page' => '\d+', 'id' => '\d+', 't' => '\w+', 'id' => '\d+', 's' => '\w+', 'pp' => '\d+', 'dmy' => '\d\d\d\d\d\d\d\d', 'ty' => '\w+');
        var $dispatch = array('dc' => '_data_collection',
                              );
        var $def = 'dc';
        
        var $sidebar = True;
        
        var $root = 'Data Collections';
        var $root_link = '/dc';
                
        
        # List of data collections for a proposal / visit / sample
        function _data_collection() {
            if (!($this->has_arg('visit') || $this->has_arg('prop'))) $this->error('No visit /proposal specified', 'No visit or proposal specified');
            
            $active = False;
            $is_visit = False;
            $is_sample = False;
            
            if ($this->has_arg('visit')) {
                $info = $this->db->pq("SELECT (s.enddate - s.startdate)*24 as len, TO_CHAR(s.startdate, 'HH24') as sh, TO_CHAR(s.startdate, 'DD-MM-YYYY HH24:MI') as st, TO_CHAR(s.enddate, 'DD-MM-YYYY HH24:MI') as en, case when sysdate between s.startdate and s.enddate then 1 else 0 end as active, s.sessionid, s.beamlinename as bl, vr.run, vr.runid, TO_CHAR(s.startdate, 'YYYY') as yr, p.proposalcode||p.proposalnumber as prop FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));
                
                if (!sizeof($info)) {
                    $this->msg('No such visit', 'That visit doesnt appear to exist');
                } else $info = $info[0];
                
                $info['LEN'] = intval($info['LEN']);
                
                $info['ACTIVE'] = time() >= strtotime($info['ST']) && time() <= strtotime($info['EN']);
                
                $this->cookie($info['PROP']);
                $this->args['prop'] = $info['PROP'];
                $active = $info['ACTIVE'];
                $is_visit = True;
                
                $p = array($info['BL'], $this->arg('visit'));
                $l = array('', '');

                $title = $this->arg('visit');
                
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
                
            $this->t->active = $active;
            $this->t->is_visit = $is_visit;
            
            if ($is_visit) $this->t->js_var('sh', intval($info['SH']));
            if ($is_visit) $this->t->js_var('len', $info['LEN']);
            
            $this->t->is_sample = $is_sample;
            $this->t->js_var('is_visit', $is_visit);
            $this->t->js_var('prop', $this->has_arg('prop') ? $this->arg('prop') : '');
            
            $this->t->js_var('page', $this->has_arg('page') ? intval($this->arg('page')) : 1);
            $this->t->js_var('pp', $this->has_arg('pp') ? intval($this->arg('pp')) : '');
            #$this->t->js_var('year', $info['YR']);
            $this->t->js_var('type', $this->has_arg('t') ? $this->arg('t') : '');
            $this->t->js_var('search', $this->has_arg('s') ? $this->arg('s') : '');
            $this->t->js_var('dcid', $this->has_arg('id') ? $this->arg('id') : '');
            
            $this->t->js_var('h', $this->has_arg('h') ? $this->arg('h') : '');
            $this->t->js_var('dmy', $this->has_arg('dmy') ? $this->arg('dmy') : '');
            
            $this->t->dcid = $this->has_arg('id') ? $this->arg('id') : '';
            
            $this->render('gen/dc_list');
        }
        
        
    }

?>