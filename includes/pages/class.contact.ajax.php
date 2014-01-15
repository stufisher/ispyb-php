<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('value' => '.*',
                              'cid' => '\d+',
                              'ty' => '\w+',
                              'iDisplayStart' => '\d+',
                              'iDisplayLength' => '\d+',
                              );
        var $dispatch = array('contacts' => '_get_contacts',
                              'update' => '_update_contact',
                              );
        
        var $def = 'contacts';
        #var $profile = True;
        #var $debug = True;
        
        
        # ------------------------------------------------------------------------
        # Get List of Lab Contacts
        function _get_contacts() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            
            $props = $this->db->pq('SELECT proposalid as id FROM ispyb4a_db.proposal WHERE proposalcode || proposalnumber LIKE :1', array($this->arg('prop')));
            
            if (!sizeof($props)) $this->_error('No such proposal');
            else $p = $props[0]['ID'];
            
            $args = array($p);
            $where = 'WHERE c.proposalid = :1';
            
            $sta = $this->has_arg('iDisplayStart') ? $this->arg('iDisplayStart') : 0;
            $len = $this->has_arg('iDisplayLength') ? $this->arg('iDisplayLength') : 20;
            
            $tot = $this->db->pq("SELECT count(c.labcontactid) as tot FROM ispyb4a_db.labcontact c  $where", $args);
            $tot = $tot[0]['TOT'];

            $st = sizeof($args) + 1;
            array_push($args, $sta);
            array_push($args, $sta+$len);
            
            $order = 'c.labcontactid DESC';
            
            if ($this->has_arg('iSortCol_0')) {
                $cols = array();
                $dir = $this->has_arg('sSortDir_0') ? ($this->arg('sSortDir_0') == 'asc' ? 'ASC' : 'DESC') : 'ASC';
                if ($this->arg('iSortCol_0') < sizeof($cols)) $order = $cols[$this->arg('iSortCol_0')].' '.$dir;
            }
        
            $rows = $this->db->pq("SELECT outer.* FROM (SELECT ROWNUM rn, inner.* FROM (
                                 SELECT c.labcontactid, c.cardname, pe.givenname, pe.familyname, pe.phonenumber, l.name as labname, l.address, l.city, l.country FROM ispyb4a_db.labcontact c INNER JOIN ispyb4a_db.person pe ON c.personid = pe.personid INNER JOIN ispyb4a_db.laboratory l ON l.laboratoryid = pe.laboratoryid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = c.proposalid $where ORDER BY $order
                                  ) inner) outer WHERE outer.rn > :$st AND outer.rn <= :".($st+1), $args);
            
            $data = array();
            foreach ($rows as $r) {
                $addr = array($r['ADDRESS']);
                if ($r['CITY']) array_push($addr, $r['CITY']."\n");
                if ($r['COUNTRY']) array_push($addr, $r['COUNTRY']."\n");
                
                array_push($data, array($r['CARDNAME'], $r['GIVENNAME'].' '.$r['FAMILYNAME'], str_replace("\n", '<br/>',  implode(', ', $addr)), $r['PHONENUMBER'], $r['LABNAME'], '<a class="view" title="View Lab Contact" href="/contact/cid/'.$r['LABCONTACTID'].'">View</a>'));
            }
            
            $this->_output(array('iTotalRecords' => $tot,
                                 'iTotalDisplayRecords' => $tot,
                                 'aaData' => $data,
                           ));
        }
        
        
        # ------------------------------------------------------------------------
        # Update field for lab contact
        function _update_contact() {
            if (!$this->has_arg('cid')) $this->_error('No contact specified');
            if (!$this->has_arg('value')) $this->error('No value specified');
            if (!$this->has_arg('ty')) $this->error('No field specified');
            
            $cont = $this->db->pq("SELECT c.labcontactid, l.laboratoryid, p.personid FROM ispyb4a_db.labcontact c INNER JOIN ispyb4a_db.person p ON p.personid = c.personid INNER JOIN ispyb4a_db.laboratory l ON l.laboratoryid = p.laboratoryid WHERE c.labcontactid=:1", array($this->arg('cid')));
            
            if (!sizeof($cont)) $this->_error('The specified contact doesnt exist');
            else $cont = $cont[0];
            
            $v = $this->arg('value');
            
            # Update labcontact
            $ctypes = array('cardname' => array('([\w\s])+', 'cardname'),
                            'courier' => array('.*', 'defaultcourriercompany'),
                            'courierac' => array('.*', 'courieraccount'),
                            'billing' => array('.*', 'billingreference'),
                            'transport' => array('\d+', 'dewaravgtransportvalue'),
                            'customs' => array('\d+', 'dewaravgcustomsvalue'),
                            );
            if (array_key_exists($this->arg('ty'), $ctypes)) {
                $t = $ctypes[$this->arg('ty')];

                if (preg_match('/^'.$t[0].'$/m', $v)) {
                    $this->db->pq('UPDATE ispyb4a_db.labcontact SET '.$t[1].'=:1 WHERE labcontactid=:2', array($v, $cont['LABCONTACTID']));
                }
            }
            
            
            # Update person
            $ptypes = array('familyname' => array('\w+', 'familyname'),
                            'givenname' => array('.*', 'givenname'),
                            'phone' => array('.*', 'phonenumber'),
                            'email' => array('.*', 'emailaddress'),
                            );
            if (array_key_exists($this->arg('ty'), $ptypes)) {
                $t = $ptypes[$this->arg('ty')];

                if (preg_match('/^'.$t[0].'$/m', $v)) {
                    $this->db->pq('UPDATE ispyb4a_db.person SET '.$t[1].'=:1 WHERE personid=:2', array($v, $cont['PERSONID']));
                }
            }
            
            
            # Update laboratory
            $ltypes = array('labname' => array('.*', 'name'),
                            'address' => array('.*', 'address'),
                            );
            if (array_key_exists($this->arg('ty'), $ltypes)) {
                $t = $ltypes[$this->arg('ty')];

                if (preg_match('/^'.$t[0].'$/m', $v)) {
                    $this->db->pq('UPDATE ispyb4a_db.laboratory SET '.$t[1].'=:1 WHERE laboratoryid=:2', array($v, $cont['LABORATORYID']));
                }
            }
            
            print $v;
        }

    }

?>