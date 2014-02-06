<?php

    class Cell extends Page {
        
        var $arg_list = array('pdb' => '\w+',
                              'a' => '\d+(.\d+)?',
                              'b' => '\d+(.\d+)?',
                              'c' => '\d+(.\d+)?',
                              'al' => '\d+(.\d+)?',
                              'be' => '\d+(.\d+)?',
                              'ga' => '\d+(.\d+)?',
                              'res' => '\d+(.\d+)?',
                              );
        
        
        var $dispatch = array('cells' => '_cells',
                              'batch' => '_batch',
                              );
        var $def = 'cells';
        
        var $root = 'Nearest Cell';
        var $root_link = '/cell';
        
        var $sidebar = True;
    
        #var $debug = True;
        
        # ------------------------------------------------------------------------
        # Cell finder main page
        function _cells() {
            $this->template('Nearest Cell');
            
            foreach (array('a', 'b', 'c', 'al', 'be', 'ga', 'res', 'pdb')  as $a)
                $this->t->js_var($a, $this->has_arg($a) ? $this->arg($a) : '');
            
            $this->render('cell');
        }
        
    
        
        # ------------------------------------------------------------------------
        # Batch cell finder
        function _batch() {
            if (!$this->staff) $this->error('Access Denied', 'You dont not have access to view this page');
            
            $this->template('Nearest Cell > RCSB vs ISpyB', array('RCSB vs ISpyB'), array(''));
            $this->render('cell_batch');
        }
    }

?>