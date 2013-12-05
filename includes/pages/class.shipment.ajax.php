<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('did' => '\d+',
                              'cid' => '\d+',
                              'sid' => '\d+',
                              'name' => '\w+',
                              'ty' => '\w+',
                              'value' => '.*',
                              'code' => '([\w-])+',
                              'trackto' => '\w+',
                              'trackfrom' => '\w+',
                              'array' => '\d',
                              'term' => '\w+',
                              'exp' => '\d+',
                              
                              'n' => '\w+',
                              'c' => '.*',
                              'sg' => '\w+',
                              'p' => '\d+',
                              'pos' => '\d+',
                              );
        
        var $dispatch = array('containers' => '_get_containers',
                              'samples' => '_get_samples',
                              'dewars' => '_get_dewars',
                              'addd' => '_add_dewar',
                              'history' => '_get_history',
                              'pro' => '_get_proteins',
                              'addp' => '_add_protein',
                              'vis' => '_get_visits',
                              
                              'lc' => '_get_contacts',
                              
                              'update' => '_update_shipment',
                              'updated' => '_update_dewar',
                              'updates' => '_update_sample',
                              );
        
        var $def = 'containers';
        var $profile = True;
        //var $debug = True;
        #var $explain = True;
        
        
        
        function _get_containers() {
            if (!$this->has_arg('did')) $this->_error('No dewar id specified');
            
            $rows = $this->db->pq('SELECT count(s.blsampleid) as scount, c.containerid, c.code FROM ispyb4a_db.container c LEFT OUTER JOIN ispyb4a_db.blsample s ON s.containerid = c.containerid INNER JOIN ispyb4a_db.dewar d ON d.dewarid = c.dewarid INNER JOIN ispyb4a_db.shipping s ON s.shippingid = d.shippingid WHERE d.dewarid=:1 AND s.proposalid=:2 GROUP BY c.containerid, c.code', array($this->arg('did'), $this->proposalid));
            
            $this->_output($rows);
        }
        
        
        function _get_history() {
            if (!$this->has_arg('did')) $this->_error('No dewar id specified');
            
            $rows = $this->db->pq("SELECT h.dewarid, h.dewarstatus,h.storagelocation,TO_CHAR(h.arrivaldate, 'HH24:II DD-MM-YYYY') as arrival FROM ispyb4a_db.dewartransporthistory h INNER JOIN ispyb4a_db.dewar d ON d.dewarid = h.dewarid INNER JOIN ispyb4a_db.shipping s ON d.shippingid = s.shippingid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE h.dewarid=:1 AND p.proposalid=:2 ORDER BY h.arrivaldate", array($this->arg('did'), $this->proposalid));
            
            $this->_output($rows);
        }

        
        function _get_dewars() {
            if (!$this->has_arg('prop')) $this->_error('No proposal id specified');
            if (!$this->has_arg('sid')) $this->_error('No shipment id specified');
            
            $dewars = $this->db->pq("SELECT count(c.containerid) as ccount, (case when se.visit_number > 0 then (p.proposalcode||p.proposalnumber||'-'||se.visit_number) else '' end) as exp, d.code, d.barcode, d.storagelocation, d.dewarstatus, d.dewarid,  d.trackingnumbertosynchrotron, d.trackingnumberfromsynchrotron FROM ispyb4a_db.dewar d LEFT OUTER JOIN ispyb4a_db.container c ON c.dewarid = d.dewarid INNER JOIN ispyb4a_db.shipping s ON d.shippingid = s.shippingid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid LEFT OUTER JOIN ispyb4a_db.blsession se ON d.firstexperimentid = se.sessionid WHERE s.proposalid=:1 AND d.shippingid=:2 GROUP BY (case when se.visit_number > 0 then (p.proposalcode||p.proposalnumber||'-'||se.visit_number) else '' end), d.code, d.barcode, d.storagelocation, d.dewarstatus, d.dewarid,  d.trackingnumbertosynchrotron, d.trackingnumberfromsynchrotron", array($this->proposalid, $this->arg('sid')));
            
            $this->_output($dewars);
            
        }
        
        
        # Add a dewar to a shipment
        function _add_dewar() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            if (!$this->has_arg('sid')) $this->_error('No shipping id specified');
            if (!$this->has_arg('code')) $this->_error('No dewar name specified');
            
            $ship = $this->db->pq("SELECT s.shippingid FROM ispyb4a_db.shipping s INNER JOIN ispyb4a_db.proposal p ON s.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1 AND s.shippingid = :2", array($this->arg('prop'),$this->arg('sid')));
            
            if (!sizeof($ship)) $this->_error('No such shipment');
            
            $to = $this->has_arg('trackto') ? $this->arg('trackto') : '';
            $from = $this->has_arg('trackfrom') ? $this->arg('trackfrom') : '';
            
            $exp = $this->has_arg('exp') ? $this->arg('exp') : '';
            
            $this->db->pq("INSERT INTO ispyb4a_db.dewar (dewarid,code,trackingnumbertosynchrotron,trackingnumberfromsynchrotron,shippingid,bltimestamp,dewarstatus,firstexperimentid) VALUES (s_dewar.nextval,:1,:2,:3,:4,CURRENT_TIMESTAMP,'opened',:5) RETURNING dewarid INTO :id", array($this->arg('code'), $to, $from, $this->arg('sid'), $exp));
            
            $id = $this->db->id();
            
            # Need to generate barcode
            $vis = '';
            if ($exp) {
                $vr = $this->db->pq("SELECT s.beamlinename as bl,s.visit_number as vis FROM ispyb4a_db.blsession s WHERE s.sessionid=:1", array($exp));
                if (sizeof($vr)) $vis = '-'.$vr[0]['VIS'].'-'.$vr[0]['BL'];
            }
            
            $this->db->pq("UPDATE ispyb4a_db.dewar set barcode=:1 WHERE dewarid=:2", array($this->arg('prop').$vis.'-'.str_pad($id,7,'0',STR_PAD_LEFT), $id));
            
            $this->_output($id);
        }
        
        
        # Return Proteins for a visit
        function _get_proteins() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            
            $args = array($this->proposalid);
            $where = '';
            
            if ($this->has_arg('term')) {
                $where = " AND lower(pr.acronym) LIKE lower('%'||:2||'%')";
                array_push($args, $this->arg('term'));
            }
            
            $rows = $this->db->pq("SELECT distinct pr.acronym, max(pr.proteinid) as proteinid FROM protein pr WHERE pr.acronym is not null AND pr.proposalid=:1 $where GROUP BY pr.acronym ORDER BY lower(pr.acronym)", $args);
            
            $proteins = array();
            foreach ($rows as &$r) {
                array_push($proteins, array('value' => $r['ACRONYM'], 'id' => $r['PROTEINID']));
                //$proteins[$r['PROTEINID']] = $r['ACRONYM'];
                $r['PROTEINID'] = intval($r['PROTEINID']);
            }
                                 
            $this->_output($this->has_arg('array') ? $proteins : $rows);
        }
        
        
        # Return available visits
        function _get_visits() {
            $visits = $this->db->pq("SELECT p.proposalcode || p.proposalnumber||'-'||b.visit_number as visit, b.sessionid, b.beamlinename, TO_CHAR(b.startdate, 'DD-MM-YYYY') as st FROM ispyb4a_db.blsession b INNER JOIN ispyb4a_db.proposal p ON p.proposalid = b.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1 AND b.startdate > SYSDATE-60", array($this->arg('prop')));
            
            $vl = array();
            foreach ($visits as $v) {
                $vl[$v['SESSIONID']] = $v['VISIT'] .' ('.$v['BEAMLINENAME'].': '.$v['ST'].')';
            }
            
            $this->_output($vl);
        }
        
        
        
        # Add a new protein
        function _add_protein() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            if (!$this->has_arg('name')) $this->_error('No protein name specified');
                                 
            $pids = $this->db->pq("SELECT p.proposalid FROM blsession bl INNER JOIN proposal p ON bl.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1", array($this->arg('prop')));
                                 
            if (sizeof($pids) > 0) {
                $pid = $pids[0]['PROPOSALID'];
                                 
                $vals = $this->db->pq("INSERT INTO protein (proteinid,proposalid,acronym,bltimestamp) VALUES (s_protein.nextval,:1,:2,CURRENT_TIMESTAMP) RETURNING proteinid INTO :id", array($pid, $this->arg('name')));
                                 
                $this->_output($this->db->id());
            }
        }
        
        
        # Get list of Lab Contacts
        function _get_contacts() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
                                  
            $cards = $this->db->pq('SELECT l.cardname,l.labcontactid FROM ispyb4a_db.labcontact l INNER JOIN ispyb4a_db.proposal p ON p.proposalid = l.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1', array($this->arg('prop')));
            
            $array = array();
            foreach ($cards as $c) {
                $array[$c['LABCONTACTID']] = $c['CARDNAME'];
            }
                                  
            $this->_output($array);
        }
        
        
        # Update shipment
        function _update_shipment() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            if (!$this->has_arg('sid')) $this->_error('No shipping id specified');
            if (!$this->has_arg('value')) $this->_error('No value specified');
            
            $ship = $this->db->pq("SELECT s.shippingid FROM ispyb4a_db.shipping s INNER JOIN ispyb4a_db.proposal p ON s.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1 AND s.shippingid = :2", array($this->arg('prop'),$this->arg('sid')));
            
            if (!sizeof($ship)) $this->_error('No such shipment');
            
            $types = array('lcout' => array('\d+', 'sendinglabcontactid', 'SELECT cardname as value FROM ispyb4a_db.labcontact WHERE labcontactid=:1', 0),
                           'lcret' => array('\d+', 'returnlabcontactid', 'SELECT cardname as value FROM ispyb4a_db.labcontact WHERE labcontactid=:1', 0),
                           'cour' => array('\w+', 'deliveryagent_agentname', '', 0),
                           'courac' => array('\w+', 'deliveryagent_agentcode', '', 0),
                           'sd' => array('\d+-\d+-\d+', 'deliveryagent_shippingdate', '', 1),
                           'dd' => array('\d+-\d+-\d+', 'deliveryagent_deliverydate', '', 1),
                           'com' => array('.*', 'comments', '', 0),
                           'safety' => array('\w+', 'safetylevel', '', 0),
                           'title' => array('([\w-])+', 'shippingname', '', 0),
                           );
            
            if (array_key_exists($this->arg('ty'), $types)) {
                $t = $types[$this->arg('ty')];
                $v = $this->arg('value');
                                
                // Check the value matches the template
                if (preg_match('/^'.$t[0].'$/m', $v)) {
                    $pp = array('','');

                    if ($t[3]) {
                        $pp[0] = "TO_DATE(";
                        $pp[1] = ", 'DD-MM-YYYY HH24:MI')";
                    }
                                  
                    $this->db->pq('UPDATE ispyb4a_db.shipping SET '.$t[1].'='.$pp[0].':1'.$pp[1].' WHERE shippingid=:2', array($v, $this->arg('sid')));
                    
                    
                    $ret = $v;
                    if ($t[2]) {
                        $rets = $this->db->pq($t[2], array($v));
                        if (sizeof($rets)) $ret = $rets[0]['VALUE'];
                    }
                    
                    print $ret;
                }
                
            }
        }
        
        
        # Update dewar in shipment
        function _update_dewar() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            if (!$this->has_arg('did')) $this->_error('No dewar id specified');
            if (!$this->has_arg('value')) $this->_error('No value specified');
            
            $dewar = $this->db->pq("SELECT d.dewarid FROM ispyb4a_db.dewar d INNER JOIN ispyb4a_db.shipping s ON d.shippingid = s.shippingid INNER JOIN ispyb4a_db.proposal p ON s.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1 AND d.dewarid = :2", array($this->arg('prop'),$this->arg('did')));
            
            if (!sizeof($dewar)) $this->_error('No such dewar');
            
            $types = array('code' => array('([\w-])+', 'code', ''),
                           'tt' => array('\w+', 'trackingnumbertosynchrotron', ''),
                           'tf' => array('\w+', 'trackingnumberfromsynchrotron', ''),
                           'exp' => array('\d+', 'firstexperimentid', "SELECT p.proposalcode||p.proposalnumber||'-'||s.visit_number as value FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE s.sessionid=:1"),
                           );
            
            if (array_key_exists($this->arg('ty'), $types)) {
                $t = $types[$this->arg('ty')];
                $v = $this->arg('value');
                                
                // Check the value matches the template
                if (preg_match('/^'.$t[0].'$/m', $v)) {
                    $this->db->pq('UPDATE ispyb4a_db.dewar SET '.$t[1].'=:1 WHERE dewarid=:2', array($v, $this->arg('did')));
                    
                    $ret = $v;
                    if ($t[2]) {
                        $rets = $this->db->pq($t[2], array($v));
                        if (sizeof($rets)) $ret = $rets[0]['VALUE'];
                    }
                    
                    print $ret;
                }
                
            } 
        }
        
        
        function _get_samples() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            if (!$this->has_arg('cid')) $this->_error('No container id specified');
            
            $rows = $this->db->pq("SELECT sp.blsampleid, pr.proteinid, sp.comments, sp.name, to_number(sp.location) as location, pr.acronym, cr.spacegroup, count(dc.datacollectionid) as dcount FROM blsample sp  INNER JOIN crystal cr ON sp.crystalid = cr.crystalid INNER JOIN protein pr ON cr.proteinid = pr.proteinid INNER JOIN container c ON sp.containerid = c.containerid INNER JOIN dewar d ON d.dewarid = c.dewarid INNER JOIN shipping s ON s.shippingid = d.shippingid LEFT OUTER JOIN ispyb4a_db.datacollection dc ON dc.blsampleid = sp.blsampleid WHERE pr.proposalid=:1 AND c.containerid = :2 GROUP BY sp.blsampleid, pr.proteinid, sp.comments, sp.name, sp.location, pr.acronym, cr.spacegroup ORDER BY to_number(sp.location)", array($this->proposalid,$this->arg('cid')));

            $used = array();
            foreach($rows as $r) array_push($used, $r['LOCATION']);
            $tot = array();
            for ($i = 1; $i < 17; $i++) array_push($tot, $i);
            
            foreach (array_diff($tot, $used) as $i => $d) {
                array_splice($rows, $d-1, 0, array(array('BLSAMPLEID' => '', 'COMMENTS' => '', 'NAME' => '', 'LOCATION' => $d, 'ACRONYM' => '', 'SPACEGROUP' => '', 'DCOUNT' => 0)));
            }
            
            $this->_output($rows);
        }
        
        
        # Update sample in dewar
        function _update_sample() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            if (!$this->has_arg('cid')) $this->_error('No container id specified');
            if (!$this->has_arg('n')) $this->_error('No sample name specified');
            if (!$this->has_arg('p')) $this->_error('No proteinid specified');
            if (!$this->has_arg('pos')) $this->_error('No sample position specified');

            $c = $this->has_arg('c') ? $this->arg('c') : '';
            $sg = $this->has_arg('sg') ? $this->arg('sg') : '';
            
            # Update sample
            if ($this->has_arg('sid')) {
                $samp = $this->db->pq("SELECT sp.blsampleid, pr.proteinid, cr.crystalid FROM blsample sp  INNER JOIN crystal cr ON sp.crystalid = cr.crystalid INNER JOIN protein pr ON cr.proteinid = pr.proteinid INNER JOIN container c ON sp.containerid = c.containerid INNER JOIN dewar d ON d.dewarid = c.dewarid INNER JOIN shipping s ON s.shippingid = d.shippingid INNER JOIN proposal p ON s.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1 AND c.containerid=:2 AND sp.blsampleid=:3 ORDER BY to_number(sp.location)", array($this->arg('prop'),$this->arg('cid'),$this->arg('sid')));
                
                if (!sizeof($samp)) $this->_error('No such sample');
                else $samp = $samp[0];
                

                $this->db->pq("UPDATE ispyb4a_db.blsample set name=:1,comments=:2 WHERE blsampleid=:3", array($this->arg('n'),$c,$this->arg('sid')));
                
                $this->db->pq("UPDATE ispyb4a_db.crystal set spacegroup=:1,proteinid=:2 WHERE crystalid=:3", array($sg, $this->arg('p'), $samp['CRYSTALID']));
                
                $this->_output(1);
                
            # Add sample
            } else {
                $this->db->pq("INSERT INTO ispyb4a_db.crystal (crystalid,proteinid,spacegroup) VALUES (s_crystal.nextval,:1,:2) RETURNING crystalid INTO :id", array($this->arg('p'), $sg));
                $crysid = $this->db->id();
                             
                $this->db->pq("INSERT INTO ispyb4a_db.blsample (blsampleid,crystalid,containerid,location,comments,name) VALUES (s_blsample.nextval,:1,:2,:3,:4,:5)", array($crysid, $this->arg('cid'), $this->arg('pos'), $c, $this->arg('n')));
                
                $this->_output(1);
            }
            
        }
        
        
        
    }

?>