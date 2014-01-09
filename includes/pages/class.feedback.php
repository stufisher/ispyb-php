<?php

    class Feedback extends Page {
        
        var $arg_list = array('name' => '.*',
                              'email' => '.*',
                              'feedback' => '.*',
                              'submit' => '\d',
                              );
        var $dispatch = array('feedback' => '_feedback');
        var $def = 'feedback';
        
        var $root = 'Feedback';
        var $root_link = '/feedback';
        var $sidebar = True;
        
        
        # Feedback form
        function _feedback() {
            if ($this->has_arg('submit')) {
                if (!($this->has_arg('name') && $this->has_arg('email') && $this->has_arg('feedback'))) $this->error('Missing Fields', 'One of more fields was missing');

                # Email people
                mail('stuart.fisher@diamond.ac.uk', 'ISpyB-PHP Feedback', "Feedback from the ISpyB-PHP webappliction\n\nName: ".$this->arg('name')."\nEmail: ".$this->arg('email')."\nMessage:\n".$this->arg('feedback')."\n");
                
                $this->msg('Feedback Sent', 'You feedback has been sent to the relevent developers');
                
            } else {
                $this->template('Send Feedback');
                
                $this->t->user = $this->_get_name(phpCAS::getUser());
                $this->t->email = $this->_get_email(phpCAS::getUser());
                
                $this->t->render('feedback');
            }
        }
    
    }

?>