<?php
    
    class AjaxBase extends Page {
        
        function __construct($db, $args, $type) {
            $this->ptype = $type;
            
            $this->last_profile = microtime(True);
            $this->db = $db;
            $this->db->set_debug($this->debug);
            $this->db->set_explain($this->explain);
            $this->db->set_stats($this->stats);
            
            $page = $this->def;
            if (sizeof($args) > 0) {
                if (array_key_exists($args[0], $this->dispatch)) {
                    $page = $args[0];
                    array_shift($args);
                }
            }
            
            $this->_parse_args($args);
            #if (!$this->_auth()) return;
            $this->ptype->set_args($this->args);
            if (!$this->ptype->auth($this->require_staff)) return;
            $this->staff = $this->ptype->is_staff();
            $this->proposalid = $this->ptype->pid();
            
            session_write_close();
            
            $fn = $this->dispatch[$page];
            $this->$fn();
        }

        
        # Output JSON encoded data
        function _output($data) {
            if (!$this->debug) header('Content-type:application/json');
            if ($this->profile) $data['profile'] = $this->pro();
            print json_encode($data);
            if ($this->explain) print "\n".$this->db->plan;
            if ($this->db->stats) print "\n".$this->db->stat;

        }
        
        # Error messages as json object, should probably return a different
        # http code as well
        function _error($msg) {
            $this->_output($msg);
            exit();
        }
        
    }

?>