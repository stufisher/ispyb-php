<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('did' => '\d+',
                              'cid' => '\d+',
                              'sid' => '\d+',
                              'lcid' => '\d+',
                              'name' => '\w+',
                              'ty' => '\w+',
                              'value' => '.*',
                              'code' => '([\w-])+',
                              'fcode' => '([\w-])+',
                              'title' => '([\w\s-])+',
                              'trackto' => '\w+',
                              'trackfrom' => '\w+',
                              'array' => '\d',
                              'term' => '\w+',
                              'exp' => '\d+',
                              'container' => '([\w-])+',
                              
                              'name' => '([\w-])+',
                              'comment' => '.*',
                              'spacegroup' => '\w+',
                              'protein' => '\d+',
                              'pos' => '\d+',
                              'barcode' => '\w+',
                              
                              'iDisplayStart' => '\d+',
                              'iDisplayLength' => '\d+',
                              'iSortCol_0' => '\d+',
                              'sSortDir_0' => '\w+',
                              'sSearch' => '\w+',
                              );
        
        var $dispatch = array('shipments' => '_get_shipments',
                              'containers' => '_get_containers',
                              'containersall' => '_get_all_containers',
                              'samples' => '_get_samples',
                              'dewars' => '_get_dewars',
                              'addd' => '_add_dewar',
                              'history' => '_get_history',
                              'pro' => '_get_proteins',
                              'addp' => '_add_protein',
                              'vis' => '_get_visits',
                              
                              'lc' => '_get_contacts',
                              'lcd' => '_get_lc_details',
                              
                              'cache' => '_session_cache',
                              'getcache' => '_get_session_cache',
                              
                              'addcontainer' => '_add_container',
                              'move' => '_move_container',
                              
                              'update' => '_update_shipment',
                              'updated' => '_update_dewar',
                              'updates' => '_update_sample',
                              'updatec' => '_update_container',
                              
                              'send' => '_send_shipment',
                              
                              'terms' => '_get_terms',
                              'termsaccept' => '_accept_terms',
                              );
        
        var $def = 'containers';
        #var $profile = True;
        //var $debug = True;
        #var $explain = True;
        
        // Keep session open so we can cache data
        var $session_close = False;
        
        
        function _get_shipments() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified', 'Please select a proposal first');
            
            $rows = $this->db->pq("SELECT s.safetylevel, count(d.dewarid) as dcount,c.cardname as lcout, c2.cardname as lcret, s.shippingid, s.shippingname, s.shippingstatus,TO_CHAR(s.creationdate, 'DD-MM-YYYY') as created, s.isstorageshipping, s.shippingtype, s.comments FROM ispyb4a_db.proposal p INNER JOIN ispyb4a_db.shipping s ON p.proposalid = s.proposalid LEFT OUTER JOIN ispyb4a_db.labcontact c ON s.sendinglabcontactid = c.labcontactid LEFT OUTER JOIN ispyb4a_db.labcontact c2 ON s.returnlabcontactid = c2.labcontactid LEFT OUTER JOIN ispyb4a_db.dewar d ON d.shippingid = s.shippingid WHERE p.proposalcode || p.proposalnumber = :1 GROUP BY s.safetylevel, c.cardname, c2.cardname, s.shippingid, s.shippingname, s.shippingstatus,TO_CHAR(s.creationdate, 'DD-MM-YYYY'), s.isstorageshipping, s.shippingtype, s.comments", array($this->arg('prop')));
            
            $this->_output($rows);
        }
        
        
        function _get_containers() {
            if (!$this->has_arg('did')) $this->_error('No dewar id specified');
            
            $rows = $this->db->pq('SELECT count(s.blsampleid) as scount, c.containerid, c.code FROM ispyb4a_db.container c LEFT OUTER JOIN ispyb4a_db.blsample s ON s.containerid = c.containerid INNER JOIN ispyb4a_db.dewar d ON d.dewarid = c.dewarid INNER JOIN ispyb4a_db.shipping s ON s.shippingid = d.shippingid WHERE d.dewarid=:1 AND s.proposalid=:2 GROUP BY c.containerid, c.code', array($this->arg('did'), $this->proposalid));
            
            $this->_output($rows);
        }
        
        
        function _get_history() {
            if (!$this->has_arg('did')) $this->_error('No dewar id specified');
            
            $rows = $this->db->pq("SELECT h.dewarid, h.dewarstatus,h.storagelocation,TO_CHAR(h.arrivaldate, 'HH24:MI DD-MM-YYYY') as arrival FROM ispyb4a_db.dewartransporthistory h INNER JOIN ispyb4a_db.dewar d ON d.dewarid = h.dewarid INNER JOIN ispyb4a_db.shipping s ON d.shippingid = s.shippingid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE h.dewarid=:1 AND p.proposalid=:2 ORDER BY h.arrivaldate", array($this->arg('did'), $this->proposalid));
            
            $this->_output($rows);
        }

        
        function _get_dewars() {
            if (!$this->has_arg('prop')) $this->_error('No proposal id specified');
            if (!$this->has_arg('sid')) $this->_error('No shipment id specified');
            
            $dewars = $this->db->pq("SELECT d.facilitycode, count(c.containerid) as ccount, (case when se.visit_number > 0 then (p.proposalcode||p.proposalnumber||'-'||se.visit_number) else '' end) as exp, d.code, d.barcode, d.storagelocation, d.dewarstatus, d.dewarid,  d.trackingnumbertosynchrotron, d.trackingnumberfromsynchrotron FROM ispyb4a_db.dewar d LEFT OUTER JOIN ispyb4a_db.container c ON c.dewarid = d.dewarid INNER JOIN ispyb4a_db.shipping s ON d.shippingid = s.shippingid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid LEFT OUTER JOIN ispyb4a_db.blsession se ON d.firstexperimentid = se.sessionid WHERE s.proposalid=:1 AND d.shippingid=:2 GROUP BY (case when se.visit_number > 0 then (p.proposalcode||p.proposalnumber||'-'||se.visit_number) else '' end), d.code, d.barcode, d.storagelocation, d.dewarstatus, d.dewarid,  d.trackingnumbertosynchrotron, d.trackingnumberfromsynchrotron, d.facilitycode", array($this->proposalid, $this->arg('sid')));
            
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
            $fc = $this->has_arg('fcode') ? $this->arg('fcode') : '';
            
            $exp = $this->has_arg('exp') ? $this->arg('exp') : '';
            
            $this->db->pq("INSERT INTO ispyb4a_db.dewar (dewarid,code,trackingnumbertosynchrotron,trackingnumberfromsynchrotron,shippingid,bltimestamp,dewarstatus,firstexperimentid,facilitycode) VALUES (s_dewar.nextval,:1,:2,:3,:4,CURRENT_TIMESTAMP,'opened',:5,:6) RETURNING dewarid INTO :id", array($this->arg('code'), $to, $from, $this->arg('sid'), $exp, $fc));
            
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
            $visits = $this->db->pq("SELECT p.proposalcode || p.proposalnumber||'-'||b.visit_number as visit, b.visit_number as vn, b.sessionid, b.beamlinename, TO_CHAR(b.startdate, 'DD-MM-YYYY') as st FROM ispyb4a_db.blsession b INNER JOIN ispyb4a_db.proposal p ON p.proposalid = b.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1 AND b.startdate > SYSDATE-60", array($this->arg('prop')));
            
            $vl = array();
            foreach ($visits as $v) {
                $vl[$v['SESSIONID']] = $v['VN'] .' ('.$v['BEAMLINENAME'].': '.$v['ST'].')';
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
        
        
        # Get labcontact shipping and billing details
        function _get_lc_details() {
            if (!$this->has_arg('lcid')) $this->_error('No labcontact specified');
            
            $det = $this->db->pq("SELECT defaultcourriercompany, courieraccount FROM ispyb4a_db.labcontact WHERE labcontactid=:1", array($this->arg('lcid')));
            if (!sizeof($det)) $this->_error('No such labcontact');
            
            $this->_output($det[0]);
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
                           'title' => array('([\w\s-])+', 'shippingname', '', 0),
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
                           'fc' => array('([\w-])+', 'facilitycode', ''),
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
        
        
        # Need to check for sample location number
        function _get_samples() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            if (!$this->has_arg('cid')) $this->_error('No container id specified');
            
            $rows = $this->db->pq("SELECT sp.blsampleid, sp.code, pr.proteinid, sp.comments, sp.name, to_number(sp.location) as location, pr.acronym, cr.spacegroup, count(dc.datacollectionid) as dcount FROM blsample sp INNER JOIN crystal cr ON sp.crystalid = cr.crystalid INNER JOIN protein pr ON cr.proteinid = pr.proteinid INNER JOIN container c ON sp.containerid = c.containerid INNER JOIN dewar d ON d.dewarid = c.dewarid INNER JOIN shipping s ON s.shippingid = d.shippingid LEFT OUTER JOIN ispyb4a_db.datacollection dc ON dc.blsampleid = sp.blsampleid WHERE pr.proposalid=:1 AND c.containerid = :2 GROUP BY sp.blsampleid, pr.proteinid, sp.code, sp.comments, sp.name, sp.location, pr.acronym, cr.spacegroup ORDER BY to_number(sp.location)", array($this->proposalid,$this->arg('cid')));

            $used = array();
            foreach($rows as $r) array_push($used, $r['LOCATION']);
            $tot = array();
            for ($i = 1; $i < 17; $i++) array_push($tot, $i);
            
            foreach (array_diff($tot, $used) as $i => $d) {
                array_splice($rows, $d-1, 0, array(array('BLSAMPLEID' => '', 'COMMENTS' => '', 'NAME' => '', 'LOCATION' => $d, 'ACRONYM' => '', 'SPACEGROUP' => '', 'DCOUNT' => 0, 'CODE' => '')));
            }
            
            $this->_output($rows);
        }
        
        
        # Update sample in dewar
        function _update_sample() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            if (!$this->has_arg('cid')) $this->_error('No container id specified');
            if (!$this->has_arg('name')) $this->_error('No sample name specified');
            if (!$this->has_arg('protein')) $this->_error('No proteinid specified');
            if (!$this->has_arg('pos')) $this->_error('No sample position specified');

            $c = $this->has_arg('comment') ? $this->arg('comment') : '';
            $sg = $this->has_arg('spacegroup') ? $this->arg('spacegroup') : '';
            $b = $this->has_arg('barcode') ? $this->arg('barcode') : '';
            
            # Update sample
            if ($this->has_arg('sid')) {
                $samp = $this->db->pq("SELECT sp.blsampleid, pr.proteinid, cr.crystalid FROM blsample sp  INNER JOIN crystal cr ON sp.crystalid = cr.crystalid INNER JOIN protein pr ON cr.proteinid = pr.proteinid INNER JOIN container c ON sp.containerid = c.containerid INNER JOIN dewar d ON d.dewarid = c.dewarid INNER JOIN shipping s ON s.shippingid = d.shippingid INNER JOIN proposal p ON s.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1 AND c.containerid=:2 AND sp.blsampleid=:3 ORDER BY to_number(sp.location)", array($this->arg('prop'),$this->arg('cid'),$this->arg('sid')));
                
                if (!sizeof($samp)) $this->_error('No such sample');
                else $samp = $samp[0];
                

                $this->db->pq("UPDATE ispyb4a_db.blsample set name=:1,comments=:2,code=:3 WHERE blsampleid=:4", array($this->arg('name'),$c,$b,$this->arg('sid')));
                
                $this->db->pq("UPDATE ispyb4a_db.crystal set spacegroup=:1,proteinid=:2 WHERE crystalid=:3", array($sg, $this->arg('protein'), $samp['CRYSTALID']));
                
                $this->_output(1);
                
            # Add sample
            } else {
                $this->db->pq("INSERT INTO ispyb4a_db.crystal (crystalid,proteinid,spacegroup) VALUES (s_crystal.nextval,:1,:2) RETURNING crystalid INTO :id", array($this->arg('protein'), $sg));
                $crysid = $this->db->id();
                             
                $this->db->pq("INSERT INTO ispyb4a_db.blsample (blsampleid,crystalid,containerid,location,comments,name,code) VALUES (s_blsample.nextval,:1,:2,:3,:4,:5,:6)", array($crysid, $this->arg('cid'), $this->arg('pos'), $c, $this->arg('name'),$b));
                
                $this->_output(1);
            }
            
        }
        
        
        
        # Update shipping status to sent, email for CL3
        function _send_shipment() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            if (!$this->has_arg('sid')) $this->_error('No shipping id specified');
            
            $ship = $this->db->pq("SELECT p.proposalcode || p.proposalnumber as prop, s.safetylevel, s.shippingid, s.deliveryagent_agentname, TO_CHAR(s.deliveryagent_shippingdate, 'DD-MM-YYYY') as shippingdate, TO_CHAR(s.deliveryagent_deliverydate, 'DD-MM-YYYY') as deliverydate, s.shippingname, s.comments, c.cardname as lcout FROM ispyb4a_db.shipping s INNER JOIN ispyb4a_db.proposal p ON s.proposalid = p.proposalid LEFT OUTER JOIN ispyb4a_db.labcontact c ON s.sendinglabcontactid = c.labcontactid WHERE p.proposalcode || p.proposalnumber LIKE :1 AND s.shippingid = :2", array($this->arg('prop'),$this->arg('sid')));
            
            if (!sizeof($ship)) $this->_error('No such shipment');
            $ship = $ship[0];
            
            $this->db->pq("UPDATE ispyb4a_db.shipping SET shippingstatus='sent to DLS' where shippingid=:1", array($ship['SHIPPINGID']));
            
            # Send email if CL3
            if ($ship['SAFETYLEVEL'] == 'Red') {
                $dewars = $this->db->pq("SELECT s.visit_number as vn, s.beamlinename as bl, TO_CHAR(s.startdate, 'DD-MM-YYYY HH24:MI') as startdate FROM ispyb4a_db.dewar d INNER JOIN ispyb4a_db.blsession s ON s.sessionid = d.firstexperimentid WHERE d.shippingid=:1", array($ship['SHIPPINGID']));
                
                $exps = array();
                foreach ($dewars as $d) {
                    array_push($exps, $ship['PROP'].'-'.$d['VN'].' on '.$d['BL'].' starting '.$d['STARTDATE']);
                }
                
                $subject = "RED safety level shipment sent to DLS for ". $ship['PROP'];
                $message = "Shipment Name: ". $ship['SHIPPINGNAME']."\nVisit(s): ".implode(', ', $exps)."\nShipment Sent: ".$ship['SHIPPINGDATE']."\nShipment Expected at Synchrotron: ".$ship['DELIVERYDATE']."\nShipment Courier: ".$ship['DELIVERYAGENT_AGENTNAME']."\nShipment Lab Contact: ".$ship['LCOUT']."\nShipment Comments: ".($ship['COMMENTS'] ? $ship['COMMENTS'] : 'None');
                
                mail('stuart.fisher@diamond.ac.uk, mark.williams@diamond.ac.uk, katherine.mcauley@diamond.ac.uk, goodshandling@diamond.ac.uk', $subject, $message);
            }
            
            $this->_output(1);
            
        }
        
        
        # Show and accept terms to use diamonds shipping account
        function _get_terms() {
            $this->_output(file_get_contents('/dls_sw/dasc/ispyb2/shipping/terms.html'));
        }
        
        function _accept_terms() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            if (!$this->has_arg('title')) $this->_error('No shipment name specified');
            
            # Register acceptance in db
            $this->db->pq("INSERT INTO ispyb4a_db.genericdata (genericdataid,parametervaluedate,parametervaluestring,parametercomments) VALUES (s_genericdata.nextval, SYSDATE, 'terms_accepted', :1)", array($this->arg('prop').','.$this->arg('title').','.phpCAS::getUser()));
            
            $root = '/dls_sw/dasc/ispyb2/shipping';
            $this->_output(array(file_get_contents($root.'/instructions.html'), file_get_contents($root.'/pin.txt'), file_get_contents($root.'/account.txt')));
        }
        
        
        
        function _get_all_containers() {
            #$this->db->set_debug(True);
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            
            $args = array($this->proposalid);
            $where = 'sh.proposalid=:1';
            
            $sta = $this->has_arg('iDisplayStart') ? $this->arg('iDisplayStart') : 0;
            $len = $this->has_arg('iDisplayLength') ? $this->arg('iDisplayLength') : 20;
            
            $tot = $this->db->pq("SELECT count(c.containerid) as tot FROM ispyb4a_db.container c INNER JOIN ispyb4a_db.dewar d ON d.dewarid = c.dewarid INNER JOIN ispyb4a_db.shipping sh ON sh.shippingid = d.shippingid WHERE $where", $args);
            $tot = $tot[0]['TOT'];
            
            if ($this->has_arg('sSearch')) {
                $st = sizeof($args) + 1;
                $where .= " AND lower(c.code) LIKE lower('%'||:".$st."||'%')";
                array_push($args, $this->arg('sSearch'));
            }
            
            $flt = $this->db->pq("SELECT count(c.containerid) as tot FROM ispyb4a_db.container c INNER JOIN ispyb4a_db.dewar d ON d.dewarid = c.dewarid INNER JOIN ispyb4a_db.shipping sh ON sh.shippingid = d.shippingid WHERE $where", $args);
            $flt = $flt[0]['TOT'];
            
            
            $st = sizeof($args) + 1;
            array_push($args, $sta);
            array_push($args, $sta+$len);
            
            $order = 'c.code DESC';
            
            
            if ($this->has_arg('iSortCol_0')) {
                $cols = array('c.code', 'd.code', 'sh.shippingname', '');
                $dir = $this->has_arg('sSortDir_0') ? ($this->arg('sSortDir_0') == 'asc' ? 'ASC' : 'DESC') : 'ASC';
                if ($this->arg('iSortCol_0') < sizeof($cols)) $order = $cols[$this->arg('iSortCol_0')].' '.$dir;
            }
            
            $rows = $this->db->pq("SELECT outer.* FROM (SELECT ROWNUM rn, inner.* FROM (
                                  SELECT c.containerstatus, c.containerid, c.code as name, d.code as dewar, sh.shippingname as shipment, d.dewarid, sh.shippingid, count(s.blsampleid) as samples
                                  FROM ispyb4a_db.container c INNER JOIN ispyb4a_db.dewar d ON d.dewarid = c.dewarid INNER JOIN ispyb4a_db.shipping sh ON sh.shippingid = d.shippingid LEFT OUTER JOIN ispyb4a_db.blsample s ON s.containerid = c.containerid
                                  WHERE $where
                                  GROUP BY c.containerstatus, c.containerid, c.code, d.code, sh.shippingname, d.dewarid, sh.shippingid
                                  ORDER BY $order
                                  ) inner) outer WHERE outer.rn > :$st AND outer.rn <= :".($st+1), $args);
            
            $data = array();
            foreach ($rows as $r) {
                array_push($data, array($r['NAME'], $r['DEWAR'], '<a href="/shipment/sid/'.$r['SHIPPINGID'].'">'.$r['SHIPMENT'].'</a>', $r['SAMPLES'], $r['CONTAINERSTATUS'], '<a class="view" title="View Conainer Details" href="/shipment/cid/'.$r['CONTAINERID'].'">View Container</a>'));
            }
            
            $this->_output(array('iTotalRecords' => $tot,
                                 'iTotalDisplayRecords' => $flt,
                                 'aaData' => $data,
                                 ));   
        
        }
        
        
        
        # Move Container
        function _move_container() {
            if (!$this->has_arg('cid')) $this->_error('No container specified');
            if (!$this->has_arg('did')) $this->_error('No dewar specified');
            
            $chkd = $this->db->pq("SELECT d.dewarid FROM ispyb4a_db.dewar d INNER JOIN ispyb4a_db.shipping s ON s.shippingid = d.shippingid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE d.dewarid=:1 AND p.proposalid=:2", array($this->arg('did'), $this->proposalid));
            $chkc = $this->db->pq("SELECT c.containerid FROM ispyb4a_db.container c INNER JOIN ispyb4a_db.dewar d ON c.dewarid = d.dewarid INNER JOIN ispyb4a_db.shipping s ON s.shippingid = d.shippingid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE c.containerid=:1 AND p.proposalid=:2", array($this->arg('cid'), $this->proposalid));
            
            if (sizeof($chkd) && sizeof($chkc)) {
                $this->db->pq("UPDATE ispyb4a_db.container SET dewarid=:1 WHERE containerid=:2", array($this->arg('did'), $this->arg('cid')));
                $this->_output(1);
            }
            
        }
        
        
        # Update Container title
        function _update_container() {
            if (!$this->has_arg('cid')) $this->_error('No container specified');
            if (!$this->has_arg('value')) $this->_error('No title specified');
            if (!preg_match('/^[\w-]+$/m', $this->arg('value'))) $this->_error('No title specified');
            
            $chkc = $this->db->pq("SELECT c.containerid FROM ispyb4a_db.container c INNER JOIN ispyb4a_db.dewar d ON c.dewarid = d.dewarid INNER JOIN ispyb4a_db.shipping s ON s.shippingid = d.shippingid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE c.containerid=:1 AND p.proposalid=:2", array($this->arg('cid'), $this->proposalid));
            
            if (sizeof($chkc)) {
                $this->db->pq("UPDATE ispyb4a_db.container SET code=:1 WHERE containerid=:2", array($this->arg('value'), $this->arg('cid')));
                print $this->arg('value');
            }
        }
        
        
        # Ajax container registration
        function _add_container() {
            if (!$this->has_arg('container')) $this->_error('No container name specified');
            if (!$this->has_arg('did')) $this->_error('No dewar id specified');
        
            $samples = array();
            if (array_key_exists('p', $_POST)) {
                foreach ($_POST['p'] as $i => $s) {
                    if ($s > -1) {
                        $val = True;
                        foreach (array('p' => '\d+', 'n' => '[\w-]+','c'=> '.*', 'sg' => '\w+', 'b' => '\w+') as $k => $m) {
                            if ($_POST[$k][$i] && !preg_match('/^'.$m.'$/', $_POST[$k][$i])) $val = False;
                        }
                                         
                        if ($val) array_push($samples, array('pos' => $i,'p' => $s, 'sg' => $_POST['sg'][$i], 'n' => $_POST['n'][$i], 'c' => $_POST['c'][$i], 'b' => $_POST['b'][$i]));
                    }
                }
            }

            $this->db->pq("INSERT INTO container (containerid,dewarid,code,bltimestamp,capacity) VALUES (s_container.nextval,:1,:2,CURRENT_TIMESTAMP,16) RETURNING containerid INTO :id", array($this->arg('did'), $this->arg('container')));
                                 
            $cid = $this->db->id();
                             
            foreach ($samples as $s) {
                $this->db->pq("INSERT INTO crystal (crystalid,proteinid,spacegroup) VALUES (s_crystal.nextval,:1,:2) RETURNING crystalid INTO :id", array($s['p'], $s['sg']));
                $crysid = $this->db->id();
                             
                $this->db->pq("INSERT INTO blsample (blsampleid,crystalid,containerid,location,comments,name,code) VALUES (s_blsample.nextval,:1,:2,:3,:4,:5,:6)", array($crysid, $cid, $s['pos']+1, $s['c'], $s['n'], $s['b']));
            }
            
            unset($_SESSION['container']);
            $this->_output($cid);
        }
        
        
        
        # Cache form temporary data to session
        function _session_cache() {
            if (!$this->has_arg('name') || !array_key_exists('data', $_POST)) $this->_error('No key and data specified');
            $caches = array('container', 'shipment');
            if (!in_array($this->arg('name'), $caches)) $this->_error('No such cache');
            
            $_SESSION[$this->arg('name')] = $_POST['data'];
            
            $this->_output(1);
        }
        
        
        function _get_session_cache() {
            if (!$this->has_arg('name')) $this->_error('No key specified');
            
            if (array_key_exists($this->arg('name'), $_SESSION)) {
                if ($_SESSION[$this->arg('name')])
                    $this->_output($_SESSION[$this->arg('name')]);
            }
            
        }
        
        
        
        
    }

?>