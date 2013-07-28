<?php

    class Fault extends Page {
        
        var $arg_list = array('bl' => '\w\d\d', 'page' => '\d+');
        var $dispatch = array('list' => '_index', 'new' => '_add_fault');
        var $def = 'list';
        
        var $root = 'Fault Logging';
        var $root_link = '/fault/';
    
        
        # List of faults by beamline / time
        function _index() {
            print '<ul>';
            
            $pvs = array(
            'FE03I-PS-SHTR-01:STA',
            'BL03I-PS-SHTR-01:STA',
            'BL03I-EA-SHTR-01:SHUTTER_STATE',
            
            'BL03I-OP-DCM-01:EURB',
            'SR-DI-DCCT-01:SIGNAL',
            'CS-CS-MSTAT-01:SCROLLM',
            
            'BL03I-DI-QBPM-01:INTEN',
            'BL03I-DI-QBPM-02:INTEN',
            'BL03I-DI-QBPM-03:INTEN',
            'BL03I-EA-QBPM-04:INTEN_C',
            
            'BL03I-EA-ATTN-01:CONV_TRANS_RBV',
            
            'BL03I-EA-I0M-01:INTEN');
            
            foreach ($pvs as $p) {
                print '<li>'.$p.': '.$this->pv($p).'</li>';
            }
            print '</ul>';
            
            $this->template('Fault List');
            $this->render('fault');
        }
        
        
        # Add new fault report
        function _add_fault() {
            $this->template('Add New Fault Report', array('New'), array(''));
            $this->render('fault_new');
        }
    }

?>