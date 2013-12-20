<?php
    
    class Cal extends Page {
        
        var $arg_list = array('mon' => '\w+', 'year' => '\d\d\d\d');
        var $dispatch = array('cal' => '_calendar', 'proposal' => '_show_proposal');
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
            
            $visits = $this->db->pq("SELECT p.proposalcode || p.proposalnumber || '-' || s.visit_number as vis, p.proposalcode || p.proposalnumber as prop, s.beamlinename as bl, TO_CHAR(s.startdate, 'DD-MM-YYYY HH24:MI') as st FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE s.startdate BETWEEN TO_DATE(:1,'dd-mm-yyyy') AND TO_DATE(:2,'dd-mm-yyyy') AND (s.beamlinename LIKE 'i02' OR s.beamlinename LIKE 'i03' OR s.beamlinename LIKE 'i04' OR s.beamlinename LIKE 'i04-1' OR s.beamlinename LIKE 'i24') ORDER BY s.startdate, s.beamlinename", array(strtoupper(date('d-m-Y', $day)), strtoupper(date('d-m-Y', $den))));
            
            $vbd = array();
            foreach ($visits as $v) {
                if (!$this->staff)
                    if (!in_array($v['VIS'], $this->visits)) continue;
                
                if ($prop) if ($this->arg('prop') != $v['PROP']) continue;
                
                $t = strtotime($v['ST']);
                $k = date('j', $t);
                $k2 = date('H:i', $t);
                $v['TIME'] = $k2;
                
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
        
    }
    
?>