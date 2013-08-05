<?php

    require_once('SqlFormatter.php');
    
    class Oracle {
        var $debug = False;
        var $id;
        
        # Initialise database connection
        function __construct($user, $pass, $db) {
            $this->tz = new DateTimeZone('UTC');

            $this->conn = oci_connect($user, $pass, $db);
            if (!$this->conn) {
                $e = oci_error();
                trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
            }
        
        }
        
        function set_debug($debug) {
            $this->debug = $debug;
        }
        
        # Perform a database query
        function q($query) {
            if ($this->debug) {
                print '<h1 class="debug">Debug: Oracle</h1>';
                print SqlFormatter::format($query);
            }

            $stid = oci_parse($this->conn, $query);
            if (!$stid) {
                $e = oci_error($conn);
                trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
            }
            
            
            // Add a bound variable incase we need it
            if (strpos($query, ':id') !== false) oci_bind_by_name($stid, ":id", $this->id, 8);
            
            // Perform the logic of the query
            $r = oci_execute($stid);
            if (!$r) {
                $e = oci_error($stid);
                trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
            }
            
            $data = array();
            
            if (strpos($query, 'INSERT') === false && strpos($query, 'UPDATE') === false) {
                while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
                    #array_push($data, json_decode(json_encode($row), FALSE));
                    array_push($data, $row);
                }
            }
            
            oci_free_statement($stid);
            
            return sizeof($data) == 0 ? array() : $data;
        }
        
        
        # Return :id variable
        function id() {
            return $this->id;
        }
        
        
        # Perform a database query
        function pq($query, $args=array()) {
            if ($this->debug) {
                print '<h1 class="debug">Debug: Oracle</h1>';
                print SqlFormatter::format($query);
            }

            $stid = oci_parse($this->conn, $query);
            if (!$stid) {
                $e = oci_error($conn);
                trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
            }
            
            
            // Get variables for prepared query
            $arg_count = preg_match_all('/:\d+/', $query);
            //if (sizeof($args) != $arg_count) trigger_error('Number of bound variables does not match number of arguments', E_USER_ERROR);
            
            for ($i = 0; $i < $arg_count; $i++) {
                oci_bind_by_name($stid, ':'.($i+1), $args[$i]);
            }
            
            
            // Add a bound variable incase we need it
            if (strpos($query, ':id') !== false) oci_bind_by_name($stid, ":id", $this->id, 8);
            
            // Perform the logic of the query
            $r = oci_execute($stid);
            if (!$r) {
                $e = oci_error($stid);
                trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
            }
            
            $data = array();
            
            if (strpos($query, 'INSERT') === false && strpos($query, 'UPDATE') === false) {
                while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
                    #array_push($data, json_decode(json_encode($row), FALSE));
                    array_push($data, $row);
                }
            }
            
            oci_free_statement($stid);
            
            return sizeof($data) == 0 ? array() : $data;
        }        
        
        
        # Cleanup when finished
        function __destruct() {
            return;
            oci_close($this->conn);
        }
        
        
        # Convert oracle date to unix timestamp
        function ut($date) {
            $dt = DateTime::createFromFormat("d#M#y H#i#s*A", $date, $this->tz);
            return $dt->getTimestamp();
        }
        
        
    }
    
?>
