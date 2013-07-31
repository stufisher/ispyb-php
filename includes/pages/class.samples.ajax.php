<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('visit' => '\w\w\d\d\d\d-\d+', 'cid' => '\d+', 'sid' => '\d+', 'pos' => '\d+', 'name' => '\w+', 'array'=>'\d+', 't' => '\w+');
        var $dispatch = array('smp' => '_samples',
                              'dwr' => '_dewars',
                              'cnt' => '_containers',
                              'pro' => '_proteins',
                              'ship' => '_shipments',
                              'rc' => '_register_container',
                              'addp' => '_add_protein',
                              'adds' => '_add_shipment',
                              'addd' => '_add_dewar',
                              'unassign' => '_unassign',
                              'assign' => '_assign',
                              );
        
        var $def = 'smp';
        var $profile = True;
        #var $debug = True;
        
        
        
        # ------------------------------------------------------------------------
        # Return Shipments for visit
        function _shipments() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            
            $rows = $this->db->q("SELECT sh.shippingid, sh.shippingname FROM shipping sh
                                INNER JOIN blsession bl ON bl.proposalid = sh.proposalid
                                INNER JOIN proposal p ON sh.proposalid = p.proposalid
                                WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE '".$this->arg('visit')."' ORDER BY sh.shippingid DESC");
                                 
            $this->_output($rows);          
        }
        
                                 
        # ------------------------------------------------------------------------
        # Return Dewars for visit
        function _dewars() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
                                 
            if ($this->has_arg('sid')) $where = 'd.shippingid='.$this->arg('sid').' AND ';
            else $where = '';
                                 
            $rows = $this->db->q("SELECT d.dewarid, d.code, sh.shippingid,d.dewarstatus
                                FROM dewar d
                                INNER JOIN shipping sh ON sh.shippingid = d.shippingid
                                INNER JOIN blsession bl ON bl.proposalid = sh.proposalid
                                INNER JOIN proposal p ON sh.proposalid = p.proposalid
                                WHERE ".$where."p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE '".$this->arg('visit')."' ORDER BY sh.shippingid DESC");
                                 
            $this->_output($rows);          
        }
                                 
        # ------------------------------------------------------------------------
        # Return Containers for a Shipment
        function _containers() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            
            $rows = $this->db->q("SELECT d.dewarstatus, d.dewarid, s.shippingid, c.beamlinelocation, c.samplechangerlocation, c.containerid, c.code FROM container c
                                INNER JOIN dewar d ON d.dewarid = c.dewarid
                                INNER JOIN shipping s ON s.shippingid = d.shippingid
                                INNER JOIN blsession bl ON bl.proposalid = s.proposalid
                                INNER JOIN proposal p ON s.proposalid = p.proposalid
                                WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE '".$this->arg('visit')."' ORDER BY c.containerid DESC");
                                 
            foreach ($rows as &$r) {
                if ($r['SAMPLECHANGERLOCATION']) $r['SAMPLECHANGERLOCATION'] = intval($r['SAMPLECHANGERLOCATION']);
                $r['CONTAINERID'] = intval($r['CONTAINERID']);
            }
                                 
            $this->_output($rows);
        }
        
                                 
        # ------------------------------------------------------------------------
        # Return Samples for a Container
        function _samples() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            if (!$this->has_arg('cid')) $this->_error('No container specified');
                                 
            $rows = $this->db->q("SELECT sp.comments, sp.name, to_number(sp.location) as location, pr.acronym FROM blsample sp
                                INNER JOIN crystal cr ON sp.crystalid = cr.crystalid
                                INNER JOIN protein pr ON cr.proteinid = pr.proteinid
                                INNER JOIN container c ON sp.containerid = c.containerid
                                INNER JOIN dewar d ON d.dewarid = c.dewarid
                                INNER JOIN shipping s ON s.shippingid = d.shippingid
                                INNER JOIN blsession bl ON bl.proposalid = s.proposalid
                                INNER JOIN proposal p ON s.proposalid = p.proposalid
                                WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE '".$this->arg('visit')."'
                                AND c.containerid = ".$this->arg('cid')." ORDER BY to_number(sp.location)");
                        
            foreach ($rows as &$r) {
                $r['LOCATION'] = intval($r['LOCATION']);
            }
                                 
            $this->_output($rows);
        }
        
        
        # ------------------------------------------------------------------------
        # Return Proteins for a visit
        function _proteins() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            
            $rows = $this->db->q("SELECT distinct pr.acronym, max(pr.proteinid) as proteinid FROM protein pr
                                INNER JOIN blsession bl ON bl.proposalid = pr.proposalid
                                INNER JOIN proposal p ON bl.proposalid = p.proposalid
                                WHERE pr.acronym is not null AND p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE '".$this->arg('visit')."' GROUP BY pr.acronym ORDER BY lower(pr.acronym)");
            
            $proteins = array();
            foreach ($rows as &$r) {
                $proteins[$r['PROTEINID']] = $r['ACRONYM'];
                $r['PROTEINID'] = intval($r['PROTEINID']);
            }
                                 
            $this->_output($this->has_arg('array') ? $proteins : $rows);
        }
                                 
                                 
        # ------------------------------------------------------------------------
        # Register a new container
        function _register_container() {      
            $args = array();
            foreach (array('container' => '[a-zA-Z0-9_\-: ]+', 'dewar' => '\d+','shipment'=> '\d+') as $k => $m) {
                if (preg_match('/^'.$m.'$/', $_POST[$k])) {
                    $args[$k] = $_POST[$k];
                }
            }
            
            $samples = array();
            foreach ($_POST['samples'] as $s) {
                $val = True;
                foreach (array('sg' => '\w+', 'id' => '\d+', 'protein' => '\d+', 'name' => '\w+','comment'=> '[a-zA-Z0-9_]+') as $k => $m) {
                    if ($s[$k] && !preg_match('/^'.$m.'$/', $s[$k])) $val = False;
                }
                                 
                if ($val) array_push($samples, $s);
            }
                                 
            $pids = $this->db->q("SELECT p.proposalid FROM blsession bl INNER JOIN proposal p ON bl.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE '".$this->arg('visit')."'");
                                 
            if (sizeof($pids) > 0) { 
                $this->db->q("INSERT INTO container (containerid,dewarid,code,bltimestamp,capacity) VALUES (s_container.nextval,".$args['dewar'].",'".$args['container']."',CURRENT_TIMESTAMP,16) RETURNING containerid INTO :id");
                                 
                $cid = $this->db->id();
                                 
                foreach ($samples as $s) {
                    $this->db->q("INSERT INTO crystal (crystalid,proteinid,spacegroup) VALUES (s_crystal.nextval,".$s['protein'].",'".$s['sg']."') RETURNING crystalid INTO :id");
                    $crysid = $this->db->id();
                                 
                    $this->db->q("INSERT INTO blsample (blsampleid,crystalid,containerid,location,comments,name) VALUES (s_blsample.nextval,".$crysid.",".$cid.",".($s['id']+1).",'".$s['comment']."','".$s['name']."')");
                }
                                 
                $this->_output(1);
            }
                                 
        }

        # ------------------------------------------------------------------------
        # Add a new protein
        function _add_protein() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            if (!$this->has_arg('name')) $this->_error('No protein name specified');
                                 
            $pids = $this->db->q("SELECT p.proposalid FROM blsession bl INNER JOIN proposal p ON bl.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE '".$this->arg('visit')."'");
                                 
            if (sizeof($pids) > 0) {
                $pid = $pids[0]['PROPOSALID'];
                                 
                $vals = $this->db->q("INSERT INTO protein (proteinid,proposalid,acronym,bltimestamp) VALUES (s_protein.nextval,".$pid.",'".$this->arg('name')."',CURRENT_TIMESTAMP) RETURNING proteinid INTO :id");
                                 
                $this->_output($this->db->id());
            }                                 
            //$this->_output(32282);
        }
                                 
        # ------------------------------------------------------------------------
        # Add a new shipment
        function _add_shipment() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            if (!$this->has_arg('name')) $this->_error('No shipment name specified');
                                 
            $pids = $this->db->q("SELECT p.proposalid FROM blsession bl INNER JOIN proposal p ON bl.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE '".$this->arg('visit')."'");
                                 
            if (sizeof($pids) > 0) {
                $pid = $pids[0]['PROPOSALID'];
                                 
                $vals = $this->db->q("INSERT INTO shipping (shippingid,proposalid,shippingname,bltimestamp) VALUES (s_shipping.nextval,".$pid.",'".$this->arg('name')."',CURRENT_TIMESTAMP) RETURNING shippingid INTO :id");
                                 
                $this->_output($this->db->id());
            }     
            #$this->_output(1884);
        }
                                 
        # ------------------------------------------------------------------------
        # Add a new dewar
        function _add_dewar() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            if (!$this->has_arg('sid')) $this->_error('No shipping id specified');
            if (!$this->has_arg('name')) $this->_error('No dewar name specified');
                                 
            $pids = $this->db->q("SELECT p.proposalid FROM blsession bl INNER JOIN proposal p ON bl.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE '".$this->arg('visit')."'");
                                 
            if (sizeof($pids) > 0) {
                $pid = $pids[0]['PROPOSALID'];
                                 
                $vals = $this->db->q("INSERT INTO dewar (dewarid,code,shippingid,bltimestamp,dewarstatus) VALUES (s_dewar.nextval,'".$this->arg('name')."',".$this->arg('sid').",CURRENT_TIMESTAMP,'processing') RETURNING dewarid INTO :id");
                                 
                $this->_output($this->db->id());
            }
            #$this->_output(2267);
        }
                                 
        # ------------------------------------------------------------------------
        # Assign a container
        function _assign() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            if (!$this->has_arg('cid')) $this->_error('No container id specified');
                                 
            $cs = $this->db->q("SELECT d.dewarid,bl.beamlinename,c.containerid FROM container c
                                INNER JOIN dewar d ON d.dewarid = c.dewarid
                                INNER JOIN shipping s ON s.shippingid = d.shippingid
                                INNER JOIN blsession bl ON bl.proposalid = s.proposalid
                                INNER JOIN proposal p ON s.proposalid = p.proposalid
                                WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE '".$this->arg('visit')."' AND c.containerid=".$this->arg('cid'));
                               
            if (sizeof($cs) > 0) {
                $c = $cs[0];
                $this->db->q("UPDATE dewar SET dewarstatus='processing' WHERE dewarid=".$c['DEWARID']);
                               
                $this->db->q("UPDATE container SET beamlinelocation='".$c['BEAMLINENAME']."',samplechangerlocation=".$this->arg('pos')." WHERE containerid=".$c['CONTAINERID']);
                               
                $this->_output(1);
            }
                               
            $this->_output(0);
        }
                                 
        # ------------------------------------------------------------------------
        # Unassign a container
        function _unassign() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            if (!$this->has_arg('cid')) $this->_error('No container id specified');
                               
            $cs = $this->db->q("SELECT d.dewarid,bl.beamlinename,c.containerid FROM container c
                                INNER JOIN dewar d ON d.dewarid = c.dewarid
                                INNER JOIN shipping s ON s.shippingid = d.shippingid
                                INNER JOIN blsession bl ON bl.proposalid = s.proposalid
                                INNER JOIN proposal p ON s.proposalid = p.proposalid
                                WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE '".$this->arg('visit')."' AND c.containerid=".$this->arg('cid'));
                               
            if (sizeof($cs) > 0) {
                $c = $cs[0];
                               
                $this->db->q("UPDATE container SET samplechangerlocation='' WHERE containerid=".$c['CONTAINERID']);
                $this->_output(1);
            }
            $this->_output(0);
        }
                                           
    }

?>