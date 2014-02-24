<?php

    class Sample extends Page {
        
        var $arg_list = array('prop' => '\w\w\d+',
                              'pid' => '\d+',
                              'sid' => '\d+',
                              'page' => '\d+',
                              'name' => '.*',
                              'acronym' => '([\w-])+',
                              'seq' => '\w+',
                              'mass' => '\d+(.\d+)',
                              'submit' => '\d',
                              'pdb_codes' => '([\w\s,])+',
                              );
        var $dispatch = array('samples' => '_sample_dispatch',
                              'proteins' => '_protein_dispatch',
                              'addp' => '_add_protein',
                              );
        var $def = 'samples';
        
        var $sidebar = True;
        
        var $root = 'Samples';
        var $root_link = '/sample';
        #var $debug = true;
        #var $explain = True;
        
        
        # ------------------------------------------------------------------------
        # View sample or list of samples based on passed args
        function _sample_dispatch() {
            if ($this->has_arg('sid')) $this->_view_sample();
            else $this->_samples();
        }
        

        # ------------------------------------------------------------------------
        # List of samples for a proposal
        function _samples() {
            $this->template('Samples');
            $this->t->render('sample');
        }
        
        
        # ------------------------------------------------------------------------
        # View a particular samples
        function _view_sample() {
            if (!$this->has_arg('sid')) $this->error('No sample id', 'No sample id specified');
            
            $samp = $this->db->pq("SELECT d.code as dewar,sh.shippingname as shipment,sh.shippingid,c.code as container,c.containerid, s.blsampleid, s.name, s.code,s.comments,cr.spacegroup,pr.acronym,pr.proteinid FROM ispyb4a_db.blsample s INNER JOIN ispyb4a_db.crystal cr ON s.crystalid = cr.crystalid INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = cr.proteinid LEFT OUTER JOIN ispyb4a_db.container c ON c.containerid = s.containerid LEFT OUTER JOIN ispyb4a_db.dewar d ON d.dewarid = c.dewarid INNER JOIN ispyb4a_db.shipping sh ON sh.shippingid = d.shippingid WHERE pr.proposalid=:1 and s.blsampleid=:2", array($this->proposalid, $this->arg('sid')));

            if (!sizeof($samp)) $this->error('No such sample', 'The specified sample id doesnt exist');
            else $samp = $samp[0];
            
            $snapshots = $this->db->pq("SELECT datacollectionid as id,xtalsnapshotfullpath1 as sn FROM datacollection WHERE blsampleid=:1", array($this->arg('sid')));
            
            $sn = array();
            foreach ($snapshots as $s) {
                if (file_exists($s['SN'])) array_push($sn, $s['ID']);
            }
            
            $this->template('View Sample', array($samp['ACRONYM'], $samp['NAME']), array('proteins/pid/'.$samp['PROTEINID'],''));
            
            $this->t->samp = $samp;
            $this->t->sn = $sn;
            $this->t->js_var('sid', $this->arg('sid'));
            $this->t->js_var('prop', $this->arg('prop'));
            $this->t->js_var('page', $this->has_arg('page') ? intval($this->arg('page')) : 1);
            $this->t->js_var('sgs', $this->sg_hash());
            
            $this->t->render('sample_view');
        }
        
        
        # ------------------------------------------------------------------------
        # View protein or list of proteins based on passed args
        function _protein_dispatch() {
            if ($this->has_arg('pid')) $this->_view_protein();
            else $this->_proteins();
        }

        
        # ------------------------------------------------------------------------
        # View list of proteins
        function _proteins() {
            $this->template('Proteins', array('Proteins'), array('proteins'));
            $this->t->render('protein');
        }
        
        
        # ------------------------------------------------------------------------
        # View a particular protein
        function _view_protein() {
            if (!$this->has_arg('pid')) $this->error('No protein id', 'No protein id was specified');
            
            $prot = $this->db->pq('SELECT pr.acronym, pr.name, pr.sequence, pr.molecularmass FROM ispyb4a_db.protein pr WHERE pr.proteinid=:1 AND pr.proposalid=:2', array($this->arg('pid'), $this->proposalid));
            
            if (!sizeof($prot)) $this->error('No such protein', 'The specified protein id doesnt exist');
            else $prot = $prot[0];
            
            if ($prot['SEQUENCE']) {
                $prot['SEQUENCE'] = $prot['SEQUENCE']->read($prot['SEQUENCE']->size());
            }
            
            $this->template('View Protein', array('Proteins', $prot['NAME'] ? $prot['NAME'] : $prot['ACRONYM']), array('proteins', ''));
            $this->t->prot = $prot;
            $this->t->js_var('pid', $this->arg('pid'));
            $this->t->render('protein_view');
        }
        
        
        
        
        # ------------------------------------------------------------------------
        # Add a new protein
        function _add_protein() {
            if (!$this->has_arg('prop')) $this->error('No proposal selected', 'No proposal selected. Select a proposal before viewing this page');
            
            if ($this->has_arg('submit')) {
                $pids = $this->db->pq("SELECT p.proposalid FROM blsession bl INNER JOIN proposal p ON bl.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1", array($this->arg('prop')));
                                 
                if (!sizeof($pids) > 0) $this->error('No such proposal', 'The specified proposal doesnt exist');
                else $pid = $pids[0]['PROPOSALID'];
                
                if (!$this->has_arg('acronym')) $this->error('No protein acronym', 'You must supply at least a protein acronym');
                
                $name = $this->has_arg('name') ? $this->arg('name') : '';
                $seq = $this->has_arg('seq') ? $this->arg('seq') : '';
                $mass = $this->has_arg('mass') ? $this->arg('mass') : '';
                
                $this->db->pq('INSERT INTO ispyb4a_db.protein (proteinid,proposalid,name,acronym,sequence,molecularmass,bltimestamp) VALUES (s_protein.nextval,:1,:2,:3,:4,:5,CURRENT_TIMESTAMP) RETURNING proteinid INTO :id',array($pid, $name, $this->arg('acronym'), $seq, $mass));
                
                $pid = $this->db->id();
                
                foreach ($_FILES['new_pdb']['name'] as $i => $pdb) {
                    if ($pdb) {
                        $info = pathinfo($pdb);
                        
                        if ($info['extension'] == 'pdb') {
                            $file = file_get_contents($_FILES['new_pdb']['tmp_name'][$i]);
                            $this->_associate_pdb($info['basename'],$file,'',$pid);
                        }
                    }
                }
                
                if ($this->has_arg('pdb_codes')) {
                    foreach (explode(',',$this->arg('pdb_codes')) as $c) {
                        $c = strtolower(trim($c));
                        if (strlen($c) == 4) {
                            $this->_associate_pdb($c,'',$c,$pid);
                        }
                    }
                }
                
                if (array_key_exists('existing_pdb', $_POST)) {
                    if (sizeof($_POST['existing_pdb'])) {
                        foreach ($_POST['existing_pdb'] as $p) {
                            $rows = $this->db->pq("SELECT p.pdbid FROM ispyb4a_db.pdb p INNER JOIN ispyb4a_db.protein_has_pdb hp ON p.pdbid = hp.pdbid INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = hp.proteinid WHERE pr.proposalid=:1 AND p.pdbid=:2", array($this->proposalid, $p));
                            
                            if (!sizeof($rows)) $this->_error('The specified pdb doesnt exist');                            
                            
                            $this->db->pq("INSERT INTO ispyb4a_db.protein_has_pdb (proteinhaspdbid,proteinid,pdbid) VALUES (s_protein_has_pdb.nextval,:1,:2)", array($pid,$p));
                        }
                    }
                }
                
                $this->msg('New Protein Added', 'You protein was successfully added, click <a href="/sample/proteins/pid/'.$pid.'">here</a> to view it');
                
                
            } else {
                $this->template('Add Protein', array('Proteins', 'Add Protein'), array('proteins', ''));
                $this->t->render('protein_add');
            }
        }
        
        
        function _associate_pdb($name,$contents,$code,$pid) { 
            $this->db->pq("INSERT INTO ispyb4a_db.pdb (pdbid,name,contents,code) VALUES(s_pdb.nextval,:1,:2,:3) RETURNING pdbid INTO :id", array($name,$contents,$code));
            $pdbid = $this->db->id();
            
            $this->db->pq("INSERT INTO ispyb4a_db.protein_has_pdb (proteinhaspdbid,proteinid,pdbid) VALUES (s_protein_has_pdb.nextval,:1,:2)", array($pid,$pdbid));
        }

    }

?>