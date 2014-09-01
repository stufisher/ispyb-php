<?php
    
    class Stats extends Page {
        
        var $arg_list = array('t' => '\w+',
                              );
        
        var $dispatch = array('online' => '_online',
                              'logon' => '_logon_stats',
                              'bl' => '_beamline',
                              'pl' => '_pl_stats',
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
        

        # Beamline Stats
        function _beamline() {
            $this->template('Beamline Stats', array('Beamline Stats'), array('bl'));
            
            $this->t->js_var('t', $this->has_arg('t') ? $this->arg('t') : '');
            $this->t->render('stats_bl');
        }
        
        # Whos online
        function _online() {
            $this->template('Whos Online', array('Online Users'), array('online'));
            $this->t->render('stats_online');
        }
        
        
        # Autoprocessing Statistics
        function _pl_stats() {
            $this->template('Pipeline Stats', array('Pipeline Stats'), array('ap'));
            
            $this->t->js_var('t', $this->has_arg('t') ? $this->arg('t') : '');
            $this->t->render('stats_pl');
        }
    }
?>