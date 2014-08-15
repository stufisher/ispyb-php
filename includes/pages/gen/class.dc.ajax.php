<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('id' => '\d+', 'visit' => '\w+\d+-\d+', 'page' => '\d+', 's' => '[\w\d-\/]+', 'pp' => '\d+', 't' => '\w+', 'bl' => '\w\d\d(-\d)?', 'value' => '.*', 'h' => '\d\d',
                              );
        var $dispatch = array('dc' => '_data_collections',
                              'chi' => '_chk_image',
                              'dat' => '_plot',
                              'dl' => '_download',
                              'comment' => '_set_comment',
                              'flag' => '_flag',
                              );
        
        var $def = 'dc';
        
        
        # Data Collections
        function _data_collections() {
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
                
                $sess = array('dc.sessionid=:1');
                array_push($args, $info['SESSIONID']);
                
            # Proposal
            } else if ($this->has_arg('prop')) {
                $info = $this->db->pq('SELECT proposalid FROM ispyb4a_db.proposal p WHERE p.proposalcode || p.proposalnumber LIKE :1', array($this->arg('prop')));
                
                $sess[0] = 'ses.proposalid=:1';
                array_push($args, $this->proposalid);
            }
            
            if (!sizeof($info)) $this->_error('The specified visit, or proposal doesnt exist');
            
            # Filter by time for visits
            if (($this->has_arg('h') && ($this->has_arg('visit') || $this->has_arg('dmy')))) {
                $where .= "AND dc.starttime > TO_DATE(:".(sizeof($args)+1).", 'HH24:MI:SS DDMMYYYY') AND dc.starttime < TO_DATE(:".(sizeof($args)+2).", 'HH24:MI:SS DDMMYYYY')";
                
                if ($this->has_arg('dmy')) {
                    $my = $this->arg('dmy');
                } else {
                    $my = $info['DMY'];
                    if ($this->arg('h') < $info['SH']) {
                        $sd = mktime(0,0,0,substr($my,2,2), substr($my,0,2), substr($my,4,4))+(3600*24);
                        $my = date('dmY', $sd);
                    }
                }
                
                
                if ($this->has_arg('h')) {
                    $st = $this->arg('h').':00:00 ';
                    $en = $this->arg('h').':59:59 ';
                } else {
                    $st = '00:00:00';
                    $en = '23:59:59 ';
                }
                
                array_push($args, $st.$my);
                array_push($args, $en.$my);
            }
            
            
            # If not staff check they have access to data collection
            if (!$this->has_arg('visit') && !$this->staff) {
                $where .= " AND u.name=:".(sizeof($args)+1);
                
                $extj[0] .= " INNER JOIN ispyb4a_db.proposal p ON p.proposalid = ses.proposalid INNER JOIN investigation@DICAT_RO i ON lower(i.visit_id) LIKE p.proposalcode||p.proposalnumber||'-'||ses.visit_number INNER JOIN investigationuser@DICAT_RO iu on i.id = iu.investigation_id INNER JOIN user_@DICAT_RO u on u.id = iu.user_id";
                array_push($args, phpCAS::getUser());
            }
            
            
            # View a single data collection
            if ($this->has_arg('id')) {
                $st = sizeof($args)+1;
                $where .= ' AND dc.datacollectionid=:'.$st;
                array_push($args, $this->arg('id'));
            }
            
            
            # Search terms
            if ($this->has_arg('s')) {
                $st = sizeof($args) + 1;
                $where .= " AND (lower(dc.filetemplate) LIKE lower('%'||:$st||'%') OR lower(dc.imagedirectory) LIKE lower('%'||:".($st+1)."||'%') OR lower(dc.comments) LIKE lower('%'||:".($st+2)."||'%'))";
                for ($i = 0; $i < 3; $i++) array_push($args, $this->arg('s'));
            }
            
            
            $tot = $this->db->pq("SELECT sum(tot) as t FROM (SELECT count(dc.datacollectionid) as tot FROM ispyb4a_db.datacollection dc
                INNER JOIN ispyb4a_db.blsession ses ON ses.sessionid = dc.sessionid
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
             SELECT $extc ses.visit_number as vn, TO_CHAR(dc.starttime, 'DD-MM-YYYY HH24:MI:SS') as st, dc.comments, dc.starttime as sta, dc.runstatus, dc.numberofimages as numimg, dc.resolution, dc.beamsizeatsamplex as bsx, dc.beamsizeatsampley as bsy, dc.imageprefix as imp, dc.datacollectionnumber as run, dc.filetemplate, dc.datacollectionid as id, dc.imagedirectory as dir, dc.exposuretime, dc.transmission, dc.wavelength
             FROM ispyb4a_db.datacollection dc
             INNER JOIN ispyb4a_db.blsession ses ON ses.sessionid = dc.sessionid
             $extj[0]
             WHERE $sess[0] $where 
                   
            ORDER BY sta DESC
             
            ) inner) outer
            WHERE outer.rn > :$st AND outer.rn <= :".($st+1);
            
            $dcs = $this->db->pq($q, $args);
            
            $nf = array(4 => array('WAVELENGTH', 'RESOLUTION'), 3 => array('RESOLUTION'));
            foreach ($dcs as $i => &$dc) {
                $dc['DIR'] = preg_replace('/.*\/\d\d\d\d\/\w\w\d+-\d+\//', '', $dc['DIR']);
                
                $dc['BSX'] = round($dc['BSX']*1000);
                $dc['BSY'] = round($dc['BSY']*1000);
                
                foreach ($nf as $nff => $cols) {
                    foreach ($cols as $c) {
                        $dc[$c] = number_format($dc[$c], $nff);
                    }
                }
            
            }

            $this->_output(array($pgs, $dcs));

        }
        
        
        
        # ------------------------------------------------------------------------
        # Check whether images exist
        function _chk_image() {
            if (!($this->has_arg('visit') || $this->has_arg('prop'))) $this->_error('No visit or proposal specified');
            
            $where = array();
            $ids = array();
            if (array_key_exists('ids', $_POST)) {
                foreach ($_POST['ids'] as $i) {
                    if (preg_match('/^\d+$/', $i)) {
                        array_push($ids,$i);
                        array_push($where,'dc.datacollectionid=:'.sizeof($ids));
                    }
                }
            }
            $where = '('.implode(' OR ', $where).')';
            
            if (!sizeof($ids)) {
                $this->_output(array());
                return;
            }
            
            $dct = $this->db->pq("SELECT dc.datacollectionid as id, dc.xtalsnapshotfullpath1 as x1, dc.xtalsnapshotfullpath2 as x2, dc.xtalsnapshotfullpath3 as x3, dc.xtalsnapshotfullpath4 as x4 FROM ispyb4a_db.datacollection dc  WHERE $where", $ids);
                
            $this->profile('dc query');
                                   
            $dcs = array();
            foreach ($dct as $d) $dcs[$d['ID']] = $d;
            
            $out = array();
            
            foreach ($dcs as $dc) {
                $images = array();
                foreach (array('X1', 'X2', 'X3', 'X4') as $j => $im) {
                    array_push($images, file_exists($dc[$im]) ? 1 : 0);
                }
            
                array_push($out, array($dc['ID'], $images));
            }
            $this->_output($out);
        }
        
        
        # ------------------------------------------------------------------------
        # Dat Plot
        function _plot() {
            session_write_close();
            if (!$this->has_arg('id')) {
                $this->_error('No data collection id specified');
                return;
            }
            
            $info = $this->db->pq('SELECT ses.visit_number, dc.datacollectionnumber as scan, dc.imageprefix as imp, dc.imagedirectory as dir FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession ses ON dc.sessionid = ses.sessionid WHERE datacollectionid=:1', array($this->arg('id')));
            if (sizeof($info) == 0) {
                $this->_error('No data for that collection id');
                return;
            } else $info = $info[0];
            
            $info['VISIT'] = $this->arg('prop') .'-'.$info['VISIT_NUMBER'];
            
            $pth = str_replace($info['VISIT'], $info['VISIT'].'/.ispyb', $this->ads($info['DIR']).$info['IMP'].'/'.$info['SCAN'].'.dat');
            
            $data = array();
            if (file_exists($pth)) {
                $dat = explode("\n",file_get_contents($pth));

                foreach (array_slice($dat,5) as $i => $d) {
                    if ($d) {
                        list($x, $y, $e) = preg_split('/\s+/', trim($d));
                        array_push($data, array(floatval($x), floatval($y), floatval($e)));
                    }
                }
            }
            
            $this->_output(array($data));
        }

        
        # ------------------------------------------------------------------------
        # Download Data
        function _download() {
            session_write_close();
            if (!$this->has_arg('id')) {
                $this->_error('No data collection id specified');
                return;
            }
            
            $info = $this->db->pq('SELECT ses.visit_number, dc.datacollectionnumber as scan, dc.imageprefix as imp, dc.imagedirectory as dir FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession ses ON dc.sessionid = ses.sessionid WHERE datacollectionid=:1', array($this->arg('id')));
            if (sizeof($info) == 0) {
                $this->_error('No data for that collection id');
                return;
            } else $info = $info[0];
            
            $info['VISIT'] = $this->arg('prop') .'-'.$info['VISIT_NUMBER'];
            
            $data = str_replace($info['VISIT'], $info['VISIT'].'/.ispyb', $this->ads($info['DIR']).$info['IMP'].'/download/download.zip');
            
            if (file_exists($data)) {
                $this->_header($this->arg('id').'_download.zip');
                readfile($data);
            }
        }
        
        # ------------------------------------------------------------------------
        # Force browser to download file
        function _header($f) {
            header("Content-Type: application/octet-stream");
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"$f\"");
        }
        
        # ------------------------------------------------------------------------
        # Update comment for a data collection
        function _set_comment() {
            if (!$this->arg('id')) $this->_error('No data collection id specified');
            if (!$this->arg('value')) $this->_error('No comment specified');
            
            $com = $this->db->pq('SELECT comments from ispyb4a_db.datacollection WHERE datacollectionid=:1', array($this->arg('id')));
            
            if (!sizeof($com)) $this->_error('No such data collection');
            
            $this->db->pq("UPDATE ispyb4a_db.datacollection set comments=:1 where datacollectionid=:2", array($this->arg('value'), $this->arg('id')));
            
            print $this->arg('value');
        }
        
        
        # ------------------------------------------------------------------------
        # Flag a data collection
        function _flag() {
            if (!$this->arg('id')) $this->_error('No data collection id specified');
            
            $com = $this->db->pq('SELECT comments from ispyb4a_db.datacollection WHERE datacollectionid=:1', array($this->arg('id')));
            
            if (!sizeof($com)) $this->_error('No such data collection');
            else $com = $com[0]['COMMENTS'];
            
            if (strpos($com, '_FLAG_') === false) {
                $this->db->pq("UPDATE ispyb4a_db.datacollection set comments=comments||' _FLAG_' where datacollectionid=:1", array($this->arg('id')));
                $this->_output(1);
            } else {
                $com = str_replace(' _FLAG_', '', $com);
                $this->db->pq("UPDATE ispyb4a_db.datacollection set comments=:1 where datacollectionid=:2", array($com, $this->arg('id')));
                
                $this->_output(0);
            }
        }
    }

?>