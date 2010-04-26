<?php
/**
 *  GroupController -> Viewing content from the database
 *
 *   Copyright (c) <2010>, Mikko Aatola <mikko@aatola.net>
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied  
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for  
 * more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program; if not, write to the Free 
 * Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * License text found in /license/
 */

/**
 *  GroupController - class
 *
 *  @package    controllers
 *  @author     Mikko Aatola
 *  @copyright  2010 Mikko Aatola
 *  @license    GPL v2
 *  @version    1.0
 */ 
 class GroupController extends Oibs_Controller_CustomController
{
    function indexAction()
    {
        // Get authentication
        $auth = Zend_Auth::getInstance();
        
        // If user has identity
        if ($auth->hasIdentity()) {
            // Get the names and descriptions of all groups.
            $groupModel = new Default_Model_Groups();
            $this->view->groupdata = $groupModel->getAllGroups();
            
            // Add the "add new group"-form to the view.
            $form = new Default_Form_AddGroupForm();
            $this->view->form = $form;
            
            // If the form is posted and valid, add the new group to db.
            $request = $this->getRequest();
            if ($request->isPost()) {
                $post = $request->getPost();
                if ($form->isValid($post)) {
                    // Add new group to db.
                    $newGroupId = $groupModel->createGroup(
                        $post['groupname'], $post['groupdesc']);
                    
                    // Add the current user to the new group.
                    $userHasGroupModel = new Default_Model_UserHasGroup();
                    $userHasGroupModel->addUserToGroup(
                        $newGroupId, $this->view->userid);
                    
                    // Make the current user an admin for the new group.
                    $groupAdminModel = new Default_Model_GroupAdmins();
                    $groupAdminModel->addAdminToGroup(
                        $newGroupId, $this->view->userid);
                    
                    // Redirect back to groups page.
                    $target = $this->_urlHelper->url(array(
                        'controller' => 'group', 
                        'action'     => 'index',
                        'language'   => $this->view->language),
                        'lang_default', true);
                    $this->_redirector->gotoUrl($target);
                }
            }
        } else {
            // Groups are only visible to registered users.
            $target = $this->_urlHelper->url(array(
                'controller' => 'index', 
                'action' => 'index',
                'language' => $this->view->language),
                'lang_default', true);
            $this->_redirector->gotoUrl($target);
        }
	}
    
    /*
     * viewAction - shows an individual group's page
     *
     * @author Mikko Aatola
     */
    function viewAction()
    {
        // Get authentication
        $auth = Zend_Auth::getInstance();
        
        // If user has identity
        if ($auth->hasIdentity()) {
            // Get data for this specific group.
            $grpId = $this->_request->getParam('groupid');
            $grpModel = new Default_Model_Groups();
            $usrHasGrpModel = new Default_Model_UserHasGroup();
            $grpAdminsModel = new Default_Model_GroupAdmins();
            $comment_form = new Default_Form_CommentForm($parentId);
            
            // Add data to the view.
            $this->view->grpId = $grpId;
            $this->view->grpData = $grpModel->getGroupData($grpId);
            $this->view->grpUsers = $usrHasGrpModel->getAllUsersInGroup($grpId);
            $this->view->grpAdmins = $grpAdminsModel->getGroupAdmins($grpId);
            $this->view->userHasGroup = $usrHasGrpModel;
            $this->view->comment_form = $comment_form;
        } else {
            // Groups are only visible to registered users.
            $target = $this->_urlHelper->url(array(
                'controller' => 'index', 
                'action' => 'index',
                'language' => $this->view->language),
                'lang_default', true);
            $this->_redirector->gotoUrl($target);
        }
    }
    
    function joinAction()
    {
        $auth = Zend_Auth::getInstance();
        
        if ($auth->hasIdentity()) {
            // Get group id and user id.
            $grpId = $this->_request->getParam('groupid');
            $usrId = $auth->getIdentity()->user_id;
            
            // Join the group.
            $usrHasGroupModel = new Default_Model_UserHasGroup();
            $usrHasGroupModel->addUserToGroup($grpId, $usrId);
            
            // Redirect back to the group page.
            $target = $this->_urlHelper->url(
                array(
                    'groupid'    => $grpId,
                    'language'   => $this->view->language),
                'group_shortview', true);
            $this->_redirector->gotoUrl($target);
        } else {
            // Not logged in - can't join a group.
            $target = $this->_urlHelper->url(
                array(
                    'controller' => 'index', 
                    'action' => 'index',
                    'language' => $this->view->language),
                'lang_default', true);
            $this->_redirector->gotoUrl($target);
        }
    }
    
    function leaveAction()
    {
        $auth = Zend_Auth::getInstance();
        
        if ($auth->hasIdentity()) {
            // Get group id and user id.
            $grpId = $this->_request->getParam('groupid');
            $usrId = $auth->getIdentity()->user_id;
            
            $groupAdminsModel = new Default_Model_GroupAdmins();
            if ($groupAdminsModel->userIsAdmin($grpId, $usrId)) {
                // Group admin can't leave the group.
                $message = "You can't leave this group "
                         . "because you're its admin.";
                $url = $this->_urlHelper->url(
                    array(
                        'controller' => 'msg', 
                        'action' => 'index',
                        'language' => $this->view->language),
                    'lang_default', true); 
                $this->flash($message, $url);
            } else {
                // Remove user from group.
                $usrHasGroupModel = new Default_Model_UserHasGroup();
                $usrHasGroupModel->removeUserFromGroup($grpId, $usrId);
            }
            
            // Redirect back to the group page.
            $target = $this->_urlHelper->url(
                array(
                    'groupid'    => $grpId,
                    'language'   => $this->view->language),
                'group_shortview', true);
            $this->_redirector->gotoUrl($target);
        } else {
            // Not logged in - can't join a group.
            $target = $this->_urlHelper->url(
                array(
                    'controller' => 'index', 
                    'action' => 'index',
                    'language' => $this->view->language),
                'lang_default', true);
            $this->_redirector->gotoUrl($target);
        }
    }
}