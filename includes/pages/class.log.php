<?php

    class Log extends Page {
        
        var $arg_list = array('bl' => '\w\d\d');
        var $dispatch = array('list' => '_index');
        var $def = 'list';
        
        var $root = 'Latest Visits';
        var $root_link = '/';
        
        var $sidebar = True;
    
        #var $debug = True;
        
        # Redirects to last visit on each beamline
        function _index() {
            
            $day = mktime(0,0,0,date('n'),date('j'),date('Y'));
            
            $visit_listl = array();
            $visit_listn = array();
            
            if ($this->staff) {
                foreach(array('i02', 'i03', 'i04', 'i04-1', 'i24') as $b) {
                    $visit = $this->db->pq('SELECT * FROM (SELECT case when sysdate between s.startdate and s.enddate then 1 else 0 end as active, p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis, TO_CHAR(s.startdate, \'DD-MM-YYYY HH24:MI\') as st, TO_CHAR(s.enddate, \'DD-MM-YYYY HH24:MI\') as en,s.beamlinename as bl, s.sessionid FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE s.enddate < TO_DATE(:1,\'dd-mm-yyyy HH24:MI\') AND s.beamlinename LIKE :2 ORDER BY s.enddate DESC) where rownum < 3', array(strtoupper(date('d-m-Y 09:01', $day)), $b));

                    $visitn = $this->db->pq('SELECT * FROM (SELECT case when sysdate between s.startdate and s.enddate then 1 else 0 end as active, p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis, TO_CHAR(s.startdate, \'DD-MM-YYYY HH24:MI\') as st, TO_CHAR(s.enddate, \'DD-MM-YYYY HH24:MI\') as en,s.beamlinename as bl, s.sessionid FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE s.startdate > TO_DATE(:1,\'dd-mm-yyyy HH24:MI\') AND s.beamlinename LIKE:2 ORDER BY s.startdate) where rownum < 3', array(strtoupper(date('d-m-Y 08:59', $day)), $b));
                    
                    if (sizeof($visit) > 0) {
                        // Nasty hack to check for overwritten visits
                        if ($visit[0]['ST'] == $visit[1]['ST']) $v = $visit[1];
                        else $v = $visit[0];
                        
                        array_push($visit_listl, '<li'.($v['ACTIVE'] ? ' class="active"' : '').'><h1>'.$b.$this->lc($v['SESSIONID']).'</h1><h2><a href="/dc/visit/'.$v['VIS'].'">'.$v['VIS'].'</a></h2><ul><li>Start: '.$v['ST'].'</li><li>End: '.$v['EN'].'</li><li><a href="/vstat/visit/'.$v['VIS'].'">Visit Statistics</a></li></ul></li>');
                    }
                    
                    if (sizeof($visitn) > 0) {
                        if ($visitn[0]['ST'] == $visitn[1]['ST']) $v = $visitn[1];
                        else $v = $visitn[0];
                        
                        array_push($visit_listn, '<li'.($v['ACTIVE'] ? ' class="active"' : '').'><h1>'.$b.$this->lc($v['SESSIONID']).'</h1><h2><a href="/dc/visit/'.$v['VIS'].'">'.$v['VIS'].'</a></h2><ul><li>Start: '.$v['ST'].'</li><li>End: '.$v['EN'].'</li><li><a href="/vstat/visit/'.$v['VIS'].'">Visit Statistics</a></li></ul></li>');
                    }
                }
                
            } else {
                $visit = $this->db->pq('SELECT * FROM (SELECT case when sysdate between s.startdate and s.enddate then 1 else 0 end as active, p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis, TO_CHAR(s.startdate, \'DD-MM-YYYY HH24:MI\') as st, TO_CHAR(s.enddate, \'DD-MM-YYYY HH24:MI\') as en,s.beamlinename as bl, s.sessionid
                    FROM ispyb4a_db.blsession s
                    INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid)
                    INNER JOIN investigation@DICAT_RO i ON (lower(i.visit_id) = p.proposalcode || p.proposalnumber || \'-\' || s.visit_number)
                    INNER JOIN investigationuser@DICAT_RO iu on i.id = iu.investigation_id
                    INNER JOIN user_@DICAT_RO u on u.id = iu.user_id
                    WHERE u.name=:1 AND s.enddate < TO_DATE(:2,\'dd-mm-yyyy HH24:MI\') ORDER BY s.enddate DESC) where rownum < 6', array(phpCAS::getUser(), strtoupper(date('d-m-Y 09:01', $day))));

                $visitn = $this->db->pq('SELECT * FROM (SELECT case when sysdate between s.startdate and s.enddate then 1 else 0 end as active, p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis, TO_CHAR(s.startdate, \'DD-MM-YYYY HH24:MI\') as st, TO_CHAR(s.enddate, \'DD-MM-YYYY HH24:MI\') as en,s.beamlinename as bl, s.sessionid
                    FROM ispyb4a_db.blsession s
                    INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid)
                    INNER JOIN investigation@DICAT_RO i ON (lower(i.visit_id) = p.proposalcode || p.proposalnumber || \'-\' || s.visit_number)
                    INNER JOIN investigationuser@DICAT_RO iu on i.id = iu.investigation_id
                    INNER JOIN user_@DICAT_RO u on u.id = iu.user_id
                    WHERE u.name=:1 AND s.startdate > TO_DATE(:2,\'dd-mm-yyyy HH24:MI\') ORDER BY s.enddate DESC) where rownum < 6', array(phpCAS::getUser(), strtoupper(date('d-m-Y 08:59', $day))));
                                                            
                
                if (sizeof($visit) > 0) {
                    foreach ($visit as $v) {
                        if ($visit[0]['ST'] == $visit[1]['ST']) $v = $visit[1];
                        else $v = $visit[0];
                        
                        array_push($visit_listl, '<li'.($v['ACTIVE'] ? ' class="active"' : '').'><h1>'.$v['BL'].$this->lc($v['SESSIONID']).'</h1><h2><a href="/dc/visit/'.$v['VIS'].'">'.$v['VIS'].'</a></h2><ul><li>Start: '.$v['ST'].'</li><li>End: '.$v['EN'].'</li><li><a href="/vstat/visit/'.$v['VIS'].'">Visit Statistics</a></li></ul></li>');
                    }
                }
                
                if (sizeof($visitn) > 0) {
                    foreach ($visitn as $v) {
                        if ($visitn[0]['ST'] == $visitn[1]['ST']) $v = $visitn[1];
                        else $v = $visitn[0];
                        
                        array_push($visit_listn, '<li'.($v['ACTIVE'] ? ' class="active"' : '').'><h1>'.$v['BL'].$this->lc($v['SESSIONID']).'</h1><h2><a href="/dc/visit/'.$v['VIS'].'">'.$v['VIS'].'</a></h2><ul><li>Start: '.$v['ST'].'</li><li>End: '.$v['EN'].'</li><li><a href="/vstat/visit/'.$v['VIS'].'">Visit Statistics</a></li></ul></li>');
                    }
                }                
                
            }
            
            $this->template($this->root);
            $this->t->visit_listl = sizeof($visit_listl) ? join('', $visit_listl) : '<li><h1>No previous visits</h1></li>';
            $this->t->visit_listn = sizeof($visit_listn) ? join('', $visit_listn) : '<li><h1>No scheduled visits</h1></li>';
            $this->render('log');
        }
        
                                                            
        function lc($sid) {
            $lc = $this->lc_lookup($sid);
            if ($lc) return ' - LC: '.$lc->name;
        }
    
    }

?>