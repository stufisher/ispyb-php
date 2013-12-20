<?php

    class Image extends Page {
        
        var $arg_list = array('id' => '\d+', 'n' => '\d+', 'f' => '\d', 'bl' => '\w\d\d(-\d)?', 'w' => '\d+', 'fid' => '\d+', 'aid' => '\d+', 'visit' => '\w+\d+-\d+');
        var $dispatch = array('xtal' => '_xtal_image',
                              'diff' => '_diffraction_image',
                              'dimp' => '_dimple_images',
                              'di' => '_diffraction_viewer',
                              'cam' => '_forward_webcam',
                              'oav' => '_forward_oav',
                              'fa' => '_fault_attachment',
                              'ai' => '_action_image',
                              );
        var $def = 'xtal';

        
        # Fault DB Attachments
        function _fault_attachment() {
            if (!$this->has_arg('fid')) return;
            
            $attachments = $this->db->pq('SELECT attachment from ispyb4a_db.bf_fault WHERE faultid = :1', array($this->arg('fid')));
            
            if (sizeof($attachments)) {
                $attachment = $attachments[0]['ATTACHMENT'];
                $ext = pathinfo($attachment, PATHINFO_EXTENSION);
                
                if (in_array($ext, array('png', 'jpg', 'jpeg', 'gif'))) $head = 'image'.$ext;
                else $head = 'application/octet-stream';
                
                header('Content-Type:'.$head);
                readfile('http://rdb.pri.diamond.ac.uk/php/elog/files/2013/'.$attachment);
            }
            else return;
        
            
        }
        
        
        # ------------------------------------------------------------------------
        # Return images for crystal wash / anneal
        function _action_image() {
            if (!$this->has_arg('visit')) return;
            if (!$this->has_arg('aid')) return;
            
            $image = $this->db->pq("SELECT r.xtalsnapshotbefore,r.xtalsnapshotafter FROM ispyb4a_db.robotaction r INNER JOIN ispyb4a_db.blsession s ON r.blsessionid = s.sessionid INNER JOIN ispyb4a_db.proposal p ON s.proposalid = p.proposalid WHERE r.robotactionid=:1 AND p.proposalcode || p.proposalnumber || '-' || s.visit_number LIKE :2", array($this->arg('aid'), $this->arg('visit')));
            
            if (!sizeof($image)) return;
            else $image = $image[0];
            
            $images = array();
            foreach (array('XTALSNAPSHOTBEFORE', 'XTALSNAPSHOTAFTER') as $i) {
                if (file_exists($image[$i])) array_push($images, $image[$i]);
            }
            
            $n = $this->has_arg('n') ? ($this->arg('n')-1) : 0;
            if ($n < sizeof($images)) {
                header('Content-Type:image/png');
                readfile($this->has_arg('f') ? $images[$n] : str_replace('.png', 't.png', $images[$n]));
            }
            
        }
        
        
        # Forward xtal images from visit directory to browser
        function _xtal_image() {
            if (!$this->has_arg('id')) return;
            
            list($row) = $this->db->pq('SELECT dc.xtalsnapshotfullpath1 as x1, dc.xtalsnapshotfullpath2 as x2, dc.xtalsnapshotfullpath3 as x3, dc.xtalsnapshotfullpath4 as x4 FROM ispyb4a_db.datacollection dc WHERE dc.datacollectionid=:1', array($this->arg('id')));
            
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
            
            list($info) = $this->db->pq('SELECT imagedirectory as loc, filetemplate as ft, numberofimages as num FROM ispyb4a_db.datacollection WHERE datacollectionid=:1', array($this->arg('id')));
            
            if ($n > $info['NUM']) return;
            
            $im = $info['LOC'] . '/' . $info['FT'];
            $out = '/tmp/'.$this->arg('id').'_'.$n.'.jpg';

            
            if (!file_exists($out)) {
                chdir('/tmp');
                exec('/tmp/cbf2jpg.sh '.$info['LOC'].' '.$info['FT'].' '.$n.' '.$out);
            }
            
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
            
            list($info) = $this->db->pq('SELECT dc.imageprefix as imp, dc.datacollectionnumber as run, dc.imagedirectory as dir, p.proposalcode || p.proposalnumber || \'-\' || s.visit_number as vis FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON s.sessionid=dc.sessionid INNER JOIN ispyb4a_db.proposal p ON (p.proposalid = s.proposalid) WHERE dc.datacollectionid=:1', array($this->arg('id')));
            $this->ads($info['DIR']);
            
            $root = str_replace($info['VIS'], $info['VIS'] . '/processed', $info['DIR']).$info['IMP'].'_'.$info['RUN'].'_/fast_dp/dimple';
            $im = $root . '/blob'.$n.'v1.png';
            
            if (file_exists($im)) {
                $this->_browser_cache();
                header('Content-Type:image/png');
                readfile($im);
            }
        }
        

        # ------------------------------------------------------------------------
        # Forward OAV to browser
        function _forward_oav() {
            if (!$this->has_arg('bl')) return;
            
            $bls = array(
                         'i02' => 'http://i02-firewire01:8080/OAV.MJPG.mjpg',
                         'i03' => 'http://i03-firewire01:8080/OAV.MJPG.mjpg',
                         'i04' => 'http://i04-firewire01:8080/OAV.MJPG.mjpg',
                         'i04-1' => 'http://i04-1-firewire01:8080/OAV.MJPG.mjpg',
                         'i24' => 'http://i24-control:8081/oav.MJPG.mjpg'
                         );
            
            if (!array_key_exists($this->arg('bl'), $bls)) return;
            
            set_time_limit(0);
            for ($i = 0; $i < ob_get_level(); $i++)
                ob_end_flush();
            ob_implicit_flush(1);
            
            while (@ob_end_clean());
            header('content-type: multipart/x-mixed-replace; boundary=--BOUNDARY');
        
            session_write_close();
        
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $bls[$this->arg('bl')]);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $im = curl_exec($ch);
            curl_close($ch);
            echo $im;
        }
        
        
        # ------------------------------------------------------------------------
        # Forward beamline webcams
        function _forward_webcam() {
            if (!$this->has_arg('bl')) return;
            
            $bls = array('i02' => array('172.23.102.177', '172.23.102.176'),
                         'i03' => array('172.23.103.177', '172.23.103.176'),
                         'i04' => array('172.23.104.177', '172.23.104.176'),
                         'i04-1' => array('i04-1-webcam1.diamond.ac.uk', 'i04-1-webcam2.diamond.ac.uk'),
                         'i24' => array('172.23.124.177', '172.23.124.176')
                         );
            
            if (!array_key_exists($this->arg('bl'), $bls)) return;
            
            $n = $this->has_arg('n') ? $this->arg('n') : 0;
            $img = $bls[$this->arg('bl')][$n];
            
            set_time_limit(0);
            for ($i = 0; $i < ob_get_level(); $i++)
                ob_end_flush();
            ob_implicit_flush(1);
            
            while (@ob_end_clean());
            header('content-type: multipart/x-mixed-replace; boundary=--myboundary');
            
            # Close session for this page as to not block the rest of the php process
            session_write_close();
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://'.$img.'/axis-cgi/mjpg/video.cgi?fps=15&resolution=CIF');
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $im = curl_exec($ch);
            curl_close($ch);
            echo $im;
        }
        
        
        # ------------------------------------------------------------------------
        # Enable browser cache for static images
        function _browser_cache() {
            $expires = 60*60*24*14;
            header('Pragma: public');
            header('Cache-Control: maxage='.$expires);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
        }
        
    }

?>