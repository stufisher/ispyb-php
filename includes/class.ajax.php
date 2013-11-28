<?php
    
    class AjaxBase extends Page {
        
        function __construct($db, $args) {
            $this->last_profile = microtime(True);
            $this->db = $db;
            $this->db->set_debug($this->debug);
            $this->db->set_explain($this->explain);
            
            $page = $this->def;
            if (sizeof($args) > 0) {
                if (array_key_exists($args[0], $this->dispatch)) {
                    $page = $args[0];
                    array_shift($args);
                }
            }
            
            $this->_parse_args($args);
            $this->_auth();
            $fn = $this->dispatch[$page];
            $this->$fn();
        }

        
        # Output JSON encoded data
        function _output($data) {
            if (!$this->debug) header('Content-type:application/json');
            #$data['profile'] = $this->pro();
            print json_encode($data);
            if ($this->explain) print "\n".$this->db->plan;
            #if ($this->profile) print_r($this->pro());
        }
        
        # Error messages
        function _error($msg) {
            $this->_output($msg);
            exit();
        }
        
    }

?>