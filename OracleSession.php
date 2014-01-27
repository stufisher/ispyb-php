<?php
    include_once('includes/class.db.php');
    include_once('includes/class.template.php');
    include_once('config.php');
    
    class OracleSessionHandler {
        private $db;

        public function __construct(){
            
        }

        public function _open(){
            global $isb;
            
            $this->db = new Oracle($isb['user'], $isb['pass'], $isb['db']);
            if($this->db){
                return true;
            }
            return false;
        }

        public function _close(){
            $this->db->__destruct();
            return true;
        }

        
        public function _read($id){
            $rows = $this->db->pq("SELECT parametercomments as data FROM genericdata WHERE parametervaluestring = :1", array($id));
            
            if(sizeof($rows)){
                return $rows[0]['DATA'];
            } else {
                return '';
            }
        }


        public function _write($id, $data){
            $chk = $this->db->pq("SELECT parametercomments as data FROM genericdata WHERE parametervaluestring = :1", array($id));
            
            if (sizeof($chk)) {
                $this->db->pq("UPDATE genericdata SET parametercomments=:1,parametervaluedate=SYSDATE WHERE parametervaluestring=:2", array($data,$id));
                
                return true;
                
            } else {
                $this->db->pq("INSERT INTO genericdata (genericdataid,parametervaluestring, parametervaluedate, parametercomments) VALUES (s_genericdata.nextval,:1,SYSDATE,:2)", array($id, $data));
                
                return true;
            }
            
            return false;
        }

        
        public function _destroy($id){
            $this->db->pq("DELETE FROM genericdata WHERE parametervaluestring = :1", array($id));
            return true;
        }


        public function _gc($max){
            $old = date('d-m-Y H:i', time() - $max);
            $this->db->query("DELETE FROM genericdata WHERE parametervaluedate < TO_DATE(:old, 'DD-MM-YYYY HH24:MI')", array($old));

            return true;
        }
    
    }
?>