<?php

    class Tracking extends Page {
        
        var $arg_list = array('submit' => '\d');
        var $dispatch = array('main' => '_index');
        var $def = 'main';
        
        var $root = 'Track Dewars';
        var $root_link = '/';
    
        #var $debug = True;
        
        # Dewar tracking / shipping from barcode reader
        function _index() {
            $this->template('Dewar Tracking');
            $this->t->minimal();
            
            if ($this->has_arg('submit')) {
                $this->t->submit = 1;
            } else {
                $this->t->submit = 0;

            }
            
            $this->render('tracking');
        }
        
    
    }

?>