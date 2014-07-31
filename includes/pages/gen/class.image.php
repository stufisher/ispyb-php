<?php

    class Image extends Page {
        
        var $arg_list = array('id' => '\d+', 'n' => '\d+', 'f' => '\d');
        var $dispatch = array('im' => '_image',
                              );
        var $def = 'im';
        
        
        # Snapshot viewer
        function _image() {
            if (!$this->has_arg('id')) return;
            
            $rows = $this->db->pq('SELECT dc.xtalsnapshotfullpath1 as x1, dc.xtalsnapshotfullpath2 as x2, dc.xtalsnapshotfullpath3 as x3, dc.xtalsnapshotfullpath4 as x4 FROM ispyb4a_db.datacollection dc WHERE dc.datacollectionid=:1', array($this->arg('id')));
            
            if (sizeof($rows) > 0) {
                $r = $rows[0];
                $ims = array($r['X1'], $r['X2'], $r['X3'], $r['X4']);
                
                $n = $this->has_arg('n') ? $this->arg('n')-1 : 0;
                
                if (!($n < sizeof($ims))) return;
                
                if (file_exists($ims[$n])) {
                    $this->_browser_cache();
                    header('Content-Type:image/jpeg');
                    readfile($this->has_arg('f') ? $ims[$n] : str_replace('.png', 't.png', $ims[$n]));
                    
                } else {
                    header('Content-Type:image/png');
                    readfile('templates/images/no_image.png');
                }
            } else {
                header('Content-Type:image/png');
                readfile('templates/images/no_image2.png');
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