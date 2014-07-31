<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('id' => '\d+', 'visit' => '\w+\d+-\d+', 'page' => '\d+', 's' => '[\w\d-\/]+', 'pp' => '\d+', 't' => '\w+', 'bl' => '\w\d\d(-\d)?', 'value' => '.*', 'sid' => '\d+', 'aid' => '\d+', 'pjid' => '\d+', 'imp' => '\d', 'pid' => '\d+', 'h' => '\d\d', 'dmy' => '\d\d\d\d\d\d\d\d',
                              );
        var $dispatch = array('em' => '_data_collections_em',
                              'chiem' => '_chk_image_em',
                              'emp' => '_em_plot',
                              'sem' => '_get_sample_em',
                              );
        
        var $def = 'em';
        
        
        # EM Data Collections
        function _data_collections_em() {
            if (!($this->has_arg('visit') || $this->has_arg('prop'))) $this->_error('No visit or proposal specified');
            
            $args = array();
            $where = '';
            $extj = array('','','','');
            $extc = '';
            $sess = array();
            
            # Pagination
            $start = 0;
            $end = 10;
            $pp = $this->has_arg('pp') ? $this->arg('pp') : 15;
            
            if ($this->has_arg('page')) {
                $pg = $this->arg('page') - 1;
                $start = $pg*$pp;
                $end = $pg*$pp+$pp;
            }
            
            # Check that whatever we are looking for actually exists
            $info = array();
            # Visits
            if ($this->has_arg('visit')) {
                list($info,) = $this->db->pq("SELECT TO_CHAR(s.startdate, 'HH24') as sh, TO_CHAR(s.startdate, 'DDMMYYYY') as dmy, s.sessionid, s.beamlinename as bl, vr.run, vr.runid FROM ispyb4a_db.v_run vr INNER JOIN ispyb4a_db.blsession s ON (s.startdate BETWEEN vr.startdate AND vr.enddate) INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE  p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));
                
                $sess = array('em.blsessionid=:1');
                array_push($args, $info['SESSIONID']);
                
            # Proposal
            } else if ($this->has_arg('prop')) {
                $info = $this->db->pq('SELECT proposalid FROM ispyb4a_db.proposal p WHERE p.proposalcode || p.proposalnumber LIKE :1', array($this->arg('prop')));
                
                $sess[$i] = 'ses.proposalid=:'.($i+1);
                array_push($args, $this->proposalid);
            }
            
            if (!sizeof($info)) $this->_error('The specified visit, sample, or project doesnt exist');
            
            $tot = $this->db->pq("SELECT sum(tot) as t FROM (SELECT count(em.emmovieid) as tot FROM ispyb4a_db.emmovie em
                INNER JOIN ispyb4a_db.blsession ses ON ses.sessionid = em.blsessionid
                $extj[0]
                WHERE $sess[0] $where
                
                )", $args);
            
            $tot = $tot[0]['T'];
            
            $pgs = intval($tot/$pp);
            if ($tot % $pp != 0) $pgs++;

            $st = sizeof($args) + 1;
            array_push($args, $start);
            array_push($args, $end);

            $q = "SELECT outer.*
             FROM (SELECT ROWNUM rn, inner.*
             FROM (
             SELECT $extc ses.visit_number as vn, TO_CHAR(em.starttime, 'DD-MM-YYYY HH24:MI:SS') as st, em.comments, em.starttime as sta, em.runstatus, em.emmovieid as id, em.rundirectory, em.noimages, em.framelength, em.magnification, em.samplepixsize, em.totalexposure, em.doseperframe, em.totaldose, m.instrumentname as microscope, m.voltage, m.cs, m.detectorpixelsize, m.c2aperture, m.objaperture, m.c2lens, em.moviefile FROM ispyb4a_db.emmovie em
             INNER JOIN ispyb4a_db.blsession ses ON ses.sessionid = em.blsessionid
             INNER JOIN ispyb4a_db.emmicroscope m ON em.emmicroscopeid = m.emmicroscopeid
             $extj[0]
             WHERE $sess[0] $where 
                   
            ORDER BY sta DESC
             
            ) inner) outer
            WHERE outer.rn > :$st AND outer.rn <= :".($st+1);
            
            $dcs = $this->db->pq($q, $args);
            $this->profile('main query');            
            
            $nf = array();
            foreach ($dcs as $i => &$dc) {
                $dc['SN'] = 0;
                $dc['DI'] = 0;
                $dc['MOVIEFILE'] = preg_replace('/.*\/\d\d\d\d\/\w\w\d+-\d+\//', '', $dc['MOVIEFILE']);
                
                if ($this->has_arg('sid') || $this->has_arg('pjid')) $dc['VIS'] = $this->arg('prop').'-'.$dc['VN'];
                
                foreach ($nf as $nff => $cols) {
                    foreach ($cols as $c) {
                        $dc[$c] = number_format($dc[$c], $nff);
                    }
                }
            
            }
            
            $this->profile('processing');
            $this->_output(array($pgs, $dcs));

        }
        
        
        
        # ------------------------------------------------------------------------
        # Check whether em images exist
        function _chk_image_em() {
            if (!($this->has_arg('visit') || $this->has_arg('prop'))) $this->_error('No visit or proposal specified');
            
            $where = array();
            $ids = array();
            if (array_key_exists('ids', $_POST)) {
                foreach ($_POST['ids'] as $i) {
                    if (preg_match('/^\d+$/', $i)) {
                        array_push($ids,$i);
                        array_push($where,'em.emmovieid=:'.sizeof($ids));
                    }
                }
            }
            $where = '('.implode(' OR ', $where).')';
            
            if (!sizeof($ids)) {
                $this->_output(array());
                return;
            }
            
            $dct = $this->db->pq("SELECT em.emmovieid as id, p.proposalcode||p.proposalnumber||'-'||s.visit_number as vis, em.powerspectrumfullpath1 as ps1, em.powerspectrumfullpath2 as ps2, em.micrographfullpath as im FROM ispyb4a_db.emmovie em INNER JOIN ispyb4a_db.blsession s ON s.sessionid=em.blsessionid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE $where", $ids);
                
            $this->profile('dc query');
                                   
            $dcs = array();
            foreach ($dct as $d) $dcs[$d['ID']] = $d;
            
            $out = array();
            
            foreach ($dcs as $dc) {
                $sn = 0;
                $images = array();
                foreach (array('PS1', 'PS2') as $j => $im) {
                    if (file_exists($dc[$im])) {
                        array_push($images, $j);
                        if ($im == 'PS1') {
                            $sn = 1;
                            //if (file_exists(str_replace('.png', 't.png', $dc[$im]))) $sn = 1;
                        }
                    }
                    unset($dc[$im]);
                }

                $emi = file_exists($dc['IM']) ? 1 : 0;
            
                array_push($out, array($dc['ID'], array($emi,$images,$sn)));
            }
            $this->_output($out);
        }
        
        
        # ------------------------------------------------------------------------
        # EM Plot
        function _em_plot() {
            session_write_close();
            if (!$this->has_arg('id')) {
                $this->_error('No data collection id specified');
                return;
            }
            
            $info = $this->db->pq('SELECT driftfullpath as pth FROM ispyb4a_db.emmovie WHERE emmovieid=:1', array($this->arg('id')));
            if (sizeof($info) == 0) {
                $this->_error('No data for that collection id');
                return;
            } else $info = $info[0];
            
            $data = array();
            if (file_exists($info['PTH'])) {
                $dat = explode("\n",file_get_contents($info['PTH']));

                foreach ($dat as $i => $d) {
                    if ($d) {
                        list($x, $y) = preg_split('/\s+/', trim($d));
                        array_push($data, array(floatval($x), intval($y)));
                    }
                }
            }
            
            $this->_output($data);
        }
        
        
        # ------------------------------------------------------------------------
        # Get sample
        function _get_sample_em() {
            $ids = array();
            if (array_key_exists('ids', $_POST)) {
                foreach ($_POST['ids'] as $n => $i) {
                    if (preg_match('/^\d+$/', $i)) {
                        array_push($ids,$i);
                    }
                }
            }
                   
            if (!sizeof($ids)) {
                $this->_output(array());
                return;
            }

            $smps = array();
            foreach ($ids as $id) {
                $smp = $this->db->pq("SELECT bls.blsampleid as sid, bls.name as san FROM ispyb4a_db.emmovie em INNER JOIN ispyb4a_db.blsample bls ON em.blsampleid = bls.blsampleid WHERE em.emmovieid=:1", array($id));
                        
                $smps[$id] = sizeof($smp) ? $smp[0] : array('SAN' => '', 'SID' => '');
            }
                                   
            $this->_output($smps);
        }
        
        
        # ------------------------------------------------------------------------
        # Update comment for a data collection
        function _set_comment() {
            if (!$this->arg('id')) $this->_error('No data collection id specified');
            if (!$this->arg('value')) $this->_error('No comment specified');
            
            $com = $this->db->pq('SELECT comments from ispyb4a_db.emmovie WHERE emmovieid=:1', array($this->arg('id')));
            
            if (!sizeof($com)) $this->_error('No such data collection');
            
            $this->db->pq("UPDATE ispyb4a_db.emmovie set comments=:1 where emmovieid=:2", array($this->arg('value'), $this->arg('id')));
            
            print $this->arg('value');
        }
    }

?>