<?php
    
    class Template {
        private $vars  = array();
        public $js_vars = array();
        
        
        function __construct($title, $nav, $hf=1) {
            $this->mobile = false;
            $this->hf = $hf;
            $this->sb = false;
            
            $this->template_url = '/templates/';
            $this->template_path = 'templates/';

            $stf = array();
            foreach (glob($this->template_path.'*.css') as $s) {
                array_push($stf, '/'.$s);
            }
            
            $this->st = $stf;
            
            $jsf = array();
            foreach (glob($this->template_path.'js/*.js') as $js) {
                array_push($jsf, '/'.$js);
            }
            
            $this->js = $jsf;
            
            $this->header = '';
            $this->nav = $nav;
            $this->title = $title;
        }
        
        
        public function side() {
            $this->sb = true;
        }
        
        
        public function __get($name) {
            return $this->vars[$name];
        }
    
        
        public function __set($name, $value) {
            $this->vars[$name] = $value;
        }
        
        private function _js($file) {
            $fp = $this->template_path.'js/pages/'.$file.'.js';
            if (file_exists($fp)) $this->header .= '    <script type="text/javascript" src="'. $this->template_url.'js/pages/'.$file.'.js"></script>' . "\n";
        }
        
        public function js_var($name, $val) {
            array_push($this->js_vars, $name.' = '.json_encode($val).';');
        }
        
        public function mobile() {
            $this->sb = false;
            $this->hf = false;
            $this->mobile = true;

            $this->header .= '<script type="text/javascript" src="'. $this->template_url.'js/jquery-1.9.1.min.js"></script>' . "\n";
            $this->header .= '    <script type="text/javascript" src="'. $this->template_url.'mobile/jquery.mobile-1.3.2.min.js"></script>' . "\n";
            $this->header .= '    <link href="'. $this->template_url.'mobile/jquery.mobile-1.3.2.min.css" type="text/css" rel="stylesheet" />' . "\n";
            $this->header .= '    <link href="'. $this->template_url.'mobile/mobile.css" type="text/css" rel="stylesheet" >' . "\n";
            
            $this->header .= '<script type="text/javascript" src="'. $this->template_url.'mobile/jsKeyboard.js"></script>' . "\n";
            $this->header .= '<link href="'. $this->template_url.'mobile/jsKeyboard.css" type="text/css" rel="stylesheet" />' . "\n";
        }
        
        public function minimal() {
            $this->mobile = True;
            $this->sb = false;
            $this->hf = false;

            $this->header .= '<link href="'. $this->template_url.'mobile/minimal.css" type="text/css" rel="stylesheet" />' . "\n";
            $this->header .= '    <script type="text/javascript" src="'. $this->template_url.'js/jquery-1.9.1.min.js"></script>' . "\n";
        }
        
        public function head($str) {
            $this->header .= $str;
        }
        
        
        public function render($template, $js=null) {
            $this->_js($js ? $js : $template);
                       
            if (sizeof($this->js_vars) > 0) {
                $this->header = "<script type=\"text/javascript\">\n".join("\n", $this->js_vars)."</script>\n" . $this->header;
            }
        
            extract($this->vars);
            
            include($this->template_path.'header.php');
            include($this->template_path .'/pages/' . $template . '.php');
            include($this->template_path.'footer.php');
        }
    }

?>
