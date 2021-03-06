/*******************************************************************************
help_ajax.js

Created by Jason White (jbwhite@emory.edu)

This file is part of ReservesDirect

Copyright (c) 2004-2006 Emory University, Atlanta, Georgia.

Licensed under the ReservesDirect License, Version 1.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the full License at
http://www.reservesdirect.org/licenses/LICENSE-1.0

ReservesDirect is distributed in the hope that it will be useful,
but is distributed "AS IS" and WITHOUT ANY WARRANTY, without even the
implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE, and without any warranty as to non-infringement of any third
party's rights.  See the License for the specific language governing
permissions and limitations under the License.


ReservesDirect is located at:
http://www.reservesdirect.org/


*******************************************************************************

javascript functions that do stuff
	
*******************************************************************************/

<!-- //activate cloaking device

function checkAll(form, theState)
{
	for  (i = 0; i < form.elements.length; i++) {
		e = form.elements[i];
		if (e.type == "checkbox") {
			e.checked = theState ;
		}
	}
}

function focusOnForm() {
	if(document.forms.length > 0) {
		if(document.forms[0].elements.length > 0) {
			for(var x=0; x < document.forms[0].elements.length; x++) {
				if((document.forms[0].elements[x].type != "hidden") && (document.forms[0].elements[x].focus)) {	//if element supports focus() method
					document.forms[0].elements[x].focus();
					return;
				}
			}
		}
	}
}



function help(query) {
	if(document.getElementById('container') && document.getElementById('help-container') && document.getElementById('help-content')) {
		if(!query) {	//make sure there is a non-blank query
			query = 'cmd=help';
		}
		//point the iframe to the appropriate page
		document.getElementById('help-content').src = "help.php?" + query;
		toggleHelp(1);
	}
}

function toggleHelp(on) {
	if(document.getElementById('container') && document.getElementById('help-container')) {
		if(on) {
			//show the help block
			document.getElementById('container').className = 'helpOn';
			document.getElementById('help-container').style.display = 'block';
		}
		else {
			//hide the help block
			document.getElementById('container').className = 'helpOff';
			document.getElementById('help-container').style.display = 'none';
		}
	}
	
	return false;
}


/*
*@desc - This Function is called via the onChange event, when a user updates the sortOrder for a reserve item
*@desc - The function updates the Sort Order for all of the reserve records
*@param form - the form containing rows to be sorted
*@param oldSort - the name of the hidden field containing the oldSort Value of the changed row
*@param newSort - new Sort Value for the changed row
*@param elementName - name of the sortOrder text box for the changed row
*/
function updateSort(form, oldSort, newSort, elementName)
{
	var e, i, oldSortValue;
	
	//Why do I have to do this now??
	oldSort = oldSort + "";
	//Loop through elements to retrieve the oldSort Value from the hidden fields, and to set the oldSortValue = the newSort value
	for(i=0; i<form.elements.length; i++) {
		e = form.elements[i];
		if ((e.name == oldSort) && (e.type == "hidden")) {
			oldSortValue = e.value;
			e.value = newSort;
		}
	}
	
	//This is done so JavaScript will treat these variables as numbers, and not strings
	oldSortValue = (oldSortValue - 0);
	newSort = (newSort - 0);
	
	if ((newSort > oldSortValue)) {
		for(i=0; i<form.elements.length; i++) {
			e = form.elements[i];
			if (e.type == "text") {
				if (e.name != elementName) {
					e.value = (e.value - 0);
					if ((e.value > oldSortValue) && (e.value <= newSort)) {
						//this variable stores the hidden field, which contains the oldSortValue for this reserve item
						var k = form.elements[i-1];
						//decrement the current SortValue
						e.value = (e.value - 1);
						//update the oldSort value stored in the hidden field
						k.value = e.value;
					} 
				} 
			}
		}
	} else if ((newSort < oldSortValue)) {
		for(i=0; i<form.elements.length; i++) {
			e = form.elements[i];
			if (e.type == "text") {
				if (e.name != elementName) {
					e.value = (e.value - 0);
					if ((e.value < oldSortValue) && (e.value >= newSort)) {
						//this variable stores the hidden field, which contains the oldSortValue for this reserve item
						var k = form.elements[i-1];
						//increment the current SortValue 
						//(the addition is performed as minus a negative value b/c JavaScript treats the '+' operator as string concatenation)
						e.value = (e.value - -1);
						//update the oldSort value stored in the hidden field
						k.value = e.value;
					}
				} 
			}
		}
	}
}
	
function resetForm(form)
{
	//Reset the Form Fields
	form.reset();
	//Loop through elements to reset the hidden fields.
	//The Hidden fields contain the oldSortValue, and will be initialized to = the current sortValue
	for(i=0; i<form.elements.length; i++) {
		e = form.elements[i];
		if (e.type == "text") {
			var k=form.elements[i-1];
			k.value=e.value;
		}
	}
}

var newWindow;
var newWindow_returnValue;
//function openWindow(argList, size='width=800,height=500')
function openWindow(argList, size)
{
	var options  = size + ",toolbar=no,alwaysRaised=yes,dependent=yes,directories=no,hotkeys=no,menubar=no,resizable=yes,scrollbars=yes";
	var location = "index.php?" + argList;
	
	newWindow = window.open(location, "noteWindow", options);
}


function openNewWindow(url, size)
{
	var options  = size + ",toolbar=no,alwaysRaised=yes,dependent=yes,directories=no,hotkeys=no,menubar=no,resizable=yes,scrollbars=yes";
	var location = url;
	
	newWindow = window.open(location, "newWindow", options);
}

//-->