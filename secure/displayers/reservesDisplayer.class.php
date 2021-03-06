<?
/*******************************************************************************
reservesDisplayer.class.php


Created by Kathy Washington (kawashi@emory.edu)

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

*******************************************************************************/
require_once("secure/common.inc.php");
require_once('secure/displayers/noteDisplayer.class.php');
require_once('secure/classes/tree.class.php');


class reservesDisplayer extends noteDisplayer {

  function displayReserves($cmd, &$ci, &$tree_walker, $reserve_count, &$hidden_reserves=null, $preview_only=false) {
    global $u;
        
    if(!($ci->course instanceof course)) {
      $ci->getPrimaryCourse();
    }
    
    //if previewing, temporarily give the current user a role of student
    //Note: this process is reversed at the end of this method.
    if($preview_only) {
      $curr_user = $u;  //save current user
      $users = new users();
      $u = $users->initUser('student', $curr_user->getUserName());  //init current user as student
    }
        
        // announce rss feed to capable browsers
        echo "<link rel=\"alternate\" title=\"{$ci->course->department->name} {$ci->course->courseNo} {$ci->term} {$ci->year}\" href=\"rss.php?ci={$ci->courseInstanceID}\" type=\"application/rss+xml\"/>\n";
    
    $exit_class_link = $preview_only ? '<a href="javascript:window.close();">Close Window</a>' : '<a href="index.php">Exit class</a>' ;   
?>

    <div>   
      <div style="text-align:right;"><strong><?=$exit_class_link?></strong></div> 
      <div class="courseTitle"><?=$ci->course->displayCourseNo() . " " . $ci->course->getName()?></div>     
      <div class="courseHeaders"><span class="label"><?=$ci->displayTerm()?></span></div>     
      <div class="courseHeaders">
        <span class="label">Instructor(s):</span>
              
<?php 
    for($i=0;$i<count($ci->instructorList);$i++) {
      if ($i!=0) echo ',&nbsp;';
      echo '<a href="mailto:'.$ci->instructorList[$i]->getEmail().'">'.$ci->instructorList[$i]->getFirstName().'&nbsp;'.$ci->instructorList[$i]->getLastName().'</a>';
    }
?>    
  
      </div>
      <div class="courseHeaders">
        <span class="label">Crosslstings:</span>  
            
<?php
    if (count($ci->crossListings)==0) {
      echo 'None';
    }
    else {
      for ($i=0; $i<count($ci->crossListings); $i++) {
        if ($i>0) echo',&nbsp;';
        echo $ci->crossListings[$i]->displayCourseNo();
      }
    }
?>

      </div>
      <p />
      <small><strong>Helper Applications:</strong> <a href="http://www.adobe.com/products/acrobat/readstep2.html" target="_new">Adobe Acrobat</a>, <a href="http://www.real.com" target="_new">RealPlayer</a>, <a href="http://www.apple.com/quicktime/download/" target="_new">QuickTime</a>, <a href="http://office.microsoft.com/Assistance/9798/viewerscvt.aspx" target="_new">Microsoft Word</a></small>   
    </div>
    
        
    <form method="post" name="editReserves" action="index.php">
    
      <input type="hidden" name="cmd" value="<?=$cmd?>" />
      <input type="hidden" name="ci" value="<?=$ci->getCourseInstanceID()?>" />
      <input type="hidden" name="hideSelected" value="" />
      <input type="hidden" name="showAll" value="" />

    <table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
      <tr align="left" valign="middle">
        <td class="headingCell1">COURSE MATERIALS</td>
        <td width="75%" align="right">
<?php if(!$preview_only): ?>
          <input type="submit" name="hideSelected" value="Hide Selected" />
          <input type="submit" name="showAll" value="Show All" />
<?php endif; ?>
        </td>
      </tr>
      <tr valign="middle">
        <td class="headingCell1" align="center" colspan="2">
          <?php echo $reserve_count; ?> Item(s) On Reserve
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <ul style="list-style:none; padding-left:0px; margin:0px;">
      
<?php
    //begin displaying individual reserves
    //loop
    $prev_depth = 0;
    foreach($tree_walker as $leaf) {
      //close list tags if backing out of a sublist
      if($prev_depth > $tree_walker->getDepth()) {
        echo str_repeat('</ul></li>', ($prev_depth-$tree_walker->getDepth()));
      }
      
    
      $reserve = new reserve($leaf->getID()); //init a reserve object

      //is this item hidden?
      $reserve->hidden = in_array($leaf->getID(), $hidden_reserves) ? true : false;
      
      $rowStyle = ($rowStyle=='oddRow') ? 'evenRow' : 'oddRow'; //set the style

      //display the info
      echo '<li>';
      if($preview_only) {
        self::displayReserveRowPreview($reserve, 'class="'.$rowStyle.'"');
      }
      else {
        self::displayReserveRowView($reserve, 'class="'.$rowStyle.'"');
      }
      
      //start sublist or close list-item?
      echo ($leaf->hasChildren()) ? '<ul style="list-style:none;">' : '</li>';
      
      $prev_depth = $tree_walker->getDepth();
    }
    echo str_repeat('</ul></li>', ($prev_depth)); //close all lists
?>

          </ul>
        </td>
      </tr>
      <tr valign="middle">
        <td class="headingCell1" align="center" colspan="2">
          &nbsp;
        </td>
      </tr>
    </table>
    </form>
    
    <p />
    <div style="margin-left:5%; margin-right:5%; text-align:right;"><strong><?=$exit_class_link?></strong></div>
<?php

    //if previewing, return user to original state
    if($preview_only) {
      $u = $curr_user;
    }
  }
  
