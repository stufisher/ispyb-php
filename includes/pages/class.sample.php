<?php

    class Sample extends Page {
        
        var $arg_list = array('prop' => '\w\w\d+',
                              'pid' => '\d+',
                              'sid' => '\d+',
                              'page' => '\d+',
                              );
        var $dispatch = array('samples' => '_sample_dispatch',
                              'proteins' => '_protein_dispatch',
                              );
        var $def = 'samples';
        
        var $sidebar = True;
        
        var $root = 'Samples';
        var $root_link = '/sample';
        #var $debug = true;
        
        function _sample_dispatch() {
            if ($this->has_arg('sid')) $this->_view_sample();
            else $this->_samples();
        }
        

        function _samples() {
            $this->template('Samples');
            $this->t->render('sample');
        }
        
        
        function _view_sample() {
            if (!$this->has_arg('sid')) $this->error('No sample id', 'No sample id specified');
            
            $samp = $this->db->pq("SELECT s.blsampleid, s.name, s.code,s.comments,cr.spacegroup,pr.acronym,pr.proteinid FROM ispyb4a_db.blsample s INNER JOIN ispyb4a_db.crystal cr ON s.crystalid = cr.crystalid INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = cr.proteinid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = pr.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1 and s.blsampleid=:2", array($this->arg('prop'), $this->arg('sid')));

            if (!sizeof($samp)) $this->error('No such sample', 'The specified sample id doesnt exist');
            else $samp = $samp[0];
            
            $snapshots = $this->db->pq("SELECT datacollectionid as id,xtalsnapshotfullpath1 as sn FROM datacollection WHERE blsampleid=:1", array($this->arg('sid')));
            
            $sn = array();
            foreach ($snapshots as $s) {
                if (file_exists($s['SN'])) array_push($sn, $s['ID']);
            }
            
            $this->template('View Sample', array($samp['NAME']), array(''));
            
            $this->t->samp = $samp;
            $this->t->sn = $sn;
            $this->t->js_var('sid', $this->arg('sid'));
            $this->t->js_var('prop', $this->arg('prop'));
            $this->t->js_var('page', $this->has_arg('page') ? intval($this->arg('page')) : 1);
            $this->t->js_var('sgs', $this->sg_hash());
            
            $this->t->render('sample_view');
        }
        
        
        
        function _protein_dispatch() {
            if ($this->has_arg('pid')) $this->_view_protein();
            else $this->_proteins();
        }

        
        function _proteins() {
            $this->template('Proteins');
            $this->t->render('protein');
        }
        
        
        
        function _view_protein() {
            if (!$this->has_arg('pid')) $this->error('No protein id', 'No protein id was specified');
            
            $prot = $this->db->pq('SELECT pr.acronym, pr.name, pr.sequence, pr.molecularmass FROM ispyb4a_db.protein pr INNER JOIN ispyb4a_db.proposal p ON p.proposalid = pr.proposalid  WHERE pr.proteinid=:1 AND p.proposalcode || p.proposalnumber LIKE :2', array($this->arg('pid'), $this->arg('prop')));
            
            if (!sizeof($prot)) $this->error('No such protein', 'The specified protein id doesnt exist');
            else $prot = $prot[0];
            
            $this->template('View Protein', array($prot['NAME'] ? $prot['NAME'] : $prot['ACRONYM']), array(''));
            $this->t->prot = $prot;
            $this->t->js_var('pid', $this->arg('pid'));
            $this->t->render('protein_view');
        }
    }

?>