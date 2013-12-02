<?php

    require_once('mpdf/mpdf.php');
    
    class PDF extends Page {
        private $vars  = array();
        
        var $arg_list = array('visit' => '\w+\d+\-\d+',
                              'sid' => '\d+',
                              'p' => '\d',
                              );
        var $dispatch = array('shipment' => '_shipment_label',
                              'report' => '_visit_report',
                              );
        var $def = 'shipment';

        
        
        # ------------------------------------------------------------------------
        # Shipment Labels
        function _shipment_label() {
            if (!$this->has_arg('prop')) $this->error('No proposal specified', 'Please select a proposal first');
            if (!$this->has_arg('sid')) $this->error('No shipment specified', 'No shipment id was specified');
            
            $ship = $this->db->pq("SELECT p.proposalcode || p.proposalnumber as prop, s.shippingid, s.shippingname, pe.givenname, pe.familyname, pe.phonenumber,pe.faxnumber, l.name as labname, l.address, l.city, l.country, pe2.givenname as givenname2, pe2.familyname as familyname2, pe2.phonenumber as phonenumber2, pe2.faxnumber as faxnumber2, l2.name as labname2, l2.address as address2, l2.city as city2, l2.country as country2, c2.courieraccount, c2.billingreference, c2.defaultcourriercompany FROM ispyb4a_db.shipping s INNER JOIN ispyb4a_db.labcontact c ON s.sendinglabcontactid = c.labcontactid INNER JOIN ispyb4a_db.person pe ON c.personid = pe.personid INNER JOIN ispyb4a_db.laboratory l ON l.laboratoryid = pe.laboratoryid INNER JOIN ispyb4a_db.labcontact c2 ON s.returnlabcontactid = c2.labcontactid  INNER JOIN ispyb4a_db.person pe2 ON c2.personid = pe2.personid INNER JOIN ispyb4a_db.laboratory l2 ON l2.laboratoryid = pe2.laboratoryid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid  WHERE s.shippingid=:1", array($this->arg('sid')));
            if (!sizeof($ship)) $this->error('No such shipment', 'The specified shipment doesnt exist');
            else $ship = $ship[0];
            $this->ship = $ship;
            
            $this->dewars = $this->db->pq("SELECT bl.beamlinename, bl.beamlineoperator, TO_CHAR(bl.startdate, 'DD-MM-YYYY') as st, d.transportvalue, d.customsvalue, d.code, d.barcode FROM ispyb4a_db.dewar d LEFT OUTER JOIN ispyb4a_db.blsession bl ON d.firstexperimentid = bl.sessionid WHERE d.shippingid=:1", array($ship['SHIPPINGID']));
            
            $this->_render('shipment_label');
        }
        
        
        # ------------------------------------------------------------------------
        # Report of Data Collections
        function _visit_report() {
            if (!$this->has_arg('visit')) $this->error('No visit specified', 'You need to specify a visit to view this page');
            
            
            $this->_render('visit_report', 'L');
        }
        
        
        
        
        # ------------------------------------------------------------------------
        # Render html template to PDF file
        function _render($file, $orientation = '') {
            $f = 'templates/pdf/'.$file.'.php';
            
            if (!$this->has_arg('p')) {
                if ($orientation) $orientation = '-'.$orientation;
                ob_start();
                $mpdf = new mPDF('', 'A4'.$orientation);
                $mpdf->WriteHTML(file_get_contents('templates/pdf/styles.css'),1);
                
                if (file_exists($f)) {
                    extract($this->vars);
                    include($f);
                }

                $mpdf->WriteHTML(ob_get_contents());
                ob_end_clean();
                $mpdf->Output();
                
                
            } else {
                print "<html>\n     <head>\n        <link href=\"/templates/pdf/styles.css\" type=\"text/css\" rel=\"stylesheet\" >\n    </head>\n    <body>\n\n";

                if (file_exists($f)) {
                    extract($this->vars);
                    include($f);
                }

                print "\n\n    </body>\n</html>";
            }
        }

        function __get($name) {
            return $this->vars[$name];
        }
        
        function __set($name, $value) {
            $this->vars[$name] = $value;
        }
                        
    }

?>