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
                              'value' => '.*',
                              'ty' => '\w+',
                               );
        
        var $dispatch = array('samples' => '_samples',
                              'proteins' => '_proteins',
                              'update' => '_update_sample',
                              'updatep' => '_update_protein',
                              );
        
        var $def = 'samples';
        var $profile = True;
        #var $debug = True;
        #var $explain = True;
        
        function _samples() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            
            $args = array($this->proposalid);
            $where = '';
            
            if ($this->has_arg('pid')) {
                $where .= ' AND pr.proteinid=:'.(sizeof($args)+1);
                array_push($args, $this->arg('pid'));
            }
            
            $sta = $this->has_arg('iDisplayStart') ? $this->arg('iDisplayStart') : 0;
            $len = $this->has_arg('iDisplayLength') ? $this->arg('iDisplayLength') : 20;
            
            $tot = $this->db->pq("SELECT count(b.blsampleid) as tot FROM ispyb4a_db.blsample b INNER JOIN ispyb4a_db.crystal cr ON cr.crystalid = b.crystalid INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = cr.proteinid INNER JOIN ispyb4a_db.container c ON b.containerid = c.containerid INNER JOIN ispyb4a_db.dewar d ON d.dewarid = c.dewarid INNER JOIN ispyb4a_db.shipping s ON s.shippingid = d.shippingid WHERE pr.proposalid=:1 $where", $args)[0]['TOT'];
            
            if ($this->has_arg('sSearch')) {
                $st = sizeof($args) + 1;
                $where .= " AND (lower(b.name) LIKE lower('%'||:".$st."||'%') OR lower(pr.acronym) LIKE lower('%'||:".($st+1)."||'%') OR lower(b.comments) LIKE lower('%'||:".($st+2)."||'%'))";
                for ($i = 0; $i < 3; $i++) array_push($args, $this->arg('sSearch'));
            }
            
            
            $flt = $this->db->pq("SELECT count(b.blsampleid) as tot FROM ispyb4a_db.blsample b INNER JOIN ispyb4a_db.crystal cr ON cr.crystalid = b.crystalid INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = cr.proteinid INNER JOIN ispyb4a_db.container c ON b.containerid = c.containerid INNER JOIN ispyb4a_db.dewar d ON d.dewarid = c.dewarid INNER JOIN ispyb4a_db.shipping s ON s.shippingid = d.shippingid WHERE pr.proposalid=:1 $where", $args)[0]['TOT'];
            
            $st = sizeof($args) + 1;
            array_push($args, $sta);
            array_push($args, $sta+$len);
            
            $order = 'b.blsampleid DESC';
            
            
            if ($this->has_arg('iSortCol_0')) {
                $cols = array('b.blsampleid', 'b.name', 'pr.acronym', 'cr.spacegroup', 'b.comments', 'shipment', 'dewar', 'container', 'x1', 'dcount');
                $dir = $this->has_arg('sSortDir_0') ? ($this->arg('sSortDir_0') == 'asc' ? 'ASC' : 'DESC') : 'ASC';
                if ($this->arg('iSortCol_0') < sizeof($cols)) $order = $cols[$this->arg('iSortCol_0')].' '.$dir;
            }
            
            $rows = $this->db->pq("SELECT outer.* FROM (SELECT ROWNUM rn, inner.* FROM (
                                  SELECT max(dc.xtalsnapshotfullpath1) as x1, max(dc.datacollectionid) as dcid, count(dc.datacollectionid) as dcount, count(es.energyscanid) as ecount, count(xfe.xfefluorescencespectrumid) as xcount, b.blsampleid, pr.acronym, pr.proteinid, cr.spacegroup,b.comments,b.name,s.shippingname as shipment,s.shippingid,d.dewarid,d.code as dewar, c.code as container, c.containerid FROM ispyb4a_db.blsample b
                                  INNER JOIN ispyb4a_db.crystal cr ON cr.crystalid = b.crystalid
                                  INNER JOIN ispyb4a_db.protein pr ON pr.proteinid = cr.proteinid
                                  INNER JOIN ispyb4a_db.container c ON b.containerid = c.containerid
                                  INNER JOIN ispyb4a_db.dewar d ON d.dewarid = c.dewarid
                                  INNER JOIN ispyb4a_db.shipping s ON s.shippingid = d.shippingid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid
                                  
                                  LEFT OUTER JOIN ispyb4a_db.datacollection dc ON dc.blsampleid = b.blsampleid
                                  LEFT OUTER JOIN ispyb4a_db.blsample_has_energyscan es ON dc.blsampleid = es.blsampleid
                                  LEFT OUTER JOIN ispyb4a_db.xfefluorescencespectrum xfe ON dc.blsampleid = xfe.blsampleid
                                  
                                  
                                  WHERE pr.proposalid=:1 $where
                                  GROUP BY b.blsampleid, pr.acronym, pr.proteinid, cr.spacegroup,b.comments,b.name,s.shippingname,s.shippingid,d.dewarid,d.code, c.code, c.containerid
                                  
                                  ORDER BY $order
                                  ) inner) outer WHERE outer.rn > :$st AND outer.rn <= :".($st+1), $args);
            
            $data = array();
            foreach ($rows as $r) {
                $snap = '';
                if (file_exists($r['X1'])) $snap = '<image class="img" src="/image/id/'.$r['DCID'].'" title="Crystal Snapshot 1" />';
                
                array_push($data, array($r['BLSAMPLEID'], $r['NAME'], '<a href="/sample/proteins/pid/'.$r['PROTEINID'].'">'.$r['ACRONYM'].'</a>', $r['SPACEGROUP'], $r['COMMENTS'], '<a href="/shipment/sid/'.$r['SHIPPINGID'].'">'.$r['SHIPMENT'].'</a>', $r['DEWAR'], '<a href="/shipment/cid/'.$r['CONTAINERID'].'">'.$r['CONTAINER'].'</a>', $snap, $r['DCOUNT']+$r['ECOUNT']+$r['XCOUNT'], '<a class="small view" title="View Sample" href="/sample/sid/'.$r['BLSAMPLEID'].'"></a>'));
            }
            
            $this->_output(array('iTotalRecords' => $tot,
                                 'iTotalDisplayRecords' => $flt,
                                 'aaData' => $data,
                           ));   
        }
        
        
        
        function _proteins() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            
            $args = array($this->proposalid);
            $where = '';
            
            $sta = $this->has_arg('iDisplayStart') ? $this->arg('iDisplayStart') : 0;
            $len = $this->has_arg('iDisplayLength') ? $this->arg('iDisplayLength') : 20;
            
            $tot = $this->db->pq("SELECT count(pr.proteinid) as tot FROM ispyb4a_db.protein pr WHERE pr.proposalid=:1 $where", $args)[0]['TOT'];

            if ($this->has_arg('sSearch')) {
                $st = sizeof($args) + 1;
                $where .= " AND (lower(pr.name) LIKE lower('%'||:".$st."||'%') OR lower(pr.acronym) LIKE lower('%'||:".($st+1)."||'%'))";
                for ($i = 0; $i < 2; $i++) array_push($args, $this->arg('sSearch'));
            }
            
            $flt = $this->db->pq("SELECT count(pr.proteinid) as tot FROM ispyb4a_db.protein pr WHERE pr.proposalid=:1 $where", $args)[0]['TOT'];

            
            $st = sizeof($args) + 1;
            array_push($args, $sta);
            array_push($args, $sta+$len);
            
            $order = 'pr.proteinid DESC';
            
            
            if ($this->has_arg('iSortCol_0')) {
                $cols = array('pr.name', 'pr.acronym', 'pr.molecularmass', 'pr.sequence', 'scount', 'dcount');
                $dir = $this->has_arg('sSortDir_0') ? ($this->arg('sSortDir_0') == 'asc' ? 'ASC' : 'DESC') : 'ASC';
                if ($this->arg('iSortCol_0') < sizeof($cols)) $order = $cols[$this->arg('iSortCol_0')].' '.$dir;
            }
            
            $rows = $this->db->pq("SELECT outer.* FROM (SELECT ROWNUM rn, inner.* FROM (
                                  SELECT pr.proteinid,pr.name,pr.acronym,pr.molecularmass,pr.sequence, count(distinct b.blsampleid) as scount, count(distinct dc.datacollectionid) as dcount FROM ispyb4a_db.protein pr
                                  LEFT OUTER JOIN ispyb4a_db.crystal cr ON cr.proteinid = pr.proteinid
                                  LEFT OUTER JOIN ispyb4a_db.blsample b ON b.crystalid = cr.crystalid
                                  LEFT OUTER JOIN ispyb4a_db.datacollection dc ON b.blsampleid = dc.blsampleid
                                  
                                  WHERE pr.proposalid=:1 $where
                                  GROUP BY pr.proteinid,pr.name,pr.acronym,pr.molecularmass,pr.sequence
                                  ORDER BY $order
                                  ) inner) outer WHERE outer.rn > :$st AND outer.rn <= :".($st+1), $args);
            
            $data = array();
            foreach ($rows as $r) {
                array_push($data, array($r['NAME'], $r['ACRONYM'], $r['MOLECULARMASS'], $r['SEQUENCE'] ? 'Yes' : 'No', $r['SCOUNT'], $r['DCOUNT'], '<a class="small view" title="View Protein Details" href="/sample/proteins/pid/'.$r['PROTEINID'].'"></a>'));
            }
            
            $this->_output(array('iTotalRecords' => $tot,
                                 'iTotalDisplayRecords' => $flt,
                                 'aaData' => $data,
                           ));   
        }

        
        
        function _update_protein() {
            if (!$this->has_arg('pid')) $this->_error('No proteinid specified');
            if (!$this->has_arg('value')) $this->_error('No value specified');
            
            $prot = $this->db->pq("SELECT pr.proteinid FROM ispyb4a_db.protein pr INNER JOIN ispyb4a_db.proposal p ON pr.proposalid = p.proposalid WHERE p.proposalcode || p.proposalnumber LIKE :1 AND pr.proteinid = :2", array($this->arg('prop'),$this->arg('pid')));
            
            if (!sizeof($prot)) $this->_error('No such protein');
            
            $types = array('name' => array('\w+', 'name'),
                           'seq' => array('\w+', 'sequence'),
                           'acronym' => array('\w+', 'acronym'),
                           'mass' => array('\w+', 'molecularmass'),
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
        
        
    }

?>