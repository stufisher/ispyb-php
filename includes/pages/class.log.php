<?php

    class Log extends Page {
        
        var $arg_list = array('bl' => '\w\d\d');
        var $dispatch = array('list' => '_index');
        var $def = 'list';
        
        var $root = 'Latest Visits';
        var $root_link = '/';
    
        //var $debug = True;
        
        # Redirects to last visit on each beamline
        function _index() {
            $day = mktime(0,0,0,date('n'),date('j'),date('Y'));
            
            $visit_listl = array();
            $visit_listn = array();
            foreach(array('i02', 'i03', 'i04', 'i04-1', 'i24') as $b) {
                $visit = $this->db->pq('SELECT * FROM (SELECT p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis, TO_CHAR(s.startdate, \'DD-MM-YYYY HH24:MI\') as st, TO_CHAR(s.enddate, \'DD-MM-YYYY HH24:MI\') as en,s.beamlinename as bl FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE s.enddate < TO_DATE(:1,\'dd-mm-yyyy HH24:MI\') AND s.beamlinename LIKE :2 ORDER BY s.enddate DESC) where rownum < 3', array(strtoupper(date('d-m-Y 09:01', $day)), $b));

                $visitn = $this->db->pq('SELECT * FROM (SELECT p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis, TO_CHAR(s.startdate, \'DD-MM-YYYY HH24:MI\') as st, TO_CHAR(s.enddate, \'DD-MM-YYYY HH24:MI\') as en,s.beamlinename as bl FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE s.startdate > TO_DATE(:1,\'dd-mm-yyyy HH24:MI\') AND s.beamlinename LIKE:2 ORDER BY s.startdate) where rownum < 3', array(strtoupper(date('d-m-Y 08:59', $day)), $b));
                
                if (sizeof($visit) > 0) {
                    // Nasty hack to check for overwritten visits
                    if ($visit[0]['ST'] == $visit[1]['ST']) $v = $visit[1];
                    else $v = $visit[0];
                    
                    list($id,$no) = explode('-',$v['VIS']);
                    array_push($visit_listl, '<li><h1>'.$b.'</h1><h2><a href="/vstat/bag/'.$id.'/visit/'.$no.'">'.$v['VIS'].'</a></h2><ul><li>Started: '.$v['ST'].'</li><li>Ended: '.$v['EN'].'</li><li><a href="/dc/visit/'.$v['VIS'].'">Data Collections</a></li></ul></li>');
                }
                
                if (sizeof($visitn) > 0) {
                    if ($visitn[0]['ST'] == $visitn[1]['ST']) $v = $visitn[1];
                    else $v = $visitn[0];
                    
                    list($id,$no) = explode('-',$v['VIS']);
                    array_push($visit_listn, '<li><h1>'.$b.'</h1><h2><a href="/vstat/bag/'.$id.'/visit/'.$no.'">'.$v['VIS'].'</a></h2><ul><li>Starts: '.$v['ST'].'</li><li>Ends: '.$v['EN'].'</li><li><a href="/dc/visit/'.$v['VIS'].'">Data Collections</a></li></ul></li>');
                }
            }
            
            $this->template($this->root);
            $this->t->visit_listl = join('', $visit_listl);
            $this->t->visit_listn = join('', $visit_listn);
            $this->render('log');
        }
        
    
    }

?>