<?php
    
    class Ajax extends AjaxBase {
        
        var $arg_list = array('pid' => '\d+',
                              'array' => '\d',
                              'array1' => '\d',
                              'title' => '.*',
                              'acronym' => '([\w-])+',
                              'ty' => '\w+',
                              'iid' => '\d+',
                              'rem' => '\d',
                              'value' => '.*',
                              'user' => '\w+\d+',
                              'uid' => '\d+',
                              );
        var $dispatch = array('projects' => '_projects',
                              'add' => '_add_project',
                              'check' => '_check_project',
                              'addto' => '_add_to_project',
                              'update' => '_update_project',
                              'users' => '_project_users',
                              'adduser' => '_add_user',
                              'remuser' => '_del_user',
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
            $args = array(phpCAS::getUser(), phpCAS::getUser());
            $where = "WHERE (p.owner LIKE :1 OR pu.username LIKE :2)";
            
            $sta = $this->has_arg('iDisplayStart') ? $this->arg('iDisplayStart') : 0;
            $len = $this->has_arg('iDisplayLength') ? $this->arg('iDisplayLength') : 20;

            $tot = $this->db->pq("SELECT count(distinct p.projectid) as tot FROM ispyb4a_db.project p LEFT OUTER JOIN ispyb4a_db.project_has_user pu ON pu.projectid = p.projectid $where", $args);
            $tot = $tot[0]['TOT'];
            
            if ($this->has_arg('pid')) {
                $where .= ' AND p.projectid=:'.(sizeof($args)+1);
                array_push($args, $this->arg('pid'));
            }
            
            $st = sizeof($args) + 1;
            array_push($args, $sta);
            array_push($args, $sta+$len);
            
            $rows = $this->db->pq("SELECT outer.* FROM (SELECT ROWNUM rn, inner.* FROM (SELECT p.title, p.projectid, p.acronym, p.owner FROM ispyb4a_db.project p LEFT OUTER JOIN ispyb4a_db.project_has_user pu ON pu.projectid = p.projectid $where ORDER BY p.projectid) inner) outer WHERE outer.rn > :$st AND outer.rn <= :".($st+1), $args);
            
            foreach ($rows as &$ro) {
                $ro['OWNER_NAME'] = $this->_get_name($ro['OWNER']);
                $ro['IS_OWNER'] = $ro['OWNER'] == phpCAS::getUser();
            }
            
            if ($this->has_arg('array')) {
                $data = array();
                foreach ($rows as $r) $data[$r['PROJECTID']] = $r['TITLE'];
                $this->_output($data);
            
            } else if ($this->has_arg('pid')) {
                if (sizeof($rows)) $this->_output($rows[0]);
                else $this->_error('No such project');
                
            } else {
                $data = array();
                foreach ($rows as $r) {
                    array_push($data, array($r['TITLE'], $r['ACRONYM'], '<a href="/projects/pid/'.$r['PROJECTID'].'" title="View Project" class="view">View</a>'));
                }
            
                $this->_output(array('iTotalRecords' => $tot,
                                 'iTotalDisplayRecords' => $tot,
                                 'aaData' => $this->has_arg('array1') ? $rows : $data,
                           ));
            }
        }
        
        
        function _add_project() {
            if (!$this->has_arg('title')) $this->_error('No title specified');
            if (!$this->has_arg('acronym')) $this->_error('No acronym specified');
            
            $this->db->pq("INSERT INTO ispyb4a_db.project (projectid,title,acronym,owner) VALUES (s_project.nextval, :1, :2, :3) RETURNING projectid INTO :id", array($this->arg('title'), $this->arg('acronym'), phpCAS::getUser()));
            
            $this->_output($this->db->id());
        }
        
        
        # Add to / remove from project
        function _add_to_project() {
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
        
        
        # Users on project
        function _project_users() {
            if (!$this->has_arg('pid')) $this->_error('No project id specified');
            
            $pu = $this->db->pq("SELECT projectid,username,projecthasuserid as puid FROM ispyb4a_db.project_has_user WHERE projectid=:1", array($this->arg('pid')));
            
            foreach ($pu as &$p) {
                $p['NAME'] = $this->_get_name($p['USERNAME']);
            }
            
            $this->_output($pu);
        }
        
    
        # Add a user to a project
        function _add_user() {
            if (!$this->has_arg('pid')) $this->_error('No project id specified');
            if (!$this->has_arg('user')) $this->_error('No user specified');
            
            $proj = $this->db->pq("SELECT p.projectid FROM ispyb4a_db.project p WHERE p.owner LIKE :1 AND p.projectid=:2", array(phpCAS::getUser(),$this->arg('pid')));
            
            if (!sizeof($proj)) $this->_error('No such project');
            $proj = $proj[0];
            
            $this->db->pq("INSERT INTO ispyb4a_db.project_has_user (projecthasuserid, projectid, username) VALUES (s_project_has_user.nextval, :1, :2) RETURNING projecthasuserid INTO :id", array($this->arg('pid'), $this->arg('user')));
            
            $this->_output($this->db->id());
        }
        
        
        # Remove a user
        function _del_user() {
            if (!$this->has_arg('pid')) $this->_error('No project id specified');
            if (!$this->has_arg('uid')) $this->_error('No user specified');
            
            $proj = $this->db->pq("SELECT p.projectid FROM ispyb4a_db.project p WHERE p.owner LIKE :1 AND p.projectid=:2", array(phpCAS::getUser(),$this->arg('pid')));
            
            if (!sizeof($proj)) $this->_error('No such project');
            $proj = $proj[0];
            
            $this->db->pq("DELETE FROM ispyb4a_db.project_has_user WHERE projecthasuserid=:2 AND projectid=:1", array($this->arg('pid'), $this->arg('uid')));
            
            $this->_output(1);
        }
    }

?>