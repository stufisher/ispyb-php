<?php
    
    class Template {
        private $vars  = array();
        public $js_vars = array();
        
        
        function __construct($title, $nav, $hf=1) {
            $this->hf = $hf;
            
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
        
        
        public function render($template, $js=null) {
            $this->_js($js ? $js : $template);
                       
            if (sizeof($this->js_vars) > 0) {
                $this->header = "<script type=\"text/javascript\">\n".join("\n", $this->js_vars)."</script>\n" . $this->header;
            }
        
            extract($this->vars);
            
            include($this->template_path.'header.php');
            include($this->template_path .'/pages/' . $template . '.php');
            if ($this->hf) include($this->template_path.'footer.php');
        }
    }

?>
