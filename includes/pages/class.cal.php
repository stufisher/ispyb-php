<?php
    
    class Cal extends Page {
        
        var $arg_list = array('mon' => '\w+', 'year' => '\d\d\d\d', 'bl' => '\w\d\d(-\d)?');
        var $dispatch = array('cal' => '_calendar',
                              'proposal' => '_show_proposal',
                              'ics' => '_export_ics',
                              );
        var $def = 'cal';
        
        var $sidebar = True;
        
        var $root = 'Calendar';
        var $root_link = '/cal';
        
        var $short_visit = array('09:00' => array('14:00', '19:00'),
                                 '17:00' => array('21:00', '02:00'),
                                 '01:00' => array('04:00', '09:00'),
                                 );
        
        
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
            
            $visits = $this->db->pq("SELECT p.proposalcode || p.proposalnumber || '-' || s.visit_number as vis, p.proposalcode || p.proposalnumber as prop, s.beamlinename as bl, TO_CHAR(s.startdate, 'DD-MM-YYYY HH24:MI') as st, s.sessionid FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE s.startdate BETWEEN TO_DATE(:1,'dd-mm-yyyy') AND TO_DATE(:2,'dd-mm-yyyy') AND (s.beamlinename LIKE 'i02' OR s.beamlinename LIKE 'i03' OR s.beamlinename LIKE 'i04' OR s.beamlinename LIKE 'i04-1' OR s.beamlinename LIKE 'i24') $where ORDER BY s.startdate, s.beamlinename", $args);
            
            $vbd = array();
            foreach ($visits as $v) {
                $v['REG'] = $this->staff || in_array($v['VIS'], $this->visits) ? 1 : 0;
                
                if (!$this->staff && !$prop) if (!in_array($v['VIS'], $this->visits)) continue;
                
                $t = strtotime($v['ST']);
                $k = date('j', $t);
                $k2 = date('H:i', $t);
                $v['TIME'] = $k2;
                
                $lc = $this->lc_lookup($v['SESSIONID']);
                $v['LC'] = $lc ? ('(<abbr title="'.$lc->name.'">'.$lc->i.'</abbr>)</span>') : '';
                $v['LCF'] = $lc ? $lc->name : '';
                $v['OCF'] = $lc ? $lc->oc : '';
                $v['TY'] = $lc ? $lc->type : '';
                
                if ($v['TY'] == 'Short Visit') {
                    $k2 = $this->short_visit[$k2][0];
                }
                
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
            
            $this->render('dc');
        }
        
        
        # Calendar ics export
        function _export_ics() {
            $where = '';
            $args = array('2012');
            
            if (!$this->staff && !$this->has_arg('prop')) $this->error('No proposal', 'No proposal specified');
            
            if ($this->has_arg('prop')) {
                $where = ' AND p.proposalid=:'.(sizeof($args)+1);
                array_push($args, $this->proposalid);
            }
            
            if ($this->has_arg('bl')) {
                $where .= ' AND s.beamlinename LIKE :'.(sizeof($args)+1);
                array_push($args, $this->arg('bl'));
            }
            
            $visits = $this->db->pq("SELECT p.proposalcode || p.proposalnumber || '-' || s.visit_number as vis, p.proposalcode || p.proposalnumber as prop, s.beamlinename as bl, TO_CHAR(s.startdate, 'DD-MM-YYYY') as d, TO_CHAR(s.startdate, 'HH24:MI') as st, TO_CHAR(s.enddate, 'HH24:MI') as en, s.sessionid FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE (s.beamlinename LIKE 'i02' OR s.beamlinename LIKE 'i03' OR s.beamlinename LIKE 'i04' OR s.beamlinename LIKE 'i04-1' OR s.beamlinename LIKE 'i24') AND s.startdate > TO_DATE(:1,'YYYY') $where ORDER BY s.startdate, s.beamlinename", $args);
            
            
            $output = '';
            foreach ($visits as $v) {
                $lc = $this->lc_lookup($v['SESSIONID']);
                $v['LC'] = $lc ? $lc->name : '';
                $v['OC'] = $lc ? $lc->oc : '';
                $v['TY'] = $lc ? $lc->type : '';
                
                if ($v['TY'] == 'Short Visit') {
                    $v['ST'] = $v['D'] . ' ' . $this->short_visit[$v['ST']][0];
                    $v['EN'] = $v['D'] . ' ' . $this->short_visit[$v['ST']][1];
                } else {
                    $v['ST'] = $v['D'] . ' ' . $v['ST'];
                    $v['EN'] = $v['D'] . ' ' . $v['EN'];
                }
                
                if ($v['TY']) $v['TY'] = ' ['.$v['TY'].']';
                
                $st = strtotime($v['ST']);
                $en = strtotime($v['EN']);
                
                $output .= 'BEGIN:VEVENT
                DTSTAMP:'.date('dmY\THi',$st).'00Z
                DTSTART:'.date('dmY\THi', $st).'00Z
                DTSTEND:'.date('dmY\THi', $en).'00Z
                SUMMARY: '.$v['BL'].': '.$v['VIS'].$v['TY'].($v['LC'] ? ' LC: '.$v['LC'] : '').($v['OC'] ? ' OC: '.$v['OC'] : '').'
                END:VEVENT
                ';
            }
            
            #header("Content-type: text/calendar; charset=utf-8");
            #header('Content-Disposition: inline; filename=calendar.ics');
            print "BEGIN:VCALENDAR
            VERSION:2.0
            $output
            END:VCALENDAR
            ";
        }
        
    }
    
?>