<?php

    class Contact extends Page {
        
        var $arg_list = array('visit' => '\w\w\d\d\d\d-\d+');
        var $dispatch = array('view' => '_view_contacts'
                              );
        var $def = 'view';
        
        var $root = 'Lab Contacts';
        var $root_link = '/contacts';
        var $sidebar = True;
        
        
        function _view_contacts() {
            $this->template('Lab Contacts');
            $this->t->render('contact');   
        }
        
    }

?>