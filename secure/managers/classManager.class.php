<?
/*******************************************************************************
classManager.class.php


Created by Kathy Washington (kawashi@emory.edu)

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
require_once("secure/classes/users.class.php");
require_once("secure/classes/checkDuplicates.class.php");
require_once("secure/managers/checkDuplicatesManager.class.php");
require_once("secure/displayers/classDisplayer.class.php");

class classManager
{
	public $user;
	public $displayClass;
	public $displayFunction;
	public $argList;

	function display()
	{
		//echo "attempting to call ". $this->displayClass ."->". $this->displayFunction ."<br>";

		if (is_callable(array($this->displayClass, $this->displayFunction)))
			call_user_func_array(array($this->displayClass, $this->displayFunction), $this->argList);

	}


	function classManager($cmd, $user, $adminUser, $request)
	{
		global $g_permission, $page, $loc, $ci, $alertMsg;
		
//echo "classManager($cmd, $user, $adminUser)<P>"; //classManager

		$this->displayClass = "classDisplayer";

		switch ($cmd)
		{
			case 'manageClasses':
				$page = "manageClasses";

				if ($user->getDefaultRole() >= $g_permission['staff'])
				{
					$loc  = "home";

					$this->displayFunction = 'displayStaffHome';
					$this->argList = array($user);
				} else {
					$loc  = "home";

					$this->displayFunction = 'displayInstructorHome';
					$this->argList = "";
				}
			break;

			case 'reactivateClass':
				$page = "manageClasses";

				if ($user->getUserClass() == 'instructor')
				{
					$instructors 		= null;
					$courses 			= null;
					$courseInstances 	= $user->getAllCourseInstances(true);
					for($i=0;$i<count($courseInstances);$i++) { $courseInstances[$i]->getPrimaryCourse(); }
				} else {
					$usersObject = new users();
					//$instructors = $usersObject->getUsersByRole('instructor');
	
					if (isset($request['selected_instr']))
					{
						$i = new instructor();
						$i->getUserByID($request['selected_instr']);
						$courses = $i->getAllCourses(true);
					} else
						$courses = null;

					if (isset($request['course']))
					{
						$courseInstances = $user->getCourseInstancesByCourse($request['course'], $request['selected_instr']);
						//As an option, this call would return the same as statement above: 
						//$courseInstances = $i->getMyCourseInstancesByCourseID($request['course']);
					} else
						$courseInstances = null;
				}				
				
				$this->displayFunction = 'displayReactivate';
				$this->argList = array($courses, $courseInstances, 'reactivateList', $_REQUEST, array('cmd'=>$cmd));
			break;

			case 'reactivateList':
				$ci->getPrimaryCourse();
				$ci->getCrossListings();
				$ci->getInstructors();
				$ci->getReserves();

				$ci->course->getDepartment();
				$loan_periods = $ci->course->department->getInstructorLoanPeriods();
				
				$instructorList = null;
				if ($user->getDefaultRole() >= $g_permission['staff'])
				{
					$usersObj = new users();
					$instructorList = $usersObj->getUsersByRole('instructor');
				}

				$this->displayFunction = 'displaySelectReservesToReactivate';
				$this->argList = array($ci, $user, $instructorList, array('cmd'=>'reactivate', 'instructor'=>$_REQUEST['selected_instr'], 
					'course'=>$_REQUEST['course'], 'ci'=>$_REQUEST['ci'], 'term'=>$_REQUEST['term']), $loan_periods);
			break;

			case 'reactivate':
					$page = "manageClasses";
					$term = new term($_REQUEST['term']);
					$srcCI = new courseInstance($_REQUEST['ci']);
					$srcCI->getPrimaryCourse();
					$srcCI->getProxies();

					$proxyList = (isset($request['restoreProxies']) && $request['restoreProxies'] == "on") ? $srcCI->proxyIDs : null;

					$instructorList = $_REQUEST['carryInstructor'];
					if (isset($_REQUEST['additionalInstructor']) && $_REQUEST['additionalInstructor'] != "") array_push($instructorList, $_REQUEST['additionalInstructor']);

					$carryXListing = (isset($_REQUEST['carryCrossListing'])) ? $_REQUEST['carryCrossListing'] : null;
					$carryReserves = (isset($_REQUEST['carryReserve'])) ? $_REQUEST['carryReserve'] : null;

					$activeStatus = "ACTIVE";
					
					
					//find requested loan periods
					//for($i=0;$i<count($_REQUEST);$i++)
					foreach ($_REQUEST as $field => $value)
					{
						if (ereg("requestedLoanPeriod_", $field))
						{
							list($devnull, $rID) = split("requestedLoanPeriod_", $field);
							$requested_loan_periods[$rID] =  $value;
						}
					}

					$newCI = $user->copyCourseInstance($srcCI, $term->getTermName(), $term->getTermYear(), $term->getBeginDate(), $term->getEndDate(), $activeStatus, $srcCI->course->getSection(), $instructorList, $proxyList, $carryXListing, $carryReserves, $requested_loan_periods);

					if ($newCI instanceof courseInstance ) {
						$this->displayFunction = 'displaySuccess';
						$this->argList = array($page, $newCI);
					} else { //duplicateReactivation Error: 
							 //if $newCI is not a courseInstance, it will be an array of duplicate reactivations... 
							 //not very pretty or elegant
						
						// goto check Duplicates
						//checkDuplicatesManager::checkDuplicatesManager('checkDuplicateReactivation', $user, $duplicateReactivations);
						checkDuplicatesManager::checkDuplicatesManager('checkDuplicateReactivation', $user, $newCI);
					}
			break;		
			
			case 'editClass':			
				$page = "manageClasses";
				$loc  = "home";

				$reserves = (isset($_REQUEST['reserve'])) ? $_REQUEST['reserve'] : null;


				if (isset($_REQUEST['reserveListAction']))
					switch ($_REQUEST['reserveListAction'])
					{
						case 'copyAll':
							if (is_array($reserves) && !empty($reserves)){
								$reservesArray = array();
								foreach($reserves as $r)
								{
									$reservesArray[]=$r;
									
									$originalClass = $_REQUEST['ci'];
									unset($request);
									$request['originalClass']=$originalClass;
									$request['reservesArray']=$reservesArray;
									
									classManager::classManager('copyItems',$user,$adminUser,$request);
								}
							}
						break;
						
						case 'deleteAll':
							if (is_array($reserves) && !empty($reserves)){
								foreach($reserves as $r)
								{
									$reserve = new reserve($r);
									$reserve->getItem();
									if ($reserve->item->isPhysicalItem()) {
										$reqst = new request();
										$reqst->getRequestByReserveID($r);
										$reqst->destroy();
									}
									$reserve->destroy();
								}
							}
						break;

						case 'activateAll':
							if (is_array($reserves) && !empty($reserves)){
								foreach($reserves as $r)
								{
									$reserve = new reserve($r);
									$reserve->setStatus('ACTIVE');
								}
							}
						break;

						case 'deactivateAll':
							if (is_array($reserves) && !empty($reserves)){
								foreach($reserves as $r)
								{
									$reserve = new reserve($r);
									//Headings always have a status of active
									if (!$reserve->isHeading())
										$reserve->setStatus('INACTIVE');
								}
							}
						break;
					}
									
				if ($_REQUEST['reserveListAction']!='copyAll')
				{
					$ci = new courseInstance($_REQUEST['ci']);
					//$ci->getCourseForInstructor($user->getUserID());
					if (isset($_REQUEST['updateClassDates'])) {
						$ci->setActivationDate($_REQUEST['activation']);
						$ci->setExpirationDate($_REQUEST['expiration']);
					}
					$ci->getReserves();
					$ci->getInstructors();
					$ci->getCrossListings();
					$ci->getProxies();
					$ci->getPrimaryCourse();
					$ci->course->getDepartment();
			
					$this->displayFunction = 'displayEditClass';
					$this->argList = array($user, $ci);
				}
			break;

			case 'editTitle':
			case 'editCrossListings':

				$ci = new courseInstance($_REQUEST['ci']);

				if (isset($_REQUEST['deleteCrossListings']))
				{
					$courses = $_REQUEST['deleteCrossListing'];
					if (is_array($courses) && !empty($courses)){
						foreach($courses as $c)
						{
							$alertMsg = $user->removeCrossListing($c);
						}
					}
				}


				if (isset($_REQUEST['addCrossListing']))
				{

					$dept = $_REQUEST['newDept'];
					$courseNo = $_REQUEST['newCourseNo'];
					$section = $_REQUEST['newSection'];
					$courseName = $_REQUEST['newCourseName'];

					if ($dept==NULL || $courseNo==NULL || $courseName==NULL)	
						$alertMsg =	'Please supply a Department, Course#, Section, and Title before adding the Cross Listing.';
					else 
						$user->addCrossListing($ci, $dept, $courseNo, $section, $courseName);
					

				}

				if (isset($_REQUEST['updateCrossListing'])) {
					/* commented out by kawashi on 11.12.04 - No longer able to change primary course
					$oldPrimaryCourse = new course($_REQUEST['oldPrimaryCourse']);
					$oldPrimaryCourse->setDepartmentID($_REQUEST['primaryDept']);
					$oldPrimaryCourse->setCourseNo($_REQUEST['primaryCourseNo']);
					$oldPrimaryCourse->setSection($_REQUEST['primarySection']);
					$oldPrimaryCourse->setName($_REQUEST['primaryCourseName']);

					//Set New Primary Course
					$ci->setPrimaryCourseAliasID($_REQUEST['primaryCourse']);
					*/

					$primaryCourse = new course($_REQUEST['primaryCourse']);
					$primaryCourse->setDepartmentID($_REQUEST['primaryDept']);
					$primaryCourse->setCourseNo($_REQUEST['primaryCourseNo']);
					$primaryCourse->setSection($_REQUEST['primarySection']);
					$primaryCourse->setName($_REQUEST['primaryCourseName']);

					if ($_REQUEST['cross_listings'])
					{
						$cross_listings = array_keys($_REQUEST['cross_listings']);
						foreach ($cross_listings as $cross_listing)
						{
							$updateCourse = new course($cross_listing);
							$updateCourse->setDepartmentID($_REQUEST['cross_listings'][$cross_listing]['dept']);
							$updateCourse->setCourseNo($_REQUEST['cross_listings'][$cross_listing]['courseNo']);
							$updateCourse->setSection($_REQUEST['cross_listings'][$cross_listing]['section']);
							$updateCourse->setName($_REQUEST['cross_listings'][$cross_listing]['courseName']);
						}
					}
				}

				//$ci->getCourseForInstructor($user->getUserID());
				$ci->getPrimaryCourse();
				$ci->course->getDepartment();   //$this->department = new department($this->deptID);
				$ci->getCrossListings();

				$deptList = $ci->course->department->getAllDepartments();
				$deptID = $ci->course->department->getDepartmentID();

				$this->displayFunction = 'displayEditTitle';
				$this->argList = array($ci, $deptList, $deptID);
			break;

			case 'editInstructors':
				$ci = new courseInstance($_REQUEST['ci']);
				$ci->getCrossListings();  //load cross listings 

				if ($_REQUEST['addInstructor']) {
					$ci->addInstructor($ci->primaryCourseAliasID,$_REQUEST['selected_instr']); //Add instructor to primary course alias
					for ($i=0; $i<count($ci->crossListings); $i++) {
						$ci->addInstructor($ci->crossListings[$i]->courseAliasID, $_REQUEST['selected_instr']); // add instructor to the Xlistings
					}		
					
				}

				if ($_REQUEST['removeInstructor']) {
					//Problem - Should there be a stipulation that you can't remove the last instructor?
					$instructors = $_REQUEST['Instructor'];

					if (is_array($instructors) && !empty($instructors)){
						foreach($instructors as $instructorID)
						{
							$ci->removeInstructor($ci->primaryCourseAliasID,$instructorID); //remove instructor from primary course alias
							for ($i=0; $i<count($ci->crossListings); $i++) {
								$ci->removeInstructor($ci->crossListings[$i]->courseAliasID, $instructorID); // remove instructor from the Xlistings
							}
						}
					}
				}

				$ci->getInstructors(); //load current instructors
				//$ci->getCourseForInstructor($user->getUserID());
				$ci->getPrimaryCourse();
				//$instructorList = common_getUsers('instructor'); //get instructors to populate drop down box				
				$this->displayFunction = 'displayEditInstructors';
				$this->argList = array($ci, 'ADD AN INSTRUCTOR', 'Choose an Instructor', 'Instructor', 'CURRENT INSTRUCTORS', 'Remove Selected Instructors', $request);
			break;

			case 'editProxies':
				$ci = new courseInstance($_REQUEST['ci']);
				$ci->getCrossListings();  //load cross listings

				if (isset($_REQUEST['addProxy'])) {
					$user->makeProxy($_REQUEST['proxy'],$ci->courseInstanceID);
					/*
					$ci->addProxy($ci->primaryCourseAliasID,$_REQUEST['prof']); //Add proxy to primary course alias
					for ($i=0; $i<count($ci->crossListings); $i++) {
						$ci->addProxy($ci->crossListings[$i]->courseAliasID, $_REQUEST['prof']); // add proxy to the Xlistings
					}
					*/
				}

				if (isset($_REQUEST['removeProxy'])) {
					$proxies = $_REQUEST['proxies'];

					if (is_array($proxies) && !empty($proxies)){
						foreach($proxies as $proxyID)
						{
							$user->removeProxy($proxyID, $ci->courseInstanceID);
							/*
							$ci->removeProxy($ci->primaryCourseAliasID,$proxyID); //remove proxy from primary course alias
							for ($i=0; $i<count($ci->crossListings); $i++) {
								$ci->removeProxy($ci->crossListings[$i]->courseAliasID, $proxyID); // remove proxy from the Xlistings
							}
							*/
						}
					}
				}

				$ci->getProxies(); //load current proxies
				$ci->getPrimaryCourse();

				if (isset($_REQUEST['queryText']) &&  $_REQUEST['queryText'] != "")
				{
					$usersObj = new users();
					$usersObj->search($_REQUEST['queryTerm'], $_REQUEST['queryText']);  //populate userList
				}

				//$ci->getCourseForInstructor($user->getUserID());
				//$instructorList = common_getUsers('proxy'); //get instructors to populate drop down box

				$this->displayFunction = 'displayEditProxies';
				$this->argList = array($ci, $usersObj->userList, $_REQUEST);
			break;

			case 'selectClass':
				if ($user->getDefaultRole() >= $g_permission['staff']) {
					$courseInstances = $user->getCourseInstances($adminUser->getUserID());
				} elseif ($user->getDefaultRole() >= $g_permission['proxy']) { //2 = proxy
					$courseInstances = $user->getCourseInstances();
				} else {
					trigger_error("Permission Denied:  Cannot add reserves. UserID=".$u->getUserID(), E_ERROR);
				}

				for($i=0;$i<count($courseInstances); $i++)
				{
					$ci = $courseInstances[$i];
					$ci->getCourseForUser($user->getUserID());
				}

				$this->displayFunction = 'displaySelectClasses';
				$this->argList = array($courseInstances);
			break;

			case 'selectInstructor':
				echo "select Instructor<BR>";
				$this->displayFunction = 'displaySelectInstructor';
				$this->argList = array($user);

				echo $this->displayFunction . " " . $this->argList . "<HR>";
			break;

			case 'addReserve':
				if ($user->getDefaultRole() >= $g_permission['staff']) {
					if (is_null($adminUser))
					{
					 	$this->classManager("selectInstructor", $user, null);
					 	break;
					}
					else
						$courseInstances = $adminUser->getCourseInstances();

				} elseif ($user->getDefaultRole() >= $g_permission['proxy']) { //2 = proxy
					$courseInstances = $user->getCourseInstances();
				} else {
					trigger_error("Permission Denied:  Cannot add reserves. UserID=".$u->getUserID(), E_ERROR);
				}

				for($i=0;$i<count($courseInstances); $i++)
				{
					$ci = $courseInstances[$i];
					$ci->getCourseForUser($user->getUserID());
				}

				$this->displayFunction = 'displaySelectClasses';
				$this->argList = array($courseInstances);
			break;

			case 'searchForClass':
				$page = "myReserves";
				//$instructorList = common_getUsers('instructor');
				$deptList = common_getDepartments();

				$this->displayFunction = "displaySearchForClass";
				$this->argList = array($deptList, $request);
			break;

			case 'addClass':
				$page = "myReserves";
				
				$prof = $_REQUEST['selected_instr'];
				$dept = $_REQUEST['dept'];

				if ($prof) {
					$courseList = array ();
					
					$user->getCurrentClassesFor($prof, $g_permission['instructor']);
					
					for ($i=0;$i<count($user->courseInstances);$i++)
					{
						$ci = $user->courseInstances[$i];						
						$ci->getCourses();
						$courseList = $ci->courseList;
					}
					
					$searchParam = new instructor();
					$searchParam->getUserByID($prof);		
				} elseif ($dept) {
					$user->getCoursesByDept($dept);
					$courseList = $user->courseList;

					$searchParam = new department($dept);
				} else {
					$alertMsg = "You must choose either an Instructor Name or a Department";
					return;
				}

				$this->displayFunction = "displayAddClass";
				$this->argList = array($courseList, $searchParam);
			break;

			case 'removeClass':
				$page = "myReserves";

				if ($user->getDefaultRole() < $g_permission['proxy']) {
					$user->getCourseInstances();
				} else {
					$user->getCurrentClassesFor($user->getUserID());
				}
				for ($i=0;$i<count($user->courseInstances);$i++)
				{
					$ci = $user->courseInstances[$i];
					$ci->getCourseForUser($user->getUserID());  //load courses
				}

				$this->displayFunction = "displayRemoveClass";
				$this->argList = "";
			break;


			case 'viewEnrollment':
			case 'processViewEnrollment':
				$page = "manageClasses";
				$loc = "enrolled students";

				$this->displayFunction = 'displayClassEnrollment';
				$this->argList = array($cmd, $user, $request);
			break;

			case 'createClass':
				$page = "manageClasses";

				$usersObject = new users();
				$dept = new department();
				$terms = new terms();

				$this->displayFunction = 'displayCreateClass';
				//$this->argList = array($dept->getAllDepartments(), $terms->getTerms(), array('cmd'=>'createNewClass'), $request);
				$this->argList = array($dept->getAllDepartments(), $terms->getTerms(), array('cmd'=>'createClass'), $request);
			break;

			case 'createNewClass':
				$page = "manageClasses";
				$loc = "create class";
			
				$checkDuplicates = new checkDuplicates();
				$duplicateClasses = $checkDuplicates->checkDuplicateClass($request['department'],$request['course_number'], $request['section'], $user->getUserID());

				if ($duplicateClasses) {
					
					// goto check Duplicates
					checkDuplicatesManager::checkDuplicatesManager('checkDuplicateClass', $user, $duplicateClasses);
					break;
				}
				else {
					$t = new term($request['term']);

					$c  = new course(null);
					$ci = new courseInstance(null);
					
					$ci->createCourseInstance();
					$c->createNewCourse($ci->getCourseInstanceID());

					$ci->addInstructor($c->getCourseAliasID(), $request['selected_instr']);
				
					$c->setCourseNo($request['course_number']);
					$c->setDepartmentID($request['department']);
					$c->setName($request['course_name']);
					$c->setSection($request['section']);
					$ci->setPrimaryCourseAliasID($c->getCourseAliasID());
					$ci->setTerm($t->getTermName());
					$ci->setYear($t->getTermYear());
					$ci->setActivationDate($request['activation_date']);
					$ci->setExpirationDate($request['expiration_date']);
					$ci->setEnrollment($request['enrollment']);
					$ci->setStatus('ACTIVE');
				
					$this->displayFunction = 'displaySuccess';
					$this->argList = array($page, $ci);
				}
			break;

			case 'deleteClass':
				$page = "manageClasses";
				$loc = "delete class";

				if ($user->getDefaultRole() >= $g_permission['staff'])
				{
					$this->displayFunction = 'displayDeleteClass';
					$this->argList = array($cmd, $user, $request);
				}
			break;

			case 'confirmDeleteClass':
				$page = "manageClasses";
				$loc = "confirm delete class";

				if (isset($request['ci']))
				{
					$courseInstance = new courseInstance($request['ci']);
					$courseInstance->getPrimaryCourse();
					$courseInstance->getStudents();
					$courseInstance->getInstructors();

					$this->displayFunction = 'displayConfirmDelete';
					$this->argList = array($courseInstance);
				}
			break;

			case 'deleteClassSuccess':
				$page = "manageClasses";
				$loc = "delete class";

				if (isset($request['ci']))
				{
					$courseInstance = new courseInstance($request['ci']);
					$courseInstance->getPrimaryCourse();
					$courseInstance->destroy();

					$this->displayFunction = 'displayDeleteSuccess';
					$this->argList = array($courseInstance);
				}
			break;
			
			case 'copyItems':
				$page = "manageClasses";
				$loc = "copy reserve items to another class";
				
				$this->displayFunction = 'displayCopyItems';
				$this->argList = array($cmd,$user,$request);
			break;
			
			case 'processCopyItems':
				$page = "manageClasses";
				$loc = "copy reserve items to another class";
				
				$targetClass = new courseInstance($request['ci']);
				$originalClass = new courseInstance($request['originalClass']);
				
				$targetClass->getPrimaryCourse();
				$originalClass->getPrimaryCourse();
				
				for ($i=0;$i<count($request['reservesArray']);$i++)
				{
					
					$reserve = new reserve($request['reservesArray'][$i]);
					$reserve->getItem();
				
					if (!$reserve->item->isPhysicalItem())
					{
						if ($reserve->createNewReserve($targetClass->getCourseInstanceID(), $reserve->itemID))
						{
							$reserve->setActivationDate($targetClass->getActivationDate());	
							$reserve->setExpirationDate($targetClass->getExpirationDate());	
						}

					} else {
						//store reserve for physical items with status = IN PROCESS	
							
						if($reserve->createNewReserve($targetClass->getCourseInstanceID(), $reserve->itemID)) {
							$reserve->setStatus("IN PROCESS");
							$reserve->setActivationDate($targetClass->getActivationDate());	
							$reserve->setExpirationDate($targetClass->getExpirationDate());	
								
							//create request
							$requst = new request();				
							$requst->createNewRequest($targetClass->getCourseInstanceID(), $reserve->itemID);
							$requst->setRequestingUser($user->getUserID());
							$requst->setReserveID($reserve->getReserveID());
						}
							
					}
				}
				
				
				$this->displayFunction = 'displayCopyItemsSuccess';
				$this->argList = array($targetClass,$originalClass,count($request['reservesArray']));
			break;
				
		}	
	}
}

?>