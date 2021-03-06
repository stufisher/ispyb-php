<?php
    
    class Ajax extends AjaxBase {
        
        var $arg_list = array('iDisplayStart' => '\d+',
                              'iDisplayLength' => '\d+',
                              'iSortCol_0' => '\d+',
                              'sSortDir_0' => '\w+',
                              'sSearch' => '[\w\s-]+',
                              'prop' => '\w+\d+',
                              'array' => '\d',
                              'term' => '\w+',
                              'value' => '.*',
                              'visit' => '\w+\d+-\d+',
                              'all' => '\d',
                              'year' => '\d\d\d\d',
                              'month' => '\d+',
                              'bl' => '\w\d\d(-\d)?',
                              'ty' => '\w+',
                              'next' => '\d',
                              'prev' => '\d',
                              'proposal' => '\w+\d+',
                               );
        
        var $dispatch = array('proposals' => '_get_proposals',
                              'p' => '_proposals',
                              'visits' => '_get_visits',
                              'set' => '_set_proposal',
                              'comment' => '_set_comment',
                              'user' => '_get_user',
                              'login' => '_login',
                              'users' => '_get_users',
                              );
        
        var $def = 'proposals';
        #var $profile = True;
        //var $debug = True;
        #var $explain = True;
        
        
        
        function _get_user() {
            $this->_output(array('user' => phpCAS::getUser(), 'is_staff' => $this->staff, 'visits' => $this->visits));
        }
        
        function _login() {
        }
        
        
        # ------------------------------------------------------------------------
        # List proposals for current user
        function _get_proposals() {
            global $prop_types, $bl_types;
            
            $args = array();
            $where = "WHERE p.proposalcode in ('cm', 'mx', 'nt', 'nr', 'sw', 'in', 'mt', 'ee')";
            
            $sta = $this->has_arg('iDisplayStart') ? $this->arg('iDisplayStart') : 0;
            $len = $this->has_arg('iDisplayLength') ? $this->arg('iDisplayLength') : 20;
            
            if ($this->has_arg('proposal')) {
                $where .= " AND p.proposalcode||p.proposalnumber LIKE :".(sizeof($args)+1);
                array_push($args, $this->arg('proposal'));
            }
            
            if (!$this->staff) {
                $where = " INNER JOIN investigation@DICAT_RO i ON lower(i.visit_id) LIKE p.proposalcode || p.proposalnumber || '-' || s.visit_number INNER JOIN investigationuser@DICAT_RO iu on i.id = iu.investigation_id inner join user_@DICAT_RO u on u.id = iu.user_id ".$where." AND u.name=:".(sizeof($args)+1);
                array_push($args, phpCAS::getUser());
                
                #$where .= " AND s.sessionid in ('".implode("','", $this->sessionids)."')";
            }
            
            $tot = $this->db->pq("SELECT count(distinct p.proposalid) as tot FROM ispyb4a_db.proposal p INNER JOIN ispyb4a_db.blsession s ON p.proposalid = s.proposalid $where");
            $tot = $tot[0]['TOT'];
            
            if ($this->has_arg('sSearch')) {
                $st = sizeof($args) + 1;
                $where .= " AND (lower(p.title) LIKE lower('%'||:".$st."||'%') OR lower(p.proposalcode || p.proposalnumber) LIKE lower('%'||:".($st+1)."||'%'))";
                for ($i = 0; $i < 2; $i++) array_push($args, $this->arg('sSearch'));
            }
            
            
            $flt = $this->db->pq("SELECT count(distinct p.proposalid) as tot FROM ispyb4a_db.proposal p INNER JOIN ispyb4a_db.blsession s ON p.proposalid = s.proposalid $where", $args);
            $flt = $flt[0]['TOT'];
            
            $st = sizeof($args) + 1;
            array_push($args, $sta);
            array_push($args, $sta+$len);
            
            $order = 'p.bltimestamp DESC';
            
            if ($this->has_arg('iSortCol_0')) {
                $cols = array('p.bltimestamp', 'p.proposalcode', 'p.proposalnumber', 'vcount', 'p.title');
                $dir = $this->has_arg('sSortDir_0') ? ($this->arg('sSortDir_0') == 'asc' ? 'ASC' : 'DESC') : 'ASC';
                if ($this->arg('iSortCol_0') < sizeof($cols)) $order = $cols[$this->arg('iSortCol_0')].' '.$dir;
            }
            
            $rows = $this->db->pq("SELECT outer.* FROM (SELECT ROWNUM rn, inner.* FROM (SELECT p.title, TO_CHAR(p.bltimestamp, 'DD-MM-YYYY') as st, p.proposalcode, p.proposalnumber, count(s.sessionid) as vcount, p.proposalid FROM ispyb4a_db.proposal p INNER JOIN ispyb4a_db.blsession s ON p.proposalid = s.proposalid $where GROUP BY TO_CHAR(p.bltimestamp, 'DD-MM-YYYY'), p.bltimestamp, p.proposalcode, p.proposalnumber, p.title, p.proposalid ORDER BY $order) inner) outer WHERE outer.rn > :$st AND outer.rn <= :".($st+1), $args);
            
            $ty = '';
            $data = array();
            foreach ($rows as &$r) {
                array_push($data, array($r['ST'], $r['PROPOSALCODE'], $r['PROPOSALNUMBER'], $r['VCOUNT'], $r['TITLE']));
                
                // See if proposal code matches list in config
                $found = False;
                foreach ($prop_types as $pty) {
                    if ($r['PROPOSALCODE'] == $pty) {
                        $ty = $pty;
                        $found = True;
                    }
                }
                
                // Proposal code didnt match, work out what beamline the visits are on
                if (!$found) {
                    $bls = $this->db->pq("SELECT s.beamlinename FROM blsession s WHERE s.proposalid=:1", array($r['PROPOSALID']));
                    
                    if (sizeof($bls)) {
                        foreach ($bls as $bl) {
                            $b = $bl['BEAMLINENAME'];
                            foreach ($bl_types as $tty => $bls) {
                                if (in_array($b, $bls)) {
                                    $ty = $tty;
                                    break;
                                }
                            }
                        }
                    }
                }
                
                if (!$ty) $ty = 'gen';
                $r['TYPE'] = $ty;
                
            }
            
            if ($this->has_arg('proposal')) {
                if (sizeof($rows)) $this->_output($rows[0]);
                else $this->_error('No such proposal');
            } else $this->_output(array('iTotalRecords' => $tot,
                                 'iTotalDisplayRecords' => $flt,
                                 'aaData' => $this->has_arg('array') ? $rows : $data,
                           ));
        }
        
    
        function _proposals() {
            
            $where = "WHERE (p.proposalcode LIKE 'mx' OR p.proposalcode LIKE 'cm' OR p.proposalcode LIKE 'nt' OR p.proposalcode LIKE 'sw' OR p.proposalcode LIKE 'in')";
            $args = array();
            
            if ($this->has_arg('term')) {
                $where .= " AND p.proposalcode || p.proposalnumber LIKE '%'||:".(sizeof($args)+1)."||'%' ";
                array_push($args, $this->arg('term'));
            }
            
            if (!$this->staff) {
                $where = " INNER JOIN investigation@DICAT_RO i ON lower(i.visit_id) LIKE p.proposalcode || p.proposalnumber || '-' || s.visit_number INNER JOIN investigationuser@DICAT_RO iu on i.id = iu.investigation_id inner join user_@DICAT_RO u on u.id = iu.user_id ".$where." AND u.name=:".(sizeof($args)+1);
                array_push($args, phpCAS::getUser());
            }
            
            $rows = $this->db->pq("SELECT count(s.sessionid),p.proposalid,p.proposalcode || p.proposalnumber as prop FROM ispyb4a_db.proposal p INNER JOIN ispyb4a_db.blsession s ON s.proposalid = p.proposalid $where GROUP BY p.proposalid,p.proposalcode || p.proposalnumber,p.proposalnumber ORDER BY p.proposalnumber", $args);
            
            $arr = array();
            foreach ($rows as $r) array_push($arr, $r['PROP']);//$arr[$r['PROP']] = $r['PROP'];
            $this->_output($arr);
        }
        
    
        # ------------------------------------------------------------------------
        # Get visits for a proposal
        function _get_visits() {
            global $bl_types;
            global $short_visit;
            
            if (!$this->staff && !$this->has_arg('prop')) $this->_error('No proposal specified');
            
            $props = $this->db->pq('SELECT proposalid as id FROM ispyb4a_db.proposal WHERE proposalcode || proposalnumber LIKE :1', array($this->arg('prop')));
            
            if (!sizeof($props)) $this->_error('No such proposal');
            else $p = $props[0]['ID'];
            
            if ($this->has_arg('all') && $this->staff) {
                $args = array();
                $where = 'WHERE 1=1';
            } else {
                $args = array($p);
                $where = 'WHERE s.proposalid = :1';
            }
            
            if ($this->has_arg('year')) {
                $where .= " AND TO_CHAR(s.startdate, 'YYYY') = :".(sizeof($args)+1);
                array_push($args, $this->arg('year'));
            }
                
            if ($this->has_arg('month')) {
                $where .= " AND TO_CHAR(s.startdate, 'MM') = :".(sizeof($args)+1);
                array_push($args, $this->arg('month'));
            }
            
            if ($this->has_arg('prev')) {
                $where .= " AND s.enddate < SYSDATE";
            }

            if ($this->has_arg('next')) {
                $where .= " AND s.enddate > SYSDATE AND TO_CHAR(s.startdate,'YYYY') > 2009";
                $this->args['sSortDir_0'] = 'asc';
                $this->args['iSortCol_0'] = 0;
            }
            
            if ($this->has_arg('bl')) {
                $where .= " AND s.beamlinename = :".(sizeof($args)+1);
                array_push($args, $this->arg('bl'));
            }
            
            if ($this->has_arg('ty')) {
                if ($this->arg('ty') == 'mx') {
                    $where .= " AND s.beamlinename IN ('i02', 'i03', 'i04', 'i04-1', 'i24', 'i23', 'b21')";
                }
            }
            
            
            if ($this->has_arg('visit')) {
                $where .= " AND p.proposalcode||p.proposalnumber||'-'||s.visit_number LIKE :".(sizeof($args)+1);
                array_push($args, $this->arg('visit'));
            }
            
            $sta = $this->has_arg('iDisplayStart') ? $this->arg('iDisplayStart') : 0;
            $len = $this->has_arg('iDisplayLength') ? $this->arg('iDisplayLength') : 20;
            
            if (!$this->staff) {
                $where = " INNER JOIN investigation@DICAT_RO i ON lower(i.visit_id) LIKE p.proposalcode || p.proposalnumber || '-' || s.visit_number INNER JOIN investigationuser@DICAT_RO iu on i.id = iu.investigation_id inner join user_@DICAT_RO u on u.id = iu.user_id ".$where." AND u.name=:".(sizeof($args)+1);
                array_push($args, phpCAS::getUser());
            }
            
            $tot = $this->db->pq("SELECT count(s.sessionid) as tot FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid $where", $args);
            $tot = $tot[0]['TOT'];

            $st = sizeof($args) + 1;
            array_push($args, $sta);
            array_push($args, $sta+$len);
            
            $order = 's.startdate DESC';
            
            if ($this->has_arg('iSortCol_0')) {
                $cols = array('s.startdate', 's.enddate', 's.visit_number', 's.beamlinename', 's.beamlineoperator', 's.comments');
                $dir = $this->has_arg('sSortDir_0') ? ($this->arg('sSortDir_0') == 'asc' ? 'ASC' : 'DESC') : 'ASC';
                if ($this->arg('iSortCol_0') < sizeof($cols)) $order = $cols[$this->arg('iSortCol_0')].' '.$dir;
            }
            
            
            $rows = $this->db->pq("SELECT outer.* FROM (SELECT ROWNUM rn, inner.* FROM (SELECT case when sysdate between s.startdate and s.enddate then 1 else 0 end as active, p.proposalcode||p.proposalnumber||'-'||s.visit_number as visit, TO_CHAR(s.startdate, 'HH24:MI DD-MM-YYYY') as st, TO_CHAR(s.enddate, 'HH24:MI DD-MM-YYYY') as en, TO_CHAR(s.startdate, 'YYYY-MM-DD\"T\"HH24:MI:SS') as stiso, TO_CHAR(s.enddate, 'YYYY-MM-DD\"T\"HH24:MI:SS') as eniso,  s.sessionid, s.visit_number as vis, s.beamlinename as bl, s.beamlineoperator as lc, s.comments/*, count(dc.datacollectionid) as dcount*/ FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid /*LEFT OUTER JOIN ispyb4a_db.datacollection dc ON s.sessionid = dc.sessionid*/ $where /*GROUP BY TO_CHAR(s.startdate, 'HH24:MI DD-MM-YYYY'),TO_CHAR(s.enddate, 'HH24:MI DD-MM-YYYY'), s.sessionid, s.visit_number,s.beamlinename,s.beamlineoperator,s.comments,s.startdate*/ ORDER BY $order) inner) outer WHERE outer.rn > :$st AND outer.rn <= :".($st+1), $args);
            
            $ids = array();
            $wcs = array();
            foreach ($rows as $r) {
                array_push($ids, $r['SESSIONID']);
                array_push($wcs, 'sessionid=:'.sizeof($ids));
            }
            
            $dcs = array();
            if (sizeof($ids)) {
                $where = implode(' OR ', $wcs);
                $tdcs = $this->db->pq("SELECT count(datacollectionid) as c, sessionid FROM ispyb4a_db.datacollection WHERE $where GROUP BY sessionid", $ids);
                foreach($tdcs as $t) $dcs[$t['SESSIONID']] = $t['C'];
            }
            
            $data = array();
            foreach ($rows as &$r) {
                $dc = array_key_exists($r['SESSIONID'], $dcs) ? $dcs[$r['SESSIONID']] : 0;
                $r['COMMENT'] = $r['COMMENTS'];
                $r['COMMENTS'] = '<span class="comment">'.$r['COMMENTS'].'</span>';
                $r['DCCOUNT'] = $dc;
                
                $r['TYPE'] = null;
                foreach ($bl_types as $tty => $bls) {
                    if (in_array($r['BL'], $bls)) {
                        $r['TYPE'] = $tty;
                        break;
                    }
                }
                if (!$r['TYPE']) $r['TYPE'] = 'gen';
                
                /*
                $lc = $this->lc_lookup($r['SESSIONID']);
                if (!$r['LC'] && $lc) $r['LC'] = $lc->name;
                if ($lc) {
                    if ($lc->type) $r['COMMENTS'] = $lc->type.' | '.$r['COMMENTS'];
                    
                    if ($lc->type == 'Short Visit') {
                        $t = strtotime($r['ST']);
                        $r['ST'] = $short_visit[date('H:i', $t)][0].' '.date('d-m-Y', $t);
                        $e = strtotime($r['EN']);
                        $r['EN'] = $short_visit[date('H:i', $t)][1].' '.date('d-m-Y', $e);
                    }
                }*/
                
                array_push($data, array($r['ST'], $r['EN'], $r['VIS'], $r['BL'], $r['LC'], $r['COMMENTS'], $dc, '<a class="view" title="View Data Collections" href="/dc/visit/'.$this->arg('prop').'-'.$r['VIS'].'">View Data</a> <a class="stats" title="View Statistics" href="/vstat/visit/'.$this->arg('prop').'-'.$r['VIS'].'">View Statistics</a> <a class="report" title="Download PDF Report" href="/pdf/report/visit/'.$this->arg('prop').'-'.$r['VIS'].'">Download Report</a> <a class="export" title="Export Data Collections to CSV" href="/download/csv/visit/'.$this->arg('prop').'-'.$r['VIS'].'">Download CSV</a>'));
                
                #<a class="process" title="Reprocess Data Collections" href="/mc/visit/'.$this->arg('prop').'-'.$r['VIS'].'">Reprocess Data</a>
            }
            
            if ($this->has_arg('visit')) {
                if (sizeof($rows))$this->_output($rows[0]);
                else $this->_error('No such visit');
            } else $this->_output(array('iTotalRecords' => $tot,
                                 'iTotalDisplayRecords' => $tot,
                                 'aaData' => $this->has_arg('array') ? $rows : $data,
                           ));
        }
        
        
        # ------------------------------------------------------------------------
        # Cookie selected proposal
        function _set_proposal() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            $this->cookie($this->arg('prop'));          
            print $this->arg('prop');
        }
    
        
        
        # ------------------------------------------------------------------------
        # Update comment for a visit
        function _set_comment() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            if (!$this->arg('value')) $this->_error('No comment specified');
            
            $com = $this->db->pq("SELECT s.comments,s.sessionid from ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE p.proposalcode||p.proposalnumber||'-'||s.visit_number LIKE :1", array($this->arg('visit')));
            
            if (!sizeof($com)) $this->_error('No such data collection');
            else $com = $com[0];
            
            $this->db->pq("UPDATE ispyb4a_db.blsession set comments=:1 where sessionid=:2", array($this->arg('value'), $com['SESSIONID']));
            
            print $this->arg('value');
        }
        
        
        # ------------------------------------------------------------------------
        # Get users for a visit
        function _get_users() {
            if (!$this->has_arg('visit')) $this->_error('No visit specified');
            
            $rows = $this->db->pq("SELECT iu.role, u.name, u.fullname, count(it.id) as visits, TO_CHAR(max(it.startdate), 'DD-MM-YYYY HH24:MI') as last FROM investigation@DICAT_RO i INNER JOIN investigationuser@DICAT_RO iu ON i.id = iu.investigation_id INNER JOIN user_@DICAT_RO u ON u.id = iu.user_id LEFT OUTER JOIN investigationuser@DICAT_RO iut ON u.id = iut.user_id LEFT OUTER JOIN investigation@DICAT_RO it ON it.id = iut.investigation_id AND it.startdate < i.startdate WHERE lower(i.visit_id) LIKE :1 GROUP BY iu.role,u.name, u.fullname ORDER BY u.fullname", array($this->arg('visit')));
            
            $this->_output($rows);
        }
        
    }
?>