<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('prop' => '\w\w\d+');
        var $dispatch = array('smp' => '_samples',
                              );
        
        var $def = 'smp';
        var $profile = True;
        #var $debug = True;
        
                                                 
    }

?>