  function displayStaffAddReserve($request=null)
  {
    echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
    echo "  <tr><td width=\"100%\"><img src=\"images/spacer.gif\" width=\"1\" height=\"5\"></td></tr>\n";
    echo "  <tr>\n";
    echo "    <td align=\"center\" valign=\"top\">\n";
    //echo "      <table width=\"40%\" border=\"0\" cellspacing=\"0\" cellpadding=\"8\">\n";
    echo '      <table width="40%" border="0" cellspacing="0" cellpadding="8" class="borders">';
    echo "        <tr class=\"headingCell1\"><td width=\"40%\">Add / Process Materials</td></tr>\n";
    echo "        <tr align=\"left\" valign=\"top\">\n";
    echo "          <td width=\"40%\">\n";
    echo "            <ul>\n";
    echo "              <li><a href=\"index.php?cmd=displayRequest\" align=\"center\">Process Requests</a></li>\n";
    if(!empty($_REQUEST['ci'])) {
      echo '            <li><a href="index.php?cmd=importClass&amp;new_ci='.$_REQUEST['ci'].'" align="center">Import Class</a></li>';
    }
    if (!isset($request['ci'])) {
      // Do not display 'Add an Electronic Item' if the course instance is not defined.
      //echo "              <li><a href=\"index.php?cmd=addDigitalItem\" align=\"center\">Add an Electronic Item</a></li>\n";
    }
    else if ($request['ci'])
      echo "              <li><a href=\"index.php?cmd=addDigitalItem&ci=".$request['ci']."\" align=\"center\">Add an Electronic Item</a></li>\n";
    if (!isset($request['ci'])) {
      // Do not display 'Add an Physical Item' if the course instance is not defined.
      //echo "              <li><a href=\"index.php?cmd=addPhysicalItem\">Add a Physical Item</a></li>\n";
    }
    else if ($request['ci']) {
      echo "              <li><a href=\"index.php?cmd=addPhysicalItem&ci=".$request['ci']."\">Add a Physical Item</a></li>\n";
      echo "              <li><a href=\"index.php?cmd=searchScreen&ci=".$request['ci']."\">Search for the Item</a></li>\n";
    }
    echo "              <!--<li><a href=\"index.php?cmd=physicalItemXListing\">Physical Item Cross-listings </a>--><!--Goes to staff-mngClass-phys-XList1.html --></li>\n";
    echo "            </ul>\n";
    echo "          </td>\n";
    echo "        </tr>\n";
    //echo "<tr><td>&nbsp;</td></tr>";
    echo "      </table>\n";
    echo "    </td>\n";
    echo "  </tr>\n";
    echo "  <tr><td>&nbsp;</td></tr>\n";
    echo "</table>\n";    
  }
  
  
  function displaySelectInstructor($user, $page, $cmd)
  {
    $subordinates = common_getUsers('instructor');

    echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
    echo "  <tbody>\n";
    echo "    <tr><td width=\"100%\"><img src=\"images/spacer.gif\" width=\"1\" height=\"5\"> </td></tr>\n";
    echo "    <tr>\n";
        echo "      <td align=\"left\" valign=\"top\">\n";
        echo "        <table border=\"0\" align=\"center\" cellpadding=\"10\" cellspacing=\"0\">\n";
    echo "          <tr align=\"left\" valign=\"top\" class=\"headingCell1\">\n";
        echo "            <td width=\"50%\">Search by Instructor </td><td width=\"50%\">Search by Department</td>\n";
        echo "          </tr>\n";

        echo "          <tr>\n";
        echo "            <td width=\"50%\" align=\"left\" valign=\"top\" class=\"borders\" align=\"center\" NOWRAP>\n";

        echo "              <form method=\"post\" action=\"index.php\" name=\"frmReserveItem\">\n";
        //if (!is_null($courseInstance)) echo "         <input type=\"hidden\" name=\"ci\" value=\"$courseInstance\">\n";
      echo "                <input type=\"hidden\" name=\"page\" value=\"$page\">\n";
    echo "                <input type=\"hidden\" name=\"cmd\" value=\"$cmd\">\n";
    echo "                <input type=\"hidden\" name=\"u\" value=\"".$user->getUserID()."\">\n";
    echo "                <input type=\"submit\" name=\"Submit2\" value=\"Admin Your Classes\">\n";
    echo "              </form>\n";
        echo "              <br>\n";
        echo "              <form method=\"post\" action=\"index.php\" name=\"frmReserveItem\">\n";
      //if (!is_null($courseInstance)) echo "         <input type=\"hidden\" name=\"ci\" value=\"$courseInstance\">\n";
        echo "                <input type=\"hidden\" name=\"page\" value=\"$page\">\n";
    echo "                <input type=\"hidden\" name=\"cmd\" value=\"$cmd\">\n";
    echo "                <select name=\"u\">\n";
      echo "                  <option value=\"--\" selected>Choose an Instructor\n";
        foreach($subordinates as $subordinate)
    {
      echo "                  <option value=\"" . $subordinate['user_id'] . "\">" . $subordinate['full_name'] . "</option>\n";
    }

        echo "                </select>\n";
        //echo "                <br>\n";
        //echo "                <br>\n";
        echo "                <input type=\"submit\" name=\"Submit2\" value=\"Select Instructor\">\n";
        echo "              </form>\n";
        echo "            </td>\n";
        echo "            <td width=\"50%\" align=\"left\" valign=\"top\" class=\"borders\" align=\"center\" NOWRAP>&nbsp;\n";
        echo "            </td>\n";
        echo "          </tr>\n";
        echo "        </table>\n";
    echo "      </td>\n";
    echo "    </tr>\n";
    echo "  </tbody>\n";
    echo "</table>\n";

  }

