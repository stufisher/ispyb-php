<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('t' => '\w+',
        );
        
        var $dispatch = array('online' => '_online_users',
                              'last' => '_last_actions',
                              'logon' => '_logons',
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
        
        
        function _logons() {
            $days = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
            
            $ty = $this->has_arg('t') ? $this->arg('t') : 'hour';
            
            $types = array('hour' => array('hour', 'hour'), 'wd' => array('day', 'weekday'), 'week' => array('week', 'week'), 'md' => array('day', 'monthday'));
            
            $k = array_key_exists($ty, $types) ? $ty : 'hour';
            $t = $types[$k];
            
            $rows = $this->db->pq("SELECT $t[0] as k, \"Total logins\" as t, \"Distinct logins\" as d FROM ispyb4a_db.v_logonby${t[1]}2");
            
            $out = array(array('label' => 'Total (ISPyB)', 'color' => 'red', 'data' => array()), array('label' => 'Unique (ISPyB)', 'color' => 'orange', 'data' => array()),
                         array('label' => 'Total (ISPyB2)', 'color' => 'blue', 'data' => array()), array('label' => 'Unique (ISPyB2)', 'color' => 'cyan', 'data' => array())
                         );
            
            foreach ($rows as $r) {
                if ($k == 'wd') $r['K'] = array_search($r['K'], $days);
                
                array_push($out[2]['data'], array(intval($r['K']), intval($r['T'])));
                array_push($out[3]['data'], array(intval($r['K']), intval($r['D'])));
            }
            
            $rows = $this->db->pq("SELECT $t[0] as k, \"Total logins\" as t, \"Distinct logins\" as d FROM ispyb4a_db.v_logonby${t[1]}");
            foreach ($rows as $r) {
                if ($k == 'wd') $r['K'] = array_search($r['K'], $days);
                
                array_push($out[0]['data'], array(intval($r['K']), intval($r['T'])));
                array_push($out[1]['data'], array(intval($r['K']), intval($r['D'])));
            }
            
            $this->_output($out);
            
        }
        
    }

?>