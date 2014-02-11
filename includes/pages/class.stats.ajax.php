<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array(
        );
        
        var $dispatch = array('online' => '_online_users',
                              'last' => '_last_actions'
                              );
        var $def = 'online';
        var $require_staff = True;
        
        # Whos online list
        function _online_users() {
            $rows = $this->db->pq("SELECT username, comments, TO_CHAR(datetime, 'DD-MM-YYYY HH24:MI:SS') as time FROM ispyb4a_db.adminactivity WHERE datetime > SYSDATE-((1/24/60)*15) ORDER BY datetime DESC");
            
            foreach ($rows as &$r) {
                $r['NAME'] = $this->_get_name($r['USERNAME']);
            }
            
            $this->_output($rows);
        }
        
        
        function _last_actions() {
            $rows = $this->db->pq("SELECT * FROM (SELECT username, comments, TO_CHAR(datetime, 'DD-MM-YYYY HH24:MI:SS') as time FROM ispyb4a_db.adminactivity WHERE comments LIKE 'ISPyB2%' ORDER BY datetime DESC) WHERE rownum < 25");
            
            foreach ($rows as &$r) {
                $r['NAME'] = $this->_get_name($r['USERNAME']);
            }
            
            $this->_output($rows);            
        }
    }

?>