  /**
 * @return void
 * @param int $ci -- user selected course_instance selected for DisplaySelectClass
 * @desc Allows user to determine how they would like to add Reserves
 *    expected next steps
 *      searchItems::searchScreen
 *      searchItems::uploadDocument
 *      searchItems::addURL
*/
function displaySearchItemMenu($ci)
{
  global $g_copyrightNoticeURL;
    
  print "<div style=\"border:1px solid #333333; padding:8px 8px 8px 40px; width:40%;float:left;\">\n"
  .  "          <p><strong>How would you like to add an item to your class?</strong></p>\n"
  .  "            <ul><li><a href=\"index.php?cmd=searchScreen&ci=$ci\">Search for the Item</a></li>\n"
  .  "                <li><a href=\"index.php?cmd=addDigitalItem&ci=$ci\">Add an electronic item</a> - upload a document or add a URL</li>\n"
  .  "            </ul>\n"
  .  "</div>\n"
  .    "<div style=\"float:right; width:40%; margin-top:25px; padding:10px; text-align:center; border:1px solid #666666; background-color:#CCCCCC;\">\n"
  .  "<strong><a href=\"$g_copyrightNoticeURL\" target=\"blank\">Copyright policy</a><strong> for adding materials to ReservesDirect.\n"
  .  "</div>\n"
  ;
}

  /**
   * @return void
   * @param string $page    -- the current page selector
   * @param string $subpage -- subpage selector
   * @param string $courseInstance -- user selected courseInstance
   * @desc Allows user search for items
   *    expected next steps
   *      open catalog in new window
   *      searchItems::displaySearchResults
  */
  function displaySearchScreen($page, $cmd, $ci=null)
  {
    global $g_catalogName, $g_libraryURL;

    $instructors = common_getUsers('instructor');

    echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
    echo "  <tbody>\n";
    echo "    <tr><td width=\"100%\"><img src=\"images/spacer.gif\" width=\"1\" height=\"5\"> </td></tr>\n";
    echo "    <tr>\n";
        echo "      <td align=\"left\" valign=\"top\">\n";
        echo "        <table border=\"0\" align=\"center\" cellpadding=\"10\" cellspacing=\"0\">\n";
    echo "          <tr align=\"left\" valign=\"top\" class=\"headingCell1\">\n";
        echo "            <td width=\"50%\">Search for Archived Materials</td><td width=\"50%\">Search by Instructor</td>\n";
        echo "          </tr>\n";

        echo "          <tr>\n";
        //    SEARCH BY Author or Title
        echo "            <td width=\"50%\" class=\"borders\" align=\"center\">\n";
        echo "              <br>\n";
        echo "              <form action=\"index.php\" method=\"post\">\n";
        echo "              <input type=\"text\" name=\"query\" size=\"25\">\n";
        echo "              <input type=\"hidden\" name=\"page\" value=\"$page\">\n";
    echo "              <input type=\"hidden\" name=\"cmd\" value=\"$cmd\">\n";
        if (!is_null($ci)) echo "             <input type=\"hidden\" name=\"ci\" value=\"$ci\">\n";
        //echo "              <br>\n";
        echo "              <select name=\"field\">\n";
        echo "                <option value=\"Title\" selected>Title</option><option value=\"Author\">Author</option>\n";
        echo "              </select>\n";
        //echo "              <br>\n";
        //echo "              <br>\n";
        echo "              <input type=\"submit\" name=\"Submit\" value=\"Find Items\">\n";
        echo "              <br>\n";
        echo "              <br>\n";
        echo "              </form>\n";
        echo "            </td>\n";

        echo "            <td width=\"50%\" align=\"left\" valign=\"top\" class=\"borders\" align=\"center\" NOWRAP>\n";
        echo "              <form method=\"post\" action=\"index.php\" name=\"frmReserveItem\">\n";
    echo "                <input type=\"hidden\" name=\"page\" value=\"$page\">\n";
    echo "                <input type=\"hidden\" name=\"cmd\" value=\"$cmd\">\n";
    echo "                <input type=\"hidden\" name=\"searchType\" value=\"reserveItem\">\n";
    echo "                <input type=\"hidden\" name=\"field\" value=\"instructor\">\n";
    if (!is_null($ci)) echo "         <input type=\"hidden\" name=\"ci\" value=\"$ci\">\n";

        echo "                <br>\n";
    echo "                <select name=\"query\">\n";
      echo "                  <option value=\"--\" selected>Choose an Instructor\n";
        foreach($instructors as $instructor)
    {
      echo "                  <option value=\"" . $instructor['user_id'] . "\">" . $instructor['full_name'] . "</option>\n";
    }

        echo "                </select>\n";
        //echo "                <br>\n";
        //echo "                <br>\n";
        echo "                <input type=\"submit\" name=\"Submit2\" value=\"Get Instructor's Reserves\">\n";
        echo "              </form>\n";
        echo "            </td>\n";
        echo "          </tr>\n";
        echo "          <tr align=\"left\" valign=\"top\">\n";
        echo "            <td colspan=\"2\" class=\"borders\" align=\"center\">\n";
        echo "            </td>\n";
        echo "          </tr>\n";
        echo "        </table>\n";
    echo "      </td>\n";
    echo "    </tr>\n";
    echo "  </tbody>\n";
    echo "</table>\n";

  }

