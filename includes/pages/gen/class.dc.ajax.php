<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('id' => '\d+', 'visit' => '\w+\d+-\d+', 'page' => '\d+', 's' => '[\w\d-\/]+', 'pp' => '\d+', 't' => '\w+', 'bl' => '\w\d\d(-\d)?', 'value' => '.*', 'sid' => '\d+', 'aid' => '\d+', 'pjid' => '\d+', 'imp' => '\d', 'pid' => '\d+', 'h' => '\d\d', 'dmy' => '\d\d\d\d\d\d\d\d',
                              );
        var $dispatch = array('dc' => '_data_collections',
                              'chi' => '_chk_image',
                              'dat' => '_plot',
                              'dl' => '_download',
                              'comment' => '_set_comment',
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
            $this->profile('main query');            
            
            $nf = array();
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
            
            $this->profile('processing');
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
            
            $this->_output($data);
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
    }

?>