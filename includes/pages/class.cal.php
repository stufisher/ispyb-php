<?php
    
    class Cal extends Page {
        
        var $arg_list = array('mon' => '\w+', 'year' => '\d\d\d\d', 'bl' => '\w\d\d(-\d)?', 'h' => '\w+');
        
        var $dispatch = array('cal' => '_calendar',
                              'proposal' => '_show_proposal',
                              'ics' => '_export_ics',
                              'ext' => '_external_link',
                              );
        var $def = 'cal';
        
        var $sidebar = True;
        
        var $root = 'Calendar';
        var $root_link = '/cal';
        
        
        # Only show visits for a propsoal
        function _show_proposal() {
            if (!$this->has_arg('prop')) $this->error('No proposal', 'No proposal specified');
            $this->_calendar(1);
        }


        # List of visits by date / beamline
        function _calendar($prop=False) {
            $this->template('Visits');            
            
            $c_year = date('Y');
            $c_month = date('n');
            $c_day = date('j');
            
            $this->t->days = array(1=>'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
            $this->t->months = array(1=>'January','February','March','April','May','June','July','August','September','October','November','December');
            
            if ($this->has_arg('mon')) {
                $id = array_search($this->arg('mon'), $this->t->months);
                if ($id !== False) $c_month = $id;
            }
            
            if ($this->has_arg('year')) {
                $c_year = $this->arg('year');
            }
            
            $this->t->first = (date('w',mktime(0,0,0,$c_month,1,$c_year)) - 1) % 7;
            if ($this->t->first < 0) $this->t->first += 7;
            
            $this->t->dim = date('t', mktime (0,0,0,$c_month,1,$c_year));
            $this->t->rem = 7 - (($this->t->first+$this->t->dim) % 7);
            
            $day = mktime(0,0,0,$c_month,1,$c_year);
            $den = mktime(23,59,59,$c_month,$this->t->dim+1,$c_year);
            
            $where = '';
            $args = array(strtoupper(date('d-m-Y', $day)), strtoupper(date('d-m-Y', $den)));
            
            if ($prop) {
                $where = 'AND p.proposalid=:'.(sizeof($args)+1);
                array_push($args, $this->proposalid);
            }
            
            if ($this->has_arg('bl')) {
                $where .= ' AND s.beamlinename LIKE :'.(sizeof($args)+1);
                array_push($args, $this->arg('bl'));
            }
            
            $visits = $this->db->pq("SELECT s.beamlineoperator as lc, p.proposalcode || p.proposalnumber || '-' || s.visit_number as vis, p.proposalcode || p.proposalnumber as prop, s.beamlinename as bl, TO_CHAR(s.startdate, 'DD-MM-YYYY HH24:MI') as st, s.sessionid FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE s.startdate BETWEEN TO_DATE(:1,'dd-mm-yyyy') AND TO_DATE(:2,'dd-mm-yyyy') AND (s.beamlinename LIKE 'i02' OR s.beamlinename LIKE 'i03' OR s.beamlinename LIKE 'i04' OR s.beamlinename LIKE 'i04-1' OR s.beamlinename LIKE 'i24') $where ORDER BY s.startdate, s.beamlinename", $args);
            
            $vbd = array();
            foreach ($visits as $v) {
                $v['REG'] = $this->staff || in_array($v['VIS'], $this->visits) ? 1 : 0;
                
                if (!$this->staff && !$prop) if (!in_array($v['VIS'], $this->visits)) continue;
                
                $t = strtotime($v['ST']);
                $k = date('j', $t);
                $k2 = date('H:i', $t);
                $v['TIME'] = $k2;
                
                /*
                $lc = $this->lc_lookup($v['SESSIONID']);
                $v['LC'] = $lc ? ('(<abbr title="'.$lc->name.'">'.$lc->i.'</abbr>)</span>') : '';
                $v['LCF'] = $lc ? $lc->name : '';
                $v['OCF'] = $lc ? $lc->oc : '';
                $v['TY'] = $lc ? $lc->type : '';
                
                if ($v['TY'] == 'Short Visit') {
                    global $short_visit;
                    $k2 = $short_visit[$k2][0];
                }*/
                
                if (!array_key_exists($k, $vbd)) $vbd[$k] = array();
                if (!array_key_exists($k2, $vbd[$k])) $vbd[$k][$k2] = array();
                
                array_push($vbd[$k][$k2], $v);
            }
            
            $last = ($c_month-2 % 12);
            if ($last < 0) $last += 12;
            
            $next = ($c_month % 12);
            if ($next < 0) $next+= 12;
            
            $this->t->next_mon = $this->t->months[$next+1];
            $this->t->prev_mon = $this->t->months[$last+1];
            
            $this->t->vbd = $vbd;
            $this->t->c_day = $c_day;
            $this->t->c_month = $c_month;
            $this->t->c_year = $c_year;
            $this->t->has_prop = $prop;
            $this->t->pr = $this->has_arg('prop') ? $this->arg('prop') : '';
            
            # Generate private url
            $this->t->purl = '';
            if ($prop || $this->has_arg('bl')) {
                $arg = $prop ? $this->arg('prop') : $this->arg('bl');
                $args = $this->db->pq("SELECT parametercomments as p FROM ispyb4a_db.genericdata WHERE parametervaluestring LIKE :1", array($arg));
                
                if (sizeof($args)) {
                    $this->t->purl = '/cal/ics/h/'.$args[0]['P'].'/calendar.ics';
                } else {
                    $h = md5(uniqid());
                    $this->db->pq("INSERT INTO ispyb4a_db.genericdata (genericdataid,parametervaluestring,parametercomments) VALUES (s_genericdata.nextval, :1, :2)", array($arg, $h));
                    
                    $this->t->purl = '/cal/ics/h/'.$h.'/calendar.ics';
                }
            }
            
            $this->render('dc');
        }
        
        
        # Calendar ics export
        function _export_ics() {
            $where = '';
            $args = array(date('Y'));
            
            if (!$this->has_arg('h')) $this->error('No proposal specified', 'You must specify a proposal to view a calendar');

            $hash = $this->db->pq("SELECT parametervaluestring as p FROM ispyb4a_db.genericdata WHERE parametercomments LIKE :1", array($this->arg('h')));
            $bls = array('i02', 'i03', 'i04', 'i04-1', 'i24', 'i23');
            
            
            if (!sizeof($hash)) $this->error('No proposal specified', 'The specified proposal doesnt appear to exist');
            $arg = $hash[0]['P'];
            
            if (in_array($arg, $bls)) {
                $where .= ' AND s.beamlinename LIKE :'.(sizeof($args)+1);
                array_push($args, $arg);
                
            } else {
                $where = ' AND p.proposalcode||p.proposalnumber=:'.(sizeof($args)+1);
                array_push($args, $arg);
            }
            
            $visits = $this->db->pq("SELECT s.beamlineoperator as lc, p.proposalcode || p.proposalnumber || '-' || s.visit_number as vis, p.proposalcode || p.proposalnumber as prop, s.beamlinename as bl, TO_CHAR(s.startdate, 'DD-MM-YYYY') as d, TO_CHAR(s.enddate, 'DD-MM-YYYY') as e, TO_CHAR(s.startdate, 'HH24:MI') as st, TO_CHAR(s.enddate, 'HH24:MI') as en, s.sessionid FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE (s.beamlinename LIKE 'i02' OR s.beamlinename LIKE 'i03' OR s.beamlinename LIKE 'i04' OR s.beamlinename LIKE 'i04-1' OR s.beamlinename LIKE 'i24') AND s.startdate > TO_DATE(:1,'YYYY') $where ORDER BY s.startdate, s.beamlinename", $args);
            
            $user_tmp = $this->db->pq("SELECT u.name,u.fullname,s.sessionid FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) INNER JOIN investigation@DICAT_RO i ON lower(i.visit_id) = p.proposalcode||p.proposalnumber||'-'||s.visit_number INNER JOIN investigationuser@DICAT_RO iu on i.id = iu.investigation_id INNER JOIN user_@DICAT_RO u on u.id = iu.user_id WHERE (s.beamlinename LIKE 'i02' OR s.beamlinename LIKE 'i03' OR s.beamlinename LIKE 'i04' OR s.beamlinename LIKE 'i04-1' OR s.beamlinename LIKE 'i24') AND s.startdate > TO_DATE(:1,'YYYY') AND iu.role='NORMAL_USER' $where", $args);
            
            $users = array();
            foreach ($user_tmp as $u) {
                if (!array_key_exists($u['SESSIONID'], $users)) $users[$u['SESSIONID']] = array();
                
                array_push($users[$u['SESSIONID']], $u);
            }
            
            $output = '';
            foreach ($visits as $v) {
                /*$lc = $this->lc_lookup($v['SESSIONID']);
                $v['LC'] = $lc ? $lc->name : '';
                $v['OC'] = $lc ? $lc->oc : '';
                $v['TY'] = $lc ? $lc->type : '';
                
                if ($v['TY'] == 'Short Visit') {
                    global $short_visit;
                    $v['EN'] = $v['E'] . ' ' . $short_visit[$v['ST']][1];
                    $v['ST'] = $v['D'] . ' ' . $short_visit[$v['ST']][0];
                } else {
                    $v['ST'] = $v['D'] . ' ' . $v['ST'];
                    $v['EN'] = $v['E'] . ' ' . $v['EN'];
                }
                
                if ($v['TY']) $v['TY'] = ' ['.$v['TY'].']';*/
                
                $st = strtotime($v['ST']);
                $en = strtotime($v['EN']);
                
                $title = $v['VIS'].($v['LC'] ? ' LC: '.$v['LC'] : '');
                if (!in_array($arg, $bls)) $title = $v['BL'].': '.$title;
                
                $us = '';
                if (array_key_exists($v['SESSIONID'], $users)) {
                    foreach ($users[$v['SESSIONID']] as $u) {
                        $us .= 'ATTENDEE;CN="'.$u['FULLNAME']."\":MAILTO:".$u['NAME']."\r\n";
                    }
                }
                
                
                $output .= "BEGIN:VEVENT\r\nDTSTAMP:".date('Ymd\THi',$st)."00Z\r\nDTSTART:".date('Ymd\THi', $st)."00Z\r\nDTEND:".date('Ymd\THi', $en)."00Z\r\nSUMMARY:".$title."\r\n".$us."\r\nEND:VEVENT\r\n";
            }
            
            header("Content-type: text/calendar; charset=utf-8");
            #header('Content-Disposition: inline; filename=calendar.ics');
            print "BEGIN:VCALENDAR\r\nVERSION:2.0\r\n$output\r\nEND:VCALENDAR\r\n";
        }
        
    }
    
?>