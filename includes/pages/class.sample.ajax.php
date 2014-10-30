<?php

    class Ajax extends AjaxBase {
        
        var $arg_list = array('iDisplayStart' => '\d+',
                              'iDisplayLength' => '\d+',
                              'iSortCol_0' => '\d+',
                              'sSortDir_0' => '\w+',
                              'sSearch' => '\w+',
                              'prop' => '\w\w\d+',
                              'term' => '\w+',
                              'pid' => '\d+',
                              'sid' => '\d+',
                              'cid' => '\d+',
                              'value' => '.*',
                              'ty' => '\w+',
                              't' => '\w+',
                              'pjid' => '\d+',
                              'imp' => '\d',
                              'existing_pdb' => '\d+',
                              'pdb_code' => '\w\w\w\w',
                              'pdbid' => '\d+',
                              'visit' => '\w+\d+-\d+',
                              'array' => '\d',
                              'name' => '.*',
                              'acronym' => '([\w-])+',
                              'seq' => '\w+',
                              'mass' => '\d+(.\d+)',
                              'sort_by' => '\w+',
                              'order' => '\w+',
                               );
        
        var $dispatch = array('samples' => '_samples',
                              'proteins' => '_proteins',
                              'update' => '_update_sample',
                              'updatep' => '_update_protein',
                              'pdbs' => '_get_pdbs',
                              'addpdb' => '_add_pdb',
                              'rempdb' => '_remove_pdb',
                              'addp' => '_add_protein',
                              );
        
        var $def = 'samples';
        #var $profile = True;
        #var $debug = True;
        #var $explain = True;
        #var $stats = True;
        
        
        # ------------------------------------------------------------------------
        # List of samples for a proposal
        function _samples() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            
            $args = array($this->proposalid);
            $where = 'pr.proposalid=:1';
            $having = '';
            $join = '';
            
            # For a specific project
            if ($this->has_arg('pjid')) {
                $args = array($this->arg('pjid'));
                $where = '(pj.projectid=:'.sizeof($args).')';
                $join = ' LEFT OUTER JOIN ispyb4a_db.project_has_blsample pj ON pj.blsampleid=b.blsampleid';
                
                if (!$this->staff) {
                    $join .= " INNER JOIN ispyb4a_db.blsession ses ON ses.proposalid = p.proposalid INNER JOIN investigation@DICAT_RO i ON lower(i.visit_id) LIKE p.proposalcode || p.proposalnumber || '-' || ses.visit_number INNER JOIN investigationuser@DICAT_RO iu on i.id = iu.investigation_id inner join user_@DICAT_RO u on u.id = iu.user_id";
                    $where .= " AND u.name=:".(sizeof($args)+1);
                    array_push($args, phpCAS::getUser());
                }
                
                if ($this->has_arg('imp')) {
                    if ($this->arg('imp')) {
                        array_push($args, $this->arg('pjid'));
                        $join .= ' LEFT OUTER JOIN ispyb4a_db.project_has_protein pji ON pji.proteinid=pr.proteinid';
                        $where = preg_replace('/\(pj/', '(pji.projectid=:'.sizeof($args).' OR pj', $where);
                    }
                }
            }
            
            # For a specific protein
            if ($this->has_arg('pid')) {
                $where .= ' AND pr.proteinid=:'.(sizeof($args)+1);
                array_push($args, $this->arg('pid'));
            }
            
            # For a specific container
            if ($this->has_arg('cid')) {
                $where .= ' AND c.containerid=:'.(sizeof($args)+1);
                array_push($args, $this->arg('cid'));
            }
            
            # For a particular sample
            if ($this->has_arg('sid')) {
                $where .= ' AND b.blsampleid=:'.(sizeof($args)+1);
                array_push($args, $this->arg('sid'));                
            }
            
            
            # For a visit
            if ($this->has_arg('visit')) {
                $info = $this->db->pq("SELECT s.beamlinename as bl FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :1", array($this->arg('visit')));
            
                if (!sizeof($info)) $this->_error('No such visit');
                else $info = $info[0];
                                      
                $where .= " AND d.dewarstatus='processing' AND c.beamlinelocation LIKE :".(sizeof($args)+1)." AND c.samplechangerlocation is NOT NULL";
                array_push($args, $info['BL']);
            }
            
            $sta = $this->has_arg('iDisplayStart') ? $this->arg('iDisplayStart') : 0;
            $len = $this->has_arg('iDisplayLength') ? $this->arg('iDisplayLength') : 20;
            
            $tot = $this->db->pq("SELECT count(distinct b.blsampleid) as tot FROM ispyb4a_db.blsample b INNER JOIN ispyb4a_db.crystal cr ON cr.crystalid = b.crystalid INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = cr.proteinid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = pr.proposalid INNER JOIN ispyb4a_db.container c ON c.containerid = b.containerid INNER JOIN ispyb4a_db.dewar d ON d.dewarid = c.dewarid $join WHERE $where", $args);
            $tot = $tot[0]['TOT'];
            
            
            // Search
            if ($this->has_arg('sSearch')) {
                $st = sizeof($args) + 1;
                $where .= " AND (lower(b.name) LIKE lower('%'||:".$st."||'%') OR lower(pr.acronym) LIKE lower('%'||:".($st+1)."||'%') OR lower(b.comments) LIKE lower('%'||:".($st+2)."||'%'))";
                for ($i = 0; $i < 3; $i++) array_push($args, $this->arg('sSearch'));
            }
            
            
            // Filter by sample status
            if ($this->has_arg('t')) {
                //$this->db->set_debug(true);
                $types = array('R' => 'count(distinct r.robotactionid)',
                               'SC' => 'count(distinct dc.datacollectionid)',
                               'AI' => 'count(distinct so.screeningid)',
                               'DC' => 'count(distinct dc2.datacollectionid)',
                               'AP' => 'count(distinct ap.autoprocintegrationid)');
                if (array_key_exists($this->arg('t'), $types)) {
                    $having .= " HAVING ".$types[$this->arg('t')]." > 0";
                }
            }
            
            
            #SELECT count(distinct b.blsampleid) as tot 
            $flt = $this->db->pq("SELECT count(blsampleid) as tot FROM (SELECT b.blsampleid, count(distinct dc.datacollectionid) as sc, count(distinct dc2.datacollectionid) as dc, count(distinct so.screeningid) as ai, count(distinct ap.autoprocintegrationid) as ap, count(distinct r.robotactionid) as r FROM ispyb4a_db.blsample b INNER JOIN ispyb4a_db.crystal cr ON cr.crystalid = b.crystalid INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = cr.proteinid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = pr.proposalid INNER JOIN ispyb4a_db.container c ON c.containerid = b.containerid INNER JOIN ispyb4a_db.dewar d ON d.dewarid = c.dewarid $join LEFT OUTER JOIN ispyb4a_db.datacollection dc ON b.blsampleid = dc.blsampleid AND dc.overlap != 0 LEFT OUTER JOIN ispyb4a_db.screening sc ON dc.datacollectionid = sc.datacollectionid LEFT OUTER JOIN ispyb4a_db.screeningoutput so ON sc.screeningid = so.screeningid LEFT OUTER JOIN ispyb4a_db.datacollection dc2 ON b.blsampleid = dc2.blsampleid AND dc2.overlap = 0 AND dc2.axisrange > 0 LEFT OUTER JOIN ispyb4a_db.autoprocintegration ap ON ap.datacollectionid = dc2.datacollectionid LEFT OUTER JOIN ispyb4a_db.robotaction r ON r.blsampleid = b.blsampleid AND r.actiontype = 'LOAD' WHERE $where GROUP BY b.blsampleid $having)", $args);
            
            #print_r(array(sizeof($flt), $flt));
            $flt = $flt[0]['TOT'];
            
            $st = sizeof($args) + 1;
            array_push($args, $sta);
            array_push($args, $sta+$len);
            
            $order = 'b.blsampleid DESC';
            
            
            if ($this->has_arg('iSortCol_0')) {
                $cols = array('b.blsampleid', 'b.name', 'pr.acronym', 'cr.spacegroup', 'b.comments', 'shipment', 'dewar', 'container', 'b.blsampleid', 'sc', 'scresolution', 'ap', 'dcresolution', 'TO_NUMBER(location)');
                $dir = $this->has_arg('sSortDir_0') ? ($this->arg('sSortDir_0') == 'asc' ? 'ASC' : 'DESC') : 'ASC';
                if ($this->arg('iSortCol_0') < sizeof($cols)) $order = $cols[$this->arg('iSortCol_0')].' '.$dir;
            }
            
            if ($this->has_arg('sort_by')) {
                $order = $this->arg('sort_by') .' '.($this->has_arg('order') ? $this->arg('order') : 'asc');
            }
            
            $rows = $this->db->pq("SELECT outer.* FROM (SELECT ROWNUM rn, inner.* FROM (
                                  SELECT distinct b.blsampleid, b.code, b.location, pr.acronym, pr.proteinid, cr.spacegroup,b.comments,b.name,s.shippingname as shipment,s.shippingid,d.dewarid,d.code as dewar, c.code as container, c.containerid, c.samplechangerlocation as sclocation, count(distinct dc.datacollectionid) as sc, count(distinct dc2.datacollectionid) as dc, count(distinct so.screeningid) as ai, count(distinct ap.autoprocintegrationid) as ap, count(distinct r.robotactionid) as r, min(st.rankingresolution) as scresolution, max(ssw.completeness) as sccompleteness, min(dc.datacollectionid) as scid, min(dc2.datacollectionid) as dcid, min(apss.resolutionlimithigh) as dcresolution, max(apss.completeness) as dccompleteness
                                  
                                  
                                  FROM ispyb4a_db.blsample b
                                  INNER JOIN ispyb4a_db.crystal cr ON cr.crystalid = b.crystalid
                                  INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = cr.proteinid
                                  INNER JOIN ispyb4a_db.container c ON b.containerid = c.containerid
                                  INNER JOIN ispyb4a_db.dewar d ON d.dewarid = c.dewarid
                                  INNER JOIN ispyb4a_db.shipping s ON s.shippingid = d.shippingid
                                  INNER JOIN ispyb4a_db.proposal p ON p.proposalid = pr.proposalid
                                  $join
                                  
                                  LEFT OUTER JOIN ispyb4a_db.datacollection dc ON b.blsampleid = dc.blsampleid AND dc.overlap != 0
                                  LEFT OUTER JOIN ispyb4a_db.screening sc ON dc.datacollectionid = sc.datacollectionid
                                  LEFT OUTER JOIN ispyb4a_db.screeningoutput so ON sc.screeningid = so.screeningid
                                  
                                  LEFT OUTER JOIN ispyb4a_db.screeningstrategy st ON st.screeningoutputid = so.screeningoutputid AND sc.shortcomments LIKE '%EDNA%'
                                  LEFT OUTER JOIN ispyb4a_db.screeningstrategywedge ssw ON ssw.screeningstrategyid = st.screeningstrategyid
                                  
                                  
                                  LEFT OUTER JOIN ispyb4a_db.datacollection dc2 ON b.blsampleid = dc2.blsampleid AND dc2.overlap = 0 AND dc2.axisrange > 0
                                  LEFT OUTER JOIN ispyb4a_db.autoprocintegration ap ON ap.datacollectionid = dc2.datacollectionid
                                  
                                  LEFT OUTER JOIN ispyb4a_db.autoprocscaling_has_int aph ON aph.autoprocintegrationid = ap.autoprocintegrationid
                                  LEFT OUTER JOIN ispyb4a_db.autoprocscalingstatistics apss ON apss.autoprocscalingid = aph.autoprocscalingid
                                  
                                  
                                  LEFT OUTER JOIN ispyb4a_db.robotaction r ON r.blsampleid = b.blsampleid AND r.actiontype = 'LOAD'
                                  
                                  WHERE $where
                                  
                                  GROUP BY b.blsampleid, b.code, b.location, pr.acronym, pr.proteinid, cr.spacegroup,b.comments,b.name,s.shippingname,s.shippingid,d.dewarid,d.code, c.code, c.containerid, c.samplechangerlocation
                                  
                                  $having
                                  
                                  ORDER BY $order
                                  ) inner) outer WHERE outer.rn > :$st AND outer.rn <= :".($st+1), $args);
            
            $data = array();
            foreach ($rows as &$r) {
                $snap = '';
                if ($r['DCID'] || $r['SCID']) {
                    $id = $r['DCID'] ? $r['DCID'] : $r['SCID'];
                    $snap = '<a href="/dc/id/'.$id.'"><image class="img" src="/image/id/'.$id.'" title="Crystal Snapshot 1" /><image class="img" src="/image/n/2/id/'.$id.'" title="Crystal Snapshot 2" /></a>';
                }

                $st = '';
                foreach (array('R', 'SC', 'AI', 'DC', 'AP') as $t) {
                    if ($r[$t] > 0) $st = $t;
                }
                
                if ($st) $st = '<ul class="status"><li class="'.$st.'"></li></ul>';
                
                array_push($data, array($r['BLSAMPLEID'], $r['NAME'], '<a href="/sample/proteins/pid/'.$r['PROTEINID'].'">'.$r['ACRONYM'].'</a>', $r['SPACEGROUP'], $r['COMMENTS'], '<a href="/shipment/sid/'.$r['SHIPPINGID'].'">'.$r['SHIPMENT'].'</a>', $r['DEWAR'], '<a href="/shipment/cid/'.$r['CONTAINERID'].'">'.$r['CONTAINER'].'</a>', $snap, $r['SC'], $r['SCRESOLUTION'], $r['DC'], $r['DCRESOLUTION'], $st,'<a class="view" title="View Sample" href="/sample/sid/'.$r['BLSAMPLEID'].'">View Sample</a> <button class="atp" ty="sample" iid="'.$r['BLSAMPLEID'].'" name="'.$r['NAME'].'">Add to Project</button>'));
            }
            
            if ($this->has_arg('sid')) {
                if (sizeof($rows))$this->_output($rows[0]);
                else $this->_error('No such sample');
            } else $this->_output(array('iTotalRecords' => $tot,
                                 'iTotalDisplayRecords' => $flt,
                                 'aaData' => $this->has_arg('array') ? $rows : $data,
                           ));   
        }
        
        
        
        # ------------------------------------------------------------------------
        # List of proteins for a proposal
        function _proteins() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            
            $args = array($this->proposalid);
            $where = 'pr.proposalid=:1';
            $join = '';
            
            if ($this->has_arg('pjid')) {
                $args = array($this->arg('pjid'));
                $where = 'pj.projectid=:'.sizeof($args);
                $join .= ' INNER JOIN ispyb4a_db.project_has_protein pj ON pj.proteinid=pr.proteinid';
                
                if (!$this->staff) {
                    $join .= " INNER JOIN ispyb4a_db.proposal p ON p.proposalid = pr.proposalid INNER JOIN ispyb4a_db.blsession s ON s.proposalid = p.proposalid INNER JOIN investigation@DICAT_RO i ON lower(i.visit_id) LIKE p.proposalcode || p.proposalnumber || '-' || s.visit_number INNER JOIN investigationuser@DICAT_RO iu on i.id = iu.investigation_id inner join user_@DICAT_RO u on u.id = iu.user_id ";
                    $where .= " AND u.name=:".(sizeof($args)+1);
                    array_push($args, phpCAS::getUser());
                }
            }
            
            if ($this->has_arg('pid')) {
                $where .= ' AND pr.proteinid=:'.(sizeof($args)+1);
                array_push($args, $this->arg('pid'));
            }
            
            $sta = $this->has_arg('iDisplayStart') ? $this->arg('iDisplayStart') : 0;
            $len = $this->has_arg('iDisplayLength') ? $this->arg('iDisplayLength') : 20;
            
            $tot = $this->db->pq("SELECT count(distinct pr.proteinid) as tot FROM ispyb4a_db.protein pr $join WHERE $where", $args);
            $tot = $tot[0]['TOT'];

            if ($this->has_arg('sSearch')) {
                $st = sizeof($args) + 1;
                $where .= " AND (lower(pr.name) LIKE lower('%'||:".$st."||'%') OR lower(pr.acronym) LIKE lower('%'||:".($st+1)."||'%'))";
                for ($i = 0; $i < 2; $i++) array_push($args, $this->arg('sSearch'));
            }
            
            $flt = $this->db->pq("SELECT count(distinct pr.proteinid) as tot FROM ispyb4a_db.protein pr $join WHERE $where", $args);
            $flt = $flt[0]['TOT'];

            
            $st = sizeof($args) + 1;
            array_push($args, $sta);
            array_push($args, $sta+$len);
            
            $order = 'pr.proteinid DESC';
            
            
            if ($this->has_arg('iSortCol_0')) {
                $cols = array('pr.name', 'pr.acronym', 'pr.molecularmass', 'DBMS_LOB.SUBSTR(pr.sequence,255,1)');
                $dir = $this->has_arg('sSortDir_0') ? ($this->arg('sSortDir_0') == 'asc' ? 'ASC' : 'DESC') : 'ASC';
                if ($this->arg('iSortCol_0') < sizeof($cols)) $order = $cols[$this->arg('iSortCol_0')].' '.$dir;
            }
            
            $rows = $this->db->pq("SELECT outer.* FROM (SELECT ROWNUM rn, inner.* FROM (
                                  SELECT distinct DBMS_LOB.SUBSTR(pr.sequence,255,1) as sequence, pr.proteinid,pr.name,pr.acronym,pr.molecularmass/*,  count(distinct b.blsampleid) as scount, count(distinct dc.datacollectionid) as dcount*/ FROM ispyb4a_db.protein pr
                                  /*LEFT OUTER JOIN ispyb4a_db.crystal cr ON cr.proteinid = pr.proteinid
                                  LEFT OUTER JOIN ispyb4a_db.blsample b ON b.crystalid = cr.crystalid
                                  LEFT OUTER JOIN ispyb4a_db.datacollection dc ON b.blsampleid = dc.blsampleid*/
                                  $join
                                  WHERE $where
                                  GROUP BY pr.proteinid,pr.name,pr.acronym,pr.molecularmass, DBMS_LOB.SUBSTR(pr.sequence,255,1)
                                  ORDER BY $order
                                  ) inner) outer WHERE outer.rn > :$st AND outer.rn <= :".($st+1), $args);
            
            $ids = array();
            $wcs = array();
            foreach ($rows as $r) {
                array_push($ids, $r['PROTEINID']);
                array_push($wcs, 'pr.proteinid=:'.sizeof($ids));
            }
            
            $dcs = array();
            $scs = array();
            
            if (sizeof($ids)) {
                $dcst = $this->db->pq('SELECT pr.proteinid, count(dc.datacollectionid) as dcount FROM datacollection dc INNER JOIN ispyb4a_db.blsample s ON s.blsampleid=dc.blsampleid INNER JOIN ispyb4a_db.crystal cr ON cr.crystalid = s.crystalid INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = cr.proteinid WHERE '.implode(' OR ', $wcs).' GROUP BY pr.proteinid', $ids);

                
                foreach ($dcst as $d) {
                    $dcs[$d['PROTEINID']] = $d['DCOUNT'];
                }

                $scst = $this->db->pq('SELECT pr.proteinid, count(s.blsampleid) as scount FROM ispyb4a_db.blsample s INNER JOIN ispyb4a_db.crystal cr ON cr.crystalid = s.crystalid INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = cr.proteinid WHERE '.implode(' OR ', $wcs).' GROUP BY pr.proteinid', $ids);

                foreach ($scst as $d) {
                    $scs[$d['PROTEINID']] = $d['SCOUNT'];
                }
            }
            
            $data = array();
            foreach ($rows as &$r) {
                $dcount = array_key_exists($r['PROTEINID'], $dcs) ? $dcs[$r['PROTEINID']] : 0;
                $r['DCOUNT'] = $dcount;
                $scount = array_key_exists($r['PROTEINID'], $scs) ? $scs[$r['PROTEINID']] : 0;
                $r['SCOUNT'] = $scount;
                array_push($data, array('<span class="id" value="'.$r['PROTEINID'].'"></span>'.$r['NAME'], $r['ACRONYM'], $r['MOLECULARMASS'], $r['SEQUENCE'] ? 'Yes' : 'No', $scount, $dcount, '<a class="view" title="View Protein Details" href="/sample/proteins/pid/'.$r['PROTEINID'].'">View Protein</a> <button class="atp" ty="protein" iid="'.$r['PROTEINID'].'" name="'.$r['NAME'].'">Add to Project</button>'));
            }
            
            if ($this->has_arg('pid')) {
                if (sizeof($rows))$this->_output($rows[0]);
                else $this->_error('No such protein');
            } else $this->_output(array('iTotalRecords' => $tot,
                                 'iTotalDisplayRecords' => $flt,
                                 'aaData' => $this->has_arg('array') ? $rows : $data,
                           ));   
        }

        
        
        # ------------------------------------------------------------------------
        # Update a particular field for a protein
        function _update_protein() {
            if (!$this->has_arg('pid')) $this->_error('No proteinid specified');
            if (!$this->has_arg('value')) $this->_error('No value specified');
            
            $prot = $this->db->pq("SELECT pr.proteinid FROM ispyb4a_db.protein pr INNER JOIN ispyb4a_db.proposal p ON pr.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1 AND pr.proteinid = :2", array($this->arg('prop'),$this->arg('pid')));
            
            if (!sizeof($prot)) $this->_error('No such protein');
            
            $types = array('name' => array('\w+', 'name'),
                           'seq' => array('\w+', 'sequence'),
                           'acronym' => array('\w+', 'acronym'),
                           'mass' => array('\d+(.\d+)?', 'molecularmass'),
                           );
            
            if (array_key_exists($this->arg('ty'), $types)) {
                $t = $types[$this->arg('ty')];
                $v = $this->arg('value');
                                
                // Check the value matches the template
                if (preg_match('/^'.$t[0].'$/m', $v)) {
                    $this->db->pq('UPDATE ispyb4a_db.protein SET '.$t[1].'=:1 WHERE proteinid=:2', array($v, $this->arg('pid')));
                    
                    print $v;
                } else {
                    $this->_error('Invalid characters in field');
                }
                
            } 
        }
        

        # ------------------------------------------------------------------------
        # Update a particular field for a sample
        function _update_sample() {
            if (!$this->has_arg('sid')) $this->_error('No sampleid specified');
            if (!$this->has_arg('value')) $this->_error('No value specified');
            
            $samp = $this->db->pq("SELECT pr.proteinid,cr.crystalid FROM ispyb4a_db.blsample b INNER JOIN ispyb4a_db.crystal cr ON cr.crystalid = b.crystalid INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = cr.proteinid INNER JOIN ispyb4a_db.proposal p ON pr.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1 AND b.blsampleid = :2", array($this->arg('prop'),$this->arg('sid')));
            
            if (!sizeof($samp)) $this->_error('No such sample');
            else $samp = $samp[0];
            
            $types = array('name' => array('\w+', 'name', 'blsample', 'blsampleid', $this->arg('sid')),
                           'comment' => array('.*', 'comments', 'blsample', 'blsampleid', $this->arg('sid')),
                           'acronym' => array('\d+', 'proteinid', 'crystal', 'crystalid', $samp['CRYSTALID']),
                           'sg' => array('\w+', 'spacegroup', 'crystal', 'crystalid', $samp['CRYSTALID']),
                           );
            
            if (array_key_exists($this->arg('ty'), $types)) {
                $t = $types[$this->arg('ty')];
                $v = $this->arg('value');
                                
                // Check the value matches the template
                if (preg_match('/^'.$t[0].'$/m', $v)) {
                    $this->db->pq('UPDATE ispyb4a_db.'.$t[2].' SET '.$t[1].'=:1 WHERE '.$t[3].'=:2', array($v, $t[4]));
                    
                    if ($this->arg('ty') == 'acronym') {
                        $name = $this->db->pq('SELECT acronym FROM ispyb4a_db.protein WHERE proteinid=:1', array($v));
                        if (sizeof($name)) $v = $name[0]['ACRONYM'];
                    }
                    
                    print $v;
                } else {
                    $this->_error('Invalid characters in field');
                }
                
            } 
        }
        
        
        # ------------------------------------------------------------------------
        # Get list of pdbs for a proposal
        function _get_pdbs() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            
            $where = 'pr.proposalid=:1';
            $args = array($this->proposalid);
            
            if ($this->has_arg('pid')) {
                $where .= ' AND pr.proteinid=:2';
                array_push($args, $this->arg('pid'));
            }

            $rows = $this->db->pq("SELECT distinct p.pdbid,pr.proteinid, p.name,p.code FROM ispyb4a_db.pdb p INNER JOIN ispyb4a_db.protein_has_pdb hp ON p.pdbid = hp.pdbid INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = hp.proteinid WHERE $where ORDER BY p.pdbid DESC", $args);
            
            $this->_output($rows);
        }
        
        # ------------------------------------------------------------------------
        # Add a new pdb
        function _add_pdb() {
            if (!$this->has_arg('pid')) $this->_error('No protein id specified');

            $prot = $this->db->pq("SELECT pr.proteinid FROM ispyb4a_db.protein pr INNER JOIN ispyb4a_db.proposal p ON pr.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1 AND pr.proteinid = :2", array($this->arg('prop'),$this->arg('pid')));
            
            if (!sizeof($prot)) $this->_error('No such protein');
            
            if (array_key_exists('pdb_file', $_FILES)) {
                if ($_FILES['pdb_file']['name']) {
                    $info = pathinfo($_FILES['pdb_file']['name']);
                    
                    if ($info['extension'] == 'pdb') {
                        $file = file_get_contents($_FILES['pdb_file']['tmp_name']);
                        $this->_associate_pdb($info['basename'],$file,'',$this->arg('pid'));
                    }
                }
            }
                
            if ($this->has_arg('pdb_code')) {
                $this->_associate_pdb($this->arg('pdb_code'),'',$this->arg('pdb_code'),$this->arg('pid'));
            }

            if ($this->has_arg('existing_pdb')) {
                $rows = $this->db->pq("SELECT p.pdbid FROM ispyb4a_db.pdb p INNER JOIN ispyb4a_db.protein_has_pdb hp ON p.pdbid = hp.pdbid INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = hp.proteinid WHERE pr.proposalid=:1 AND p.pdbid=:2", array($this->proposalid, $this->arg('existing_pdb')));
                
                if (!sizeof($rows)) $this->_error('The specified pdb doesnt exist');
                
                $this->db->pq("INSERT INTO ispyb4a_db.protein_has_pdb (proteinhaspdbid,proteinid,pdbid) VALUES (s_protein_has_pdb.nextval,:1,:2)", array($this->arg('pid'),$this->arg('existing_pdb')));
            }
                
            $this->_output(1);

        }
                
        # Duplication :(
        function _associate_pdb($name,$contents,$code,$pid) { 
            $this->db->pq("INSERT INTO ispyb4a_db.pdb (pdbid,name,contents,code) VALUES(s_pdb.nextval,:1,:2,:3) RETURNING pdbid INTO :id", array($name,$contents,$code));
            $pdbid = $this->db->id();
            
            $this->db->pq("INSERT INTO ispyb4a_db.protein_has_pdb (proteinhaspdbid,proteinid,pdbid) VALUES (s_protein_has_pdb.nextval,:1,:2)", array($pid,$pdbid));
        }
        
        # ------------------------------------------------------------------------
        # Remove a pdb
        function _remove_pdb() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            if (!$this->has_arg('pid')) $this->_error('No protein specified');
            if (!$this->has_arg('pdbid')) $this->_error('No pdb specified');
            
            $pdb = $this->db->pq("SELECT pd.pdbid FROM ispyb4a_db.pdb pd INNER JOIN ispyb4a_db.protein_has_pdb hp ON hp.pdbid=pd.pdbid INNER JOIN ispyb4a_db.protein p ON p.proteinid = hp.proteinid WHERE pd.pdbid=:1 AND p.proposalid=:2 AND p.proteinid=:3", array($this->arg('pdbid'), $this->proposalid, $this->arg('pid')));
            
            if (!sizeof($pdb)) $this->_error('No such pdb');
            
            # Remove association
            $this->db->pq("DELETE FROM ispyb4a_db.protein_has_pdb WHERE pdbid=:1 AND proteinid=:2", array($this->arg('pdbid'), $this->arg('pid')));
            
            # Remove entry if its the last one
            $count = $this->db->pq("SELECT pdbid FROM ispyb4a_db.protein_has_pdb WHERE pdbid=:1", array($this->arg('pdbid')));
            if (!sizeof($count)) $this->db->pq("DELETE FROM ispyb4a_db.pdb WHERE pdbid=:1", array($this->arg('pdbid')));
            
            $this->_output(1);
        }
        
        
        
        function _add_protein() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            $pids = $this->db->pq("SELECT p.proposalid FROM blsession bl INNER JOIN proposal p ON bl.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1", array($this->arg('prop')));
            
            if (!sizeof($pids) > 0) $this->_error('No such proposal');
            else $pid = $pids[0]['PROPOSALID'];
            
            if (!$this->has_arg('acronym')) $this->_error('No protein acronym');
            
            $name = $this->has_arg('name') ? $this->arg('name') : '';
            $seq = $this->has_arg('seq') ? $this->arg('seq') : '';
            $mass = $this->has_arg('mass') ? $this->arg('mass') : '';
            
            $this->db->pq('INSERT INTO ispyb4a_db.protein (proteinid,proposalid,name,acronym,sequence,molecularmass,bltimestamp) VALUES (s_protein.nextval,:1,:2,:3,:4,:5,CURRENT_TIMESTAMP) RETURNING proteinid INTO :id',array($pid, $name, $this->arg('acronym'), $seq, $mass));
            
            $pid = $this->db->id();
            
            $this->_output($pid);
        }
        
    }

?>