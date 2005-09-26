<?
/*******************************************************************************
userManager.class.php
methods to edit and display users

Created by Jason White (jbwhite@emory.edu)

This file is part of ReservesDirect

Copyright (c) 2004-2005 Emory University, Atlanta, Georgia.

ReservesDirect is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

ReservesDirect is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with ReservesDirect; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

ReservesDirect is located at:
http://www.reservesdirect.org/


*******************************************************************************/
require_once("secure/common.inc.php");
require_once("secure/displayers/userDisplayer.class.php");

class userManager
{
	public $user;
	public $displayClass;
	public $displayFunction;
	public $argList;

	function display()
	{
		if (is_callable(array($this->displayClass, $this->displayFunction)))
			call_user_func_array(array($this->displayClass, $this->displayFunction), $this->argList);
	}

	function userManager($cmd, $user, $adminUser, $msg="")
	{
		global $page, $loc, $g_permission, $ci, $alertMsg;

		$this->displayClass = "userDisplayer";

		switch ($cmd)
		{

			case 'manageUser':

				$page = "manageUser";

				if ($user->getDefaultRole() >= $g_permission['staff'])
				{
					$loc  = "home";

					$this->displayFunction = 'displayStaffHome';
					$this->argList = array(null);
				} elseif ($user->getDefaultRole() == $g_permission['instructor']) {

					$loc  = "home";

					$this->displayFunction = 'displayInstructorHome';
					$this->argList = "";
				} elseif ($user->getDefaultRole() == $g_permission['custodian']) {

					$loc  = "home";

					$this->displayFunction = 'displayCustodianHome';
					$this->argList = "";
				}
			break;

			case "newProfile":				
			case "editProfile":
				$page = "manageUser";

				$newUser = ($cmd == "newProfile") ? true : false;
				
				if ($user->getDefaultRole() >= $g_permission['instructor'])
					$user->getInstructorAttributes();
				
				$hidden_fields = array (
					'cmd' 			=> 'storeUser', 
					'previous_cmd'	=> $cmd,
					'newUser'		=> $newUser
				);
				
				$this->displayFunction = 'displayEditUser';
				$this->argList = array($user, $user, null, $_REQUEST, null, $hidden_fields);
			break;
			
			case "mergeUsers":				
				$page = "manageUser";

				$userObj = new users();
				
				if (isset($_REQUEST['userToKeep_selectedUser']) && isset($_REQUEST['userToMerge_selectedUser']) && isset($_REQUEST['subMerge']))
				{
					if ($_REQUEST['userToKeep_selectedUser'] != $_REQUEST['userToMerge_selectedUser'])
					{						
						$userObj->mergeUsers($_REQUEST['userToKeep_selectedUser'], $_REQUEST['userToMerge_selectedUser']);
						
						$alertMsg = "Users successfully merged.";
						
						$this->displayFunction = 'displayStaffHome';
						$this->argList = array(null);
					} else {
						$alertMsg = "User to keep and User to merge must be different users.";

						$this->displayFunction = 'displayMergeUser';
						$this->argList = array($_REQUEST, $hidden_fields, $userObj, $cmd);	
					}
				} else {
					$hidden_fields = array (
						'cmd' 			=> $cmd, 
					);				
					
														
					$this->displayFunction = 'displayMergeUser';
					$this->argList = array($_REQUEST, $hidden_fields, $userObj, $cmd);			
				}
			break;

			case 'addUser':
				$page = "manageUser";

				if ($_REQUEST['user']['defaultRole'] >= $g_permission['instructor']) //need to have access to intructor_attributes
					$userToEdit = new instructor();
				else
					$userToEdit = new user();

				if (isset($_REQUEST['user']))  // we do not want to store this to the db yet but should populate the object for display to the form
				{
					$userToEdit->userName  = $_REQUEST['user']['username'];
					$userToEdit->firstName = $_REQUEST['user']['first_name'];
					$userToEdit->lastName  = $_REQUEST['user']['last_name'];
					$userToEdit->dfltRole  = $_REQUEST['user']['defaultRole'];
					$userToEdit->email	   = $_REQUEST['user']['email'];

					if ($userToEdit->dfltRole >= $g_permission['instructor'] && isset($_REQUEST['user']['ils_user_name']))
					{
						$userToEdit->ils_user_id = $_REQUEST['user']['ils_user_id'];
						$userToEdit->ils_name = $_REQUEST['user']['ils_user_name'];
					}

				}

				$hidden_fields = array (
					'cmd' 			=> 'storeUser', 
					'previous_cmd'	=> $cmd,
					'newUser'		=> false
				);
				
				$this->displayFunction = 'displayEditUser';				
				$this->argList = array($userToEdit, $user, null, $_REQUEST, null, $hidden_fields);
			break;

			case 'resetPwd':
				$page = "manageUser";

				$users = new users();

				if (isset($_REQUEST['select_user_by']) && isset($_REQUEST['user_qryTerm']))
					$users->search($_REQUEST['select_user_by'], $_REQUEST['user_qryTerm']);

				$userToEdit = (isset($_REQUEST['selectedUser'])) ? new user($_REQUEST['selectedUser']) : null;

				$sp = new specialUser();

				if (!is_null($userToEdit))
				{
					if (!$userToEdit->isSpecialUser())
						$sp->createNewSpecialUser($userToEdit->getUsername(), $userToEdit->getEmail(), null);
					else
						$sp->resetPassword($userToEdit->getUsername());

					if ($user->getDefaultRole() == $g_permission['custodian']) {
						$this->displayFunction = 'displayCustodianHome';
						$this->argList = array('Override Password Reset');
					} else {
						$this->displayFunction = 'displayStaffHome';
						$this->argList = array('Override Password Reset');
					}
				} else {
					$hidden_fields = array (
						'cmd' 			=> 'storeUser', 
						'previous_cmd'	=> $cmd,
						'newUser'		=> false
					);
				//($userToEdit, $user, $msg=null, $request, $usersObj=null, $hidden_fields=null)
					$this->displayFunction = 'displayEditUser';
					$this->argList = array($userToEdit, $user, null, $_REQUEST, $users, $hidden_fields);
				}

			break;


			case 'setPwd':
				if (isset($_REQUEST['selectedUser']) && $_REQUEST['selectedUser'] == "")
				{
					$userToEdit = new user();

					$userToEdit->createUser($_REQUEST['user']['username'], $_REQUEST['user']['first_name'], $_REQUEST['user']['last_name'], $_REQUEST['user']['email'], $_REQUEST['user']['defaultRole']);
				} else
					$userToEdit = (isset($_REQUEST['selectedUser'])) ? new user($_REQUEST['selectedUser']) : null;

				if (!is_null($userToEdit) && !$userToEdit->isSpecialUser())
				{
					$sp = new specialUser();
					$sp->createNewSpecialUser($userToEdit->getUsername(), $userToEdit->getEmail(), null);
				}

			case 'editUser':
				$page = "manageUser";

				$users = new users();

				//determine if search has been issued and if so search
				if (isset($_REQUEST['select_user_by']) && isset($_REQUEST['user_qryTerm']))
					$users->search($_REQUEST['select_user_by'], $_REQUEST['user_qryTerm']);

				//if user to be editted has been selected create object and get data
				if (!isset($userToEdit))
				{
					if (isset($_REQUEST['selectedUser']))
					{
						$userToEdit = new instructor();
						$userToEdit->getUserByID($_REQUEST['selectedUser']);
					} else
						$userToEdit = null;
				}

				if (!is_null($userToEdit) && $userToEdit->getDefaultRole() >= $g_permission['instructor'])
				{
					//recreate as instructor
					$userToEdit = new instructor($userToEdit->getUsername());
					$userToEdit->getInstructorAttributes();
				}

				$hidden_fields = array (
					'cmd' 			=> 'storeUser', 
					'previous_cmd'	=> $cmd,
					'newUser'		=> false
				);
				
				$this->displayFunction = 'displayEditUser';
				$this->argList = array($userToEdit, $user, null, $_REQUEST, $users, $hidden_fields);

			break;

			case 'assignProxy':
			case 'assignInstr':
				$page = "manageUser";

				$ci = (isset($_REQUEST['ci'])) ? new courseInstance($_REQUEST['ci']) : null;

				$this->displayFunction = 'displayEditUser';
				$this->argList = array($cmd, 'storeUser', $userToEdit, $user, null, $users, $_REQUEST);

				if (is_null($ci)) { // user has been seleced so choose class
					require_once("managers/selectClassManager.class.php");
					selectClassManager::selectClassManager('lookupClass', $cmd, $cmd, 'Assign User', $user, $_REQUEST, null);
				} else { // show proxy screen
					require_once("managers/classManager.class.php");

					if ($cmd == 'assignProxy')
						classManager::classManager('editProxies', $user, $adminUser, $_REQUEST);
					else
						classManager::classManager('editInstructors', $user, $adminUser, $_REQUEST);
				}

			break;

			case 'storeUser':
				$editUser = new user();
				if ($_REQUEST['previous_cmd'] == 'addUser')
				{
					$tmpUser = new user();
					if (!$tmpUser->getUserByUserName($_REQUEST['user']['username']))
					{
						$editUser->createUser($_REQUEST['user']['username'], '', '', '', 0);
					} else {
						$hidden_fields = array (
							'cmd' 			=> 'addUser', 
							'previous_cmd'	=> 'storeUser',
							'newUser'		=> false
						);
						
						$this->displayFunction = 'displayEditUser';
						$this->argList = array($editUser, $user, "This username is in use.  Please choose another.", $_REQUEST, $users, $hidden_fields);
						return;
					}
				} else
					$editUser->getUserByID($_REQUEST['user']['userID']);

				if ($editUser->setEmail($_REQUEST['user']['email']))
				{
					$editUser->setFirstName($_REQUEST['user']['first_name']);
					$editUser->setLastName($_REQUEST['user']['last_name']);
					$editUser->setDefaultRole($_REQUEST['user']['defaultRole']);

					if ($editUser->isSpecialUser() && isset($_REQUEST['user']['pwd']) && $_REQUEST['user']['pwd'] != "")
					{
						$sp = new specialUser($editUser->getUserID());
						$sp->resetPassword($editUser->getUsername(), $_REQUEST['user']['pwd']);
					}

					if ($editUser->getDefaultRole() >= $g_permission['instructor'] && isset($_REQUEST['user']['ils_user_id']))
					{
						$editUser = new instructor($editUser->getUsername());  //recreate as instructor
						$editUser->storeInstructorAttributes($_REQUEST['user']['ils_user_id'], $_REQUEST['user']['ils_user_name']);
					}

					$msg = "User Record Successfully Saved";
				} else {
					$msg = "Invalid Email Format - Changes Not Saved";
				}

				if ($user->getDefaultRole() == $g_permission['custodian']) {
					$this->displayFunction = 'displayCustodianHome';
					$this->argList = array("User Password Successfully Changed");
				} elseif ($user->getDefaultRole() >= $g_permission['staff']) {
					$this->displayFunction = 'displayStaffHome';
					$this->argList = array($msg);
				} else {
					require_once("secure/managers/reservesManager.class.php");
					reservesManager::reservesManager('viewCourseList', $user);
					break;
				}
			break;

			case 'addProxy':
			case 'removeProxy':
				$page = "manageUser";

				$courseInstances = $user->getCourseInstances($aDate=null,$eDate=null,$editableOnly=true);

				$this->displayFunction = 'displayEditProxy';
				$this->argList = array($courseInstances,'editProxies');
			break;

			case 'removePwd':
				$page = "manageUser";

				$users = new users();

				if (isset($_REQUEST['select_user_by']) && isset($_REQUEST['user_qryTerm']))
					$users->search($_REQUEST['select_user_by'], $_REQUEST['user_qryTerm']);
				
				$userToEdit = (isset($_REQUEST['selectedUser'])) ? new user($_REQUEST['selectedUser']) : null;

				if (!is_null($userToEdit))
				{
					if ($userToEdit->isSpecialUser())
					{
						$sp = new specialUser($userToEdit->getUserID());
						$sp->destroy();
					}

					if ($user->getDefaultRole() == $g_permission['custodian']) {
						$this->displayFunction = 'displayCustodianHome';
						$this->argList = array('Override Password Removed');
					} else {
						$this->displayFunction = 'displayStaffHome';
						$this->argList = array('Override Password Removed');
					}
				} else {
					$this->displayFunction = 'displayAssignUser';
					$this->argList = array($cmd, $cmd, $userToEdit, null, $users, 'Remove Override Password', $_REQUEST);
				}
			break;

		}
	}
}
?>