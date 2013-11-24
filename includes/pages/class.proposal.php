<?php

    class Proposal extends Page {
        
        var $arg_list = array('prop' => '\w\w\d+')
        ;
        var $dispatch = array('list' => '_get_proposals',
                              'visits' => '_get_visits',
                              );
        var $def = 'list';
        
        var $root = 'Proposals';
        var $root_link = '/proposal';
        var $sidebar = True;
        
        
        #var $debug = True;
        
        
        function _get_proposals() {
            
            $this->template('Proposals');
            $this->t->render('proposal');
            
        }

        
        function _get_visits() {
            if (!$this->has_arg('prop')) $this->error('No proposal', 'No proposal specified');
            
            $this->template('Visits', array('Visits for '.$this->arg('prop')), array(''));
            $this->t->js_var('prop', $this->arg('prop'));
            $this->t->prop = $this->arg('prop');
            $this->t->render('proposal_visits');
            
        }
    
    }

?>