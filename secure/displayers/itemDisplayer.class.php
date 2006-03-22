<?
/*******************************************************************************
itemDisplayer.class.php

Created by Dmitriy Panteleyev (dpantel@emory.edu)

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

require_once('secure/displayers/baseDisplayer.class.php');
require_once('secure/displayers/noteDisplayer.class.php');
require_once('secure/managers/ajaxManager.class.php');

class itemDisplayer extends baseDisplayer {
	
	
	/**
	 * @return void
	 * @param reserveItem object $reserveItem
	 * @desc Displays the edit-item-source block
	 */
	function displayEditItemSource(&$reserveItem) {
		global $u, $g_permission;

		//editing an electronic item - show URL/upload fields
		if($reserveItem->getItemGroup() == 'ELECTRONIC'):
?>
		<div class="headingCell1">ITEM SOURCE</div>
		<div id="item_source" style="padding:8px 8px 12px 8px;">
			<script language="JavaScript">
				var currentItemSourceOptionID;
				
				function toggleItemSourceOptions(option_id) {
					if(document.getElementById(currentItemSourceOptionID)) {
						document.getElementById(currentItemSourceOptionID).style.display = 'none';
					}
					if(document.getElementById(option_id)) {
						document.getElementById(option_id).style.display = '';
					}
					
					currentItemSourceOptionID = option_id;
				}
			</script>		

			<div style="overflow:auto;" class="strong">
				Current URL <small>[<a href="reservesViewer.php?item=<?=$reserveItem->getItemID()?>" target="_blank">Preview</a>]</small>: 
<?php		if($reserveItem->isLocalFile()): //local file ?>
				Local File 
<?php			if($u->getRole() >= $g_permission['staff']): //only show local path to staff or greater ?>
				 &ndash; <em><?=$reserveItem->getURL()?></em>
<?php			endif; ?>
<?php		else: //remote file - show link to everyone ?>
				<em><?=$reserveItem->getURL()?></em>
<?php		endif; ?>
			</div>
			<small>
				Please note that items stored on the ReservesDirect server are access-restricted; use the Preview link to view the item.
				<br />
				To overwrite this URL, use the options below.
			</small>
			<p />
			<div>
				<input type="radio" name="documentType" checked="checked" onclick="toggleItemSourceOptions('');" /> Maintain current URL &nbsp;
				<input type="radio" name="documentType" value="DOCUMENT" onclick="toggleItemSourceOptions('item_source_upload');" /> Upload new file &nbsp;
				<input type="radio" name="documentType" value="URL" onclick="toggleItemSourceOptions('item_source_link');" /> Change URL
			</div>
			<div style="margin-left:40px;">
				<div id="item_source_upload" style="display:none;">
					<input type="file" name="userFile" size="50" />
				</div>
				<div id="item_source_link" style="display:none;">
					<input name="url" type="text" size="50" />
					<input type="button" onclick="openNewWindow(this.form.url.value, 500);" value="Preview" />
				</div>
			</div>
		</div>	
<?php	
		//editing a physical item - show library, etc.
		//only allow staff or better to edit this info
		elseif($reserveItem->isPhysicalItem() && ($u->getRole() >= $g_permission['staff'])):	 
?>
		<div class="headingCell1">ITEM SOURCE</div>
		<div id="item_source" style="padding:8px 8px 12px 8px;">
	    	<table border="0" cellpadding="2" cellspacing="0">	
	    		<tr>
	    			<td align="right">
	    				Reserve Desk:
	    			</td>
	    			<td>
	    				<select name="home_library">
<?php
			foreach($u->getLibraries() as $lib):
				$selected_lib = ($reserveItem->getHomeLibraryID() == $lib->getLibraryID()) ? 'selected="selected"' : '';
?>
							<option value="<?=$lib->getLibraryID()?>"<?=$selected_lib?>><?=$lib->getLibrary()?></option>			
<?php		endforeach; ?>
	    				</select>
	    			</td>
	    		</tr>
<?php
			//details from the physical copy table (barcode/call num)
			if($reserveItem->getPhysicalCopy()):
?>
	    		<tr>		
					<td align="right">
						<font color="#FF0000">*</font>&nbsp;Barcode:
					</td>
					<td>
						<input name="barcode" type="text" id="barcode" size="30" value="<?=$reserveItem->physicalCopy->getBarcode()?>" />
	
					</td>
				</tr>
				<tr>		
					<td align="right">
						Call Number:
					</td>
					<td>
						<input name="call_num" type="text" id="call_num" size="30" value="<?=$reserveItem->physicalCopy->getCallNumber()?>" />
					</td>				
				</tr>
			</table>
		</div>
<?php
			endif;	//end physical copy info
		endif; //end physical item block
	}	//displayEditItemSource()
	
	
	/**
	 * @return void
	 * @param Reserve object $reserve
	 * @desc Displays the edit-item-reserve-details block
	 */
	function displayEditItemReserveDetails(&$reserve) {
		global $calendar;
		
		switch($reserve->getStatus()) {
			case 'ACTIVE':
				$reserve_status_active = 'checked="true"';
				$reserve_status_inactive = '';
				$reserve_block_vis = '';
				break;
			case 'INACTIVE':
				$reserve_status_active = '';
				$reserve_status_inactive = 'checked="true"';
				$reserve_block_vis = 'style="visibility:hidden;"';
				break;
		}
		
		//dates
		$reserve_activation_date = $reserve->getActivationDate();
		$reserve_expiration_date = $reserve->getExpirationDate();
		
		//set reset dates to course dates
		$ci = new courseInstance($reserve->getCourseInstanceID());
		$course_activation_date = $ci->getActivationDate();	
		$course_expiration_date = $ci->getExpirationDate();

		//determine the parent heading
		$parent_heading_id = $reserve->getParent();		
		if(empty($parent_heading_id)) {
			$parent_heading_id = 'root';	//this will pre-select the main list
		}
?>
		<script language="JavaScript">
		//<!--			
			//shows/hides activation/expiration date form elements
			function toggleDates() {
				if(document.getElementById('reserve_status_active').checked) {
					document.getElementById('reserve_dates_block').style.display = '';
				}
				else {
					document.getElementById('reserve_dates_block').style.display = 'none';
				}
			}
			
			//resets reserve dates
			function resetDates(from, to) {
				document.getElementById('reserve_activation_date').value = from;
				document.getElementById('reserve_expiration_date').value = to;
			}
		//-->
		</script>
		
		<div class="headingCell1">RESERVE DETAILS</div>
		<div id="reserve_details" style="padding:8px 8px 12px 8px;">
		
<?php	if($reserve->getStatus()=='IN PROCESS'): ?>

			<div>
				<strong>Current Status:</strong>&nbsp;<span class="inProcess">IN PROCESS</span>
				<br />
				Please contact your Reserves staff to inquire about the status of this reserve.
				<input type="hidden" name="reserve_status" value="IN PROCESS" />
			</div>
						
<?php	else: ?>
	
			<div style="float:left; width:30%;">
				<strong>Set Status:</strong>
				<br />
				<div style="margin-left:10px; padding:3px;">
					<input type="radio" name="reserve_status" id="reserve_status_active" value="ACTIVE" onChange="toggleDates();" <?=$reserve_status_active?> />&nbsp;<span class="active">ACTIVE</span>
					<input type="radio" name="reserve_status" id="reserve_status_inactive" value="INACTIVE" onChange="toggleDates();" <?=$reserve_status_inactive?> />&nbsp;<span class="inactive">INACTIVE</span>
				</div>
			</div>
						
			<div id="reserve_dates_block" style="float:left;" <?=$reserve_block_vis?>>
				<strong>Active Dates</strong> (YYYY-MM-DD) &nbsp;&nbsp; [<a href="#" name="reset_dates" onclick="resetDates('<?=$course_activation_date?>', '<?=$course_expiration_date?>'); return false;">Reset dates</a>]
				<br />
				<div style="margin-left:10px;">
					From:&nbsp;<input type="text" id="reserve_activation_date" name="reserve_activation_date" size="10" maxlength="10" value="<?=$reserve_activation_date?>" /> <?=$calendar->getWidgetAndTrigger('reserve_activation_date', $reserve_activation_date)?>
					To:&nbsp;<input type="text" id="reserve_expiration_date" name="reserve_expiration_date" size="10" maxlength="10" value="<?=$reserve_expiration_date?>" />  <?=$calendar->getWidgetAndTrigger('reserve_expiration_date', $reserve_expiration_date)?>
				</div>
			</div>
				
<?php	endif; ?>

			<div style="clear:left; padding-top:10px;">
				<strong>Current Heading:</strong> 
				<?php self::displayHeadingSelect($ci, $parent_heading_id); ?>
			</div>		
		</div>
<?php
	}
	
	
	/**
	* @return void
	* @param reserveItem $item reserveItem object
	* @desc Displays the edit-item-item-details block
	*/	
	function displayEditItemItemDetails($item) {
		global $u;
				
		//private user
		if( !is_null($item->getPrivateUserID()) ) {
			$privateUserID = $item->getPrivateUserID();
			$item->getPrivateUser();
			$privateUser = $item->privateUser->getName(). ' ('.$item->privateUser->getUsername().')';
		}
?>
        
		<script language="JavaScript">
		//<!--
			//shows/hides personal item elements; marks them as required or not
			function togglePersonal(enable) {
				if(enable) {
					document.getElementById('personal_item_yes').checked = true;
					document.getElementById('personal_item_owner_block').style.display ='';
					togglePersonalOwnerSearch();
				}
				else {
					document.getElementById('personal_item_no').checked = true;
					document.getElementById('personal_item_owner_block').style.display ='none';
				}
			}
		
			//shows/hides personal item owner search fields
			function togglePersonalOwnerSearch() {
				//if no personal owner set, 
				if(document.getElementById('personal_item_owner_curr').checked) {
					document.getElementById('personal_item_owner_search').style.visibility = 'hidden';
				}
				else if(document.getElementById('personal_item_owner_new').checked) {
					document.getElementById('personal_item_owner_search').style.visibility = 'visible';
				}	
			}
		//-->
		</script>
		
		<div class="headingCell1">ITEM DETAILS</div>
		<div id="item_details" style="padding:8px 8px 12px 8px;">
			<table border="0" cellpadding="2" cellspacing="0">		
	    		<tr>
	    			<td align="right">
	    				<font color="#FF0000">*</font>&nbsp;Document Title:
	    			</td>
	    			<td>
	    				<input name="title" type="text" id="title" size="50" value="<?=$item->getTitle()?>">
	    			</td>
	    		</tr>
	    		<tr>
	    			<td align="right">
	    				Author/Composer:
	    			</td>
	    			<td>
	    				<input name="author" type="text" id="author" size="50" value="<?=$item->getAuthor()?>">
	    			</td>
	    		</tr>
	    		<tr>
	    			<td align="right">
	    				Performer:
	    			</td>
	    			<td>
	    				<input name="performer" type="text" id="performer" size="50" value="<?=$item->getPerformer()?>">
	    			</td>
	    		</tr>
	    		<tr>
	    			<td align="right">
	    				Document Type Icon:
	    			</td>
	    			<td>
	    				<select name="selectedDocIcon" onChange="document.iconImg.src = this[this.selectedIndex].value;">
<?php
		foreach($u->getAllDocTypeIcons() as $icon):
			$selected = ($item->getItemIcon() == $icon['helper_app_icon']) ? ' selected="selected"' : '';
?>
							<option value="<?=$icon['helper_app_icon']?>"<?=$selected?>><?=$icon['helper_app_name']?></option>
<?php	endforeach; ?>
						</select>
						<img name="iconImg" width="24" height="20" src="<?=$item->getItemIcon()?>" />
	    			</td>
	    		</tr>
	    		<tr>
	    			<td align="right">
	    				Book/Journal/Work Title:
	    			</td>
	    			<td>
	    				<input name="volumeTitle" type="text" id="volumeTitle" size="50" value="<?=$item->getVolumeTitle()?>">
	    			</td>
	    		</tr>
	    		<tr>
	    			<td align="right">
	    				Volume/Edition:
	    			</td>
	    			<td>
	    				<input name="volumeEdition" type="text" id="volumeEdition" size="50" value="<?=$item->getVolumeEdition()?>">
	    			</td>
	    		</tr>
	    		<tr>
	    			<td align="right">
	    				Pages/Time
	    			</td>
	    			<td>
	    				<input name="pagesTimes" type="text" id="pagesTimes" size="50" value="<?=$item->getPagesTimes()?>">
	    			</td>
	    		</tr>
	    		<tr>
	    			<td align="right">
	    				Source/Year:
	    			</td>
	    			<td>
	    				<input name="source" type="text" id="source" size="50" value="<?=$item->getSource()?>">
	    			</td>
	    		</tr>
				<tr id="personal_item_row" valign="top">
					<td align="right">
						Personal Copy Owner:
					</td>
					<td>
						<div id="personal_item_choice" style="background-color:#EEDDCC;">
							<input type="radio" name="personal_item" id="personal_item_no" value="no" onChange="togglePersonal(0);" /> No
							&nbsp;&nbsp;
							<input type="radio" name="personal_item" id="personal_item_yes" value="yes" onChange="togglePersonal(1);" /> Yes
						</div>
						<div id="personal_item_owner_block" style="padding:2px 3px 15px; background-color:#DFD8C6; border-top:1px dashed #999999;">
<?php
	//if there is an existing owner, give a choice of picking new one
	if(isset($privateUser)):
?>
							<input type="radio" name="personal_item_owner" id="personal_item_owner_curr" value="old" checked="checked" onChange="togglePersonalOwnerSearch();" /> Current - <strong><?=$privateUser?></strong>
							<br />
							<input type="radio" name="personal_item_owner" id="personal_item_owner_new" value="new" onChange="togglePersonalOwnerSearch();" /> New &nbsp;
<?php
	else:	//if not, then just assume we are searching for a new one
?>
							<input type="hidden" name="personal_item_owner" id="personal_item_owner_new" value="new" />
<?php
	endif;
?>
							<span id="personal_item_owner_search">
<?php
		//ajax user lookup
		$mgr = new ajaxManager('lookupUser', null, null, null, null, false, array('min_user_role'=>3, 'field_id'=>'selected_owner'));
		$mgr->display();		
?>
							</span>
						</div>
					</td>
				</tr>
			</table>
		</div>
		
		<script language="JavaScript">
			//set up some fields on load
			if( document.getElementById('personal_item_owner_curr') != null ) {	//if there is already a private owner
				//select current owner
				document.getElementById('personal_item_owner_curr').checked = true;
				//show private owner block
				togglePersonal(1);			
			}
			else {
				//default to no private owner
				togglePersonal(0);
			}
		</script>
<?php
	}
	
	
	/**
	 * @return void
	 * @param reserveItem $item reserveItem object
	 * @param reserves $reserve (optional) reserve object
	 * @desc Displays the edit-item-notes block
	 */	
	function displayEditItemNotes($item, $reserve=null) {
		//item notes
		$item_notes = $item->getNotes();
		//referrer obj for deleting notes
		$note_ref = 'itemID='.$item->getItemID();
		
		//reserve notes - only applies if we are editing a reserve (item instance linked to a course instance)
		if( !empty($reserve) && ($reserve instanceof reserve) ) {	//we are editing reserve info
			//notes
			$reserve_notes = $reserve->getNotes();
			//override referrer obj
			$note_ref = 'reserveID='.$reserve->getReserveID();			
		}
		else {
			$reserve_notes = null;
		}
?>
		<div class="headingCell1">NOTES</div>
		<div id="item_notes" style="padding:8px 8px 12px 8px;">
			<table border="0" cellpadding="2" cellspacing="0">
				<?php self::displayEditNotes($reserve_notes, $note_ref);	//show reserve notes ?>
				<?php self::displayEditNotes($item_notes, $note_ref);	//show reserve notes ?>
			</table>
		</div>
		<div id="add_note" style="text-align:center; padding:10px; border-top:1px solid #333333;">
			<?php self::displayAddNoteButton($note_ref); //display "Add Note" button ?>			
		</div>
<?php
	}
	
	
	/**
	 * @return void
	 * @param reserveItem $item reserveItem object
	 * @param reserves $reserve (optional) reserve object
	 * @param array $dub_array (optional) array of information pertaining to duplicating an item. currently 'dubReserve' flag and 'selected_instr'
	 * @desc Displays form for editing item information (optionally: reserve information)
	 */	
	function displayEditItemMeta($item, $reserve=null, $dub_array=null) {
		//determine if editing a reserve
		if(!empty($reserve) && ($reserve instanceof reserve)) {	//valid reserve obj
			$edit_reserve = true;
			//make sure that the item is for this reserve
			$reserve->getItem();
			$item = $reserve->item;
		}
		else $edit_reserve = false;
		
		//build the form
?>
		<script language="JavaScript">
		//<!--
			function validateForm(frm,physicalCopy) {			
				var alertMsg = "";

				if (frm.title.value == "")
					alertMsg = alertMsg + "Title is required.<br>";
				
				if (physicalCopy) {
					//make sure this physical copy is supposed to have a barcode
					//	if it is, there will be an input element for it in the form
					if( (document.getElementById('barcode') != null) && (document.getElementById('barcode').value == '') )
						alertMsg = alertMsg + "Barcode is required.<br />";
				}
				else if((frm.documentType.value == "DOCUMENT") && (frm.userFile.value == "")) {
					alertMsg = alertMsg + "You must choose a file to upload.<br />";
				}
				else if((frm.documentType.value == "URL") && (frm.url.value == "")) {
					alertMsg = alertMsg + "URL is required.<br />";
				}
				
				if (!alertMsg == "") { 
					document.getElementById('alertMsg').innerHTML = alertMsg;
					return false;
				}					
			}
		//-->
		</script>
		
<?php	if($item->getItemGroup() == 'ELECTRONIC'): ?>
		<form name="itemForm" enctype="multipart/form-data" action="index.php?cmd=editItem" method="post" onSubmit="return validateForm(this,false);">		
<?php	else: ?>
		<form name="itemForm" action="index.php?cmd=editItem" method="post" onSubmit="return validateForm(this,true);">		
<?php	endif; ?>

			<input type="hidden" name="itemID" value="<?=$item->getItemID()?>" />
			<?php self::displayHiddenFields($dub_array); //add duplication info as hidden fields ?>	
<?php	if($edit_reserve): ?>
			<input type="hidden" name="reserveID" value="<?=$reserve->getReserveID()?>" />	
<?php	endif; ?>
			
			<div id="item_meta" class="displayArea">		
<?php
		//show reserve details block
		if($edit_reserve) {
			self::displayEditItemReserveDetails($reserve);
		}
		
		//show item source
		self::displayEditItemSource($item);
		
		//show item details
		self::displayEditItemItemDetails($item);
		
		//show item/reserve notes
		self::displayEditItemNotes($item, $reserve);
?>
			</div>

			<strong style="color:#FF0000;">*</strong> <span class="helperText">= required fields</span>
			<p />
			<div style="padding:10px; text-align:center;">
				<input type="submit" name="Submit" value="Save Changes">
			</div>
	
		</form>

<?php		
	}
	
	
	/**
	 * @return void
	 * @param reserveItem $item reserveItem object
	 * @param reserve $reserve (optional) reserve object
	 * @param array $dub_array (optional) array of information pertaining to duplicating an item. currently 'dubReserve' flag and 'selected_instr'
	 * @param string $tab (optional) indicates which sub-screen to show
	 * @desc Displays form for editing item information (optionally: reserve information)
	 */
	function displayEditItem($item, $reserve=null, $dub_array=null, $tab=null) {
		global $u, $g_permission;
		
		//determine if editing a reserve
		if(!empty($reserve) && ($reserve instanceof reserve)) {
			$edit_reserve = true;
			$edit_item_href = 'reserveID='.$reserve->getReserveID();			
		}
		else {
			$edit_reserve = false;
			$edit_item_href = 'itemID='.$item->getItemID();
		}
?>
		<div id="alertMsg" align="center" class="failedText"></div>
        <p />  
        
<?php	if($edit_reserve): ?>
		<div style="text-align:right; font-weight:bold;"><a href="index.php?cmd=editClass&amp;ci=<?=$reserve->getCourseInstanceID()?>">Return to Class</a></div>
<?php	else: ?>
		<div style="text-align:right; font-weight:bold;"><a href="index.php?cmd=doSearch&amp;search=<?=urlencode($_REQUEST['search'])?>">Return to Search Results</a></div>
<?php	endif; ?>

		<div class="contentTabs">
			<ul>
				<li class="current"><a href="index.php?cmd=editItem&amp;<?=$edit_item_href?>">Item Info</a></li>
<?php		if($u->getRole() >= $g_permission['staff']): ?>
				<li><a href="index.php?cmd=editItem&amp;<?=$edit_item_href?>&amp;tab=history">History</a></li>
				<li><a href="index.php?cmd=editItem&amp;<?=$edit_item_href?>&amp;tab=copyright">Copyright <span class="alert">! pending review !</span></a></li>
<?php		endif; ?>
			</ul>
		</div>
		<div class="clear"></div>
		
<?php
		//switch screens
		//only allow non-default tab for staff and better
		$tab = ($u->getRole() >= $g_permission['staff']) ? $tab : null;				
		switch($tab) {
			case 'history':
			break;
			
			case 'copyright':
			break;
			
			default:
				self::displayEditItemMeta($item, $reserve, $dub_array);
			break;
		}
?>
<?php
	}

	
	function displayEditHeadingScreen($ci, $heading)
	{
		$heading->getItem();
		
		if ($heading->getSortOrder() == 0 || $heading->getSortOrder() == null)
			$currentSortOrder = "Not Yet Specified";
		else
			$currentSortOrder = $heading->getSortOrder();
?>
		<form action="index.php" method="post" name="editHeading">
		
			<input type="hidden" name="cmd" value="processHeading">
			<input type="hidden" name="nextAction" value="editClass">
			<input type="hidden" name="ci" value="<?=$ci?>">
			<input type="hidden" name="headingID" value="<?=$heading->itemID?>">
			
		<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
			<tr>
				<td colspan="2" align="right"><strong><a href="index.php?cmd=editClass&ci=<?=$ci?>">Cancel and return to class</a></strong></td>	
			</tr>
			<tr>
				<td colspan="2">
					<div class="helperText" style="align:left; padding:8px 0 20px 0; margin-right:180px;">
					Headings help organize your list of materials into topics or weeks. Headings can stand alone, 
					or you can add items to them. To add an item to a heading (like you would to a folder), go to the Edit Class
					screen, check the items to add to the heading, and scroll to the bottom of your list of materials.
					Select which heading to add the materials to and click the "Submit" button.
					</div>
				</td>
			</tr>
			<tr>
				<td class="headingCell1" width="25%" align="center">HEADING DETAILS</td>
				<td width="75%" align="center">&nbsp;</td>
			</tr>
		    <tr>
		    	<td colspan="2" class="borders">
			    	<table width="100%" border="0" cellspacing="0" cellpadding="5">
			    		<tr>
			    			<td width="30%" bgcolor="#CCCCCC" align="right" class="strong">
			    				<font color="#FF0000">*</font>&nbsp;Heading Title:
			    			</td>
			    			<td>
			    				<input name="heading" type="text" size="60" value="<?=$heading->item->getTitle()?>" />
			    			</td>
			    		</tr>
			    		<tr>
			    			<td bgcolor="#CCCCCC" align="right" class="strong">
			    				Current Sort Position:
			    			</td>
	        				<td>
	        					<?=$currentSortOrder?>
	        				</td>			    		
			    		</tr>
<?php
		//notes - only deal with notes if editing a heading (as opposed to creating)
		if($heading->getReserveID()):		
			//get notes
			$itemNotes = $heading->item->getNotes();
			$reserveNotes = $heading->getNotes();
			//show note edit boxes
			self::displayEditNotes($itemNotes, 'headingID='.$heading->getReserveID().'&amp;ci='.$ci);
			self::displayEditNotes($reserveNotes, 'headingID='.$heading->getReserveID().'&amp;ci='.$ci);
			
			//display "Add Note" button
?>
						<tr>
							<td colspan="2" bgcolor="#CCCCCC" align="center" class="borders" style="border-left:0px; border-bottom:0px; border-right:0px;">
								<?php self::displayAddNoteButton('reserveID='.$heading->getReserveID()); ?>
							</td>
						</tr>
<?php	endif; ?>

					</table>
        		</td>
      		</tr>
      		<tr>
      			<td colspan="2" align="center">
      				<br />
      				<input type="submit" name="submit" value="Save Heading" />
				</td>
      		</tr>
    	</table>
    	</form>
<?php
  
	}


	/**
	* @return void
	* @param reserves $reserve reserve object
	* @param object $user user interface object (object depends on user type)
	* @desc Displays form for editing reserve information. Limited version of editItem form.
	*/
	function displayEditReserveScreen($reserve, $user) {
		global $calendar, $g_serverName;
		
		$title = $reserve->item->getTitle();
		$author = $reserve->item->getAuthor();
		$performer = $reserve->item->getPerformer();
		$volTitle = $reserve->item->getVolumeTitle();
		$volEdition = $reserve->item->getVolumeEdition();
		$pagesTimes = $reserve->item->getPagesTimes();
		$source = $reserve->item->getSource();
		$docTypeIcon = $reserve->item->getItemIcon();
		$docTypeIcons = $user->getAllDocTypeIcons();
		$reserve_notes = $reserve->getNotes();
		$url = $reserve->item->getURL();
		
		//URL hiding fun - if URL points to a local resource, hide it	
		$hide_url = (stripos($url, $g_serverName) !== false) ? true : false;

		//reserve status/dates stuff
		
		switch($reserve->getStatus()) {
			case 'ACTIVE':
				$reserve_status_active = 'checked="true"';
				$reserve_status_inactive = '';
				$reserve_block_vis = '';
				break;
			case 'INACTIVE':
				$reserve_status_active = '';
				$reserve_status_inactive = 'checked="true"';
				$reserve_block_vis = 'style="visibility:hidden;"';
				break;
		}
		
		//dates
		$reserve_activation_date = $reserve->getActivationDate();
		$reserve_expiration_date = $reserve->getExpirationDate();
		//set reset dates to course dates
		$ci = new courseInstance($reserve->getCourseInstanceID());
		$course_activation_date = $ci->getActivationDate();	
		$course_expiration_date = $ci->getExpirationDate();
			
?>
		<div id="alertMsg" align="center" class="failedText"></div>
        <p />
        
		<script language="JavaScript">
		//<!--
			function validateForm(frm) {			
				var alertMsg = "";

				if (frm.title.value == "")
					alertMsg = alertMsg + "Title is required.<br>";
				
				if(frm.url.value == "") {
					alertMsg = alertMsg + "URL is required.<br>";				
				}

				if (!alertMsg == "") { 
					document.getElementById('alertMsg').innerHTML = alertMsg;
					return false;
				}					
			}
			
			//shows/hides activation/expiration date form elements
			function toggleDates() {
				if(document.getElementById('reserve_status_active').checked) {
					document.getElementById('reserve_dates_block').style.visibility = 'visible';
				}
				else {
					document.getElementById('reserve_dates_block').style.visibility = 'hidden';
				}
			}
			
			//resets reserve dates
			function resetDates(from, to) {
				document.getElementById('reserve_activation_date').value = from;
				document.getElementById('reserve_expiration_date').value = to;
			}
		//-->
		</script>
		
		<form name="reservesForm" action="index.php?cmd=editReserve" method="post" onSubmit="return validateForm(this);">
			<input type="hidden" name="reserveID" value="<?=$reserve->getReserveID()?>" />

		<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
			<tr class="strong">
				<td align="right" colspan="2"><a href="index.php?cmd=editClass&amp;ci=<?=$reserve->getCourseInstanceID()?>">Return to Class</a></td>
			</tr>
			<tr>
				<td class="headingCell1" width="25%" align="center">RESERVE DETAILS</td>
				<td width="75%" align="center">&nbsp;</td>
			</tr>
			
			
			<tr>
				<td colspan="2" class="borders">
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr bgcolor="#CCCCCC">
							<td>
					
<?php	if($reserve->getStatus()=='IN PROCESS'): ?>

								<div id="statusText">
									<strong>Current Status:</strong>&nbsp;<span id="statusText" class="inProcess">IN PROCESS</span>
									<br />
									Please contact your Reserves staff to inquire about the status of this reserve.
									<input type="hidden" name="reserve_status" value="IN PROCESS" />
								</div>
						
<?php	else: ?>
								<table width="100%" border="0" cellspacing="0" cellpadding="5">
									<tr valign="top">
										<td width="25%">
											<strong>Set Status:</strong>
											<br />
											<div id="statusText" style="margin-left:10px;">
												<input type="radio" name="reserve_status" id="reserve_status_active" value="ACTIVE" onChange="toggleDates();" <?=$reserve_status_active?> />&nbsp;<span class="active">ACTIVE</span>
												<br />
												<input type="radio" name="reserve_status" id="reserve_status_inactive" value="INACTIVE" onChange="toggleDates();" <?=$reserve_status_inactive?> />&nbsp;<span class="inactive">INACTIVE</span>
											</div>
										</td>
										<td>
											<div id="reserve_dates_block" <?=$reserve_block_vis?>>
												<strong>Active Dates</strong> (YYYY-MM-DD) &nbsp;&nbsp; [<a href="#" name="reset_dates" onclick="resetDates('<?=$course_activation_date?>', '<?=$course_expiration_date?>'); return false;">Reset dates</a>]
												<br />
												<div style="margin-left:10px;">
													<strong>From:</strong>&nbsp;<input type="text" id="reserve_activation_date" name="reserve_activation_date" size="10" maxlength="10" value="<?=$reserve_activation_date?>" /> <?=$calendar->getWidgetAndTrigger('reserve_activation_date', $reserve_activation_date)?>
													<br />
													<strong>To:</strong>&nbsp;<input type="text" id="reserve_expiration_date" name="reserve_expiration_date" size="10" maxlength="10" value="<?=$reserve_expiration_date?>" />  <?=$calendar->getWidgetAndTrigger('reserve_expiration_date', $reserve_expiration_date)?>
												</div>
											</div>
										</td>
									</tr>
								</table>						
<?php	endif; ?>
							</td>
						</tr>
							

					</table>
				</td>
			</tr>
			<tr><td>&nbsp;</td></tr>	
			<tr>
				<td class="headingCell1" width="25%" align="center">ITEM DETAILS</td>
				<td width="75%" align="center">&nbsp;</td>
			</tr>
		    <tr>
		    	<td colspan="2" class="borders">
			    	<table width="100%" border="0" cellspacing="0" cellpadding="5">
			    		<tr>
			    			<td width="30%" bgcolor="#CCCCCC" align="right" class="strong">
			    				<font color="#FF0000">*</font>&nbsp;Document Title:
			    			</td>
			    			<td>
			    				<input name="title" type="text" id="title" size="50" value="<?=$title?>" />
			    			</td>
			    		</tr>
			    		<tr>
			    			<td bgcolor="#CCCCCC" align="right" class="strong">
			    				Author/Composer:
			    			</td>
			    			<td>
			    				<input name="author" type="text" id="author" size="50" value="<?=$author?>" />
			    			</td>
			    		</tr>
			    		
<?php	if($hide_url): ?>
						<input name="url" type="hidden" id="url" size="50" value="<?=$url?>" />
<?php	else: ?>
			    		<tr>
			    			<td bgcolor="#CCCCCC" align="right" class="strong">
			    				<font color="#FF0000">*</font>&nbsp;URL:
			    			</td>
			    			<td>
			    				<input name="url" type="text" id="url" size="50" value="<?=$url?>" />
			    			</td>
			    		</tr>
<?php	endif; ?>

			    		<tr>
			    			<td bgcolor="#CCCCCC" align="right" class="strong">
			    				Performer:
			    			</td>
			    			<td>
			    				<input name="performer" type="text" id="performer" size="50" value="<?=$performer?>" />
			    			</td>
			    		</tr>
			    		<tr>
			    			<td bgcolor="#CCCCCC" align="right" class="strong">
			    				Document Type Icon:
			    			</td>
			    			<td>
			    				<select name="selectedDocIcon" onChange="document.iconImg.src = this[this.selectedIndex].value;">
<?php
		foreach($docTypeIcons as $icon):
			$selected = ($docTypeIcon == $icon['helper_app_icon']) ? ' selected="selected"' : '';
?>
									<option value="<?=$icon['helper_app_icon']?>"><?=$icon['helper_app_name']?></option>
<?php	endforeach; ?>
								</select>
								<img name="iconImg" width="24" height="20" src="<?=$docTypeIcon?>" />
			    			</td>
			    		</tr>
			    		<tr>
			    			<td bgcolor="#CCCCCC" align="right" class="strong">
			    				Book/Journal/Work Title:
			    			</td>
			    			<td>
			    				<input name="volumeTitle" type="text" id="volumeTitle" size="50" value="<?=$volTitle?>" />
			    			</td>
			    		</tr>
			    		<tr>
			    			<td bgcolor="#CCCCCC" align="right" class="strong">
			    				Volume/Edition:
			    			</td>
			    			<td>
			    				<input name="volumeEdition" type="text" id="volumeEdition" size="50" value="<?=$volEdition?>" />
			    			</td>
			    		</tr>
			    		<tr>
			    			<td bgcolor="#CCCCCC" align="right" class="strong">
			    				Pages/Time
			    			</td>
			    			<td>
			    				<input name="pagesTimes" type="text" id="pagesTimes" size="50" value="<?=$pagesTimes?>" />
			    			</td>
			    		</tr>
			    		<tr>
			    			<td bgcolor="#CCCCCC" align="right" class="strong">
			    				Source/Year:
			    			</td>
			    			<td>
			    				<input name="source" type="text" id="source" size="50" value="<?=$source?>" />
			    			</td>
			    		</tr>
<?php	if(!empty($reserve_notes)): 
			//show reserve notes
			self::displayEditNotes($reserve_notes, 'reserveID='.$reserve->getReserveID());
endif; ?>
			    		<tr>
			    			<td class="borders" bgcolor="#CCCCCC" colspan="2" align="center" text-align="center">
<?php 
		//display "Add Note" button
		self::displayAddNoteButton('reserveID='.$reserve->getReserveID());
?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align="left" colspan="2">
					<strong><font color="#FF0000">*</font></strong>&nbsp;<span class="helperText">=required fields</span>
				</td>
			</tr>
				<td colspan="2" style="text-align:center; padding-top:15px;">
					<p />
					<input type="submit" name="Submit" value="Save Changes">
				</td>
			</tr>
		</table>
		
		</form>

<?php
	
	}
	
	
	/**
	* @return void
	* @param reserveItem $item reserveItem object
	* @param object $user user interface object (object depends on user type)
	* @param reserves $reserve (optional) reserve object
	* @param array $owner_list (optional) array of user objects
	* @param string $search_serial (optional) urlencoded search query string
	* @param array $dub_array (optional) array of information pertaining to duplicating an item. currently 'dubReserve' flag and 'selected_instr'
	* @desc Displays form for editing item information (optionally: reserve information)
	*/	
	function displayEditItemScreen($item, $user, $reserve=null, $search_serial=null, $dub_array=null) {
		global $calendar;
		
		$title = $item->getTitle();
		$author = $item->getAuthor();
		$url = $item->getURL();
		$performer = $item->getPerformer();
		$volTitle = $item->getVolumeTitle();
		$volEdition = $item->getVolumeEdition();
		$pagesTimes = $item->getPagesTimes();
		$source = $item->getSource();
		$docTypeIcon = $item->getItemIcon();
		$docTypeIcons = $user->getAllDocTypeIcons();
		$libraries = $user->getLibraries();
		$library_id = $item->getHomeLibraryID();
		//notes
		$item_notes = $item->getNotes();
		
		//format long urls
		if(strlen($url) > 75) {
			$url = substr($url, 0, 75).'...';
		}

		//private user
		if( !is_null($item->getPrivateUserID()) ) {
			$privateUserID = $item->getPrivateUserID();
			$item->getPrivateUser();
			$privateUser = $item->privateUser->getName(). ' ('.$item->privateUser->getUsername().')';
		}
		
		//reserve - only applies if we are editing a reserve (item instance linked to a course instance)
		if( !empty($reserve) && ($reserve instanceof reserve) ) {	//we are editing reserve info
			$edit_reserve = true; //set flag to show the reserve block

			//status
			switch($reserve->getStatus()) {
				case 'ACTIVE':
					$reserve_status_active = 'checked="true"';
					$reserve_status_inactive = '';
					$reserve_block_vis = '';
					break;
				case 'INACTIVE':
					$reserve_status_active = '';
					$reserve_status_inactive = 'checked="true"';
					$reserve_block_vis = 'style="visibility:hidden;"';
					break;
			}
			
			//dates
			$reserve_activation_date = $reserve->getActivationDate();
			$reserve_expiration_date = $reserve->getExpirationDate();
			//set reset dates to course dates
			$ci = new courseInstance($reserve->getCourseInstanceID());
			$course_activation_date = $ci->getActivationDate();	
			$course_expiration_date = $ci->getExpirationDate();
			
			//notes
			$reserve_notes = $reserve->getNotes();
		} 
		else $edit_reserve = false;
?>

		<div id="alertMsg" align="center" class="failedText"></div>
        <p />
        
		<script language="JavaScript">
		//<!--
			function validateForm(frm,physicalCopy) {			
				var alertMsg = "";

				if (frm.title.value == "")
					alertMsg = alertMsg + "Title is required.<br>";
				
				if (physicalCopy) {
					//make sure this physical copy is supposed to have a barcode
					//	if it is, there will be an input element for it in the form
					if( (document.getElementById('barcode') != null) && (document.getElementById('barcode').value == '') )
						alertMsg = alertMsg + "Barcode is required.<br />";
				}
				else if((frm.documentType.value == "DOCUMENT") && (frm.userFile.value == "")) {
					alertMsg = alertMsg + "You must choose a file to upload.<br />";
				}
				else if((frm.documentType.value == "URL") && (frm.url.value == "")) {
					alertMsg = alertMsg + "URL is required.<br />";
				}
				
				
				if (!alertMsg == "") { 
					document.getElementById('alertMsg').innerHTML = alertMsg;
					return false;
				}					
			}
			
			//shows/hides personal item elements; marks them as required or not
			function togglePersonal(enable) {
				if(enable) {
					document.getElementById('personal_item_yes').checked = true;
					document.getElementById('personal_item_owner_block').style.display ='';
					togglePersonalOwnerSearch();
				}
				else {
					document.getElementById('personal_item_no').checked = true;
					document.getElementById('personal_item_owner_block').style.display ='none';
				}
			}
		
			//shows/hides personal item owner search fields
			function togglePersonalOwnerSearch() {
				//if no personal owner set, 
				if(document.getElementById('personal_item_owner_curr').checked) {
					document.getElementById('personal_item_owner_search').style.visibility = 'hidden';
				}
				else if(document.getElementById('personal_item_owner_new').checked) {
					document.getElementById('personal_item_owner_search').style.visibility = 'visible';
				}	
			}
			
			//shows/hides activation/expiration date form elements
			function toggleDates() {
				if(document.getElementById('reserve_status_active').checked) {
					document.getElementById('reserve_dates_block').style.visibility = 'visible';
				}
				else {
					document.getElementById('reserve_dates_block').style.visibility = 'hidden';
				}
			}
			
			//resets reserve dates
			function resetDates(from, to) {
				document.getElementById('reserve_activation_date').value = from;
				document.getElementById('reserve_expiration_date').value = to;
			}
		//-->
		</script>
		
<?php	if($item->getItemGroup() == 'ELECTRONIC'): ?>

		<form name="itemForm" enctype="multipart/form-data" action="index.php?cmd=editItem" method="post" onSubmit="return validateForm(this,false);">
		
<?php	else: ?>

		<form name="itemForm" action="index.php?cmd=editItem" method="post" onSubmit="return validateForm(this,true);">
		
<?php	endif; ?>

			<input type="hidden" name="itemID" value="<?=$item->getItemID()?>" />
			<input type="hidden" name="search" value="<?=$search_serial?>" />
			
<?php	if($edit_reserve): ?>
			<input type="hidden" name="reserveID" value="<?=$reserve->getReserveID()?>" />	
<?php 	endif; ?>

<?php	if(!empty($dub_array)): 
			foreach($dub_array as $key=>$val):
?>
			<input type="hidden" name="<?=$key?>" value="<?=$val?>" />	
<?php
			endforeach;
		endif;
?>


			<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
				<tr>
					<td colspan="2" align="center">
						<p class="helperText">
							You are editing the source item. Changes to information such as Title, Author, etc. will affect every reserve linked to this item.

<?php	if($edit_reserve): ?>
							<br />Changes to status (active/inactive) and activation/expiration dates will affect only this class.
<?php	endif; ?>

						</p>
						<br />
					</td>
				</tr>
				<tr class="strong">

<?php	if($edit_reserve): ?>
					<td align="right" colspan="2"><a href="index.php?cmd=editClass&amp;ci=<?=$reserve->getCourseInstanceID()?>">Return to Class</a></td>
<?php	else: ?>
					<td align="right" colspan="2"><a href="index.php?cmd=doSearch&amp;search=<?=urlencode($_REQUEST['search'])?>">Return to Search Results</a></td>
<?php	endif; ?>
				</tr>
				
<?php	if($edit_reserve):	//only show this block if editing a reserve ?>

				<tr>
					<td class="headingCell1" width="25%" align="center">RESERVE DETAILS</td>
					<td width="75%" align="center">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="2" class="borders">
						<table width="100%" border="0" cellspacing="0" cellpadding="0">
							<tr bgcolor="#CCCCCC">
								<td>
					
<?php		if($reserve->getStatus()=='IN PROCESS'): ?>

									<div id="statusText">
										<strong>Current Status:</strong>&nbsp;<span id="statusText" class="inProcess">IN PROCESS</span>
										<br />
										Please contact your Reserves staff to inquire about the status of this reserve.
										<input type="hidden" name="reserve_status" value="IN PROCESS" />
									</div>
						
<?php		else: ?>
									<table width="100%" border="0" cellspacing="0" cellpadding="5">
										<tr valign="top">
											<td width="25%">
												<strong>Set Status:</strong>
												<br />
												<div id="statusText" style="margin-left:10px;">
													<input type="radio" name="reserve_status" id="reserve_status_active" value="ACTIVE" onChange="toggleDates();" <?=$reserve_status_active?> />&nbsp;<span class="active">ACTIVE</span>
													<br />
													<input type="radio" name="reserve_status" id="reserve_status_inactive" value="INACTIVE" onChange="toggleDates();" <?=$reserve_status_inactive?> />&nbsp;<span class="inactive">INACTIVE</span>
												</div>
											</td>
											<td>
												<div id="reserve_dates_block" <?=$reserve_block_vis?>>
													<strong>Active Dates</strong> (YYYY-MM-DD) &nbsp;&nbsp; [<a href="#" name="reset_dates" onclick="resetDates('<?=$course_activation_date?>', '<?=$course_expiration_date?>'); return false;">Reset dates</a>]
													<br />
													<div style="margin-left:10px;">
														<strong>From:</strong>&nbsp;<input type="text" id="reserve_activation_date" name="reserve_activation_date" size="10" maxlength="10" value="<?=$reserve_activation_date?>" /> <?=$calendar->getWidgetAndTrigger('reserve_activation_date', $reserve_activation_date)?>
														<br />
														<strong>To:</strong>&nbsp;<input type="text" id="reserve_expiration_date" name="reserve_expiration_date" size="10" maxlength="10" value="<?=$reserve_expiration_date?>" />  <?=$calendar->getWidgetAndTrigger('reserve_expiration_date', $reserve_expiration_date)?>
													</div>
												</div>
											</td>
										</tr>
									</table>						
<?php		endif; ?>
								</td>
							</tr>
							
						</table>
					</td>
				</tr>
				<tr><td>&nbsp;</td></tr>	
<?php	endif;	//end reserve info block ?>		    	
				    	
<?php
		//electronic data block (file management)
		
		if($item->getItemGroup() == 'ELECTRONIC'):
			//set up some form elements
			if (!isset($request['documentType'])) {
				$maintain_current = 'checked';
				$upload_checked  = '';
				$upload_disabled = 'disabled';
				
				$url_checked	 = ''; 
				$url_disabled	 = 'disabled';
			} elseif ($request['documentType'] == 'DOCUMENT') {
				$upload_checked  = 'checked';
				$upload_disabled = '';
				
				$url_checked	 = ''; 
				$url_disabled	 = 'disabled';
			} else {
				$upload_checked  = '';
				$upload_disabled = 'disabled';
				
				$url_checked	 = 'checked'; 
				$url_disabled	 = '';				
			}
?>
				
				<tr>
					<td class="headingCell1" width="25%" align="center">ITEM SOURCE</td>
					<td width="75%" align="center">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="2" class="borders" bgcolor="#CCCCCC">
						<div class="strong" style="padding:5px;">
							Current URL: <em><?=$url?></em> [<a href="reservesViewer.php?item=<?=$item->getItemID()?>">Preview</a>]
							<br />
							<small>Please note that items stored on the ReservesDirect server are access restricted; use the Preview link to view the item.<br/>
                            To overwrite this URL, use the options below.</small>
							<p />
							<div style="margin:2px;"><input type="radio" name="documentType" <?=$maintain_current?> onClick="this.form.userFile.disabled = true; this.form.url.disabled = true;">&nbsp;Maintain current URL</div>
							<div style="margin:2px;"><input type="radio" name="documentType" value="DOCUMENT" <?=$upload_checked?> onClick="this.form.userFile.disabled = !this.checked; this.form.url.disabled = this.checked;">&nbsp;Upload new file&nbsp;&gt;&gt;&nbsp;<input type="file" name="userFile" size="40" <?=$upload_disabled?>></div>
							<div style="margin:2px;"><input type="radio" name="documentType" value="URL" <?=$url_checked?> onClick="this.form.url.disabled = !this.checked; this.form.userFile.disabled = this.checked;">&nbsp;Change URL&nbsp;&gt;&gt;&nbsp;<input name="url" type="text" size="50" <?=$url_disabled?>>&nbsp;<input type="button" onClick="openNewWindow(this.form.url.value, 500);" value="Preview"></div>
						</div>
					</td>
				</tr>
				<tr><td>&nbsp;</td></tr>
					
<?php
		endif;	//end electronic item info block
?>		
				<tr>
					<td class="headingCell1" width="25%" align="center">ITEM DETAILS</td>
					<td width="75%" align="center">&nbsp;</td>
				</tr>
			    <tr>
			    	<td colspan="2" class="borders">
				    	<table width="100%" border="0" cellspacing="0" cellpadding="5">
				    		<tr>
				    			<td width="30%" bgcolor="#CCCCCC" align="right" class="strong">
				    				<font color="#FF0000">*</font>&nbsp;Document Title:
				    			</td>
				    			<td>
				    				<input name="title" type="text" id="title" size="50" value="<?=$title?>">
				    			</td>
				    		</tr>
				    		<tr>
				    			<td bgcolor="#CCCCCC" align="right" class="strong">
				    				Author/Composer:
				    			</td>
				    			<td>
				    				<input name="author" type="text" id="author" size="50" value="<?=$author?>">
				    			</td>
				    		</tr>
				    		<tr>
				    			<td bgcolor="#CCCCCC" align="right" class="strong">
				    				Performer:
				    			</td>
				    			<td>
				    				<input name="performer" type="text" id="performer" size="50" value="<?=$performer?>">
				    			</td>
				    		</tr>
				    		<tr>
				    			<td bgcolor="#CCCCCC" align="right" class="strong">
				    				Document Type Icon:
				    			</td>
				    			<td>
				    				<select name="selectedDocIcon" onChange="document.iconImg.src = this[this.selectedIndex].value;">
<?php
		foreach($docTypeIcons as $icon):
			$selected = ($docTypeIcon == $icon['helper_app_icon']) ? ' selected="selected"' : '';
?>
										<option value="<?=$icon['helper_app_icon']?>"><?=$icon['helper_app_name']?></option>
<?php	endforeach; ?>
									</select>
									<img name="iconImg" width="24" height="20" src="<?=$docTypeIcon?>" />
				    			</td>
				    		</tr>
				    		<tr>
				    			<td bgcolor="#CCCCCC" align="right" class="strong">
				    				Book/Journal/Work Title:
				    			</td>
				    			<td>
				    				<input name="volumeTitle" type="text" id="volumeTitle" size="50" value="<?=$volTitle?>">
				    			</td>
				    		</tr>
				    		<tr>
				    			<td bgcolor="#CCCCCC" align="right" class="strong">
				    				Volume/Edition:
				    			</td>
				    			<td>
				    				<input name="volumeEdition" type="text" id="volumeEdition" size="50" value="<?=$volEdition?>">
				    			</td>
				    		</tr>
				    		<tr>
				    			<td bgcolor="#CCCCCC" align="right" class="strong">
				    				Pages/Time
				    			</td>
				    			<td>
				    				<input name="pagesTimes" type="text" id="pagesTimes" size="50" value="<?=$pagesTimes?>">
				    			</td>
				    		</tr>
				    		<tr>
				    			<td bgcolor="#CCCCCC" align="right" class="strong">
				    				Source/Year:
				    			</td>
				    			<td>
				    				<input name="source" type="text" id="source" size="50" value="<?=$source?>">
				    			</td>
				    		</tr>
<?php
		//physical item block
		if($item->isPhysicalItem()):
	
			//home library selector
?>
				    		<tr>
				    			<td bgcolor="#CCCCCC" align="right" class="strong">
				    				Reserve Desk:
				    			</td>
				    			<td>
				    				<select name="home_library">
<?php
			foreach($libraries as $lib):
				$selected_lib = ($library_id == $lib->getLibraryID()) ? 'selected="selected"' : '';
?>
										<option value="<?=$lib->getLibraryID()?>" <?=$selected_lib?>><?=$lib->getLibrary()?></option>			
<?php		endforeach; ?>
				    				</select>
				    			</td>
				    		</tr>
<?php
			//details from the physical copy table (barcode/call num)
			if($item->getPhysicalCopy()):
?>
				    		<tr>		
								<td bgcolor="#CCCCCC" align="right" class="strong">
									<font color="#FF0000">*</font>&nbsp;Barcode:
								</td>
								<td>
									<input name="barcode" type="text" id="barcode" size="20" value="<?=$item->physicalCopy->getBarcode()?>" />
				
								</td>
							</tr>
							<tr>		
								<td bgcolor="#CCCCCC" align="right" class="strong">
									Call Number:
								</td>
								<td>
									<input name="call_num" type="text" id="call_num" size="30" value="<?=$item->physicalCopy->getCallNumber()?>" />
								</td>				
							</tr>
<?php
			endif;	//end physical copy info
		endif; //end physical item block
		
		//personal copy/private user block
?>
							<tr id="personal_item_row" valign="top">
								<td align="right" bgcolor="#CCCCCC" class="strong">
									Personal Copy Owner:
								</td>
								<td>
									<div id="personal_item_choice" style="background-color:#EEDDCC;">
										<input type="radio" name="personal_item" id="personal_item_no" value="no" onChange="togglePersonal(0);" /> No
										&nbsp;&nbsp;
										<input type="radio" name="personal_item" id="personal_item_yes" value="yes" onChange="togglePersonal(1);" /> Yes
									</div>
									<div id="personal_item_owner_block" style="padding:2px 3px 15px; background-color:#DFD8C6; border-top:1px dashed #999999;">
<?php
	//if there is an existing owner, give a choice of picking new one
	if(isset($privateUser)):
?>
										<input type="radio" name="personal_item_owner" id="personal_item_owner_curr" value="old" checked="checked" onChange="togglePersonalOwnerSearch();" /> Current - <strong><?=$privateUser?></strong>
										<br />
										<input type="radio" name="personal_item_owner" id="personal_item_owner_new" value="new" onChange="togglePersonalOwnerSearch();" /> New &nbsp;
<?php
	else:	//if not, then just assume we are searching for a new one
?>
										<input type="hidden" name="personal_item_owner" id="personal_item_owner_new" value="new" />
<?php
	endif;
?>
										<span id="personal_item_owner_search">
<?php
		//ajax user lookup
		$mgr = new ajaxManager('lookupUser', null, null, null, null, false, array('min_user_role'=>3, 'field_id'=>'selected_owner'));
		$mgr->display();		
?>
										</span>
									</div>
								</td>
							</tr>
<?php	if(!empty($reserve_notes)): 
			//show reserve notes
			self::displayEditNotes($reserve_notes, 'reserveID='.$reserve->getReserveID());
		endif; ?>
<?php
		//build correct link for the item notes, depending on if we are editing item or reserve
		$link = $edit_reserve ? 'reserveID='.$reserve->getReserveID() : 'itemID='.$item->getItemID();
		
		//show notes
		self::displayEditNotes($item_notes, $link);
?>
							<tr>
			    			<td class="borders" bgcolor="#CCCCCC" colspan="2" align="center" text-align="center">
<?php 
		//display "Add Note" button
		self::displayAddNoteButton($link);
?>
							</td>
						</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td align="left" colspan="2">
						<strong><font color="#FF0000">*</font></strong>&nbsp;<span class="helperText">=required fields</span>
					</td>
				</tr>
				<tr>
					<td colspan="2">&nbsp;</td>
				<tr>
					<td colspan="2" align="center">
						<input type="submit" name="Submit" value="Save Changes">
					</td>
				</tr>
			</table>
		</form>

		<script language="JavaScript">
			//set up some fields on load
			if( document.getElementById('personal_item_owner_curr') != null ) {	//if there is already a private owner
				//select current owner
				document.getElementById('personal_item_owner_curr').checked = true;
				//show private owner block
				togglePersonal(1);			
			}
			else {
				//default to no private owner
				togglePersonal(0);
			}
		</script> 

<?php
	}

	
	/**
	* @return void
	* @param int $ci_id courseInstance ID
	* @param string $search_serial serialized search _request
	* @desc Displays editItem/editReserve success screen
	*/	
	function displayItemSuccessScreen($ci_id=null, $search_serial=null)	{		
?>
		<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
			<tr>
		    	<td width="140%"><img src="/images/spacer.gif" width="1" height="5"> </td>
		    </tr>
		    <tr>
		        <td align="left" valign="top" class="borders">
					<table width="50%" border="0" align="center" cellpadding="0" cellspacing="5">
		            	<tr>
		                	<td><strong>Your item has been updated successfully.</strong></td>
		                </tr>
		                <tr>
		                	<td align="left" valign="top">
		                		<ul>		                		
<?php	if($ci_id): ?>
					<li><a href="index.php?cmd=editClass&amp;ci=<?=$ci_id?>">Return to Class</a></li>
<?php	elseif($search_serial): ?>
					<li><a href="index.php?cmd=doSearch&amp;search=<?=$search_serial?>">Return to Search Results</a></li>					
<?php	endif; ?>
		                			<li><a href="index.php">Return to myReserves</a><br></li>
		                		</ul>
		                	</td>
		                </tr>
		            </table>
				</td>
			</tr>
		</table>
<?php
	}
	
	/**
	* @return void
	* @param int $ci_id courseInstance ID
	* @desc Displays editHeading success screen
	*/	
	function displayHeadingSuccessScreen($ci_id=null)	{		
?>
		<div class="borders" style="text-align:middle;">
			<div style="width:50%; margin:auto;">
				<strong>Your heading has been added/updated successfully.</strong>
				<br />
				<ul>
	    			<li><a href="index.php?cmd=editClass&amp;ci=<?=$ci_id?>">Return to class</a></li>
	    			<li><a href="index.php?cmd=editHeading&amp;ci=<?=$ci_id?>">Create another heading</a></a></li>
	    			<li><a href="index.php?cmd=customSort&amp;ci=<?=$ci_id?>">Change heading sort position</a></li>
	    		</ul>
			</div>
		</div>
<?php
	}
	
}
?>
