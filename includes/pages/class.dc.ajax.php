<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('id' => '\d+', 'visit' => '\w+\d+-\d+', 'page' => '\d+', 's' => '[\w\d-\/]+', 'pp' => '\d+', 't' => '\w+', 'bl' => '\w\d\d(-\d)?', 'value' => '.*', 'sid' => '\d+', 'aid' => '\d+', 'pjid' => '\d+', 'imp' => '\d', 'pid' => '\d+', 'h' => '\d\d', 'dmy' => '\d\d\d\d\d\d\d\d',
                              'a' => '\d+(.\d+)?',
                              'b' => '\d+(.\d+)?',
                              'c' => '\d+(.\d+)?',
                              'al' => '\d+(.\d+)?',
                              'be' => '\d+(.\d+)?',
                              'ga' => '\d+(.\d+)?',
                              'sg' => '\w+',
                              );
        var $dispatch = array('strat' => '_dc_strategies',
                              'ap' => '_dc_auto_processing',
                              'dp' => '_dc_downstream',
                              'dc' => '_data_collections',
                              'ed' => '_edge',
                              'mca' => '_mca',
                              'aps' => '_ap_status',
                              'chi' => '_chk_image',
                              'sf' => '_get_sample_flux',
                              'imq' => '_image_qi',
                              'rd' => '_rd',
                              'flag' => '_flag',
                              'comment' => '_set_comment',
                              'sym' => '_get_symmetry',
                              );
        
        var $def = 'dc';
        #var $profile = True;
        #var $debug = True;
        #var $explain = True;
        
        # ------------------------------------------------------------------------
        # Data Collection AJAX Requests
        #   This is pretty crazy, it will return unioned data collections, energy
        #   scans, xfe spectra, and robot/sample actions as a single array ordered
        #   by start time descending for:
        #   - a proposal /
        #   - a visit /visit/
        #   - a particular sample id /sid/
        #   - a project (explicit or implicit) /pjid/(imp/1/)
        #   - a protein /pid/
        #   Its also searchable (A-z0-9-/) and filterable
        function _data_collections() {
            session_write_close();
            
            $this->profile('starting dc page');
            #if (!$this->has_arg('visit') &&
            #    !($this->has_arg('sid') && $this->arg('prop')) &&
            #    !($this->has_arg('pjid') && $this->arg('prop')))
            #        $this->_error('No visit, sample, or project specified');
            
            if (!($this->has_arg('visit') || $this->has_arg('prop'))) $this->_error('No visit or proposal specified');
            
            $args = array();
            
            $where = '';
            $where2 = '';
            $where3 = '';
            $where4 = '';
            
            $sess = array();
            
            # Extra joins
            $extj = array('','','','');
            # Extra columns
            $extc = '';
            
            
            # Filter by types
            if ($this->has_arg('t')) {
                if ($this->arg('t') == 'dc' || $this->arg('t') == 'sc' || $this->arg('t') == 'fc' || $this->arg('t') == 'gr') {
                    
                    if ($this->arg('t') == 'sc') $where .= ' AND dc.overlap != 0';
                    else if ($this->arg('t') == 'gr') $where .= ' AND dc.axisrange = 0';
                    else if ($this->arg('t') == 'fc') $where .= ' AND dc.overlap = 0 AND dc.axisrange > 0';
                    
                    $where2 .= ' AND es.energyscanid < 0';
                    $where3 .= ' AND r.robotactionid < 0';
                    $where4 .= ' AND xrf.xfefluorescencespectrumid < 0';
                    
                } else if ($this->arg('t') == 'ed') {
                    $where .= ' AND dc.datacollectionid < 0';
                    $where3 .= ' AND r.robotactionid < 0';
                    $where4 .= ' AND xrf.xfefluorescencespectrumid < 0';
                    
                } else if ($this->arg('t') == 'fl') {
                    $where .= ' AND dc.datacollectionid < 0';
                    $where2 .= ' AND es.energyscanid < 0';
                    $where3 .= ' AND r.robotactionid < 0';
                    
                } else if ($this->arg('t') == 'rb') {
                    $where .= ' AND dc.datacollectionid < 0';
                    $where2 .= ' AND es.energyscanid < 0';
                    $where3 .= " AND (r.actiontype LIKE 'LOAD' OR r.actiontype LIKE 'UNLOAD' OR r.actiontype LIKE 'DISPOSE')";
                    $where4 .= ' AND xrf.xfefluorescencespectrumid < 0';
                    
                } else if ($this->arg('t') == 'ac') {
                    $where .= ' AND dc.datacollectionid < 0';
                    $where2 .= ' AND es.energyscanid < 0';
                    $where3 .= " AND (r.actiontype NOT LIKE 'LOAD' AND r.actiontype NOT LIKE 'UNLOAD' AND r.actiontype NOT LIKE 'DISPOSE')";
                    $where4 .= ' AND xrf.xfefluorescencespectrumid < 0';
                    
                } else if ($this->arg('t') == 'flag') {
                    $where .= " AND dc.comments LIKE '%_FLAG_%'";
                    $where2 .= " AND es.comments LIKE '%_FLAG_%'";
                    $where3 .= " AND r.robotactionid < 0";
                    $where4 .= " AND xrf.comments LIKE '%_FLAG_%'";
                }
            }
            
            
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
            
                $sess = array('dc.sessionid=:1', 'es.sessionid=:2', 'r.blsessionid=:3', 'xrf.sessionid=:4');
                for ($i = 0; $i < 4; $i++) array_push($args, $info['SESSIONID']);
                
            # Samples
            } else if ($this->has_arg('sid') && $this->has_arg('prop')) {
                $info = $this->db->pq("SELECT s.blsampleid FROM ispyb4a_db.blsample s INNER JOIN ispyb4a_db.crystal cr ON cr.crystalid = s.crystalid INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = cr.proteinid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = pr.proposalid WHERE s.blsampleid=:1 AND p.proposalcode || p.proposalnumber LIKE :2", array($this->arg('sid'), $this->arg('prop')));
                
                $extj[1] .= ' INNER JOIN blsample_has_energyscan be ON be.energyscanid = es.energyscanid';
                $tables2 = array('dc', 'be', 'r', 'xrf');
                foreach ($tables2 as $i => $t) $sess[$i] = $t.'.blsampleid=:'.($i+1);
                for ($i = 0; $i < 4; $i++) array_push($args, $this->arg('sid'));
                 
            # Projects
            } else if ($this->has_arg('pjid')) {
                $info = $this->db->pq('SELECT p.title FROM ispyb4a_db.project p LEFT OUTER JOIN ispyb4a_db.project_has_user pu ON pu.projectid = p.projectid WHERE p.projectid=:1 AND (p.owner=:2 or pu.username=:3)', array($this->arg('pjid'), phpCAS::getUser(), phpCAS::getUser()));
                
                $extc = 'bls.name as sample,bls.blsampleid,';
                $tables = array(array('project_has_dcgroup', 'dc', 'datacollectiongroupid'),
                                array('project_has_energyscan', 'es', 'energyscanid'),
                                array('project_has_session', 'r', 'blsessionid', 'sessionid'),
                                array('project_has_xfefspectrum', 'xrf', 'xfefluorescencespectrumid'),
                                );
                
                foreach ($tables as $i => $t) {
                    $ct = sizeof($t) == 4 ? $t[3] : $t[2];
                    
                    # Fucking inconsistencies!
                    $smp = $t[1] == 'es' ? " LEFT OUTER JOIN blsample_has_energyscan she ON she.energyscanid = es.energyscanid LEFT OUTER JOIN ispyb4a_db.blsample bls ON bls.blsampleid = she.blsampleid"
                    : " LEFT OUTER JOIN ispyb4a_db.blsample bls ON bls.blsampleid = $t[1].blsampleid";
                    
                    $extj[$i] .= " LEFT OUTER JOIN ispyb4a_db.$t[0] prj ON $t[1].$t[2] = prj.$ct $smp";
                    $sess[$i] = 'prj.projectid=:'.($i+1);
                    
                    if ($this->has_arg('imp')) {
                        if ($this->arg('imp')) {
                            # Extra linker table needed for energy scans :(
                            $ij = $t[1] == 'es' ? "LEFT OUTER JOIN ispyb4a_db.blsample_has_energyscan bes ON $t[1].$t[2] = bes.$t[2] LEFT OUTER JOIN ispyb4a_db.blsample smp ON bes.blsampleid = smp.blsampleid"
                                                : "LEFT OUTER JOIN ispyb4a_db.blsample smp ON $t[1].blsampleid = smp.blsampleid";
                            
                            $extj[$i] .= " $ij LEFT OUTER JOIN ispyb4a_db.crystal cr ON cr.crystalid = smp.crystalid LEFT OUTER JOIN ispyb4a_db.protein pr ON pr.proteinid = cr.proteinid LEFT OUTER JOIN ispyb4a_db.project_has_protein prj2 ON prj2.proteinid = pr.proteinid LEFT OUTER JOIN ispyb4a_db.project_has_blsample prj3 ON prj3.blsampleid = smp.blsampleid";
                            $sess[$i] = '(prj.projectid=:'.($i*3+1).' OR prj2.projectid=:'.($i*3+2).' OR prj3.projectid=:'.($i*3+3).')';
                        }
                    }
                }
                
                
                $n = 4;
                if ($this->has_arg('imp'))
                    if ($this->arg('imp')) $n = 12;
                for ($i = 0; $i < $n; $i++) array_push($args, $this->arg('pjid'));
        
                
            # Proteins
            } else if ($this->has_arg('pid')) {
                $info = $this->db->pq("SELECT proteinid FROM ispyb4a_db.protein p WHERE p.proteinid=:1", array($this->arg('pid')));
                $extc = 'smp.name as sample,smp.blsampleid,';
                
                foreach (array('dc', 'es', 'r', 'xrf') as $i => $t) {
                    if ($t == 'r') {
                        $sess[$i] = 'r.robotactionid < 0';
                        $extj[$i] = "INNER JOIN ispyb4a_db.blsample smp ON r.blsampleid = smp.blsampleid";
                        
                    } else {
                        $ij = $t == 'es' ? "INNER JOIN ispyb4a_db.blsample_has_energyscan bes ON $t.energyscanid = bes.energyscanid INNER JOIN ispyb4a_db.blsample smp ON bes.blsampleid = smp.blsampleid"
                        : "INNER JOIN ispyb4a_db.blsample smp ON $t.blsampleid = smp.blsampleid";
                        
                        $extj[$i] .= " $ij INNER JOIN ispyb4a_db.crystal cr ON cr.crystalid = smp.crystalid INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = cr.proteinid";
                        $sess[$i] = 'pr.proteinid=:'.(sizeof($args)+1);
                        array_push($args, $this->arg('pid'));
                    }
                }
                
            # Proposal
            } else if ($this->has_arg('prop')) {
                $info = $this->db->pq('SELECT proposalid FROM ispyb4a_db.proposal p WHERE p.proposalcode || p.proposalnumber LIKE :1', array($this->arg('prop')));
                
                for ($i = 0; $i < 4; $i++) {
                    $sess[$i] = 'ses.proposalid=:'.($i+1);
                    array_push($args, $this->proposalid);
                }
            }
            
            if (!sizeof($info)) $this->_error('The specified visit, sample, or project doesnt exist');
            
            
            # Filter by time for visits
            if (($this->has_arg('h') && ($this->has_arg('visit') || $this->has_arg('dmy'))) || $this->has_arg('dmy')) {
                $where .= "AND dc.starttime > TO_DATE(:".(sizeof($args)+1).", 'HH24:MI:SS DDMMYYYY') AND dc.starttime < TO_DATE(:".(sizeof($args)+2).", 'HH24:MI:SS DDMMYYYY')";
                $where2 .= "AND es.starttime > TO_DATE(:".(sizeof($args)+3).", 'HH24:MI:SS DDMMYYYY') AND es.starttime < TO_DATE(:".(sizeof($args)+4).", 'HH24:MI:SS DDMMYYYY')";
                $where3 .= "AND r.starttimestamp > TO_DATE(:".(sizeof($args)+5).", 'HH24:MI:SS DDMMYYYY') AND r.starttimestamp < TO_DATE(:".(sizeof($args)+6).", 'HH24:MI:SS DDMMYYYY')";
                $where4 .= "AND xrf.starttime > TO_DATE(:".(sizeof($args)+7).", 'HH24:MI:SS DDMMYYYY') AND xrf.starttime < TO_DATE(:".(sizeof($args)+8).", 'HH24:MI:SS DDMMYYYY')";
                
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
                
                for ($i = 0; $i < 4; $i++) {
                    array_push($args, $st.$my);
                    array_push($args, $en.$my);
                }
            }
            
            
            # If not staff check they have access to data collection
            if (!$this->has_arg('visit') && !$this->staff) {
                $where .= " AND u.name=:".(sizeof($args)+1);
                $where2 .= " AND u.name=:".(sizeof($args)+2);
                $where3 .= " AND u.name=:".(sizeof($args)+3);
                $where4 .= " AND u.name=:".(sizeof($args)+4);
                
                for ($i = 0; $i < 4; $i++) {
                    $extj[$i] .= " INNER JOIN ispyb4a_db.proposal p ON p.proposalid = ses.proposalid INNER JOIN investigation@DICAT_RO i ON lower(i.visit_id) LIKE p.proposalcode||p.proposalnumber||'-'||ses.visit_number INNER JOIN investigationuser@DICAT_RO iu on i.id = iu.investigation_id INNER JOIN user_@DICAT_RO u on u.id = iu.user_id";
                    array_push($args, phpCAS::getUser());
                }
            }
            
            
            
            # View a single data collection
            if ($this->has_arg('id')) {
                $st = sizeof($args)+1;
                $where .= ' AND dc.datacollectionid=:'.$st;
                $where3 .= ' AND r.robotactionid=:'.($st+1);
                $where2 .= ' AND es.energyscanid=:'.($st+2);
                $where4 .= ' AND xrf.xfefluorescencespectrumid=:'.($st+3);
                for ($i = 0; $i < 4; $i++) array_push($args, $this->arg('id'));
            }
            
            
            # Search terms
            if ($this->has_arg('s')) {
                $st = sizeof($args) + 1;
                $where .= " AND (lower(dc.filetemplate) LIKE lower('%'||:$st||'%') OR lower(dc.imagedirectory) LIKE lower('%'||:".($st+1)."||'%'))";
                $where2 .= " AND (lower(es.comments) LIKE lower('%'||:".($st+2)."||'%') OR lower(es.element) LIKE lower('%'||:".($st+3)."||'%'))";
                $where3 .= ' AND r.robotactionid < 0';
                $where4 .= " AND lower(xrf.filename) LIKE lower('%'||:".($st+4)."||'%')";
                
                for ($i = 0; $i < 5; $i++) array_push($args, $this->arg('s'));
            }
            
            $tot = $this->db->pq("SELECT sum(tot) as t FROM (SELECT count(dc.datacollectionid) as tot FROM ispyb4a_db.datacollection dc
                INNER JOIN ispyb4a_db.blsession ses ON ses.sessionid = dc.sessionid
                $extj[0]
                WHERE $sess[0] $where
                
                UNION SELECT count(es.energyscanid) as tot FROM ispyb4a_db.energyscan es
                INNER JOIN ispyb4a_db.blsession ses ON ses.sessionid = es.sessionid
                $extj[1]
                WHERE $sess[1] $where2
                                
                UNION SELECT count(xrf.xfefluorescencespectrumid) as tot from ispyb4a_db.xfefluorescencespectrum xrf
                INNER JOIN ispyb4a_db.blsession ses ON ses.sessionid = xrf.sessionid
                $extj[3]
                WHERE $sess[3] $where4
                                
                UNION SELECT count(r.robotactionid) as tot FROM ispyb4a_db.robotaction r
                INNER JOIN ispyb4a_db.blsession ses ON ses.sessionid = r.blsessionid
                $extj[2]
                WHERE $sess[2]  $where3)", $args);
            $tot = $tot[0]['T'];
            
            $this->profile('after page count');
            
            $pgs = intval($tot/$pp);
            if ($tot % $pp != 0) $pgs++;

            $st = sizeof($args) + 1;
            array_push($args, $start);
            array_push($args, $end);

            $q = "SELECT outer.*
             FROM (SELECT ROWNUM rn, inner.*
             FROM (
             SELECT $extc ses.visit_number as vn, dc.kappastart as kappa, dc.phistart as phi, dc.startimagenumber as si, dc.experimenttype as dct, dc.datacollectiongroupid as dcg, dc.runstatus, dc.beamsizeatsamplex as bsx, dc.beamsizeatsampley as bsy, dc.overlap, 1 as flux, 1 as scon, 'a' as spos, 'a' as san, 'data' as type, dc.imageprefix as imp, dc.datacollectionnumber as run, dc.filetemplate, dc.datacollectionid as id, dc.numberofimages as ni, dc.imagedirectory as dir, dc.resolution, dc.exposuretime, dc.axisstart, dc.numberofimages as numimg, TO_CHAR(dc.starttime, 'DD-MM-YYYY HH24:MI:SS') as st, dc.transmission, dc.axisrange, dc.wavelength, dc.comments, 1 as epk, 1 as ein, dc.xtalsnapshotfullpath1 as x1, dc.xtalsnapshotfullpath2 as x2, dc.xtalsnapshotfullpath3 as x3, dc.xtalsnapshotfullpath4 as x4, dc.starttime as sta FROM ispyb4a_db.datacollection dc
             INNER JOIN ispyb4a_db.blsession ses ON ses.sessionid = dc.sessionid
             $extj[0]
             WHERE $sess[0] $where
                   
             UNION
             SELECT $extc ses.visit_number as vn, 1,1,1,'A',1,'A',1,1,1, 1, 1 as scon, 'A' as spos, 'A' as sn, 'edge' as type, es.jpegchoochfilefullpath, 1, 'A', es.energyscanid, 1, es.element, es.peakfprime, es.exposuretime, es.peakfdoubleprime, 1, TO_CHAR(es.starttime, 'DD-MM-YYYY HH24:MI:SS') as st, es.transmissionfactor, es.inflectionfprime, es.inflectionfdoubleprime, es.comments, es.peakenergy, es.inflectionenergy, 'A', 'A', 'A', 'A', es.starttime as sta FROM ispyb4a_db.energyscan es
            INNER JOIN ispyb4a_db.blsession ses ON ses.sessionid = es.sessionid
            $extj[1]
            WHERE $sess[1] $where2
                   
            UNION
            SELECT $extc ses.visit_number as vn, 1,1,1,'A',1,'A',1,1,1, 1, 1, 'A', 'A', 'mca' as type, 'A', 1, 'A', xrf.xfefluorescencespectrumid, 1, xrf.filename, 1, xrf.exposuretime, 1, 1, TO_CHAR(xrf.starttime, 'DD-MM-YYYY HH24:MI:SS') as st, xrf.beamtransmission, 1, xrf.energy, xrf.comments, 1, 1, 'A', 'A', 'A', 'A', xrf.starttime as sta FROM ispyb4a_db.xfefluorescencespectrum xrf
            INNER JOIN ispyb4a_db.blsession ses ON ses.sessionid = xrf.sessionid
            $extj[3]
            WHERE $sess[3] $where4
                   
            UNION
            SELECT $extc ses.visit_number as vn, 1,1,1,'A',1,'A',ROUND((CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*86400, 1),1,1, 1, 1, r.status, r.message, 'load' as type, r.actiontype, 1, 'A', r.robotactionid, 1,  r.samplebarcode, r.containerlocation, r.dewarlocation, 1, 1, TO_CHAR(r.starttimestamp, 'DD-MM-YYYY HH24:MI:SS') as st, 1, 1, 1, 'A', 1, 1, 'A', 'A', 'A', 'A', r.starttimestamp as sta FROM ispyb4a_db.robotaction r
            INNER JOIN ispyb4a_db.blsession ses ON ses.sessionid = r.blsessionid
            $extj[2]
            WHERE $sess[2] $where3
                 
                   
            ORDER BY sta DESC
             
            ) inner) outer
            WHERE outer.rn > :$st AND outer.rn <= :".($st+1);
            
            $dcs = $this->db->pq($q, $args);
            $this->profile('main query');            
            
            foreach ($dcs as $i => &$dc) {
                $dc['SN'] = 0;
                $dc['DI'] = 0;
                
                if ($this->has_arg('sid') || $this->has_arg('pjid')) $dc['VIS'] = $this->arg('prop').'-'.$dc['VN'];
                
                
                // Data collections
                if ($dc['TYPE'] == 'data') {
                    $nf = array(1 => array('AXISSTART'), 2 => array('RESOLUTION', 'TRANSMISSION', 'AXISRANGE'), 3 => array('EXPOSURETIME'), 4 => array('WAVELENGTH'));
                    $dc['DIR'] = preg_replace('/.*\/\d\d\d\d\/\w\w\d+-\d+\//', '', $dc['DIR']);
                    
                    $dc['BSX'] = round($dc['BSX']*1000);
                    $dc['BSY'] = round($dc['BSY']*1000);
                    
                    if (!$dc['DCT']) {
                        if ($dc['AXISRANGE'] == 0) $dc['DCT'] = 'Still Image';
                        if ($dc['AXISRANGE'] == 0 && $dc['NI'] > 1) $dc['DCT'] = 'Grid Scan';
                        if ($dc['OVERLAP'] != 0) $dc['DCT'] = 'Screening';
                        if ($dc['AXISRANGE'] > 0 && $dc['OVERLAP'] == 0) $dc['DCT'] = 'Data Collection';
                    }
                    
                    //$this->profile('dc');
                    
                // Edge Scans
                } else if ($dc['TYPE'] == 'edge') {
                    $dc['EPK'] = floatVal($dc['EPK']);
                    $dc['EIN'] = floatVal($dc['EIN']);
                    
                    # Transmission factor rather than transmission :(
                    $dc['TRANSMISSION'] *= 100;
                    
                    $nf = array(2 => array('EXPOSURETIME'), 2 => array('AXISSTART', 'RESOLUTION', 'TRANSMISSION'));
                    $this->profile('edge');  
                
                // MCA Scans
                } else if ($dc['TYPE'] == 'mca') {
                    # -- Move to ajax
                    /*$results = str_replace('.mca', '.results.dat', preg_replace('/(data\/\d\d\d\d\/\w\w\d+-\d+)/', '\1/processed/pymca', $dc['DIR']));
                    
                    $elements = array();
                    if (file_exists($results)) {
                        $dat = explode("\n",file_get_contents($results));
                        foreach ($dat as $i => $d) {
                            if ($i < 5) array_push($elements, $d);
                        }
                    }
                    
                    $dc['ELEMENTS'] = $elements;*/
                    $nf = array(2 => array('EXPOSURETIME', 'WAVELENGTH', 'TRANSMISSION'));
                    
                // Robot loads
                } else if ($dc['TYPE'] == 'load') $nf = array();
                
                
                $dc['AP'] = array(0,0,0,0,0,0,0,0);
                
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
        # Check whether diffraction and snapshot images exist
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
            
            $dct = $this->db->pq("SELECT p.proposalcode||p.proposalnumber||'-'||s.visit_number as vis, dc.datacollectionid as id, dc.startimagenumber, dc.filetemplate, dc.xtalsnapshotfullpath1 as x1, dc.xtalsnapshotfullpath2 as x2, dc.xtalsnapshotfullpath3 as x3, dc.xtalsnapshotfullpath4 as x4,dc.imageprefix as imp, dc.datacollectionnumber as run, dc.imagedirectory as dir, s.visit_number FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON s.sessionid=dc.sessionid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE $where", $ids);
                
            $this->profile('dc query');
                                   
            $dcs = array();
            foreach ($dct as $d) $dcs[$d['ID']] = $d;
            
            $out = array();
            
            foreach ($dcs as $dc) {
                //$dc['VIS'] = $this->arg('prop').'-'.$dc['VISIT_NUMBER'];
                
                $sn = 0;
                $images = array();
                foreach (array('X1', 'X2', 'X3', 'X4') as $j => $im) {
                    if (file_exists($dc[$im])) {
                        array_push($images, $j);
                        if ($im == 'X1') {
                            if (file_exists(str_replace('.png', 't.png', $dc[$im]))) $sn = 1;
                        }
                    }
                    unset($dc[$im]);
                }

                $dc['DIR'] = $this->ads($dc['DIR']);
                $dc['X'] = $images;
                
                $di = str_replace($dc['VIS'], $dc['VIS'].'/jpegs', $dc['DIR']).str_replace('.cbf', '.jpeg',preg_replace('/#+/', sprintf('%0'.substr_count($dc['FILETEMPLATE'], '#').'d', $dc['STARTIMAGENUMBER']),$dc['FILETEMPLATE']));
                
                $this->profile('diffraction image');
                $die = 0;
                if (file_exists($di)) $die = 1;
            
                array_push($out, array($dc['ID'], array($die,$images,$sn)));
            }
            $this->_output($out);
        }
        

        # ------------------------------------------------------------------------
        # Get sample
        function _get_sample_flux() {
            $ids = array();
            if (array_key_exists('ids', $_POST)) {
                foreach ($_POST['ids'] as $n => $i) {
                    if (preg_match('/^\d+$/', $i)) {
                        array_push($ids,array($i, $_POST['tys'][$n]));
                    }
                }
            }
                   
            if (!sizeof($ids)) {
                $this->_output(array());
                return;
            }
            
            $tables = array('data' => array('datacollection','datacollectionid'),
                            'mca' => array('xfefluorescencespectrum','xfefluorescencespectrumid'),
                            'edge' => array('blsample_has_energyscan', 'energyscanid'),
                            'robot' => array('robotaction', 'robotactionid'),
                            );
                          
            $smps = array();
            foreach ($ids as $r) {
                list($id, $ty) = $r;
                
                if (array_key_exists($ty, $tables)) {
                    $c = $tables[$ty];
                    $smp = $this->db->pq("SELECT cr.spacegroup as sg, bls.blsampleid as sid, c.samplechangerlocation as scon, bls.location as spos, bls.name as san FROM $c[0] d INNER JOIN ispyb4a_db.blsample bls ON d.blsampleid = bls.blsampleid INNER JOIN ispyb4a_db.container c ON bls.containerid = c.containerid INNER JOIN ispyb4a_db.crystal cr ON cr.crystalid = bls.crystalid WHERE d.$c[1]=:1", array($id));
                            
                    $smps[$id] = sizeof($smp) ? $smp[0] : array('SCON' => '', 'SPOS' => '', 'SAN' => '', 'SID' => '', 'SG' => '');
                    $smps[$id]['TY'] = $ty;
                }
                                   
            }
                                   
            $this->_output($smps);
        }
        
        
        # ------------------------------------------------------------------------
        # Autoprocessing Status
        function _ap_status() {
            session_write_close();
            
            $where = array();
            
            #$this->db->set_debug(True);
            #$this->db->set_stats(True);
            $this->profile = True;
            
            if (!($this->has_arg('visit') || $this->has_arg('prop'))) $this->_error('No visit or proposal specified');
            
            $ids = array();
            if (array_key_exists('ids', $_POST)) {
                foreach ($_POST['ids'] as $i) {
                    if (preg_match('/^\d+$/', $i)) {
                        array_push($ids,$i);
                        array_push($where,'dc.datacollectionid=:'.sizeof($ids));
                    }
                }
            }
                   
            if (!sizeof($ids)) {
                $this->_output(array());
                return;
            }
                                
            $where = '('.implode(' OR ', $where).')';
            
            $this->profile('start');
            
            $aps1 = array(
                         array('simple_strategy/', 'strategy_native.log', 'Phi start'),
                         array('edna/', 'summary.html', 'Selected spacegroup'),
                         );
            $aps2 = array(
                         array('fast_dp/', 'fast_dp.log', 'dF/F'),
                         
                         array('xia2/2da-run/', 'xia2.txt' , 'dF/F'),
                         array('xia2/3da-run/', 'xia2.txt' , 'dF/F'),
                         array('xia2/3daii-run/', 'xia2.txt' , 'dF/F'),
                         
                         array('fast_ep/', 'fast_ep.log', 'Best spacegroup'),
                         array('fast_dp/dimple/', 'refmac5_restr.log', 'Cruickshanks'),
                         );
            
            $out = array();
            
            # DC Details
            $dct = $this->db->pq("SELECT dc.overlap, dc.blsampleid, dc.datacollectionid as id, dc.startimagenumber, dc.filetemplate, dc.xtalsnapshotfullpath1 as x1, dc.xtalsnapshotfullpath2 as x2, dc.xtalsnapshotfullpath3 as x3, dc.xtalsnapshotfullpath4 as x4,dc.imageprefix as imp, dc.datacollectionnumber as run, dc.imagedirectory as dir, s.visit_number FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON s.sessionid=dc.sessionid WHERE $where", $ids);
                
            $this->profile('dc query');
                                   
            $dcs = array();
            foreach ($dct as $d) $dcs[$d['ID']] = $d;
                                   
            foreach ($dcs as $dc) {
                $flx = $this->db->pq("SELECT * FROM (SELECT measuredintensity as flux from ispyb4a_db.image WHERE datacollectionid=:1 ORDER BY imagenumber) WHERE rownum = 1", array($dc['ID']));
                
                $dc['FLUX'] =  sizeof($flx) ? $flx[0]['FLUX'] : 'N/A';
                $this->profile('flux query');

                $this->profile('qend');

                $dc['VIS'] = $this->arg('prop').'-'.$dc['VISIT_NUMBER'];
                
                $dc['DIR'] = $this->ads($dc['DIR']);
                $root = str_replace($dc['VIS'], $dc['VIS'].'/processed', $dc['DIR']).$dc['IMP'].'_'.$dc['RUN'].'_'.'/';
            
                $this->profile('filestart');
                if ($dc['OVERLAP'] == 0) {
                    $aps = $aps2;
                    $apr = array(0,0);
                } else {
                    $aps = $aps1;
                    $apr = array();
                }
                foreach ($aps as $ap) {
                    # 0: didnt run, 1: running, 2: success, 3: failed
                    $val = 0;

                    $rt = $root.$ap[0];
                    if (file_exists($rt)) {
                        $val = 1;
                        $logs = glob($root.$ap[0].'*'.$ap[1]);
                        
                        if (sizeof($logs)) $log = $logs[0];
                        else $log = '';
                        
                        if (is_readable($log)) {
                            //$file = file_get_contents($log);
                            $val = 3;
                            //if (strpos($file, $ap[2]) !== False) $val = 2;
                            exec('grep -q "'.$ap[2].'" '.$log, $out,$ret);
                            if ($ret == 0) $val = 2;
                        }
                    } //else $val = 3;
                    
                    array_push($apr, $val);
                    
                }
            
                if ($dc['OVERLAP'] != 0) for ($i = 0; $i < 5; $i++) array_push($apr, 0);
                $this->profile('fileend');
                
                array_push($out, array($dc['ID'], $apr, array('FLUX' => $dc['FLUX'] ? sprintf('%.2e', $dc['FLUX']) : 'N/A')));
            }
        
            $this->profile('end');
            $this->_output($out);
        }
        
        
        
        # ------------------------------------------------------------------------
        # Edge Scan Data
        function _edge() {
            session_write_close();
            if (!$this->has_arg('id')) {
                $this->_error('No data collection id specified');
                return;
            }
            
            $info = $this->db->pq('SELECT jpegchoochfilefullpath as pth FROM ispyb4a_db.energyscan WHERE energyscanid=:1', array($this->arg('id')));
            if (sizeof($info) == 0) {
                $this->_error('No data for that collection id');
                return;
            }
            
            $ch = str_replace('.png', '', $info[0]['PTH']);
            
            $data = array(array(), array(), array());
            if (file_exists($ch)) {
                $dat = explode("\n",file_get_contents($ch));
                
                foreach ($dat as $i => $d) {
                    if ($d) {
                        list($x, $y) = explode(' ', $d);
                        array_push($data[0], array(floatval($x), intval($y)));
                    }
                }
                
                $dat = explode("\n",file_get_contents($ch.'.efs'));
                foreach ($dat as $i => $d) {
                    if ($d) {
                        list($x, $y, $y2) = preg_split('/\s+/', trim($d));
                        array_push($data[1], array(floatval($x), intval($y)));
                        array_push($data[2], array(floatval($x), intval($y2)));
                    }
                }
            }
            
            $this->_output($data);
        }
        
        
        # ------------------------------------------------------------------------
        # MCA Scan Data
        function _mca() {
            session_write_close();
            if (!$this->has_arg('id')) {
                $this->_error('No data collection id specified');
                return;
            }
            
            $info = $this->db->pq('SELECT filename as dir,energy,scanfilefullpath as dat FROM ispyb4a_db.xfefluorescencespectrum WHERE xfefluorescencespectrumid=:1', array($this->arg('id')));
            if (sizeof($info) == 0) {
                $this->_error('No data for that spectrum id');
                return;
            }
            
            $info = $info[0];
            
            
            $data = array(array(),array());
            if (file_exists($info['DAT'])) {
                $dat = explode("\n",file_get_contents($info['DAT']));

                foreach ($dat as $i => $d) {
                    if ($i >2 && $d) {
                        list($e, $v) = preg_split('/\s+/', trim($d));
                        if ($i % 2 == 1) {
                            if (floatval($e) <= $info['ENERGY']) {
                                if (floatval($e) > ($info['ENERGY'] - 1100)) array_push($data[1], array(floatval($e), floatval($v)));
                                else array_push($data[0], array(floatval($e), floatval($v)));
                            }
                        }
                    }
                }
                
            }
            
            
            # pymca
            $results = str_replace('.mca', '.results.dat', preg_replace('/(data\/\d\d\d\d\/\w\w\d+-\d+)/', '\1/processed/pymca', $info['DIR']));
            
            $el_to_en = json_decode(file_get_contents('tables/energies.json'), true);
            $elements = array();
            $el_no_match = array();
            $max_counts = 0;
            
            if (file_exists($results)) {
                $dat = explode("\n",file_get_contents($results));
                foreach ($dat as $i => $d) {
                    if ($i < 5) {
                        $l = explode(' ', $d);
                        if ($i == 0) $max_counts = floatval($l[1]);
                        if (array_key_exists($l[0], $el_to_en)) {
                            $els = $el_to_en[$l[0]];
                            if (($els[sizeof($els)-1]*1000) < ($info['ENERGY'] - 1000))
                                $elements[$l[0]] = array(array_map('floatval', $els), floatval($l[1]), floatval($l[2]));
                        } else array_push($el_no_match, $l[0]);
                    }
                }
            }
            array_push($data, $elements);
            array_push($data, $el_no_match);
            array_push($data, $max_counts);
            
            $max = 0;
            foreach ($data[0] as $d) {
                if ($d[1] > $max) $max = $d[1];
            }
            
            array_push($data, $max);
            
            $this->_output($data);
        }
        
        
        # ------------------------------------------------------------------------        
        # Strategies for a data collection
        function _dc_strategies() {
            if (!$this->has_arg('id')) {
                $this->_error('No data collection id specified');
                return;
            }
            
            $rows = $this->db->pq('SELECT dc.datacollectionid as dcid, s.comments, dc.transmission as dctrn, dc.wavelength as lam, dc.imagedirectory imd, dc.imageprefix as imp, dc.comments as dcc, dc.blsampleid as sid, sl.spacegroup as sg, sl.unitcell_a as a, sl.unitcell_b as b, sl.unitcell_c as c, sl.unitcell_alpha as al, sl.unitcell_beta as be, sl.unitcell_gamma as ga, s.shortcomments as com, sssw.axisstart as st, sssw.exposuretime as time, sssw.transmission as tran, sssw.oscillationrange as oscran, sssw.resolution as res, sssw.numberofimages as nimg FROM ispyb4a_db.screeningstrategy st INNER JOIN ispyb4a_db.screeningoutput so on st.screeningoutputid = so.screeningoutputid INNER JOIN ispyb4a_db.screening s on so.screeningid = s.screeningid INNER JOIN ispyb4a_db.screeningstrategywedge ssw ON ssw.screeningstrategyid = st.screeningstrategyid INNER JOIN ispyb4a_db.screeningstrategysubwedge sssw ON sssw.screeningstrategywedgeid = ssw.screeningstrategywedgeid INNER JOIN ispyb4a_db.screeningoutputlattice sl ON sl.screeningoutputid = st.screeningoutputid INNER JOIN ispyb4a_db.datacollection dc on s.datacollectionid = dc.datacollectionid WHERE s.datacollectionid = :1', array($this->arg('id')));
        
            $output = array('EDNA' => array('CELL' => array(), 'STRATS' => array()), 'Mosflm' => array('CELL' => array(), 'STRATS' => array()));
            $nf = array('A', 'B', 'C', 'AL', 'BE', 'GA');
            foreach ($rows as &$r) {
                $t = strpos($r['COM'], 'EDNA') === false ? 'Mosflm' : 'EDNA'; 
                
                foreach ($r as $k => &$v) {
                    if (in_array($k, $nf)) {
                        $v = number_format(floatval($v), 2);
                        $output[$t]['CELL'][$k] = $v;
                        unset($r[$k]);
                    }
                    
                    if ($k == 'TRAN') $v = number_format($v, 1);
                    if ($k == 'TIME') $v = number_format($v, 3);
                    if ($k == 'OSCRAN') $v = number_format($v, 2);
                    if ($k == 'RES') $v = number_format($v, 2);
                }
                
                $output[$t]['CELL']['SG'] = $r['SG'];
                unset($r['SG']);
                
                $r['COM'] = str_replace('EDNA', '', $r['COM']);
                $r['COM'] = str_replace('Mosflm ', '', $r['COM']);
                
                $r['VPATH'] = join('/', array_slice(explode('/', $r['IMD']),0,6));
                list(,,$r['BL']) = explode('/', $r['IMD']);
                $r['DIST'] = $this->_r_to_dist($r['BL'], $r['LAM'], $r['RES']);
                $r['ATRAN'] = round($r['TRAN']/100.0*$r['DCTRN'],1);
                list($r['NTRAN'], $r['NEXP']) = $this->_norm_et($r['ATRAN'], $r['TIME']);
                $r['AP'] = $this->_get_ap($r['DCC']);
                
                array_push($output[$t]['STRATS'], $r);
            }
                
            $this->_output(array(sizeof($rows), $output));
        }
        
        # ------------------------------------------------------------------------        
        # Normalise transmission fo 25hz data collection
        function _norm_et($t, $e) {
            if ($t < 100 && $e > 0.04) {
                $f = $e / 0.04;
                $maxe = 0.04;
                $maxt = ($e / 0.04) * $t;
                
                if ($maxt > 100) {
                    $maxe *= $maxt/100;
                    $maxt = 100;
                }
                return array(number_format($maxt,1), number_format($maxe,3));
            } else {
                return array($t, $e);
            }
        
        }
        
        # ------------------------------------------------------------------------        
        # Convert resolution to detector distance
        function _r_to_dist($bl, $lambda, $r) {
            $diam = $bl == 'i04-1' ? 252.5 : 415;
            $b=$lambda/(2*$r);
            $d=2*asin($b);
            $f=2*tan($d);
            
            return number_format($diam/$f, 2);
        }
        
        # ------------------------------------------------------------------------        
        # Work out which aperture is selected
        function _get_ap($com) {
            $aps = array('Aperture: Large'=>'LARGE_APERTURE',
                         'Aperture: Medium'=>'MEDIUM_APERTURE',
                         'Aperture: Small'=>'SMALL_APERTURE',
                         'Aperture: 10'=>'In_10',
                         'Aperture: 20'=>'In_20',
                         'Aperture: 30'=>'In_30',
                         'Aperture: 50'=>'In_50',
                         'Aperture: 70'=>'In_70');
            
            $app = '';
            foreach ($aps as $k => $v) {
                if (strpos($com, $k) !== False) $app = $v;
            }
            
            return $app;
        }
        
        
        # ------------------------------------------------------------------------
        # Auto processing for a data collection
        function _dc_auto_processing() {
            if (!$this->has_arg('id')) {
                $this->_error('No data collection id specified');
                return;
            }
        
            $rows = $this->db->pq('SELECT app.autoprocprogramid,app.processingcommandline as type, apss.ntotalobservations as ntobs, apss.ntotaluniqueobservations as nuobs, apss.resolutionlimitlow as rlow, apss.resolutionlimithigh as rhigh, apss.scalingstatisticstype as shell, apss.rmeasalliplusiminus as rmeas, apss.rmerge, apss.completeness, apss.anomalouscompleteness as anomcompleteness, apss.anomalousmultiplicity as anommultiplicity, apss.multiplicity, apss.meanioversigi as isigi, ap.spacegroup as sg, ap.refinedcell_a as cell_a, ap.refinedcell_b as cell_b, ap.refinedcell_c as cell_c, ap.refinedcell_alpha as cell_al, ap.refinedcell_beta as cell_be, ap.refinedcell_gamma as cell_ga FROM ispyb4a_db.autoprocintegration api INNER JOIN ispyb4a_db.autoprocscaling_has_int aph ON api.autoprocintegrationid = aph.autoprocintegrationid INNER JOIN ispyb4a_db.autoprocscaling aps ON aph.autoprocscalingid = aps.autoprocscalingid INNER JOIN ispyb4a_db.autoproc ap ON aps.autoprocid = ap.autoprocid INNER JOIN ispyb4a_db.autoprocscalingstatistics apss ON apss.autoprocscalingid = aph.autoprocscalingid INNER JOIN ispyb4a_db.autoprocprogram app ON api.autoprocprogramid = app.autoprocprogramid WHERE api.datacollectionid = :1', array($this->arg('id')));
            
            $types = array('fast_dp' => 'Fast DP', '-3da ' => 'XIA2 3da', '-2da ' => 'XIA2 2da', '-3daii ' => 'XIA2 3daii');
            
            $dts = array('cell_a', 'cell_b', 'cell_c', 'cell_al', 'cell_be', 'cell_ga');
            $dts2 = array('rlow', 'rhigh');
            
            $output = array();
            foreach ($rows as &$r) {
                if (!array_key_exists($r['AUTOPROCPROGRAMID'], $output)) $output[$r['AUTOPROCPROGRAMID']] = array('SHELLS' => array(), 'CELL' => array());
                
                $shell = array();
                foreach ($r as $k => &$v) {
                    if ($k == 'TYPE') {
                        foreach ($types as $id => $name) {
                            if (strpos($v, $id)) {
                                $v = $name;
                                break;
                            }
                        }
                    }

                    if ($k == 'ISIGI') $v = number_format($v, 1);
                    if ($k == 'RMERGE') $v = number_format($v, 3);
                    if ($k == 'RMEAS') $v = number_format($v, 3);
                    if ($k == 'COMPLETENESS') $v = number_format($v, 1);
                    if ($k == 'MULTIPLICITY') $v = number_format($v, 1);
                    if ($k == 'ANOMCOMPLETENESS') $v = number_format($v, 1);
                    if ($k == 'ANOMMULTIPLICITY') $v = number_format($v, 1);
                    
                    if ($k == 'AUTOPROCPROGRAMID' | $k == 'SHELL') {
                        continue;
                    
                    } else if ($k == 'TYPE') {
                        $output[$r['AUTOPROCPROGRAMID']]['TYPE'] = $v;
                        
                    } else if ($k == 'SG') {
                        $output[$r['AUTOPROCPROGRAMID']]['SG'] = $v;
                    
                    } else if (in_array(strtolower($k), $dts2)) {
                        $shell[$k] = number_format($v, 2);
                    
                    } else if (in_array(strtolower($k), $dts)) {
                        $v = number_format($v, 2);
                        $output[$r['AUTOPROCPROGRAMID']]['CELL'][$k] = $v;
                    } else {
                        $shell[$k] = $v;
                    }
                }
                
                $output[$r['AUTOPROCPROGRAMID']]['AID'] = $r['AUTOPROCPROGRAMID'];
                $output[$r['AUTOPROCPROGRAMID']]['SHELLS'][$r['SHELL']] = $shell;
            }
                  
            #$this->_output($rows);
            $this->_output(array(sizeof($output), $output));
        }
        
        
        # ------------------------------------------------------------------------
        # Results from downstream processing
        function _dc_downstream() {
            $ap = array('Fast EP' => array('fast_ep', array('sad.mtz', 'sad_fa.pdb')),
                        'MrBUMP' => array('auto_mrbump', array('PostMRRefine.pdb', 'PostMRRefine.mtz')),
                        #'Dimple' => array('fast_dp', array('dimple_refined.pdb', 'dimple_map.mtz')),
                        'Dimple' => array('fast_dp', array('dimple/final.pdb', 'dimple/final.mtz'))
                        );
            
            list($info) = $this->db->pq('SELECT dc.imageprefix as imp, dc.datacollectionnumber as run, dc.imagedirectory as dir, p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON s.sessionid=dc.sessionid INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE dc.datacollectionid=:1', array($this->arg('id')));
            
            $info['DIR'] = $this->ads($info['DIR']);
            $data = array();
            
            foreach($ap as $n => $p) {
                $dat = array();
                
                $root = str_replace($info['VIS'], $info['VIS'] . '/processed', $info['DIR']).$info['IMP'].'_'.$info['RUN'].'_'.'/'.$p[0].'/';
                
                $file = $root . $p[1][0];
                if (file_exists($file)) {
                    $dat['TYPE'] = $n;
                    
                    # Fast EP
                    if ($n == 'Fast EP') {
                        # parse pdb file
                        
                        $ats = array();
                        $p1 = array();
                        $p2 = array();
                        
                        $pdb = file_get_contents($root . $p[1][1]);
                        foreach (explode("\n", $pdb) as $l) {
                            if (strpos($l,'HETATM') !== False) {
                                $parts = preg_split('/\s+/', $l);
                                array_push($ats, array($parts[1], $parts[5], $parts[6], $parts[7], $parts[8]));
                            }
                            
                        }
                        
                        $dat['ATOMS'] = array_slice($ats, 0, 5);
                        
                        if (file_exists($root.'/sad.lst')) {
                            $lst = file_get_contents($root.'/sad.lst');
                            $graph_vals = 0;
                            $gvals = array();
                            foreach (explode("\n", $lst) as $l) {
                                if (strpos($l, 'Estimated mean FOM and mapCC as a function of resolution') !== False) {
                                    $graph_vals = 1;
                                }
                                
                                if ($graph_vals && $graph_vals < 5) {
                                    array_push($gvals, $l);
                                    $graph_vals++;
                                }
                                
                                if (preg_match('/ Estimated mean FOM = (\d+.\d+)\s+Pseudo-free CC = (\d+.\d+)/', $l, $mat)) {
                                    $dat['FOM'] = floatval($mat[1]);
                                    $dat['CC'] = floatval($mat[2]);
                                }
                            }
                            
                            
                            if (sizeof($gvals) > 0) {
                                $x = array_map('floatval', array_slice(explode(' - ', $gvals[1]), 1));
                                $y = array_map('floatval', array_slice(preg_split('/\s+/', $gvals[2]), 1));
                                $y2 = array_map('floatval', array_slice(preg_split('/\s+/', $gvals[3]), 1));

                                foreach ($x as $i => $v) {
                                    array_push($p1, array($v, $y[$i]));
                                    array_push($p2, array($v, $y2[$i]));
                                }
                            }
                            
                        }
                        $dat['PLOTS']['FOM'] = $p1;
                        $dat['PLOTS']['CC'] = $p2;
                        array_push($data, $dat);
                        
                    # Dimple
                    } else if ($n == 'Dimple') {
                        //$pth = glob($root.'/EDApplication_*.log');
                        $lfs = glob($root . '/dimple/*refmac5_restr.log');
                        
                        if (sizeof($lfs)) $lf = $lfs[0];
                        else $lf = '';
                        
                        //if (sizeof($pth) > 0) {
                        if (file_exists($lf)) {
                            $log = file_get_contents($lf);
                         
                            $refmac = 0;
                            $stats = array();
                            $plot = 0;
                            $plots = array();
                            foreach (explode("\n", $log) as $l) {
                                if ($plot == 1) {
                                    $plot++;
                                    continue;
                                }
                                
                                if (strpos(trim($l), '$TEXT:Result: $$ Final results $$') !== False) {
                                    $refmac = 1;
                                    $stats = array();
                                    continue;
                                }
                                if (strpos(trim($l), '$$') !== False) $refmac = 0;
                                
                                if ($refmac) {
                                    array_push($stats, preg_split('/\s\s+/', trim($l)));
                                }
                                
                                if (strpos(trim($l), 'Ncyc    Rfact    Rfree') !== False) {
                                    $plot = 1;
                                    $plots = array();
                                    continue;
                                }
                                
                                if (strpos(trim($l), '$$') !== False) $plot = 0;
                                    
                                if ($plot) {
                                    array_push($plots, preg_split('/\s+/', trim($l)));
                                }
                            }
                            
                            $plts = array('RVC'=>array(), 'FVC'=>array(), 'RVR'=>array());
                            foreach ($plots as $p) {
                                $p = array_map('floatval', $p);
                                array_push($plts['RVC'], array($p[0], $p[1]));
                                array_push($plts['FVC'], array($p[0], $p[2]));
                            }
                            
                            $peaks = glob($root . '/dimple/*find-blobs.log');
                            $pklist = array();
                            if (sizeof($peaks)) {
                                $pk = $peaks[0];
                                if (file_exists($pk)) {
                                    $pks = explode("\n", file_get_contents($pk));
                                    foreach ($pks as $p) {
                                        if (strpos($p, '#') === 0) {
                                            array_push($pklist, array(floatval(substr($p, 40,7)), floatval(substr($p, 48,7)), floatval(substr($p, 56,7)), floatval(substr($p, 29,5))));
                                        }
                                    }
                                }
                                
                            }
                            
                            array_unshift($stats[0], 'Parameter');
                            $dat['STATS'] = $stats;
                            $dat['PLOTS'] = $plts;
                            $dat['PKLIST'] = $pklist;
                            
                            $blobs = glob($root .'/dimple/blob*v*.png');
                            $dat['BLOBS'] = sizeof($blobs)/3;
                            
                            array_push($data, $dat);
                        }
                    }
                    
                    
                }
                
            }
            
            $this->_output($data);
        }
        
        
        # ------------------------------------------------------------------------
        # Image quality indicators from distl
        function _image_qi() {
            if (!$this->has_arg('id')) $this->_error('No data collection id specified');
            
            session_write_close();
            $iqs = array(array(), array(), array());
            $imqs = $this->db->pq('SELECT im.imagenumber as nim, imq.method2res as res, imq.spottotal as s, imq.goodbraggcandidates as b FROM ispyb4a_db.image im INNER JOIN ispyb4a_db.imagequalityindicators imq ON imq.imageid = im.imageid AND im.datacollectionid=:1 ORDER BY imagenumber', array($this->arg('id')));
            
            foreach ($imqs as $imq) {
                array_push($iqs[0], array(intval($imq['NIM']), intval($imq['S'])));
                array_push($iqs[1], array(intval($imq['NIM']), intval($imq['B'])));
                array_push($iqs[2], array(intval($imq['NIM']), floatval($imq['RES'])));
            }

            $this->_output($iqs);
        }

                
        # ------------------------------------------------------------------------
        # Flag a data collection
        function _flag() {
            if (!$this->has_arg('t')) $this->_error('No data type specified');
            if (!$this->arg('id')) $this->_error('No data collection id specified');
            
            $types = array('data' => array('datacollection', 'datacollectionid'),
                           'edge' => array('energyscan', 'energyscanid'),
                           'mca' => array('xfefluorescencespectrum', 'xfefluorescencespectrumid'),
                           );
            
            if (!array_key_exists($this->arg('t'), $types)) $this->_error('No such data type');
            $t = $types[$this->arg('t')];
            
            $com = $this->db->pq('SELECT comments from ispyb4a_db.'.$t[0].' WHERE '.$t[1].'=:1', array($this->arg('id')));
            
            if (!sizeof($com)) $this->_error('No such data collection');
            else $com = $com[0]['COMMENTS'];
            
            if (strpos($com, '_FLAG_') === false) {
                $this->db->pq("UPDATE ispyb4a_db.$t[0] set comments=comments||' _FLAG_' where $t[1]=:1", array($this->arg('id')));
                $this->_output(1);
            } else {
                $com = str_replace(' _FLAG_', '', $com);
                $this->db->pq("UPDATE ispyb4a_db.$t[0] set comments=:1 where $t[1]=:2", array($com, $this->arg('id')));
                
                $this->_output(0);
            }
        }
        
        
        # ------------------------------------------------------------------------
        # Update comment for a data collection
        function _set_comment() {
            if (!$this->has_arg('t')) $this->_error('No data type specified');
            if (!$this->arg('id')) $this->_error('No data collection id specified');
            if (!$this->arg('value')) $this->_error('No comment specified');
            
            $types = array('data' => array('datacollection', 'datacollectionid'),
                           'edge' => array('energyscan', 'energyscanid'),
                           'mca' => array('xfefluorescencespectrum', 'xfefluorescencespectrumid'),
                           );
            
            if (!array_key_exists($this->arg('t'), $types)) $this->_error('No such data type');
            $t = $types[$this->arg('t')];
            
            $com = $this->db->pq('SELECT comments from ispyb4a_db.'.$t[0].' WHERE '.$t[1].'=:1', array($this->arg('id')));
            
            if (!sizeof($com)) $this->_error('No such data collection');
            
            $this->db->pq("UPDATE ispyb4a_db.$t[0] set comments=:1 where $t[1]=:2", array($this->arg('value'), $this->arg('id')));
            
            print $this->arg('value');
        }
        
        
        # Plot R_d for fast_dp
        function _rd() {
            if (!$this->has_arg('id')) $this->_error('No data collection id specified');
            if (!$this->has_arg('aid')) $this->_error('No auto processing id specified');
            
            $info = $this->db->pq("SELECT appa.filename,appa.filepath,appa.filetype FROM ispyb4a_db.autoprocintegration api INNER JOIN ispyb4a_db.autoprocscaling_has_int aph ON api.autoprocintegrationid = aph.autoprocintegrationid INNER JOIN ispyb4a_db.autoprocscaling aps ON aph.autoprocscalingid = aps.autoprocscalingid INNER JOIN ispyb4a_db.autoproc ap ON aps.autoprocid = ap.autoprocid INNER JOIN ispyb4a_db.autoprocprogram app ON api.autoprocprogramid = app.autoprocprogramid INNER JOIN ispyb4a_db.autoprocprogramattachment appa ON appa.autoprocprogramid = app.autoprocprogramid WHERE api.datacollectionid = :1 AND api.autoprocprogramid=:2 AND appa.filetype LIKE 'Log'", array($this->arg('id'), $this->arg('aid')));
            
            if (!sizeof($info)) $this->_error('The specified auto processing doesnt exist');
            else $info = $info[0];
                
            $file = $info['FILEPATH'].'/'.str_replace('fast_dp.log', 'xdsstat.log', $info['FILENAME']);
            
            $rows = array();
            if (file_exists($file)) {
                $log = file_get_contents($file);
                
                $start = 0;
                foreach (explode("\n", $log) as $l) {
                    if (strpos(trim($l), 'Framediff #refs R_d n-notfriedel Rd-notfriedel n-friedel Rd-friedel dummy $$') !== False) {
                        $start = 1;
                    }
                    
                    if ($start) $start++;
                    
                    if (strpos(trim($l), '$$') !== False && $start > 4) {
                        $start = 0;
                    }
                    
                    if ($start > 3) {
                        $start++;
                        if (trim($l)) {
                            $f = preg_split('/\s+/', trim($l));
                            array_push($rows, array(intval($f[0]), floatval($f[2])));
                        }
                    }
                    
                }
            }
            
            $this->_output($rows);
            
        }
        
        
        
        function _get_symmetry() {
            if (!($this->has_arg('a') && $this->has_arg('b') && $this->has_arg('c') && $this->has_arg('al') && $this->has_arg('be') && $this->has_arg('ga') && $this->has_arg('sg'))) $this->_error('Missing parameters');
            
            exec('./symtry.sh '.$this->arg('a').' '.$this->arg('b').' '.$this->arg('c').' '.$this->arg('al').' '.$this->arg('be').' '.$this->arg('ga').' '.$this->arg('sg'), $ret);
            
            $matrices = array();
            foreach ($ret as $l) {
                $parts = array_map('floatval', explode(' ', $l));
                array_push($matrices, array(array_slice($parts, 0, 4),
                                            array_slice($parts, 4, 4),
                                            array_slice($parts, 8, 4))
                           );
            }
            
            $this->_output($matrices);
        }
    }

?>