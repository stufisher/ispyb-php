<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('visit' => '\w+\d+-\d+', 'cid' => '\d+', 'sid' => '\d+', 'pos' => '\d+', 'name' => '\w+', 'array'=>'\d+', 't' => '\w+', 'container' => '[a-zA-Z0-9_\-: ]+', 'dewar' => '\d+','shipment'=> '\d+');
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
            
            $rows = $this->db->pq("SELECT sh.shippingid, sh.shippingname FROM shipping sh INNER JOIN blsession bl ON bl.proposalid = sh.proposalid INNER JOIN proposal p ON sh.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE :1 ORDER BY sh.shippingid DESC", array($this->arg('visit')));
                                 
            $this->_output($rows);          
        }
        
                                 
        # ------------------------------------------------------------------------
        # Return Dewars for visit
        function _dewars() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
                                 
            $args = array($this->arg('visit'));
                                  
            if ($this->has_arg('sid')) {
                $where = 'd.shippingid=:2 AND ';
                array_push($args, $this->arg('sid'));
                
            }else $where = '';
                                 
            $rows = $this->db->pq("SELECT d.dewarid, d.code, sh.shippingid,d.dewarstatus FROM dewar d INNER JOIN shipping sh ON sh.shippingid = d.shippingid INNER JOIN blsession bl ON bl.proposalid = sh.proposalid INNER JOIN proposal p ON sh.proposalid = p.proposalid WHERE $where p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE :1 ORDER BY sh.shippingid DESC", $args);
                                 
            $this->_output($rows);          
        }
                                 
        # ------------------------------------------------------------------------
        # Return Containers for a Shipment
        function _containers() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            
            $rows = $this->db->pq("SELECT d.dewarstatus, d.dewarid, s.shippingid, c.beamlinelocation, c.samplechangerlocation, c.containerid, c.code FROM container c
                                INNER JOIN dewar d ON d.dewarid = c.dewarid
                                INNER JOIN shipping s ON s.shippingid = d.shippingid
                                INNER JOIN blsession bl ON bl.proposalid = s.proposalid
                                INNER JOIN proposal p ON s.proposalid = p.proposalid
                                WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE :1 ORDER BY c.containerid DESC", array($this->arg('visit')));
                                 
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
                                 
            $rows = $this->db->pq("SELECT sp.comments, sp.name, to_number(sp.location) as location, pr.acronym FROM blsample sp
                                INNER JOIN crystal cr ON sp.crystalid = cr.crystalid
                                INNER JOIN protein pr ON cr.proteinid = pr.proteinid
                                INNER JOIN container c ON sp.containerid = c.containerid
                                INNER JOIN dewar d ON d.dewarid = c.dewarid
                                INNER JOIN shipping s ON s.shippingid = d.shippingid
                                INNER JOIN blsession bl ON bl.proposalid = s.proposalid
                                INNER JOIN proposal p ON s.proposalid = p.proposalid
                                WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE :1
                                AND c.containerid = :2 ORDER BY to_number(sp.location)", array($this->arg('visit'), $this->arg('cid')));
                        
            foreach ($rows as &$r) {
                $r['LOCATION'] = intval($r['LOCATION']);
            }
                                 
            $this->_output($rows);
        }
        
        
        # ------------------------------------------------------------------------
        # Return Proteins for a visit
        function _proteins() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            
            $rows = $this->db->pq("SELECT distinct pr.acronym, max(pr.proteinid) as proteinid FROM protein pr
                                INNER JOIN blsession bl ON bl.proposalid = pr.proposalid
                                INNER JOIN proposal p ON bl.proposalid = p.proposalid
                                WHERE pr.acronym is not null AND p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE :1 GROUP BY pr.acronym ORDER BY lower(pr.acronym)", array($this->arg('visit')));
            
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
            
            $samples = array();
            if (array_key_exists('samples', $_POST)) {
                foreach ($_POST['samples'] as $s) {
                    $val = True;
                    foreach (array('sg' => '\w+', 'id' => '\d+', 'protein' => '\d+', 'name' => '\w+','comment'=> '[a-zA-Z0-9_]+') as $k => $m) {
                        if ($s[$k] && !preg_match('/^'.$m.'$/', $s[$k])) $val = False;
                    }
                                     
                    if ($val) array_push($samples, $s);
                }
            }
                                 
            $pids = $this->db->pq("SELECT p.proposalid,bl.sessionid,bl.beamlinename FROM blsession bl INNER JOIN proposal p ON bl.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE :1", array($this->arg('visit')));
                                 
            if (sizeof($pids) > 0) {
                $did = $this->has_arg('dewar') ? $this->arg('dewar') : $this->_default_shipment_dewar($this->arg('visit'), $pids[0]['SESSIONID'], $pids[0]['PROPOSALID']);
                                  
                if ($did > -1) {
                    $bll = '';
                    $pos = '';
                                  
                    if ($this->has_arg('pos')) {
                        if ($this->arg('pos') > -1) {
                            $bll = $pids[0]['BEAMLINENAME'];
                            $pos = $this->arg('pos');
                        }
                    }
                                  
                    $this->db->pq("INSERT INTO container (containerid,dewarid,code,bltimestamp,capacity,beamlinelocation,samplechangerlocation) VALUES (s_container.nextval,:1,:2,CURRENT_TIMESTAMP,16,:3,:4) RETURNING containerid INTO :id", array($did, $this->arg('container'),$bll,$pos));
                                     
                    $cid = $this->db->id();
                                     
                    foreach ($samples as $s) {
                        $this->db->pq("INSERT INTO crystal (crystalid,proteinid,spacegroup) VALUES (s_crystal.nextval,:1,:2) RETURNING crystalid INTO :id", array($s['protein'], $s['sg']));
                        $crysid = $this->db->id();
                                     
                        $this->db->pq("INSERT INTO blsample (blsampleid,crystalid,containerid,location,comments,name) VALUES (s_blsample.nextval,:1,:2,:3,:4,:5)", array($crysid, $cid, $s['id']+1, $s['comment'], $s['name']));
                    }
                                     
                    $this->_output(1);
                }
            }
                                 
        }
                                  
    
        function _default_shipment_dewar($visit, $sid, $pid) {
            $sids = $this->db->pq("SELECT shippingid FROM shipping WHERE proposalid LIKE :1 AND shippingname LIKE :2", array($pid, $visit.'_Shipment1'));
            
            if (sizeof($sids) > 0) {
                $shid = $sids[0]['SHIPPINGID'];
            } else {
                $this->db->pq("INSERT INTO shipping (shippingid,proposalid,shippingname,bltimestamp,creationdate,shippingstatus) VALUES (s_shipping.nextval,:1,:2,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,'processing') RETURNING shippingid INTO :id", array($pid, $visit.'_Shipment1'));
                                  
                $shid = $this->db->id();
                                  
                $vals = $this->db->pq("INSERT INTO shippinghassession (shippingid,sessionid) VALUES (:1,:2)", array($shid, $sid));
                                  
            }
            
            $did = -1;
            if ($sid) {
                $dids = $this->db->pq("SELECT dewarid from dewar WHERE shippingid LIKE :1 AND code LIKE :2", array($shid, $visit.'_Dewar1'));
                                     
                if (sizeof($dids) > 0) {
                    $did = $dids[0]['DEWARID'];
                                  
                } else {   
                    $this->db->pq("INSERT INTO dewar (dewarid,code,shippingid,bltimestamp,dewarstatus) VALUES (s_dewar.nextval,:1,:2,CURRENT_TIMESTAMP,'processing') RETURNING dewarid INTO :id", array($visit.'_Dewar1', $shid));
                                     
                    $did = $this->db->id();
                }
            }
                                  
            return $did;
        }

        # ------------------------------------------------------------------------
        # Add a new protein
        function _add_protein() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            if (!$this->has_arg('name')) $this->_error('No protein name specified');
                                 
            $pids = $this->db->pq("SELECT p.proposalid FROM blsession bl INNER JOIN proposal p ON bl.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE :1", array($this->arg('visit')));
                                 
            if (sizeof($pids) > 0) {
                $pid = $pids[0]['PROPOSALID'];
                                 
                $vals = $this->db->pq("INSERT INTO protein (proteinid,proposalid,acronym,bltimestamp) VALUES (s_protein.nextval,:1,:2,CURRENT_TIMESTAMP) RETURNING proteinid INTO :id", array($pid, $this->arg('name')));
                                 
                $this->_output($this->db->id());
            }
        }
                                 
        # ------------------------------------------------------------------------
        # Add a new shipment
        function _add_shipment() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            if (!$this->has_arg('name')) $this->_error('No shipment name specified');
                                 
            $pids = $this->db->pq("SELECT p.proposalid,bl.sessionid FROM blsession bl INNER JOIN proposal p ON bl.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE :1", array($this->arg('visit')));
                                 
            if (sizeof($pids) > 0) {
                $pid = $pids[0]['PROPOSALID'];
                                 
                $vals = $this->db->pq("INSERT INTO shipping (shippingid,proposalid,shippingname,bltimestamp) VALUES (s_shipping.nextval,:1,:2,CURRENT_TIMESTAMP) RETURNING shippingid INTO :id", array($pid, $this->arg('name')));
                                  
                $sid = $this->db->id();
                                  
                $vals = $this->db->pq("INSERT INTO shippinghassession (shippingid,sessionid) VALUES (:1,:2)", array($sid, $pids[0]['SESSIONID']));
                                  
                $this->_output($sid);
            }     
            #$this->_output(1884);
        }
                                 
        # ------------------------------------------------------------------------
        # Add a new dewar
        function _add_dewar() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            if (!$this->has_arg('sid')) $this->_error('No shipping id specified');
            if (!$this->has_arg('name')) $this->_error('No dewar name specified');
                                 
            $pids = $this->db->pq("SELECT p.proposalid FROM blsession bl INNER JOIN proposal p ON bl.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE :1", array($this->arg('visit')));
                                 
            if (sizeof($pids) > 0) {
                $pid = $pids[0]['PROPOSALID'];
                                 
                $vals = $this->db->pq("INSERT INTO dewar (dewarid,code,shippingid,bltimestamp,dewarstatus) VALUES (s_dewar.nextval,:1,:2,CURRENT_TIMESTAMP,'processing') RETURNING dewarid INTO :id", array($this->arg('name'), $this->arg('sid')));
                                 
                $this->_output($this->db->id());
            }
            #$this->_output(2267);
        }
                                 
        # ------------------------------------------------------------------------
        # Assign a container
        function _assign() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            if (!$this->has_arg('cid')) $this->_error('No container id specified');
                                 
            $cs = $this->db->pq("SELECT d.dewarid,bl.beamlinename,c.containerid FROM container c
                                INNER JOIN dewar d ON d.dewarid = c.dewarid
                                INNER JOIN shipping s ON s.shippingid = d.shippingid
                                INNER JOIN blsession bl ON bl.proposalid = s.proposalid
                                INNER JOIN proposal p ON s.proposalid = p.proposalid
                                WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE :1 AND c.containerid=:2", array($this->arg('visit'), $this->arg('cid')));
                               
            if (sizeof($cs) > 0) {
                $c = $cs[0];
                $this->db->pq("UPDATE dewar SET dewarstatus='processing' WHERE dewarid=:1", array($c['DEWARID']));
                               
                $this->db->pq("UPDATE container SET beamlinelocation=:1,samplechangerlocation=:2 WHERE containerid=:3", array($c['BEAMLINENAME'], $this->arg('pos'), $c['CONTAINERID']));        
                $this->_update_history($c['DEWARID'], 'processing');
                                
                $this->_output(1);
            }
                               
            $this->_output(0);
        }
                                 
        # ------------------------------------------------------------------------
        # Unassign a container
        function _unassign() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            if (!$this->has_arg('cid')) $this->_error('No container id specified');
                               
            $cs = $this->db->pq("SELECT d.dewarid,bl.beamlinename,c.containerid FROM container c
                                INNER JOIN dewar d ON d.dewarid = c.dewarid
                                INNER JOIN shipping s ON s.shippingid = d.shippingid
                                INNER JOIN blsession bl ON bl.proposalid = s.proposalid
                                INNER JOIN proposal p ON s.proposalid = p.proposalid
                                WHERE p.proposalcode || p.proposalnumber || '-' || bl.visit_number LIKE :1 AND c.containerid=:2", array($this->arg('visit'), $this->arg('cid')));
                               
            if (sizeof($cs) > 0) {
                $c = $cs[0];
                               
                $this->db->pq("UPDATE container SET samplechangerlocation='' WHERE containerid=:1",array($c['CONTAINERID']));                
                //$this->_update_history($c['DEWARID'], 'unprocessing');
                                
                $this->_output(1);
            }
            $this->_output(0);
        }
                                
                                
        function _update_history($did,$status) {
            # Update history
            $this->db->pq("INSERT INTO ispyb4a_db.dewartransporthistory (dewartransporthistoryid,dewarid,dewarstatus,arrivaldate) VALUES (s_dewartransporthistory.nextval,:1,:2,CURRENT_TIMESTAMP)", array($did, $status));
                                
            # Update dewar status
            if ($status == 'unprocessing') $status = 'at DLS';
            $this->db->pq("UPDATE ispyb4a_db.dewar set dewarstatus=:2 WHERE dewarid=:1", array($did, $status));
        }
                                           
    }

?>