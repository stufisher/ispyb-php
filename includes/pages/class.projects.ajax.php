<?php
    
    class Ajax extends AjaxBase {
        
        var $arg_list = array('pid' => '\d+',
                              'array' => '\d',
                              'title' => '.*',
                              'acronym' => '([\w-])+',
                              'ty' => '\w+',
                              'iid' => '\d+',
                              'rem' => '\d',
                              'value' => '.*',
                              );
        var $dispatch = array('projects' => '_projects',
                              'add' => '_add_project',
                              'check' => '_check_project',
                              'addto' => '_add_to_project',
                              'update' => '_update_project',
                              );
        
        var $def = 'projects';
    

        var $types = array('protein' => array('project_has_protein', 'proteinid'),
                           'sample' => array('project_has_blsample', 'blsampleid'),
                           'edge' => array('project_has_energyscan', 'energyscanid'),
                           'mca' => array('project_has_xfefspectrum', 'xfefluorescencespectrumid'),
                           'dc' => array('project_has_dcgroup', 'datacollectiongroupid'),
                           );
        
        
        # List of projects
        function _projects() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            
            $args = array(phpCAS::getUser());
            $where = "WHERE p.owner LIKE :1";
            " OR phu.username LIKE :2";
            
            $sta = $this->has_arg('iDisplayStart') ? $this->arg('iDisplayStart') : 0;
            $len = $this->has_arg('iDisplayLength') ? $this->arg('iDisplayLength') : 20;
            
            " INNER JOIN ispyb4a_db.project_has_user phu ON phu.projectid = p.projectid";
            $tot = $this->db->pq("SELECT count(projectid) as tot FROM ispyb4a_db.project p $where");
            $tot = $tot[0]['TOT'];
            
            $st = sizeof($args) + 1;
            array_push($args, $sta);
            array_push($args, $sta+$len);
            
            $rows = $this->db->pq("SELECT outer.* FROM (SELECT ROWNUM rn, inner.* FROM (SELECT p.title, p.projectid, p.acronym FROM ispyb4a_db.project p $where ORDER BY p.projectid) inner) outer WHERE outer.rn > :$st AND outer.rn <= :".($st+1), $args);
            
            if ($this->has_arg('array')) {
                $data = array();
                foreach ($rows as $r) $data[$r['PROJECTID']] = $r['TITLE'];
                $this->_output($data);
            
            } else {
                $data = array();
                foreach ($rows as $r) {
                    array_push($data, array($r['TITLE'], $r['ACRONYM'], '<a href="/projects/pid/'.$r['PROJECTID'].'" title="View Project" class="view">View</a>'));
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
            
            $this->db->pq("INSERT INTO ispyb4a_db.project (projectid,title,acronym,owner) VALUES (s_project.nextval, :1, :2, :3)", array($this->arg('title'), $this->arg('acronym'), phpCAS::getUser()));
            
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
        
        
        # Update project
        function _update_project() {
            if (!$this->has_arg('prop')) $this->_error('No proposal specified');
            if (!$this->has_arg('pid')) $this->_error('No project id specified');
            if (!$this->has_arg('value')) $this->_error('No value specified');
            
            $proj = $this->db->pq("SELECT p.projectid FROM ispyb4a_db.project p WHERE p.owner LIKE :1 AND p.projectid=:2", array(phpCAS::getUser(),$this->arg('pid')));
            
            if (!sizeof($proj)) $this->_error('No such project');
            
            $types = array('title' => array('.*', 'title'),
                           'acronym' => array('([\w-])+', 'acronym'),
                           );
            
            if (array_key_exists($this->arg('ty'), $types)) {
                $t = $types[$this->arg('ty')];
                $v = $this->arg('value');
                                
                // Check the value matches the template
                if (preg_match('/^'.$t[0].'$/m', $v)) {
                    $this->db->pq('UPDATE ispyb4a_db.project SET '.$t[1].'=:1 WHERE projectid=:2', array($v, $this->arg('pid')));

                    print $v;
                }
                
            } 
        }
    
    }

?>