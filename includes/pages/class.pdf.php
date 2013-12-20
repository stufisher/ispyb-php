<?php

    ini_set('memory_limit', '256M');
    require_once('mpdf/mpdf.php');

    # ------------------------------------------------------------------------
    # PDF Generation
    # In order to keep things simple, pdfs are rendered from html templates using
    # mdpf, this should make it easy to update reports and labels in the future.
    # Templates are kept in templates/pdf, member variables of this class are
    # automatically available in the template files
    
    class Pdf extends Page {
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
            
            $ship = $this->db->pq("SELECT s.safetylevel, p.proposalcode || p.proposalnumber as prop, s.shippingid, s.shippingname, pe.givenname, pe.familyname, pe.phonenumber,pe.faxnumber, l.name as labname, l.address, l.city, l.country, pe2.givenname as givenname2, pe2.familyname as familyname2, pe2.phonenumber as phonenumber2, pe2.faxnumber as faxnumber2, l2.name as labname2, l2.address as address2, l2.city as city2, l2.country as country2, c2.courieraccount, c2.billingreference, c2.defaultcourriercompany FROM ispyb4a_db.shipping s INNER JOIN ispyb4a_db.labcontact c ON s.sendinglabcontactid = c.labcontactid INNER JOIN ispyb4a_db.person pe ON c.personid = pe.personid INNER JOIN ispyb4a_db.laboratory l ON l.laboratoryid = pe.laboratoryid INNER JOIN ispyb4a_db.labcontact c2 ON s.returnlabcontactid = c2.labcontactid  INNER JOIN ispyb4a_db.person pe2 ON c2.personid = pe2.personid INNER JOIN ispyb4a_db.laboratory l2 ON l2.laboratoryid = pe2.laboratoryid INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid  WHERE s.shippingid=:1", array($this->arg('sid')));
            if (!sizeof($ship)) $this->error('No such shipment', 'The specified shipment doesnt exist');
            else $ship = $ship[0];
            
            $addr = array($ship['ADDRESS']);
            if ($ship['CITY']) array_push($addr, $ship['CITY']."\n");
            if ($ship['COUNTRY']) array_push($addr, $ship['COUNTRY']."\n");
            $ship['ADDRESS'] = str_replace("\n", '<br/>',  implode(', ', $addr));

            $addr = array($ship['ADDRESS2']);
            if ($ship['CITY2']) array_push($addr, $ship['CITY2']."\n");
            if ($ship['COUNTRY2']) array_push($addr, $ship['COUNTRY2']."\n");
            $ship['ADDRESS2'] = str_replace("\n", '<br/>',  implode(', ', $addr));
            
            $this->ship = $ship;
            
            $this->dewars = $this->db->pq("SELECT bl.beamlinename, bl.beamlineoperator, TO_CHAR(bl.startdate, 'DD-MM-YYYY') as st, d.transportvalue, d.customsvalue, d.code, d.barcode FROM ispyb4a_db.dewar d LEFT OUTER JOIN ispyb4a_db.blsession bl ON d.firstexperimentid = bl.sessionid WHERE d.shippingid=:1", array($ship['SHIPPINGID']));
            
            $this->_render('shipment_label');
        }
        
        
        # ------------------------------------------------------------------------
        # Report of Data Collections
        function _visit_report() {
            if (!$this->has_arg('visit')) $this->error('No visit specified', 'You need to specify a visit to view this page');
            
            $info = $this->db->pq("SELECT (s.enddate - s.startdate)*24 as len, s.sessionid as sid, s.beamlinename, TO_CHAR(s.startdate, 'DD_MM_YYYY') as st, TO_CHAR(s.enddate, 'DD_MM_YYYY') as en, p.proposalcode||p.proposalnumber||'-'||s.visit_number as visit, p.proposalcode||p.proposalnumber as prop FROM ispyb4a_db.blsession s INNER JOIN ispyb4a_db.proposal p ON p.proposalid = s.proposalid WHERE p.proposalcode||p.proposalnumber||'-'||s.visit_number LIKE :1", array($this->arg('visit')));
            
            if (!sizeof($info)) $this->error('No such visit', 'The specified visit doesnt exist');
            else $info = $info[0];
            
            $this->info = $info;
            
            $rows = $this->db->pq("SELECT dc.imageprefix,s.beamlinename,dc.datacollectionnumber,TO_CHAR(dc.starttime, 'DD/MM/YYYY HH24:MI:SS'), sa.name, p.name as protein, dc.numberofimages, dc.wavelength, dc.detectordistance, dc.exposuretime, dc.axisstart, dc.axisrange, dc.xbeam, dc.ybeam, dc.resolution, dc.comments FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON s.sessionid = dc.sessionid LEFT OUTER JOIN ispyb4a_db.blsample sa ON dc.blsampleid = sa.blsampleid LEFT OUTER JOIN ispyb4a_db.crystal c ON sa.crystalid = c.crystalid LEFT OUTER JOIN ispyb4a_db.protein p ON c.proteinid = p.proteinid WHERE dc.sessionid=:1 ORDER BY dc.starttime", array($info['SID']));
            
            $this->dcs = $rows;
            
            
            # Percentage breakdown of time used
            list($dc) = $this->db->pq("SELECT TO_CHAR(MAX(dc.endtime), 'DD-MM-YYYY HH24:MI') as last, SUM(dc.endtime - dc.starttime)*24 as dctime, GREATEST((max(s.enddate)-max(dc.endtime))*24,0) as rem, GREATEST((min(dc.starttime)-min(s.startdate))*24,0) as sup  FROM ispyb4a_db.datacollection dc INNER JOIN ispyb4a_db.blsession s ON dc.sessionid=s.sessionid WHERE dc.sessionid=:1 ORDER BY min(s.startdate)", array($info['SID']));
            
            list($rb) = $this->db->pq("SELECT SUM(CAST(r.endtimestamp AS DATE)-CAST(r.starttimestamp AS DATE))*24 as dctime FROM ispyb4a_db.robotaction r WHERE r.blsessionid=:1", array($info['SID']));
            
            list($ed) = $this->db->pq("SELECT SUM(e.endtime-e.starttime)*24 as dctime FROM ispyb4a_db.energyscan e WHERE e.sessionid=:1", array($info['SID']));
            
            list($fa) = $this->db->pq("SELECT SUM(f.beamtimelost_endtime-f.beamtimelost_starttime)*24 as dctime FROM ispyb4a_db.bf_fault f WHERE f.sessionid=:1", array($info['SID']));
            
            $rb = array_key_exists('DCTIME', $rb) ? $rb['DCTIME'] : 0;
            $ed = array_key_exists('DCTIME', $ed) ? $ed['DCTIME'] : 0;
            $fa = array_key_exists('DCTIME', $fa) ? $fa['DCTIME'] : 0;
            $t = max($info['LEN'] - $dc['SUP'] - $dc['DCTIME'] - $dc['REM'] - $rb - $ed,0);
            
            $pie = array();
            array_push($pie, array('label'=>'Startup', 'color'=>'grey', 'data'=>$dc['SUP']));
            array_push($pie, array('label'=>'Data Collection', 'color'=> 'green', 'data'=>$dc['DCTIME']));
            array_push($pie, array('label'=>'Energy Scans', 'color'=> 'orange', 'data'=>$ed));
            array_push($pie, array('label'=>'Robot Actions', 'color'=> 'blue', 'data'=>$rb));
            array_push($pie, array('label'=>'Thinking', 'color'=> 'purple', 'data'=>$t));
            array_push($pie, array('label'=>'Remaining', 'color'=> 'red', 'data'=>$dc['REM']));
            array_push($pie, array('label'=>'Beam Dump', 'color'=> 'black', 'data'=>$total_no_beam/3600));
            array_push($pie, array('label'=>'Faults', 'color'=> 'black', 'data'=>$fa));
            
            $this->_render('visit_report', 'L');
        }
        
        
        
        
        # ------------------------------------------------------------------------
        # Render html template to PDF file
        function _render($file, $orientation = '') {
            $f = 'templates/pdf/'.$file.'.php';
            
            if (!$this->has_arg('p')) {
                if ($orientation) $orientation = '-'.$orientation;
                
                # Enable output buffering to capture html
                ob_start();
                $mpdf = new mPDF('', 'A4'.$orientation);
                $mpdf->WriteHTML(file_get_contents('templates/pdf/styles.css'),1);
                
                if (file_exists($f)) {
                    extract($this->vars);
                    include($f);
                }

                # Write output buffer to pdf
                $mpdf->WriteHTML(ob_get_contents());
                ob_end_clean();
                $mpdf->Output();
                
            
            # Preview mode outputs raw html, add /p/1 to url
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