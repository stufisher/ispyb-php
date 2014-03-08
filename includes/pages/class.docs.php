<?php

    class Docs extends Page {
        
        var $arg_list = array('tut' => '\d');
        var $dispatch = array('idx' => '_index');
        var $def = 'idx';
        
        var $root = 'Tutorials';
        var $root_link = '/';
        
        var $sidebar = True;
    
        function _index() {
            $pages = array('proposal', 'contact', 'shipment', 'samples', 'prepare', 'data');
            $page_name = array('Select Proposal', 'Register Contact', 'Create Shipment', 'Register Samples', 'Prepare Experiment', 'View Your Data');
            
            $t = $this->has_arg('tut') ? ($this->arg('tut') - 1) : 0;
            if ($t < sizeof($pages)) {
                
                $this->template('Tutorials');
                $this->t->content = file_get_contents('doc/'.$pages[$t].'/index.html');
                $this->t->pn = $page_name;
                $this->t->tut = $t;
                $this->t->render('docs');
                
            } else $this->_error('No such tutorial', 'The specified tutorial doesnt exist');
            
        }
    
    }

?>