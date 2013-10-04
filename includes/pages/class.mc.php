<?php

    class MC extends Page {
        
        var $arg_list = array('visit' => '\w\w\d\d\d\d-\d+', );
        var $dispatch = array('mc' => '_data_collections',
                              'blend' => '_blend');
        var $def = 'mc';
        
        var $root = 'Multi-Crystal Integration';
        var $root_link = '/mc';
        
        
        # Main page for multicrystal integration
        function _data_collections() {
            if (!$this->has_arg('visit')) $this->error('No visit specified');
            
            
            
            $this->template('Multi-Crystal Integration', array($this->arg('visit')), array('/visit/'.$this->arg('visit')));
            
            $this->t->visit = $this->arg('visit');
            $this->t->js_var('visit', $this->arg('visit'));
            
            $this->t->render('mc_list');
            
        }
        
        
        # List of integrated data sets to blend
        function _blend() {
            if (!$this->has_arg('visit')) $this->error('No visit specified');
            
            
            
            $this->template('Multi-Crystal Integration - Blend', array($this->arg('visit'), 'Blend'), array('/visit/'.$this->arg('visit'), ''));
            
            $this->t->visit = $this->arg('visit');
            $this->t->js_var('visit', $this->arg('visit'));
            
            $this->t->render('mc_blend');
        }
        
        
    }

?>