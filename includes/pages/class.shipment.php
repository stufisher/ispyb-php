<?php

    class Shipment extends Page {
        
        var $arg_list = array('sid' => '\d+', 'did' => '\d+', 'cid' => '\d+', 'submit' => '\d',
                              'container' => '([\w-])+',
                              'lcout' => '\d+',
                              'lcret' => '\d+',
                              'comment' => '.*',
                              'shippingname' => '([\w\s-])+',
                              'type' => '\w+',
                              'shippingdate' => '\d+-\d+-\d+',
                              'deliverydate' => '\d+-\d+-\d+',
                              'couriername' => '\w+',
                              'courierno' => '\w+',
                              'safety' => '\w+',
                              'dewars' => '\d+',
                              'exp' => '\d+',
                              'visit' => '\w+\d+-\d+'
                              );
        
        var $dispatch = array('dispatch' => '_dispatch',
                              'add' => '_add_shipment',
                              'addc' => '_add_container',
                              );
        var $def = 'dispatch';
        
        var $root = 'Shipments';
        var $root_link = '/shipment';
        var $sidebar = True;
        
        #var $debug = True;
        
        var $tracking = array('dhl' => 'http://www.dhl.co.uk/content/gb/en/express/tracking.shtml?AWB=',
                              'fedex' => 'https://www.fedex.com/fedextrack/?tracknumbers=',
                              );
        
        function _dispatch() {
            if ($this->has_arg('sid')) $this->_view_shipment();
            else if ($this->has_arg('cid')) $this->_view_container();
            else $this->_index();
            
        }
        
        function _index() {
            if (!$this->has_arg('prop')) $this->error('No proposal specified', 'Please select a proposal first');
            
            $rows = $this->db->pq("SELECT s.safetylevel, count(d.dewarid) as dcount,c.cardname as lcout, c2.cardname as lcret, s.shippingid, s.shippingname, s.shippingstatus,TO_CHAR(s.creationdate, 'DD-MM-YYYY') as created, s.isstorageshipping, s.shippingtype, s.comments FROM ispyb4a_db.proposal p INNER JOIN ispyb4a_db.shipping s ON p.proposalid = s.proposalid LEFT OUTER JOIN ispyb4a_db.labcontact c ON s.sendinglabcontactid = c.labcontactid LEFT OUTER JOIN ispyb4a_db.labcontact c2 ON s.returnlabcontactid = c2.labcontactid LEFT OUTER JOIN ispyb4a_db.dewar d ON d.shippingid = s.shippingid WHERE p.proposalcode || p.proposalnumber = :1 GROUP BY s.safetylevel, c.cardname, c2.cardname, s.shippingid, s.shippingname, s.shippingstatus,TO_CHAR(s.creationdate, 'DD-MM-YYYY'), s.isstorageshipping, s.shippingtype, s.comments", array($this->arg('prop')));
            
            
            $this->template('Shipments');
            $this->t->rows = $rows;
            $this->t->render('shipment');
        }
        
                                  
        function _view_shipment() {
            if (!$this->has_arg('prop')) $this->error('No proposal specified', 'Please select a proposal first');
            if (!$this->has_arg('sid')) $this->error('No shippingid specified', 'Please specify a shipping id');
            
            $ship = $this->db->pq("SELECT safetylevel, shippingid, deliveryagent_agentname, deliveryagent_agentcode,  TO_CHAR(deliveryagent_shippingdate, 'DD-MM-YYYY') as deliveryagent_shippingdate, TO_CHAR(deliveryagent_deliverydate, 'DD-MM-YYYY') as deliveryagent_deliverydate, shippingname,comments,TO_CHAR(s.creationdate, 'DD-MM-YYYY') as created, c.cardname as lcout, c2.cardname as lcret FROM ispyb4a_db.shipping s INNER JOIN ispyb4a_db.proposal p ON s.proposalid = p.proposalid LEFT OUTER JOIN ispyb4a_db.labcontact c ON s.sendinglabcontactid = c.labcontactid LEFT OUTER JOIN ispyb4a_db.labcontact c2 ON s.returnlabcontactid = c2.labcontactid WHERE p.proposalcode || p.proposalnumber LIKE :1 AND s.shippingid = :2", array($this->arg('prop'),$this->arg('sid')));
            
            if (!sizeof($ship)) $this->error('No such shipment', 'The specified shipment does not exists');
            else $ship = $ship[0];
            
            
            $this->template('View Shipment', array($ship['SHIPPINGNAME']), array(''));
            $this->t->ship = $ship;
            $this->t->js_var('sid', $this->arg('sid'));
            $this->t->js_var('tracking', $this->tracking);
            $this->t->js_var('courier', strtolower($ship['DELIVERYAGENT_AGENTNAME']));
            
            $this->t->render('shipment_view');
        }
                            
                                  
        
        function _add_shipment() {
            if (!$this->has_arg('prop')) $this->error('No proposal specified', 'Please select a proposal first');
            
            if ($this->has_arg('submit')) {
                
                if (!$this->arg('shippingname')) $this->error('No shipment name specified', 'Please specify a shipment name');
                
                $pid = $this->db->pq('SELECT proposalid FROM ispyb4a_db.proposal WHERE proposalcode || proposalnumber LIKE :1', array($this->arg('prop')));
                
                if (!sizeof($pid)) $this->error('No such proposal', 'The specified proposal doesnt exist');
                else $pid = $pid[0]['PROPOSALID'];
                
                $sd = $this->has_arg('shippingdate') ? $this->arg('shippingdate') : '';
                $dd = $this->has_arg('delverydate') ? $this->arg('deliverydate') : '';
                $com = $this->has_arg('comment') ? $this->arg('comment') : '';
                
                $this->db->pq("INSERT INTO ispyb4a_db.shipping (shippingid, proposalid, shippingname, deliveryagent_agentname, deliveryagent_agentcode, deliveryagent_shippingdate, deliveryagent_deliverydate, bltimestamp, creationdate, comments, sendinglabcontactid, returnlabcontactid, shippingstatus, safetylevel) VALUES (s_shipping.nextval,:1,:2,:3,:4,:5,:6,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,:7,:8,:9,'opened',:10) RETURNING shippingid INTO :id", array($pid, $this->arg('shippingname'), $this->arg('couriername'), $this->arg('courierno'), $sd, $dd, $com, $this->arg('lcout'), $this->arg('lcret'), $this->arg('safety')));

                $sid = $this->db->id();
                
                $fc = array();
                if (array_key_exists('fcodes', $_POST)) {
                    foreach ($_POST['fcodes'] as $i => $f) {
                        if (preg_match('/^\w+$/', $f)) {
                            $fc[$i] = $f;
                        } else $fc[$i] =  '';
                    }
                }
                
                if ($this->has_arg('dewars')) {
                    if ($this->arg('dewars') > 0) {
                        $exp = $this->has_arg('exp') ? $this->arg('exp') : '';
                        
                        for ($i = 0; $i < $this->arg('dewars'); $i++) {
                            $n = $fc[$i] ? $fc[$i] : ('Dewar'.($i+1));
                            
                            $this->db->pq("INSERT INTO ispyb4a_db.dewar (dewarid,code,shippingid,bltimestamp,dewarstatus,firstexperimentid,facilitycode) VALUES (s_dewar.nextval,:1,:2,CURRENT_TIMESTAMP,'opened',:3,:4) RETURNING dewarid INTO :id", array($n, $sid, $exp, $fc[$i]));
                            
                            $id = $this->db->id();
                            
                            $vis = '';
                            if ($exp) {
                                $vr = $this->db->pq("SELECT s.beamlinename as bl,s.visit_number as vis FROM ispyb4a_db.blsession s WHERE s.sessionid=:1", array($exp));
                                if (sizeof($vr)) $vis = '-'.$vr[0]['VIS'].'-'.$vr[0]['BL'];
                            }
                            
                            $this->db->pq("UPDATE ispyb4a_db.dewar set barcode=:1 WHERE dewarid=:2", array($this->arg('prop').$vis.'-'.str_pad($id,7,'0',STR_PAD_LEFT), $id));
                        }
                    }
                }
                
                $this->msg('New Shipment Added', 'Your shipment was sucessfully added. Click <a href="/shipment/sid/'.$sid.'">here</a> to see to the shipment or <a href="/shipment/">here</a> to view the list of shipments');
                
            } else {
                $cards = $this->db->pq('SELECT l.cardname,l.labcontactid FROM ispyb4a_db.labcontact l INNER JOIN ispyb4a_db.proposal p ON p.proposalid = l.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1', array($this->arg('prop')));
                
                $lc = '';
                
                foreach ($cards as $c) {
                    $lc .= '<option value="'.$c['LABCONTACTID'].'">'.$c['CARDNAME'].'</option>';
                }
                
                
                $this->template('Add Shipment', array('Add Shipment'), array(''));
                $this->t->cards = $lc;
                $this->t->render('shipment_add');
            }
        }
        
        
        
        
        function _view_container() {
            if (!$this->has_arg('cid')) $this->error('No container specified', 'No containerid specified to view');
            
            $cont = $this->db->pq('SELECT s.shippingid, c.code as name, d.code as dewar, s.shippingname as shipment FROM ispyb4a_db.container c INNER JOIN ispyb4a_db.dewar d ON d.dewarid = c.dewarid INNER JOIN ispyb4a_db.shipping s ON s.shippingid = d.shippingid INNER JOIN ispyb4a_db.proposal p ON s.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1 AND c.containerid=:2', array($this->arg('prop'), $this->arg('cid')));
            
            
            if (!sizeof($cont)) $this->error('No such container', 'A container with that id doesnt exist');
            else $cont = $cont[0];
            
            $this->template('View Container', array($cont['SHIPMENT'], $cont['DEWAR'], $cont['NAME']), array('/sid/'.$cont['SHIPPINGID'], '', ''));
            $this->t->cont = $cont;
            $this->t->js_var('cid', $this->arg('cid'));
            $this->t->js_var('sg_ops', $this->sg_opts());
            
            $this->t->render('container_view');
            
        }
        
        
        function _add_container() {
            if (!$this->has_arg('did') && !$this->has_arg('visit')) $this->error('No dewar or visit specified', 'No dewarid or visit specified to append to');
            
            if ($this->has_arg('visit')) {
                $sids = $this->db->pq("SELECT s.sessionid,p.proposalid FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE p.proposalcode||p.proposalnumber||'-'||s.visit_number LIKE :1 AND p.proposalcode||p.proposalnumber LIKE :2", array($this->arg('visit'), $this->arg('prop')));
                
                if (!sizeof($sids)) $this->error('No such visit', 'The specified visit doesnt exist');
                else {
                    $sid = $sids[0];
                    
                    $this->args['did'] = $this->_default_shipment_dewar($this->arg('visit'), $sid['SESSIONID'], $sid['PROPOSALID']);
                    
                }
            }
            
            if ($this->has_arg('submit')) {
                if (!$this->has_arg('container')) $this->error('No container name specified');
                
                $samples = array();
                if (array_key_exists('p', $_POST)) {
                    foreach ($_POST['p'] as $i => $s) {
                        if ($s > -1) {
                            $val = True;
                            foreach (array('p' => '\d+', 'n' => '\w+','c'=> '[a-zA-Z0-9_]+', 'sg' => '\w+', 'b' => '\w+') as $k => $m) {
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
                
                $this->msg('New Container Added', 'Your container was sucessfully added. Click <a href="/shipment/cid/'.$cid.'">here</a> to see to the container');
                
            } else {
                $dewar = $this->db->pq('SELECT d.code as dewar, s.shippingname as shipment, s.shippingid FROM ispyb4a_db.dewar d INNER JOIN ispyb4a_db.shipping s ON s.shippingid = d.shippingid INNER JOIN ispyb4a_db.proposal p ON s.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1 AND d.dewarid=:2', array($this->arg('prop'), $this->arg('did')));
                
                if (!sizeof($dewar)) $this->error('No such dewar', 'The dewar with the specified id doesnt exist');
                else $dewar = $dewar[0];
                
                $this->template('Add Container', array($dewar['SHIPMENT'], $dewar['DEWAR']), array('/sid/'.$dewar['SHIPPINGID'], ''));
                $this->t->sgs = $this->sg_opts();
                $this->t->dewar = $dewar;
                
                $this->t->render('container_add');
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
    
    }

?>