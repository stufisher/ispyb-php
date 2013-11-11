<?php

    class Cell extends Page {
        
        var $arg_list = array('pdb' => '\w+');
        var $dispatch = array('cells' => '_cells',
                              'batch' => '_batch',
                              );
        var $def = 'cells';
        
        var $root = 'Nearest Cell';
        var $root_link = '/cell';
    
        #var $debug = True;
        
        # ------------------------------------------------------------------------
        # Cell finder main page
        function _cells() {
            $this->template('Nearest Cell');
            
            $this->t->js_var('pdb', $this->has_arg('pdb') ? $this->arg('pdb') : '');
            
            $this->render('cell');
        }
        
    
        
        # ------------------------------------------------------------------------
        # Batch cell finder
        function _batch() {
            $this->template('Nearest Cell > Batch Processor', array('Batch Processor'), array(''));
            $this->render('cell_batch');
        }
    }

?>