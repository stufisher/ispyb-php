<?php
    
    class Stats extends Page {
        
        var $arg_list = array('t' => '\w+',
                              );
        
        var $dispatch = array('online' => '_online',
                              'samples' => '_sample_stats',
                              'logon' => '_logon_stats',
                              );
        var $def = 'logon';
        
        var $root = 'Usage Statistics';
        var $root_link = '/stats';
        var $require_staff = True;
        
        # Logon Stats
        function _logon_stats() {
            $this->template('Logon Stats', array('Logon Stats'), array('logon'));
            
            $this->t->js_var('t', $this->has_arg('t') ? $this->arg('t') : '');
            $this->t->render('stats_logon');
        }
        
        # Whos online
        function _online() {
            $this->template('Whos Online', array('Online Users'), array('online'));
            $this->t->render('stats_online');
        }
        
        
        # Sample Statistics
        function _sample_stats() {
            
        }
    }
?>