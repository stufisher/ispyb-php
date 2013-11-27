<?php
    
    class Ajax extends AjaxBase {
        
        var $arg_list = array('iDisplayStart' => '\d+',
                              'iDisplayLength' => '\d+',
                              'iSortCol_0' => '\d+',
                              'sSortDir_0' => '\w+',
                              'sSearch' => '\w+',
                              'prop' => '\w\w\d+',
                              'array' => '\d',
                              'term' => '\w+',
                               );
        
        var $dispatch = array('proposals' => '_get_proposals',
                              'p' => '_proposals',
                              'visits' => '_get_visits',
                              'set' => '_set_proposal',

                              );
        
        var $def = 'proposals';
        var $profile = True;
        //var $debug = True;
        
        
        
        # ------------------------------------------------------------------------
        # List proposals for current user
        function _get_proposals() {
            $args = array();
            $where = "WHERE (p.proposalcode LIKE 'mx' OR p.proposalcode LIKE 'nt' or p.proposalcode LIKE 'cm')";
            
            $sta = $this->has_arg('iDisplayStart') ? $this->arg('iDisplayStart') : 0;
            $len = $this->has_arg('iDisplayLength') ? $this->arg('iDisplayLength') : 20;
            
            if (!$this->staff) {
                $where = " INNER JOIN investigation@DICAT_RO i ON lower(i.visit_id) LIKE p.proposalcode || p.proposalnumber || '-' || s.visit_number INNER JOIN investigationuser@DICAT_RO iu on i.id = iu.investigation_id inner join user_@DICAT_RO u on u.id = iu.user_id ".$where." AND u.name=:".(sizeof($args)+1);
                array_push($args, phpCAS::getUser());
            }
            
            $tot = $this->db->pq("SELECT count(distinct p.proposalid) as tot FROM ispyb4a_db.proposal p INNER JOIN ispyb4a_db.blsession s ON p.proposalid = s.proposalid $where")[0]['TOT'];
            
            if ($this->has_arg('sSearch')) {
                $st = sizeof($args) + 1;
                $where .= " AND (lower(p.title) LIKE lower('%'||:".$st."||'%') OR p.proposalcode || p.proposalnumber LIKE '%'||:".($st+1)."||'%')";
                for ($i = 0; $i < 2; $i++) array_push($args, $this->arg('sSearch'));
            }
            
            
            $flt = $this->db->pq("SELECT count(distinct p.proposalid) as tot FROM ispyb4a_db.proposal p INNER JOIN ispyb4a_db.blsession s ON p.proposalid = s.proposalid $where", $args)[0]['TOT'];
            
            $st = sizeof($args) + 1;
            array_push($args, $sta);
            array_push($args, $sta+$len);
            
            $order = 'p.bltimestamp DESC';
            
            if ($this->has_arg('iSortCol_0')) {
                $cols = array('p.bltimestamp', 'p.proposalcode', 'p.proposalnumber', 'vcount', 'p.title');
                $dir = $this->has_arg('sSortDir_0') ? ($this->arg('sSortDir_0') == 'asc' ? 'ASC' : 'DESC') : 'ASC';
                if ($this->arg('iSortCol_0') < sizeof($cols)) $order = $cols[$this->arg('iSortCol_0')].' '.$dir;
            }
            
            $rows = $this->db->pq("SELECT outer.* FROM (SELECT ROWNUM rn, inner.* FROM (SELECT p.title, TO_CHAR(p.bltimestamp, 'DD-MM-YYYY') as st, p.proposalcode, p.proposalnumber, count(s.sessionid) as vcount FROM ispyb4a_db.proposal p INNER JOIN ispyb4a_db.blsession s ON p.proposalid = s.proposalid $where GROUP BY TO_CHAR(p.bltimestamp, 'DD-MM-YYYY'), p.bltimestamp, p.proposalcode, p.proposalnumber, p.title ORDER BY $order) inner) outer WHERE outer.rn > :$st AND outer.rn <= :".($st+1), $args);
            
            $data = array();
            foreach ($rows as $r) {
                array_push($data, array($r['ST'], $r['PROPOSALCODE'], $r['PROPOSALNUMBER'], $r['VCOUNT'], $r['TITLE'], '<button title="Activate Proposal" class="small activate"></button>'));
            }
            
            $this->_output(array('iTotalRecords' => $tot,
                                 'iTotalDisplayRecords' => $flt,
                                 'aaData' => $data,
                           ));
        }
        
    
        function _proposals() {
            
            $where = "WHERE (p.proposalcode LIKE 'mx' OR p.proposalcode LIKE 'cm' OR p.proposalcode LIKE 'nt')";
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
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            
            $props = $this->db->pq('SELECT proposalid as id FROM ispyb4a_db.proposal WHERE proposalcode || proposalnumber LIKE :1', array($this->arg('prop')));
            
            if (!sizeof($props)) $this->_error('No such proposal');
            else $p = $props[0]['ID'];
            
            $args = array($p);
            $where = 'WHERE s.proposalid = :1';
            
            $sta = $this->has_arg('iDisplayStart') ? $this->arg('iDisplayStart') : 0;
            $len = $this->has_arg('iDisplayLength') ? $this->arg('iDisplayLength') : 20;
            
            if (!$this->staff) {
                $where = " INNER JOIN investigation@DICAT_RO i ON lower(i.visit_id) LIKE p.proposalcode || p.proposalnumber || '-' || s.visit_number INNER JOIN investigationuser@DICAT_RO iu on i.id = iu.investigation_id inner join user_@DICAT_RO u on u.id = iu.user_id ".$where." AND u.name=:".(sizeof($args)+1);
                array_push($args, phpCAS::getUser());
            }
            
            $tot = $this->db->pq("SELECT count(s.sessionid) as tot FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid $where", $args)[0]['TOT'];

            $st = sizeof($args) + 1;
            array_push($args, $sta);
            array_push($args, $sta+$len);
            
            $order = 's.startdate DESC';
            
            if ($this->has_arg('iSortCol_0')) {
                $cols = array('s.startdate', 's.enddate', 's.visit_number', 's.beamlinename', 's.beamlineoperator', 's.comments', 'dccount');
                $dir = $this->has_arg('sSortDir_0') ? ($this->arg('sSortDir_0') == 'asc' ? 'ASC' : 'DESC') : 'ASC';
                if ($this->arg('iSortCol_0') < sizeof($cols)) $order = $cols[$this->arg('iSortCol_0')].' '.$dir;
            }
            
            $rows = $this->db->pq("SELECT outer.* FROM (SELECT ROWNUM rn, inner.* FROM (SELECT TO_CHAR(s.startdate, 'HH24:MI DD-MM-YYYY') as st, TO_CHAR(s.enddate, 'HH24:MI DD-MM-YYYY') as en, s.visit_number as vis, s.beamlinename as bl, s.beamlineoperator as lc, s.comments, count(dc.datacollectionid) as dcount FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid LEFT OUTER JOIN ispyb4a_db.datacollection dc ON s.sessionid = dc.sessionid $where GROUP BY TO_CHAR(s.startdate, 'HH24:MI DD-MM-YYYY'),TO_CHAR(s.enddate, 'HH24:MI DD-MM-YYYY'),s.visit_number,s.beamlinename,s.beamlineoperator,s.comments,s.startdate ORDER BY $order) inner) outer WHERE outer.rn > :$st AND outer.rn <= :".($st+1), $args);
            
            $data = array();
            foreach ($rows as $r) {
                array_push($data, array($r['ST'], $r['EN'], $r['VIS'], $r['BL'], $r['LC'], $r['COMMENTS'], $r['DCOUNT'],'<a class="small view" title="View Data Collections" href="/dc/visit/'.$this->arg('prop').'-'.$r['VIS'].'"></a> <a class="small stats" title="View Statistics" href="/vstat/bag/'.$this->arg('prop').'/visit/'.$r['VIS'].'"></a> <a class="small report" title="Download PDF Report"></a> <a class="small process" title="Reprocess Data Collections" href="/mc/visit/'.$this->arg('prop').'-'.$r['VIS'].'"></a>'));
            }
            
            $this->_output(array('iTotalRecords' => $tot,
                                 'iTotalDisplayRecords' => $tot,
                                 'aaData' => $data,
                           ));
        }
        
        
        # Cookie selected proposal
        function _set_proposal() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            setcookie('isb_php_proposal', $this->arg('prop'), time()+31536000, '/');
            
            print $this->arg('prop');
        }
    
    }
?>