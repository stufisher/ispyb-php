<?php
    
    class Template {
        private $vars  = array();
        public $js_vars = array();
        
        
        function __construct($title, $nav, $hf=1) {
            $this->hf = $hf;
            
            $this->template_url = '/templates/';
            $this->template_path = 'templates/';
            
            $this->stylesheet = $this->template_url . 'styles.css';
            
            $this->jquery = $this->template_url . 'js/jquery-1.9.1.min.js';
            $this->jqui = $this->template_url . 'js/jquery-ui.min.js';
            $this->jqui_tp = $this->template_url . 'js/jquery-ui-timepicker-addon.js';
            $this->jqui_styles = $this->template_url . 'jquery-ui.css';
            $this->jqui_cb = $this->template_url . 'js/jquery.ui.combobox.js';
            $this->jqui_top = $this->template_url . 'js/jquery.ui.touch-punch.min.js';
            
            $this->jq_edit = $this->template_url . 'js/jquery.jeditable.min.js';
            
            $this->flot = $this->template_url . 'js/jquery.flot.min.js';
            $this->flot_pie = $this->template_url . 'js/jquery.flot.pie.js';
            $this->flot_tt = $this->template_url . 'js/jquery.flot.tooltip.min.js';
            $this->flot_rl = $this->template_url . 'js/jquery.flot.tickrotor.js';
            $this->flot_st = $this->template_url . 'js/jquery.flot.stack.js';
            $this->flot_sel = $this->template_url . 'js/jquery.flot.selection.js';
            
            $this->dt = $this->template_url . 'js/jquery.dataTables.min.js';
            
            $this->lb = $this->template_url . 'js/lightbox.js';
            $this->lb_styles = $this->template_url . 'lightbox.css';
            
            $this->caman = $this->template_url . 'js/caman.min.js';
            
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
            
            if ($this->hf) include($this->template_path.'header.php');
            include($this->template_path .'/pages/' . $template . '.php');
            if ($this->hf) include($this->template_path.'footer.php');
        }
    }

?>
