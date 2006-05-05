<?
$functional_permissions = array
(
	 'viewReservesList' 			=> $g_permission['student'], 
	 'previewReservesList' 			=> $g_permission['proxy'],
	 'previewStudentView'			=> $g_permission['proxy'],
	 'customSort' 					=> $g_permission['proxy'],
	 'selectInstructor' 			=> $g_permission['student'], 
	 'addReserve' 					=> $g_permission['proxy'], 
	 'searchScreen' 				=> $g_permission['proxy'], 
	 'searchResults' 				=> $g_permission['proxy'], 
	 'storeReserve' 				=> $g_permission['proxy'], 
	 'uploadDocument' 				=> $g_permission['proxy'], 
	 'addURL' 						=> $g_permission['proxy'], 
	 'storeUploaded' 				=> $g_permission['proxy'], 
	 'faxReserve' 					=> $g_permission['proxy'], 
	 'getFax' 						=> $g_permission['proxy'], 
	 'addFaxMetadata' 				=> $g_permission['proxy'], 
	 'storeFaxMetadata' 			=> $g_permission['proxy'], 

	 'myReserves' 					=> $g_permission['student'],
	 'viewCourseList' 				=> $g_permission['student'],  
	 'activateClass'				=> $g_permission['instructor'],
	 'deactivateClass'				=> $g_permission['instructor'],
	 'manageClasses' 				=> $g_permission['staff'],
	 'editProxies' 					=> $g_permission['instructor'],
	 'editInstructors' 				=> $g_permission['instructor'],
	 'editCrossListings' 			=> $g_permission['proxy'],
	 'editTitle' 					=> $g_permission['proxy'],
	 'editClass' 					=> $g_permission['proxy'],
	 'createClass' 					=> $g_permission['instructor'],
	 'createNewClass' 				=> $g_permission['instructor'],		
	 'addClass' 					=> $g_permission['student'],		
	 'removeClass' 					=> $g_permission['student'],
	 'deleteClass' 					=> $g_permission['staff'],
	 'confirmDeleteClass' 			=> $g_permission['staff'],
	 'deleteClassSuccess' 			=> $g_permission['staff'],	
	 'copyItems' 					=> $g_permission['instructor'],
	 'processCopyItems' 			=> $g_permission['instructor'],
	 'manageUser' 					=> $g_permission['custodian'],
	 'newProfile' 					=> $g_permission['student'],
	 'editProfile' 					=> $g_permission['student'],
	 'storeUser' 					=> $g_permission['student'],
	 'editUser' 					=> $g_permission['staff'],
	 'mergeUsers' 					=> $g_permission['staff'],
	 'addUser' 						=> $g_permission['staff'],
	 'assignProxy' 					=> $g_permission['instructor'],
	 'assignInstr' 					=> $g_permission['instructor'],
	 'setPwd' 						=> $g_permission['custodian'],
	 'resetPwd' 					=> $g_permission['custodian'],				
	 'removePwd' 					=> $g_permission['custodian'],
	 'addProxy' 					=> $g_permission['instructor'],
	 'removeProxy' 					=> $g_permission['instructor'],
	 'editItem' 					=> $g_permission['proxy'],
	 'editMultipleReserves'			=> $g_permission['proxy'],
	 'editHeading' 					=> $g_permission['proxy'],
	 'processHeading' 				=> $g_permission['proxy'],
	 'duplicateReserve'				=> $g_permission['staff'],
	 'displayRequest' 				=> $g_permission['staff'],
	 'processRequest' 				=> $g_permission['staff'],
	 'storeRequest' 				=> $g_permission['staff'],
	 'deleteRequest' 				=> $g_permission['staff'],
	 'printRequest' 				=> $g_permission['staff'],
	 'addDigitalItem' 				=> $g_permission['proxy'],
	 'addPhysicalItem' 				=> $g_permission['proxy'], 

	 'copyClass' 					=> $g_permission['staff'],
	 'copyClassOptions'				=> $g_permission['staff'],
	 'copyExisting' 				=> $g_permission['staff'],
	 'copyNew' 						=> $g_permission['staff'],
	 'importClass'					=> $g_permission['instructor'],	
	 'processCopyClass' 			=> $g_permission['instructor'],
	 'addNote' 						=> $g_permission['proxy'],
	 'saveNote' 					=> $g_permission['proxy'],
	 'exportClass' 					=> $g_permission['proxy'],
			
	 'searchTab' 					=> $g_permission['staff'],
	 'doSearch' 					=> $g_permission['staff'],
	 'addResultsToClass' 			=> $g_permission['staff'],
	
	 'reportsTab' 					=> $g_permission['instructor'],
	 'viewReport'	 				=> $g_permission['instructor'],
	 
	 'admin'	 					=> $g_permission['admin']
);
?>