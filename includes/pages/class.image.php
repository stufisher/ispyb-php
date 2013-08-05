<?php

    class Image extends Page {
        
        var $arg_list = array('id' => '\d+', 'n' => '\d+', 'f' => '\d');
        var $dispatch = array('xtal' => '_xtal_image', 'diff' => '_diffraction_image', 'dimp' => '_dimple_images', 'di' => '_diffraction_viewer');
        var $def = 'xtal';

        
        # Forward xtal images from visit directory to browser
        function _xtal_image() {
            if (!$this->has_arg('id')) return;
            
            $row = $this->db->pq('SELECT dc.xtalsnapshotfullpath1 as x1, dc.xtalsnapshotfullpath2 as x2, dc.xtalsnapshotfullpath3 as x3, dc.xtalsnapshotfullpath4 as x4 FROM ispyb4a_db.datacollection dc WHERE dc.datacollectionid=:1', array($this->arg('id')))[0];
            
            $images = array();
            foreach (array('X1', 'X2', 'X3', 'X4') as $i) {
                if (file_exists($row[$i])) {
                    array_push($images, $row[$i]);
                }
            }
            
            $n = $this->has_arg('n') ? ($this->arg('n')-1) : 0;
            if ($n < sizeof($images)) {
                header('Content-Type:image/png');
                #readfile($this->has_arg('f') ? $images[$n] : $this->_cache($images[$n]));
                readfile($this->has_arg('f') ? $images[$n] : str_replace('.png', 't.png', $images[$n]));
                
            } else {
                $this->_browser_cache();
                header('Content-Type:image/png');
                readfile('templates/images/no_image.png');
            }
        }
        
        # Full size diffraction image viewer
        function _diffraction_viewer() {
            if (!$this->has_arg('id')) return;
            $n = $this->has_arg('n') ? $this->arg('n') : 1;
            
            $info = $this->db->pq('SELECT imagedirectory as loc, filetemplate as ft, numberofimages as num FROM ispyb4a_db.datacollection WHERE datacollectionid=:1', array($this->arg('id')))[0];
            
            if ($n > $info['NUM']) return;
            
            $im = $info['LOC'] . '/' . $info['FT'];
            //$out = '/tmp/'.$this->arg('id').'_'.$n.'.jpg';
            $out = '/tmp/'.$this->arg('id').'_'.$n.'.jpg';
            
            if (!file_exists($out)) exec('./cbf2jpg.sh '.$info['LOC'].' '.$info['FT'].' '.$n.' '.$out);
            
            if (file_exists($out)) {
                $this->_browser_cache();
                header('Content-Type:image/jpeg');
                readfile($out);
            }
        }
        
        # Small diffraction image viewer
        function _diffraction_image() {
            if (!$this->has_arg('id')) return;
            
            $n = $this->has_arg('n') ? $this->arg('n') : 1;
            $rows = $this->db->pq('SELECT jpegfilefullpath as im FROM ispyb4a_db.image WHERE datacollectionid=:1 AND imagenumber=:2', array($this->arg('id'), $n));

            if (sizeof($rows) > 0) {
                $im = $rows[0]['IM'];
                if (file_exists($im)) {
                    header('Content-Type:image/jpeg');
                    #readfile($this->has_arg('f') ? $im : $this->_cache($im, 'jpg', 0.212));
                    readfile($this->has_arg('f') ? $im : str_replace('.jpeg', '.thumb.jpeg', $im));
                    
                } else {
                    header('Content-Type:image/png');
                    readfile('templates/images/no_image.png');
                }
            } else {
                header('Content-Type:image/png');
                readfile('templates/images/no_image2.png');
            }
        }
        
        
        # Dimple blob images
        function _dimple_images() {
            if (!$this->has_arg('id')) return;            
            
            $n = 1;
            if ($this->has_arg('n')) $n = $this->arg('n');
            
            $info = $this->db->pq('SELECT dc.imageprefix as imp, dc.datacollectionnumber as run, dc.imagedirectory as dir, p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON s.sessionid=dc.sessionid INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE dc.datacollectionid=:1', array($this->arg('id')))[0];
            $this->ads($info['DIR']);
            
            $root = str_replace($info['VIS'], $info['VIS'] . '/processed', $info['DIR']).$info['IMP'].'_'.$info['RUN'].'_/fast_dp/dimple';
            $im = $root . '/blob'.$n.'v1.png';
            
            if (file_exists($im)) {
                $this->_browser_cache();
                header('Content-Type:image/png');
                readfile($im);
            }
        }
        
        
        # Cache a thumbnail
        function _cache($im, $ty='png', $pc=0.17) {
            $new = str_replace('/dls', '/home/vxn01537/webservices/cache', $im);
            $base = dirname($new);

            if (!file_exists($new) ) {
                if (!file_exists($base)) mkdir($base, 0755, True);
                
                list($width, $height) = getimagesize($im);
                $newwidth = $width * $pc;
                $newheight = $height * $pc;
                
                $thumb = imagecreatetruecolor($newwidth, $newheight);
                $source = $ty == 'png' ? imagecreatefrompng($im) : imagecreatefromjpeg($im);
                
                imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
                $ty == 'png' ? imagepng($thumb, $new) : imagejpeg($thumb, $new);
                
            }
            
            return $new;
        }
        
        
        # Enable browser cache for static images
        function _browser_cache() {
            $expires = 60*60*24*14;
            header('Pragma: public');
            header('Cache-Control: maxage='.$expires);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
        }
        
    }

?>