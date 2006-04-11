<?
/*******************************************************************************
itemAudit.class.php
itemAudit Primitive Object

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
require_once("secure/classes/item.class.php");
require_once("secure/classes/reserveItem.class.php");

class itemAudit
{
	//Attributes
	var $auditID;
	var $itemID;
	var $dateAdded;
	var $addedBy;
	var $reviewDate;
	var $reviewedBy;

	function itemAudit($itemID=NULL)
	{
		if (!is_null($itemID)){
			$this->getItemAuditByItemID($itemID);
		}
	}

	/**
	* @return int reserveID
	* @desc create new item_audit record in database
	*/
	function createNewItemAudit($itemID, $addedBy)
	{
		global $g_dbConn;

		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$sql = "INSERT INTO electronic_item_audit (item_id, date_added, added_by) VALUES (!, ?, ?)";
				$sql2 = "SELECT LAST_INSERT_ID() FROM electronic_item_audit";

				$d = date("Y-m-d"); //get current date
		}


		$rs = $g_dbConn->query($sql, array($itemID, $d, $addedBy));
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }

		$rs = $g_dbConn->query($sql2);
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }

		$row = $rs->fetchRow();
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }

		$this->auditID = $row[0];
		$this->itemID = $itemID;
		$this->dateAdded = $d;
		$this->addedBy = $addedBy;
	}

	/**
	* @return void
	* @param int $itemID
	* @desc get itemAudit info from the database
	*/
	function getItemAuditByItemID($itemID)
	{
		global $g_dbConn;

		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$sql = "SELECT audit_id, item_id, date_added, added_by, date_reviewed, reviewed_by "
					.  "FROM electronic_item_audit "
					.  "WHERE item_id = !";
		}

		$rs = $g_dbConn->query($sql, $itemID);
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }

		$row = $rs->fetchRow();

		$this->auditID		= $row[0];
		$this->itemID		= $row[1];
		$this->dateAdded	= $row[2];
		$this->addedBy		= $row[3];
		$this->reviewDate	= $row[4];
		$this->reviewedBy	= $row[5];
	}

	/**
	* @return void
	* @param date $reviewDate
	* @desc set reviewDate in database
	*/
	function setReviewDate($reviewDate)
	{
		global $g_dbConn;

		$this->reviewDate = $reviewDate;
		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$sql = "UPDATE electronic_item_audit SET review_date = ? WHERE audit_id = !";
				$d = date("Y-m-d"); //get current date
		}
		$rs = $g_dbConn->query($sql, array($d, $this->auditID));
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
	}

	/**
	* @return void
	* @param string $reviewedBy
	* @desc set reviewedBy in database
	*/
	function setReviewedBy($reviewedBy)
	{
		global $g_dbConn;

		$this->reviewedBy = $reviewedBy;
		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$sql = "UPDATE electronic_item_audit SET reviewed_by = ? WHERE audit_id = !";
		}

		$rs = $g_dbConn->query($sql, array($reviewedBy, $this->auditID));
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
	}

	function getAuditID() { return $this->auditID; }
	function getItemID() { return $this->itemID; }
	function getDateAdded() { return $this->dateAdded; }
	function getAddedBy() { return htmlentities(stripslashes($this->addedBy)); }
	function getReviewDate() { return $this->reviewDate; }
	function getReviewedBy() { return htmlentities(stripslashes($this->reviewedBy)); }

}