  /**
   * @return void
   * @param string $page    -- the current page selector
   * @param string $subpage -- subpage selector
   * @param int $courseInstance -- user selected courseInstance
   * @param string $query -- users search terms
   * @desc display search resulting items
   *    expected next steps
   *      open catalog in new window and search for query
   *      dependent on page value
  */
  function displaySearchResults($user, $search, $cmd, $ci=null, $hidden_requests=null, $hidden_reserves=null, $loan_periods=null)
  {
    global $g_reservesViewer, $g_permission;

    $showNextLink = false;
    $showPrevLink = false;
    $e = 20;

    if ($search->totalCount > ($search->first + 20)){
      $showNextLink = true;
      $fNext = $search->first + 20;
    }

    if ($search->first > 0){
      $showPrevLink = true;
      $fPrev = $search->first - 20;
    }

    echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
    echo "    <tbody>\n";
    echo "      <tr><td width=\"100%\" colspan=\"2\"><img src=\"images/spacer.gif\" width=\"1\" height=\"5\"> </td></tr>\n";
    echo "      <form name=\"searchResults\"method=\"post\" action=\"index.php\">\n";

    if (is_array($hidden_reserves) && !empty($hidden_reserves)){
      foreach($hidden_reserves as $r)
      {
        echo "<input type=\"hidden\" name=\"reserve[" . $r ."]\" value=\"" . $r ."\">\n";
      }
    }

    if (is_array($hidden_requests) && !empty($hidden_requests)){
      foreach($hidden_requests as $r)
      {
        echo "<input type=\"hidden\" name=\"request[" . $r ."]\" value=\"" . $r ."\">\n";
      }
    }

    echo "      <input type=\"hidden\" name=\"cmd\" value=\"$cmd\">\n";
    echo "      <input type=\"hidden\" name=\"ci\" value=\"$ci\">\n";

    echo "      <input type=\"hidden\" name=\"f\">\n";
    echo "      <input type=\"hidden\" name=\"e\" value=\"$e\">\n";
    echo "      <input type=\"hidden\" name=\"field\" value=\"$search->field\">\n";
    echo "      <input type=\"hidden\" name=\"query\" value=\"".urlencode($search->query)."\">\n";

    echo "      <tr>\n";
    echo "          <td align=\"left\">[ <a href=\"index.php?cmd=searchScreen&ci=$ci\" class=\"editlinks\">New Search</a> ] &nbsp;[ <a href=\"index.php?cmd=editClass&ci=$ci\" class=\"editlinks\">Cancel Search</a> ]</td>\n";
    echo "          <td align=\"right\"><input type=\"submit\" name=\"Submit\" value=\"Add Selected Materials\"></td>\n";
    echo "      </tr>\n";

      if ($showNextLink || $showPrevLink) {
        echo "        <tr><td colspan=\"2\">&nbsp;</td></tr>\n";
          echo "      <tr><td colspan=\"2\" align='right'>";
          if ($showPrevLink) {
            echo "<img src=\"images/getPrevious.gif\" onClick=\"javaScript:document.forms.searchResults.cmd.value='searchResults';document.forms.searchResults.f.value=".$fPrev.";document.forms.searchResults.submit();\">&nbsp;&nbsp;";
          }
          if ($showNextLink) {
            echo "<img src=\"images/getNext.gif\" onClick=\"javaScript:document.forms.searchResults.cmd.value='searchResults';document.forms.searchResults.f.value=".$fNext.";document.forms.searchResults.submit();\">";
          }
          echo "</td></tr>\n";
        } else {
          echo "<tr><td>&nbsp;</tr></td>\n";
        }


        echo "      <tr>\n";
        echo "        <td colspan=\"2\">\n";
        echo "          <table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
        echo "              <tr align=\"left\" valign=\"top\">\n";
        echo "                <td class=\"headingCell1\"><div align=\"center\">SEARCH RESULTS</div></td><td width=\"75%\"> <div align=\"right\"></div></td>\n";
        echo "              </tr>\n";
        echo "          </table>\n";
        echo "        </td>\n";
        echo "      </tr>\n";
        echo "      <tr>\n";
        echo "        <td colspan=\"2\" align=\"left\" valign=\"top\" class=\"borders\">\n";
        echo "          <table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\" class=\"displayList\">\n";
    echo "            <tr align=\"left\" valign=\"middle\">\n";
        echo "                  <td colspan=\"2\" valign=\"left\" bgcolor=\"#FFFFFF\" class=\"headingCell2\">&nbsp;&nbsp;<i>". $search->totalCount . " items found</i></td>\n";
        echo "              <td class=\"headingCell1\">Select</td>\n";
        echo "                </tr>\n";

    $cnt = $search->first;
    $i = 0;
    for ($ndx=0;$ndx<count($search->items);$ndx++)
    {

      $item = $search->items[$ndx];
      $physicalCopy = new physicalCopy();
      $physicalCopy->getByItemID($item->getItemID());
      $callNumber = $physicalCopy->getCallNumber();

      $title = $item->getTitle();
      $author = $item->getAuthor();
      $url = $item->getURL();
      $performer = $item->getPerformer();
      $volTitle = $item->getVolumeTitle();
      $volEdition = $item->getVolumeEdition();
      $pagesTimes = $item->getPagesTimes();
      $source = $item->getSource();
      $itemNotes = $item->getNotes();

      $cnt++;
      $rowClass = ($i++ % 2) ? "evenRow" : "oddRow";

       if ((is_array($hidden_requests) && in_array($item->getItemID(),$hidden_requests)) || (is_array($hidden_reserves) && in_array($item->getItemID(),$hidden_reserves)))
       {
        $checked = 'checked';
       } else {
        $checked = '';
       }

      echo "            <tr align=\"left\" valign=\"middle\" class=\"$rowClass\">\n";
          echo "                  <td width=\"4%\" valign=\"top\">\n";
          echo "                <img src=\"". $item->getitemIcon() ."\" width=\"24\" height=\"20\"></td>\n";
          echo "              </td>\n";
          //echo "              <td width=\"88%\"><font class=\"titlelink\">" . $title . ". " . $author . "</font>";

          $viewReserveURL = "reservesViewer.php?item=" . $item->getItemID();
        if ($item->isPhysicalItem()) {
          //move to config file
          $viewReserveURL = $g_reservesViewer . $item->getLocalControlKey();
        }
        echo '<td width="88%">';
              if (!$item->isPhysicalItem()) {
                echo '<a href="'.$viewReserveURL.'" target="_blank" class="titlelink">'.$title.'</a>';
              } else {
                echo '<em>'.$title.'</em>.';
                if ($item->getLocalControlKey()) echo ' (<a href="'.$viewReserveURL.'" target="_blank">more info</a>)';
              }
              if ($author)
                echo '<br><font class="titlelink"> '. $author . '</font>';


                if ($callNumber) {
                    echo '<br>Call Number: '.$callNumber;
                    //if ($this->itemGroup == 'MULTIMEDIA' || $this->itemGroup == 'MONOGRAPH')
                  }

              if ($performer)
              {
                echo '<br><span class="itemMetaPre">Performed by:</span><span class="itemMeta"> '.$performer.'</span>';
              }
              if ($volTitle)
              {
                echo '<br><span class="itemMetaPre">From:</span><span class="itemMeta"> '.$volTitle.'</span>';
              }
              if ($volEdition)
              {
                echo '<br><span class="itemMetaPre">Volume/Edition:</span><span class="itemMeta"> '.$volEdition.'</span>';
              }
              if ($pagesTimes)
              {
                echo '<br><span class="itemMetaPre">Pages/Time:</span><span class="itemMeta"> '.$pagesTimes.'</span>';
              }
              if ($source)
              {
                echo '<br><span class="itemMetaPre">Source/Year:</span><span class="itemMeta"> '.$source.'</span>';
              }

        //show notes
        self::displayNotes($itemNotes);
              
            if ($item->isPhysicalItem() && !is_null($loan_periods)) 
          {
            echo "<br>\n";
            echo "<b>Requested Loan Period:<b> ";
            echo "  <select name=\"requestedLoanPeriod_". $item->getItemID() ."\">\n";
          for($n=0; $n < count($loan_periods); $n++)
          {
            $selected = ($loan_periods[$n]['default'] == 'true') ? " selected " : "";
              echo "    <option value=\"" . $loan_periods[$n]['loan_period'] . "\" $selected>". $loan_periods[$n]['loan_period'] . "</option>\n";
          }
            echo "  </select>\n";       
          }              

            echo "              </td>\n";

            echo "                <td width=\"8%\" valign=\"top\" class=\"borders\" align=\"center\">\n";

            if ($item->getItemGroup() == "ELECTRONIC"){
        echo "                          <input type=\"checkbox\" name=\"reserve[" . $item->getItemID() ."]\" value=\"" . $item->getItemID() ."\" ".$checked.">\n";
      } else {
        echo "                          <input type=\"checkbox\" name=\"request[" . $item->getItemID() ."]\" value=\"" . $item->getItemID() ."\" ".$checked.">\n";
      }

            echo "                    </td>\n";
            echo "            </tr>\n";
    }

        echo "              </table>\n";
        echo "            </td>\n";
        echo "          </tr>";
        echo "        <tr><td colspan=\"2\">&nbsp;</td></tr>\n";

      if ($showNextLink || $showPrevLink) {
        echo "        <tr><td colspan=\"2\">&nbsp;</td></tr>\n";
          echo "      <tr><td colspan=\"2\" align='right'>";
          if ($showPrevLink) {
            echo "<img src=\"images/getPrevious.gif\" onClick=\"javaScript:document.forms.searchResults.cmd.value='searchResults';document.forms.searchResults.f.value=".$fPrev.";document.forms.searchResults.submit();\">&nbsp;&nbsp;";
          }
          if ($showNextLink) {
            echo "<img src=\"images/getNext.gif\" onClick=\"javaScript:document.forms.searchResults.cmd.value='searchResults';document.forms.searchResults.f.value=".$fNext.";document.forms.searchResults.submit();\">";
          }
          echo "</td></tr>\n";
        } else {
          echo "<tr><td>&nbsp;</tr></td>\n";
        }        
        

    echo "      <tr><td colspan=\"2\">&nbsp;</td></tr>\n";
    echo "      <tr><td colspan=\"2\" align=\"right\"><input type=\"submit\" name=\"Submit2\" value=\"Add Selected Materials\"></td></tr>\n";
    echo "      <tr><td colspan=\"2\"><img src=\"images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n";
    echo "    </tbody>\n";
    echo "</table>\n";

  }

function displayReserveAdded($user, $reserve=null, $ci)
{
  global $g_reservesViewer, $g_permission;

  echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
  echo "  <tr><td width=\"100%\"><img src=\"images/spacer.gif\" width=\"1\" height=\"5\">&nbsp;</td></tr>\n";
  echo "  <tr>\n";
  echo "    <td align=\"left\" valign=\"top\" class=\"borders\">\n";
  echo "      <table width=\"50%\" border=\"0\" align=\"center\" cellpadding=\"0\" cellspacing=\"5\">\n";
  echo "        <tr><td><strong>Your items have been added successfully.</strong></td></tr>\n";
  echo "              <tr><td>\n";
  echo "              <ul><li class=\"nobullet\"><a href=\"index.php?cmd=editClass&ci=$ci\">Go to class</a></li>\n";
  echo "            </ul></td></tr>\n";
  echo "        <tr>\n";
  echo "          <td align=\"left\" valign=\"top\"><p>Would you like to put more items on reserve?</p><ul>\n";
  echo "            <li><a href=\"index.php\">No</a></li>\n";
  echo "            <li><a href=\"index.php?cmd=addReserve&ci=$ci\">Yes, to this class.</a></li>\n";
  echo "            <li><a href=\"index.php?cmd=addReserve\">Yes, to another class.</a></li>\n";
  echo "          </ul></td>\n";
  echo "        </tr>\n";
  
 if ($reserve) {
      
      $reserve->getItem();
      
      $viewReserveURL = "reservesViewer.php?reserve=" . $reserve->getReserveID();
      if ($reserve->item->isPhysicalItem()) {
        $reserve->item->getPhysicalCopy();
        if ($reserve->item->localControlKey)
          $viewReserveURL = $g_reservesViewer . $reserve->item->getLocalControlKey();
        else
          $viewReserveURL = null;
      }

      $itemIcon = $reserve->item->getItemIcon();
      $title = $reserve->item->getTitle();
    $author = $reserve->item->getAuthor();
    $url = $reserve->item->getURL();
    $performer = $reserve->item->getPerformer();
    $volTitle = $reserve->item->getVolumeTitle();
    $volEdition = $reserve->item->getVolumeEdition();
    $pagesTimes = $reserve->item->getPagesTimes();
    $source = $reserve->item->getSource();
    $itemNotes = $reserve->item->getNotes();
    $reserveNotes = $reserve->getNotes();
      
      echo "        <tr><td>&nbsp;</td></tr>\n";
      echo "        <tr><td><strong>Review item:</strong></td></tr>\n";
      echo "        <tr><td>&nbsp;</td></tr>\n";
      echo '<tr><td><table border="0" cellspacing="0" cellpadding="0">';
      echo '<tr align="left" valign="middle" class="oddRow">';
      echo '  <td width="5%" valign="top"><img src="'.$itemIcon.'" width="24" height="20"></td>';
      if ($viewReserveURL)
        echo '  <td width="78%"><a href="'.$viewReserveURL.'" class="itemTitle" target="_blank">'.$title.'</a>';
      else
        echo '  <td width="78%"><span class="itemTitle">'.$title.'</span>';
      if ($author)
        echo '    <br> <span class="itemAuthor">'.$author.'</span>';
      if ($performer)
        echo '<br><span class="itemMetaPre">Performed by:</span>&nbsp;<span class="itemMeta"> '.$performer.'</span>';
      if ($volTitle)
        echo '<br><span class="itemMetaPre">From:</span>&nbsp;<span class="itemMeta"> '.$volTitle.'</span>';
      if ($volEdition)
        echo '<br><span class="itemMetaPre">Volume/Edition:</span>&nbsp;<span class="itemMeta"> '.$volEdition.'</span>';
      if ($pagesTimes)
        echo '<br><span class="itemMetaPre">Pages/Time:</span>&nbsp;<span class="itemMeta"> '.$pagesTimes.'</span>';
      if ($source)
        echo '<br><span class="itemMetaPre">Source/Year:</span>&nbsp;<span class="itemMeta"> '.$source.'</span>';
        
    //show notes
    self::displayNotes($itemNotes);
    self::displayNotes($reserveNotes);
      
      echo '  </td>';
      echo '  <td width="17%" valign="top">[ <a href="index.php?cmd=editReserve&reserveID='.$reserve->getReserveID().'" class="editlinks">edit item</a> ]</td>';
      echo '  <td width="0%">&nbsp;</td>';
      echo '</tr>';
      echo '</table></td></tr>';
  }
  
  
  echo "      </table>\n";
  echo "    </td>\n";
  echo "  </tr>\n";
  echo "  <tr><td><img src=\"images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n";
  echo "</table>\n";
}



/**
 * @return void
 * @param courseInstance $ci Reference to a CI object
 * @param array $reserves Reference to an array of reserve IDs
 * @desc displays sorting form
 */
function displayCustomSort(&$ci, &$reserves) {
?>
  <div>
    <div style="text-align:right;"><strong><a href="index.php?cmd=editClass&amp;ci=<?=$ci->getCourseInstanceID()?>">Return to Edit Class</a></strong></div>
  
    <div style="width:35%; align:left; text-align:center; background:#CCCCCC;" class="borders">
      <strong>Sort by:</strong> [ <a href="index.php?cmd=customSort&amp;ci=<?=$ci->getCourseInstanceID()?>&amp;parentID=<?=$_REQUEST['parentID']?>&amp;sortBy=title" class="editlinks">title</a> ] [ <a href="index.php?cmd=customSort&amp;ci=<?=$ci->getCourseInstanceID()?>&amp;parentID=<?=$_REQUEST['parentID']?>&amp;sortBy=author" class="editlinks">author</a> ]
    </div>
  </div>
  
  <form method="post" name="customSortScreen" action="index.php">   
    <input type="hidden" name="cmd" value="<?=$_REQUEST['cmd']?>" />
    <input type="hidden" name="ci" value="<?=$ci->getCourseInstanceID()?>" />
    <input type="hidden" name="parentID" value="<?=$_REQUEST['parentID']?>" />

    <div align="right">
      <input type="button" name="reset1" value="Reset to Saved Order" onClick="javascript:this.form.submit();">
      &nbsp;<input type="submit" name="saveOrder" value="Save Order">
    </div>
    <br />
    <div class="helperText" style="margin-right:5%; margin-left:5%;">
      <? if ($_REQUEST['parentID'] == '') {?>
        NOTE: to sort items inside of a heading, return to the Edit Class screen and click on the <img src="images/sort.gif" alt="sort contents"> link next to the heading.
      <? } ?>
    </div>
    <br />    
    <table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
      <tr valign="middle">
        <td class="headingCell1">Reserves</td>
        <td class="headingCell1" width="100">Sort Order</td>
      </tr>
      <tr>
        <td colspan="2">
        <ul style="list-style:none; padding:0px; margin:0px;">
<?php
  //begin displaying individual reserves
  $reserve_count = count($reserves);
  $order = 1;
  foreach($reserves as $r_id):
    $reserve = new reserve($r_id);  //initialize reserve object
    $reserve->getItem();
    
    $rowStyle = ($rowStyle=='oddRow') ? 'evenRow' : 'oddRow'; //set the style
    $rowClass = ($reserve->item->isHeading()) ? 'class="headingCell2"' : 'class="'.$rowStyle.'"';
?>
      
        <li>
        <div <?=$rowClass?> >
        <div style="float:right; padding:7px 30px 5px 5px;">
          <input type="hidden" name="old_order[<?=$reserve->getReserveID()?>]" value="<?=$order?>">
          <input name="new_order[<?=$reserve->getReserveID()?>]" value="<?=$order?>" type="text" size="3" onChange="javascript:if (this.value <=0 || this.value > <?=$reserve_count?> || !parseInt(this.value)) {alert ('Invalid value')} else {updateSort(document.forms.customSortScreen, 'old_order[<?=$reserve->getReserveID()?>]', this.value, this.name)}">
        </div>
        
        <?php self::displayReserveInfo($reserve, 'class="metaBlock-wide"'); ?>
        
        <div style="clear:right;"></div>
        </div>  
        </li>

      
<?php
    $order++;
  endforeach;
?>
        </ul>
        </td>
      </tr>
      <tr valign="middle" class="headingCell1">
        <td class="HeadingCell1" colspan="2">&nbsp;</td>
      </tr>
    </table>
    <br />    
    <div style="margin-right:5%; margin-left:5%; text-align:right;">
      <input type="submit" name="reset1" value="Reset to Saved Order">
      &nbsp;<input type="submit" name="saveOrder" value="Save Order">
    </div>
  </form>
  
  <div style="margin-left:5%; margin-right:5%; text-align:right;"><strong><a href="index.php?cmd=editClass&amp;ci=<?=$ci->getCourseInstanceID()?>">Return to Edit Class</a></strong></div>
<?php
}

