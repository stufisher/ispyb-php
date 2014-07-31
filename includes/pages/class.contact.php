<?php

    class Contact extends Page {
        
        var $arg_list = array('cid' => '\d+',
                              'submit' => '\d',
                              'cardname' => '([\w\s-])+',
                              'familyname' => '([\w-])+',
                              'givenname' => '([\w-])+',
                              'phone' => '.*',
                              'email' => '.*',
                              'labname' => '([\w\s-])+',
                              'address' => '([\w\s-\n])+',
                              'courier' => '([\w\s-])+',
                              'courieraccount' => '([\w-])+',
                              'billingreference' => '([\w\s-])+',
                              'transportvalue' => '\d+',
                              'customsvalue' => '\d+',
                              'iframe' => '\d',
                              );
        var $dispatch = array('list' => '_dispatch',
                              'add' => '_add_contact',
                              );
        var $def = 'list';
        
        var $root = 'Home Lab Contacts';
        var $root_link = '/contact';
        var $sidebar = True;
        
        
        # Dispatch based on passed arguements
        function _dispatch() {
            if ($this->has_arg('cid')) $this->_view_contact();
            else $this->_list_contacts();
        }
        
        
        # ------------------------------------------------------------------------
        # List of lab contacts
        function _list_contacts() {
            if (!$this->has_arg('prop')) $this->error('No proposal selected', 'No proposal selected. Select a proposal before viewing this page');
            
            $this->template('Lab Contacts');
            $this->t->render('contact');   
        }
        
        
        # ------------------------------------------------------------------------
        # View a lab contact
        function _view_contact() {
            if (!$this->has_arg('prop')) $this->error('No proposal selected', 'No proposal selected. Select a proposal before viewing this page');
            if (!$this->has_arg('cid')) $this->error('No contact specified', 'No contact id was specified');
            
            $cont = $this->db->pq("SELECT c.labcontactid, c.cardname, c.courieraccount,  c.billingreference, c.defaultcourriercompany, c.dewaravgcustomsvalue, c.dewaravgtransportvalue, pe.givenname, pe.familyname, pe.phonenumber, pe.emailaddress, l.name as labname, l.address, l.city, l.country FROM ispyb4a_db.labcontact c INNER JOIN ispyb4a_db.person pe ON c.personid = pe.personid INNER JOIN ispyb4a_db.laboratory l ON l.laboratoryid = pe.laboratoryid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = c.proposalid WHERE p.proposalid=:1 AND c.labcontactid=:2", array($this->proposalid, $this->arg('cid')));
            
            
            if (!sizeof($cont)) $this->error('No such contact', 'The specified conact doesnt exist');
            else $cont = $cont[0];
            
            $this->template('View Lab Contact', array('View Lab Contact'), array(''));
            $this->t->cont = $cont;
            $this->t->js_var('cid', $this->arg('cid'));
            $this->t->render('contact_view');  
        }
        
        
        # ------------------------------------------------------------------------
        # Add a new lab contact
        function _add_contact() {
            if (!$this->has_arg('prop')) $this->error('No proposal selected', 'No proposal selected. Select a proposal before viewing this page');
            
            if ($this->has_arg('submit')) {    
                $valid = True;
                foreach (array('cardname', 'familyname', 'givenname', 'labname', 'address') as $k) {
                    if (!$this->has_arg($k)) $valid = False;
                }
                
                if (!$valid) $this->error('Missing Fields', 'Required fields were missing from the submitted input');
                
                $this->db->pq("INSERT INTO ispyb4a_db.laboratory (laboratoryid,name,address) VALUES (s_laboratory.nextval, :1, :2) RETURNING laboratoryid INTO :id", array($this->arg('labname'), $this->arg('address')));
                $lid = $this->db->id();
                
                $email = $this->has_arg('email') ? $this->arg('email') : '';
                $phone = $this->has_arg('phone') ? $this->arg('phone') : '';
                
                $this->db->pq("INSERT INTO ispyb4a_db.person (personid, givenname, familyname, emailaddress, phonenumber, laboratoryid) VALUES (s_person.nextval, :1, :2, :3, :4, :5) RETURNING personid INTO :id", array($this->arg('givenname'), $this->arg('familyname'), $email, $phone, $lid));
                
                $pid = $this->db->id();
                
                $c = $this->has_arg('courier') ? $this->arg('courier') : '';
                $ca = $this->has_arg('courieraccount') ? $this->arg('courieraccount') : '';
                $br = $this->has_arg('billingreference') ? $this->arg('billingreference') : '';
                $cv = $this->has_arg('customsvalue') ? $this->arg('customsvalue') : 0;
                $tv = $this->has_arg('transportvalue') ? $this->arg('transportvalue') : 0;
                
                $this->db->pq("INSERT INTO ispyb4a_db.labcontact (labcontactid, cardname, defaultcourriercompany, courieraccount, billingreference, dewaravgcustomsvalue, dewaravgtransportvalue, proposalid, personid) VALUES (s_labcontact.nextval, :1, :2, :3, :4, :5, :6, :7, :8) RETURNING labcontactid INTO :id", array($this->arg('cardname'), $c, $ca, $br, $cv, $tv, $this->proposalid, $pid));
                
                $this->msg('New Home Lab Contact Added', 'Your lab contact was sucessfully added. Click <a href="/contact/cid/'.$this->db->id().'">here</a> to see to the contact details');
                
            } else {
                $this->template('Add Lab Contacts', array('Add Lab Contact'), array(''), !$this->has_arg('iframe'));
                $this->t->render('contact_add');
            }
        }
        
    }

?>