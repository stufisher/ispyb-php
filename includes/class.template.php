<?php
    
    class Template {
        private $vars  = array();
        public $js_vars = array();
        
        # Very simple templating system.
        # All variables assigned to this class are available to the template files
        #
        # $this->js_var('name', value) - will add a json encoded variable to the page
        #
        # $this->mobile() - jQuery mobile environment
        # $this->minimal() - Minimal css + jQuery only (for barcode reader)
        # $this->sidebar() - Adds top menu bar to pages
        
        
        # Construct takes page title, and array for breadcrumbs, hf enables / disables
        # header / footer
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
        
        # Enable top drop down menu bar
        public function side() {
            $this->sb = true;
        }
        
        
        # Get and set variables in the class, passes them through to the actual
        # template
        public function __get($name) {
            return $this->vars[$name];
        }
    
        public function __set($name, $value) {
            $this->vars[$name] = $value;
        }
        
        # Read the appropriate js file for the template
        private function _js($file) {
            $fp = $this->template_path.'js/pages/'.$file.'.js';
            if (file_exists($fp)) $this->header .= '    <script type="text/javascript" src="'. $this->template_url.'js/pages/'.$file.'.js"></script>' . "\n";
        }
        
        # Creates a global javascript variable on the page, can json encode pretty
        # much anything
        public function js_var($name, $val) {
            array_push($this->js_vars, $name.' = '.json_encode($val).';');
        }
        
        # Mobile template system loads jQuery Mobile
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
        
        
        # Minimal template system loads minimal.css and jQuery only
        public function minimal() {
            $this->mobile = True;
            $this->sb = false;
            $this->hf = false;

            $this->header .= '<link href="'. $this->template_url.'mobile/minimal.css" type="text/css" rel="stylesheet" />' . "\n";
            $this->header .= '    <script type="text/javascript" src="'. $this->template_url.'js/jquery-1.9.1.min.js"></script>' . "\n";
        }
        
        # Enable header / footer
        public function head($str) {
            $this->header .= $str;
        }
        
        
        # Render the page, takes the template file name, and optionally the name
        # of the javascript file to load
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