  /**
   * @return void
   * @param courseInstance object $ci CI containing all of these reserves
   * @param array $reserve_ids Array of reserve IDs
   * @desc Displays the screen to edit reserve-info of multiple reserves
   */
  function displayEditMultipleReserves(&$ci, $reserve_ids) {
    global $calendar, $u, $g_permission, $g_notetype, $u;
    
    //set default activation/deactivation dates
    $course_activation_date = $ci->getActivationDate(); 
    $course_expiration_date = $ci->getExpirationDate();
    
    //set up note form
    $available_note_types = array('instructor', 'content', 'staff', 'copyright'); //all note types valid for a reserve    
    //filter allowed note types based on permission level
    $restricted_note_types = array('content', 'staff', 'copyright');
    //filter out restricted notes if role is less than staff    
    
    if($u->getRole() < $g_permission['staff']) {
      //user does not have permission so remove restricted note types
      $available_note_types = array_diff($available_note_types, $restricted_note_types);
    } 
    
    //convert $reserve_ids into an associative array so that we can pass it to displayHiddenFields()
    $reserves_array = array('selected_reserves'=>$reserve_ids);
?>
    <script language="JavaScript">
    //<!--      
      //resets reserve dates
      function resetDates(from, to) {
        document.getElementById('reserve_activation_date').value = from;
        document.getElementById('reserve_expiration_date').value = to;
      }
      
      //highlight a fieldset
      function highlightElement(element_id, onoff) {
        if(document.getElementById(element_id)) {
          if(onoff) {
            document.getElementById(element_id).className = 'highlight';  
          }
          else {
            document.getElementById(element_id).className = '';
          }
          
          //enable/disable the form elements
          toggleDisabled(document.getElementById(element_id).childNodes, !onoff); 
        }
      }
      
      //disable/enable form elements
      function toggleDisabled(nodes, onoff) {
        for(var x = 0; x < nodes.length; x++) {
          if(nodes[x].disabled != undefined) {
            nodes[x].disabled = onoff;
          }         
          //get all the children too
          toggleDisabled(nodes[x].childNodes, onoff);
        }
      }
    //-->
    </script>
    
    <div style="text-align:right; font-weight:bold;"><a href="index.php?cmd=editClass&amp;ci=<?=$ci->getCourseInstanceID()?>">Return to Class</a></div>
    
    <div class="headingCell1" style="width:30%;">EDIT MULTIPLE RESERVES</div>
    <div id="reserve_details" class="displayArea" style="padding:8px 8px 12px 8px;">  
      <div class="helperText">
        Warning: You are editing multiple reserves.  Select the checkbox next to the changes you wish to make.
      </div>
      <br />
      
      <form id="edit_multiple_form" name="edit_multiple_form" method="post" action="index.php">
        <input type="hidden" name="ci" value="<?=$ci->getCourseInstanceID()?>" />
        <input type="hidden" name="cmd" value="<?=$_REQUEST['cmd']?>" />
        <?php self::displayHiddenFields($reserves_array); ?>
        
        <table width="100%">
          <tr>
            <td width="30" align="center">
              <input type="checkbox" id="edit_status" name="edit_status" onclick="highlightElement('reserve_status', this.checked)" />
            </td>
            <td>      
              <fieldset id="reserve_status">
                <legend>Status</legend>         
                
                <input type="radio" name="reserve_status" id="reserve_status_active" value="ACTIVE" />&nbsp;<span class="active">ACTIVE</span>
                <input type="radio" name="reserve_status" id="reserve_status_inactive" value="INACTIVE" />&nbsp;<span class="inactive">INACTIVE</span>
                
                <?php if ($u->getRole() >= $g_permission['staff']): ?>
                  <br/><input type="radio" name="reserve_status" id="reserve_status_denied" value="DENIED" <?=$reserve_status_denied?> />&nbsp;<span class="copyright_denied">DENY ACCESS FOR THIS CLASS ONLY</span>
                  <br/><input type="radio" name="reserve_status" id="item_status_denied"    value="DENIED_ALL" <?=$item_status_denied?> />&nbsp;<span class="copyright_denied">DENY ACCESS FOR ALL CLASSES</span>
                <?php   endif; ?>               
                
                <p><small>If you are editing headings, changes will also affect all reserves in those headings.</small></p>
              </fieldset>
            </td>
          </tr>
          <tr>
            <td align="center">
              <input type="checkbox" id="edit_dates" name="edit_dates" onclick="javascript: highlightElement('reserve_dates', this.checked)" />
            </td>
            <td>          
              <fieldset id="reserve_dates">
                <legend>Active Dates (YYYY-MM-DD) <small>[<a href="#" name="reset_dates" onclick="resetDates('<?=$course_activation_date?>', '<?=$course_expiration_date?>'); return false;">Reset dates</a>]</small></legend>            
      
                From:&nbsp;<input type="text" id="reserve_activation_date" name="reserve_activation_date" size="10" maxlength="10" value="<?=$course_activation_date?>" /> <?=$calendar->getWidgetAndTrigger('reserve_activation_date', $course_activation_date)?>
                To:&nbsp;<input type="text" id="reserve_expiration_date" name="reserve_expiration_date" size="10" maxlength="10" value="<?=$course_expiration_date?>" />  <?=$calendar->getWidgetAndTrigger('reserve_expiration_date', $course_expiration_date)?>
                
                <p><small>If you are editing headings, changes will also affect all reserves in those headings.</small></p>
              </fieldset>
            </td>
          </tr>
          <tr>
            <td align="center">
              <input type="checkbox" id="edit_heading" name="edit_heading" onclick="javascript: highlightElement('reserve_heading', this.checked)" />
            </td>
            <td>        
              <fieldset id="reserve_heading">
                <legend>Heading</legend>
                <?php self::displayHeadingSelect($ci); ?>
              </fieldset>
            </td>
          </tr>
          <tr>
            <td align="center">
              <input type="checkbox" id="edit_note" name="edit_note" onclick="javascript: highlightElement('reserve_note', this.checked)" />
            </td>
            <td>        
              <fieldset id="reserve_note">
                <legend>Note</legend>
      
                <textarea id="note_text" name="note_text" style="width:370px; height:90px; overflow:auto;"></textarea>
                <br />
                <small>
                  <strong>Note Type:</strong>
      <?php
          $first = true;
          foreach($available_note_types as $note_type):
            $checked = $first ? ' checked="true"' : '';
            $first = false;     
      ?>
                  <input type="radio" id="note_type_<?=$g_notetype[$note_type]?>" name="note_type" value="<?=$g_notetype[$note_type]?>"<?=$checked?> /><?=ucfirst(strtolower($g_notetype[$note_type]))?>
      <?php endforeach; ?>
                </small>
                    
              </fieldset>
            </td>
          </tr>
        </table>
        <p />
        <input type="submit" name="submit_edit_multiple" value="Submit Selected Changes" />
      </form>
    </div>
    <br />
    <div style="text-align:right; font-weight:bold;"><a href="index.php?cmd=editClass&amp;ci=<?=$ci->getCourseInstanceID()?>">Return to Class</a></div>
    
    <script language="JavaScript">
    //<!--
      //disable certain form elements by default by default
      toggleDisabled(document.getElementById('reserve_status').childNodes, true);
      toggleDisabled(document.getElementById('reserve_dates').childNodes, true);
      toggleDisabled(document.getElementById('reserve_heading').childNodes, true);
      toggleDisabled(document.getElementById('reserve_note').childNodes, true);
    //-->
    </script>
<?php
  }

}
?>
