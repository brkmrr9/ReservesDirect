<?php	
/*******************************************************************************
AJAX_functions.php
returns data for ajax data fields

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
	require_once("secure/config.inc.php");
	require_once("secure/classes/copyright.class.php");
	require_once("secure/classes/department.class.php");
	require_once("secure/classes/users.class.php");
	require_once("secure/classes/terms.class.php");
	require_once("secure/managers/noteManager.class.php");
	require_once("secure/managers/copyrightManager.class.php");
	require_once("secure/displayers/noteDisplayer.class.php");
	require_once("PEAR/JSON.php");
	require_once("secure/common.inc.php");
	
	//set up error-handling/debugging, skins, etc.
	//require_once("secure/session.inc.php");
	
	//authenticate user
	//if user is valid, then initializes global user object as $u
	//else shows login page
	require_once('secure/auth.inc.php');
	
	//process passed arguments
	$f = $_REQUEST['f'];
	$qry = (isset($_REQUEST['qu'])) ? base64_decode($_REQUEST['qu']) : null;
	$rf  = (isset($_REQUEST['rf'])) ? base64_decode($_REQUEST['rf']) : null;
	
/*	
	echo "<pre>";
	print_r($_REQUEST);
	echo "</pre>";
	echo "<br>f=$f";
	echo "<br>qry=$qry";
*/
	$json = new Services_JSON();
	
	
	
	switch ($f)
	{
		case 'deptList':			
			$dept = new department();
			$depts = $dept->findByPartialName($qry);
			
			$returnValue = xmlHead();
			
			if (count($depts) > 0)					
				foreach($depts as $d)
					$returnValue .=	wrapResults($json->encode($d), $d['abbreviation'] . ' - ' . $d['name']);
			
			$returnValue .= xmlFoot();		
		break;
		
		case 'libList':
			$library = new library($qry);
			
			$data = array (
				'id'   => $library->getLibraryID(),
				'name' => $library->getLibrary(),
				'nickname' => $library->getLibraryNickname(),
				'ils_prefix' => $library->getILS_prefix(),
				'desk' => $library->getReserveDesk(),
				'url' => $library->getLibraryURL(),
				'email' => $library->getContactEmail(),
				'monograph_library_id' => $library->getMonograph_library_id(),
				'multimedia_library_id' => $library->getMultimedia_library_id()
			);		
			
			$returnValue = base64_encode($json->encode($data));
			//$returnValue .= wrapResults($json->encode($data), $data['name']);		
		break;
		
		case 'userList':
			//get the role - all by default
			$min_role = is_int($_REQUEST['role']) ? $_REQUEST['role'] : 0;
			
			$usersObj = new users();
			$usersObj->search(null, $qry, $role);
			
			$returnValue = xmlHead();
			
			if (count($usersObj->userList) > 0)				
				foreach($usersObj->userList as $usr)
					$returnValue .=	wrapResults($json->encode($usr), $usr->getName() . ' -- ' . $usr->getUsername());			
			
			$returnValue .= xmlFoot();
		break;
		
		case 'courseList':			
			$c = new course();
			$cList = $c->searchForCourses($qry);
						
			$returnValue = xmlHead();
			
			if (count($cList) > 0)
				for($i=0;$i<count($cList);$i++)
					$returnValue .=	wrapResults($json->encode($cList[$i]), $cList[$i]->displayCourseNo() . ' - ' . $cList[$i]->getName());
 			
			$returnValue .= xmlFoot();		
		break;			

		case 'classList':
			/*
				Expects $_REQUEST['qry'] to be base64 encode '::' delimited string
				instructor_id :: department_id :: course_id :: term_id
				ANY values can be empty			
			*/
		
			list($user_id, $dept_id, $course_id, $term_id) = split("::", $qry);			
			
			$userObj = new users();
			$ci_list = $userObj->searchForCI($user_id, $dept_id, $course_id, $term_id);
			
			if (count($ci_list) > 0)
			{
				
					$returnValue .= "<div align=\"left\" class=\"headingCell1\">\n";
					$returnValue .= "	<div align=\"left\" style=\"width:60px; float:left;\">&nbsp;</div>\n";
					$returnValue .= "	<div align=\"left\" style=\"width:15%; float:left;\">Course Number</div>\n";
					$returnValue .= "	<div align=\"left\" style=\"width:30%; float:left;\">Course Name</div>\n";
					$returnValue .= "	<div align=\"left\" style=\"width:25%; float:left;\">Instructor</div>\n";
					$returnValue .= "	<div align=\"left\" style=\"width:14%; float:left;\">Last Active</div>\n";
					$returnValue .= "	<div align=\"left\" style=\"width:55px; float:right; padding-right:5px;\">Preview</div>\n";
					$returnValue .= "	<div style=\"clear:both;\" class=\"headingCell1\"></div>\n";
					$returnValue .= "</div>\n";					
				
				
				for($i=0; $i<count($ci_list); $i++)
				{
					//show status icon
					switch($ci_list[$i]->getStatus()) {
						case 'AUTOFEED':
							$edit_icon = '<img src="images/activate.gif" width="24" height="20" />';	//show the 'activate-me' icon
						break;
						case 'CANCELED':
							$edit_icon = '<img src="images/cancel.gif" alt="edit" width="24" height="20">';	//show the 'activate-me' icon
						break;
						default:
							$edit_icon = '<img src="images/pencil.gif" alt="edit" width="24" height="20">';	//show the edit icon
						break;						
					}
						
					$rowStyle = ($rowStyle=='oddRow') ? 'evenRow' : 'oddRow';	//set the style
					$returnValue .= "<div align=\"left\" class=\"$rowStyle\" style=\"padding:5px;\">\n";					
					$returnValue .= "	<div align=\"left\" style=\"width: 30px; float:left; text-align:left;\"><input name=\"ci\" type=\"radio\" value=\"". $ci_list[$i]->getCourseInstanceID() ."\" onClick=\"document.getElementById('editButton').disabled=false\"></div>\n";
					$returnValue .= '	<div style="width: 30px; float:left; text-align:left">'.$edit_icon.'</div>';
					$returnValue .= "	<div align=\"left\" style=\"width:15%; float:left;\">".$ci_list[$i]->course->displayCourseNo()."&nbsp;</div>\n";
					$returnValue .= "	<div align=\"left\" style=\"width:30%; float:left;\">".$ci_list[$i]->course->getName()."&nbsp;</div>\n";
					$returnValue .= "	<div align=\"left\" style=\"width:25%; float:left;\">".$ci_list[$i]->displayInstructors()."&nbsp;</div>\n";
					$returnValue .= "	<div align=\"left\" style=\"width:14%; float:left;\">".$ci_list[$i]->displayTerm()."&nbsp;</div>\n";
					$returnValue .= "	<div align=\"left\" style=\"width:55px; float:right;\"><a href=\"javascript:openWindow('no_control=1&cmd=previewReservesList&ci=".$ci_list[$i]->courseInstanceID . "','width=800,height=600');\">preview</a></div>\n";
					$returnValue .= "	<div style=\"clear:both;\"></div>\n";
					$returnValue .= "</div>\n";					
				}
			}
			else
				$returnValue .= "<div align=\"center\" class=\"failedText\">No Matches Found.</div>\n";
		break;
		
		case 'termsList':
			$t = new terms();
			$returnValue = $json->encode($t->getTerms(true));
		break;
		

		case 'fetchNotes':
			//parse the request
			parse_str(base64_decode($_REQUEST['query']), $request);
			
			//fetch notes
			$notes = noteManager::fetchNotesForObj($request['obj_type'], $request['id'], true);
			
			//start output buffering
			ob_start();
			//output edit-note blocks (table rows)
			noteDisplayer::displayNotesContentAJAX($notes, $request['obj_type'], $request['id']);
			//grab the content for return
			$returnValue = ob_get_contents();
			//end buffering
			ob_end_clean();		
		break;

		
		case 'saveNote':	
			//parse the request
			parse_str(base64_decode($_REQUEST['query']), $request);	
			//save note
			noteManager::saveNote($request['obj_type'], $request['id'], $request['note_text'], $request['note_type'], $request['note_id']);	
		break;
		
		case 'deleteNote':
			//parse the request
			parse_str(base64_decode($_REQUEST['query']), $request);
			//delete note
			noteManager::deleteNote($request['id'], $request['obj_type'], $request['obj_id']);
		break;
		
		case 'copyrightContactList':
			//search for contacts
			$copyright = new Copyright();
			$contacts = $copyright->findContacts($qry);
			
			//add xml header
			$returnValue = xmlHead();
			
			//add contacts to result as a li
			foreach($contacts as $contact) {
				$returnValue .= wrapResults($json->encode($contact['contact_id']), $contact['org_name']);
			}
		break;
		
		case 'fetchCopyrightContact':
			//parse the request
			parse_str(base64_decode($_REQUEST['query']), $request);
			
			//search for contacts
			$copyright = new Copyright();
			$contact = $copyright->getContact($request['contact_id']);	
				
			//return info
			$returnValue = $json->encode($contact);
		break;
		
		case 'saveCopyrightContact':
			//parse the request
			parse_str(base64_decode($_REQUEST['query']), $request);
			
			//init a blank object
			$copyright = new Copyright();
			//save contact
			$copyright->saveContact($request['org_name'], $request['contact_name'], $request['address'], $request['phone'], $request['email'], $request['www'], $request['contact_id']);
		break;
		
		case 'setCopyrightContact':
			//parse the request
			parse_str(base64_decode($_REQUEST['query']), $request);
			
			//set some vars
			$_REQUEST['item_id'] = $request['item_id'];
			$_REQUEST['contact_id'] = $request['contact_id'];
			
			copyrightManager::setContact();
		break;
		
		default:
			return null;
	}
	

	
	
	print($returnValue);

function xmlHead(){	return "<?xml version='1.0' encoding='utf-8'  ?><ul class=\"LSRes\">";	}
function xmlFoot(){ return "</ul>"; }

function wrapResults($value, $option)
{
	return "<li class=\"LSRow\" onmouseover='liveSearchHover(this)' onclick='liveSearchClicked(this, \"". base64_encode($value)."\")'>$option</li>";
}

	

	
	
?>