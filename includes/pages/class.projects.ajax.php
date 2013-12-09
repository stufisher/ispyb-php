<?php
    
    class Ajax extends AjaxBase {
        
        var $arg_list = array('pid' => '\d+',
                              'array' => '\d',
                              'title' => '.*',
                              'acronym' => '([\w-])+',
                              'ty' => '\w+',
                              'iid' => '\d+',
                              'rem' => '\d',
                              );
        var $dispatch = array('projects' => '_projects',
                              'add' => '_add_project',
                              'check' => '_check_project',
                              'addto' => '_add_to_project');
        var $def = 'projects';
    

        var $types = array('protein' => array('project_has_protein', 'proteinid'),
                           'sample' => array('project_has_blsample', 'blsampleid'),
                           'edge' => array('project_has_energyscan', 'energyscanid'),
                           'mca' => array('project_has_xfefspectrum', 'xfefluorescencespectrumid'),
                           );
        
        
        # List of projects
        function _projects() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            
            $args = array($this->proposalid);
            $where = "WHERE p.proposalid=:1";

            $args = array();
            $where = "";
            
            $sta = $this->has_arg('iDisplayStart') ? $this->arg('iDisplayStart') : 0;
            $len = $this->has_arg('iDisplayLength') ? $this->arg('iDisplayLength') : 20;
            
            $tot = $this->db->pq("SELECT count(projectid) as tot FROM ispyb4a_db.project p  $where")[0]['TOT'];
            
            $st = sizeof($args) + 1;
            array_push($args, $sta);
            array_push($args, $sta+$len);
            
            $rows = $this->db->pq("SELECT outer.* FROM (SELECT ROWNUM rn, inner.* FROM (SELECT p.title, p.projectid FROM ispyb4a_db.project p $where ORDER BY p.projectid) inner) outer WHERE outer.rn > :$st AND outer.rn <= :".($st+1), $args);
            
            if ($this->has_arg('array')) {
                $data = array();
                foreach ($rows as $r) $data[$r['PROJECTID']] = $r['TITLE'];
                $this->_output($data);
            
            } else {
                $data = array();
                foreach ($rows as $r) {
                    array_push($data, array($r['TITLE'], '', 0, 0, 0, '<a href="/projects/pid/'.$r['PROJECTID'].'" title="View Project" class="view">View</a>'));
                }
            
                $this->_output(array('iTotalRecords' => $tot,
                                 'iTotalDisplayRecords' => $tot,
                                 'aaData' => $data,
                           ));
            }
        }
        
        
        function _add_project() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            if (!$this->has_arg('title')) $this->_error('No title specified');
            if (!$this->has_arg('acronym')) $this->_error('No acronym specified');
            
            $this->db->pq("INSERT INTO ispyb4a_db.project (projectid,title) VALUES (s_project.nextval, :1)", array($this->arg('title')));
            
            $this->_output($this->db->id());
        }
        
        
        # Add to / remove from project
        function _add_to_project() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            if (!$this->has_arg('pid')) $this->_error('No project id specified');
            if (!$this->has_arg('ty')) $this->_error('No item type specified');
            if (!$this->has_arg('iid')) $this->_error('No item id specified');
            
            
            if (array_key_exists($this->arg('ty'), $this->types)) {
                $t = $this->types[$this->arg('ty')];
                
                $chk = $this->db->pq("SELECT projectid FROM ispyb4a_db.$t[0] WHERE projectid=:1 AND $t[1]=:2", array($this->arg('pid'), $this->arg('iid')));
                
                if ($this->has_arg('rem') && sizeof($chk)) {
                    $this->db->pq("DELETE FROM ispyb4a_db.$t[0] WHERE projectid=:1 AND $t[1]=:2", array($this->arg('pid'), $this->arg('iid')));
                }
                
                if (!sizeof($chk)) {
                    $this->db->pq("INSERT INTO ispyb4a_db.$t[0] (projectid,$t[1]) VALUES (:1, :2)", array($this->arg('pid'), $this->arg('iid')));
                }
                
                $this->_output(1);
            }
        }
        
        
        # Check if item already exists
        function _check_project() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            if (!$this->has_arg('pid')) $this->_error('No project id specified');
            if (!$this->has_arg('ty')) $this->_error('No item type specified');
            if (!$this->has_arg('iid')) $this->_error('No item id specified');


            $ret = 0;
            
            if (array_key_exists($this->arg('ty'), $this->types)) {
                $t = $this->types[$this->arg('ty')];
                
                $rows = $this->db->pq("SELECT projectid FROM ispyb4a_db.$t[0] WHERE projectid=:1 AND $t[1]=:2", array($this->arg('pid'), $this->arg('iid')));
                
                if (sizeof($rows)) $ret = 1;
            }
            
            $this->_output($ret);
        }
    
    }

?>