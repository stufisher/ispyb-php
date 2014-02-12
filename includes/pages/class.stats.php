<?php
    
    class Stats extends Page {
        
        var $arg_list = array(
                              );
        
        var $dispatch = array('online' => '_online',
                              'samples' => '_sample_stats',
                              );
        var $def = 'online';
        
        var $root = 'Usage Statistics';
        var $root_link = '/stats';
        var $require_staff = True;
        
        # Whos online
        function _online() {
            $this->template('Whos Online', array('Online Users'), array('/online'));
            $this->t->render('stats_online');
        }
        
        
        # Sample Statistics
        function _sample_stats() {
            
        }
    }
?>