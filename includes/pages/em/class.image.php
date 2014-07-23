<?php

    class Image extends Page {
        
        var $arg_list = array('id' => '\d+', 'n' => '\d+', 'f' => '\d', 'bl' => '\w\d\d(-\d)?', 'w' => '\d+', 'fid' => '\d+', 'visit' => '\w+\d+-\d+');
        var $dispatch = array('em' => '_em_image',
                              'fft' => '_fft_image',
                              );
        var $def = 'em';
        
        
        # Small em image viewer
        function _em_image() {
            if (!$this->has_arg('id')) return;
            
            $rows = $this->db->pq('SELECT micrographfullpath as im FROM ispyb4a_db.emmovie WHERE emmovieid=:1', array($this->arg('id')));
            
            if (sizeof($rows) > 0) {
                $im = $rows[0]['IM'];
                if (file_exists($im)) {
                    $this->_browser_cache();
                    header('Content-Type:image/jpeg');
                    readfile($this->has_arg('f') ? $im : str_replace('.png', 't.png', $im));
                    
                } else {
                    header('Content-Type:image/png');
                    readfile('templates/images/no_image.png');
                }
            } else {
                header('Content-Type:image/png');
                readfile('templates/images/no_image2.png');
            }
        }
        
        # EM power spectrum images
        function _fft_image() {
            if (!$this->has_arg('id')) return;
            
            list($row) = $this->db->pq('SELECT em.powerspectrumfullpath1 as ps1, em.powerspectrumfullpath2 as ps2 FROM ispyb4a_db.emmovie em WHERE em.emmovieid=:1', array($this->arg('id')));
            
            $images = array();
            foreach (array_reverse(array('PS1', 'PS2')) as $i) {
                if (file_exists($row[$i])) {
                    array_push($images, $row[$i]);
                }
            }
            
            $n = $this->has_arg('n') ? ($this->arg('n')-1) : 0;
            if ($n < sizeof($images)) {
                $this->_browser_cache();
                header('Content-Type:image/png');
                readfile($this->has_arg('f') ? $images[$n] : str_replace('.png', 't.png', $images[$n]));
                
            } else {
                $this->_browser_cache();
                header('Content-Type:image/png');
                readfile('templates/images/no_image.png');
            }
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