<?php

    class Projects extends Page {
        
        var $arg_list = array('pid' => '\d+');
        var $dispatch = array('dispatch' => '_dispatch');
        var $def = 'dispatch';
        
        var $root = 'Projects';
        var $root_link = '/projects';
        var $sidebar = True;

        
        # Dispatch to a particular project or list based on passed args
        function _dispatch() {
            if ($this->has_arg('pid')) $this->_view_project();
            else $this->_index();
        }
        
        
        # List of projects
        function _index() {
            $this->template('Projects');
            $this->t->render('projects');   
        }
        
        
        # View a particular project
        function _view_project() {
            if (!$this->has_arg('pid')) $this->error('No project', 'No project was specified');

            $proj = $this->db->pq("SELECT p.title,p.acronym,p.owner FROM ispyb4a_db.project p LEFT OUTER JOIN ispyb4a_db.project_has_user pu ON pu.projectid = p.projectid WHERE p.projectid=:1 AND (p.owner LIKE :2 OR pu.username LIKE :3)", array($this->arg('pid'), phpCAS::getUser(), phpCAS::getUser()));

            if (!sizeof($proj)) $this->error('No such project', 'The specified project doesnt exist');
            else $proj = $proj[0];
            
            $proj['OWNER_NAME'] = $this->_get_name($proj['OWNER']);
            
            $this->template('View Project', array($proj['TITLE']), array(''));
            $this->t->js_var('pid', $this->arg('pid'));
            $this->t->js_var('owner', phpCAS::getUser() == $proj['OWNER']);
            $this->t->owner = phpCAS::getUser() == $proj['OWNER'];
            $this->t->proj = $proj;
            $this->t->render('project_view');
        }
        
    
    }

?>