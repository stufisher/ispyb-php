<?php
    
    class Status extends Page {
        
        var $arg_list = array('bl' => '\w\d\d(-\d)?');
        var $dispatch = array('list' => '_index', 'moo' => 'moo');
        var $def = 'list';
        
        var $root = 'Beamline Status';
        var $root_link = '/status';
    
        //var $debug = True;
        
        
        // Show Beamline Status
        function _index() {
            if (!$this->has_arg('bl')) $this->error('No beamline', 'No beamline specified');
            
            $this->template('Beamline Status', array($this->arg('bl')), array(''));
            $this->t->bl = $this->arg('bl');
            $this->t->js_var('bl', $this->arg('bl'));
            
            $this->render('status');
        }
        
        
        
        
        function moo() {
            
        }
        
    }
?>
