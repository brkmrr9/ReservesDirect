<?
/*******************************************************************************
config.inc.php
Read config.xml

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
	require_once("DB.php");
	
	//sets $xmlConfig to path of config.xml file
	require_once(realpath(dirname(__FILE__) . "/../config_loc.inc.php"));

	if (!is_readable($xmlConfig)) { trigger_error("Could not read configure xml file path=$xmlConfig", E_USER_ERROR); }

	$configure = simplexml_load_file($xmlConfig);

	
	$g_authenticationType = (string)$configure->authentication->type;
	
	$dsn = array(
	    'phptype'  => (string)$configure->database->dbtype,
	    'username' => (string)$configure->database->username,
	    'password' => (string)$configure->database->pwd,
	    'hostspec' => (string)$configure->database->host,
	    'database' => (string)$configure->database->dbname,
	    'key'      => (string)$configure->database->dbkey,
	    'cert'     => (string)$configure->database->dbcert,
	    'ca'       => (string)$configure->database->dbca,
	    'capath'   => (string)$configure->database->capath,
	    'cipher'   => (string)$configure->database->cipher
	);

	$options = array(
	    'ssl' 		=> (string)$configure->database->ssl,
	    'debug'     => (string)$configure->database->debug
	);

	//open connection
	$g_dbConn = DB::connect($dsn, $options);
	if (DB::isError($g_dbConn)) { trigger_error($g_dbConn->getMessage(), E_USER_ERROR); }

	$g_error_log			= (string)$configure->error_log;
	$g_errorEmail		 	= (string)$configure->errorEmail;
	$g_adminEmail		 	= (string)$configure->adminEmail;
	$g_reservesEmail		= (string)$configure->reservesEmail;

	$g_faxDirectory			= (string)$configure->fax->directory;
	$g_faxURL				= (string)$configure->fax->URL;
    $g_faxCopyright         = (string)$configure->fax->copyright;
    $g_faxLog               = (string)$configure->fax->log;

    $g_fax2pdf_bin          = (string)$configure->fax->fax2pdf_bin;
    $g_faxinfo_bin          = (string)$configure->fax->faxinfo_bin;
    $g_gs_bin               = (string)$configure->fax->gs_bin;

	$g_documentDirectory	= (string)$configure->documentDirectory;
	$g_documentURL			= (string)$configure->documentURL;
	$g_docCover				= (string)$configure->documentCover;

	$g_siteURL				= (string)$configure->siteURL;

	$g_newUserEmail['subject']  = (string)$configure->newUserEmail->subject;
	$g_newUserEmail['msg']  = (string)$configure->newUserEmail->msg;	
	
	$g_specialUserEmail['subject']  = (string)$configure->specialUserEmail->subject;
	$g_specialUserEmail['msg']  = (string)$configure->specialUserEmail->msg;
	
	$g_specialUserDefaultPwd = (string)$configure->specialUserDefaultPwd;

	$g_EmailRegExp = (string)$configure->EmailRegExp;

	//zWidget configuration
	$g_zhost 			= (string)$configure->catalog->zhost;
	$g_zport 			= (string)$configure->catalog->zport;
	$g_zdb	 			= (string)$configure->catalog->zdb;
	$g_zReflector		= (string)$configure->catalog->zReflector;
	$g_catalogName		= (string)$configure->catalog->catalogName;
	$g_reserveScript	= (string)$configure->catalog->reserve_script;
	$g_holdingsScript	= (string)$configure->catalog->holdings_script;
	$g_reservesViewer	= (string)$configure->catalog->web_search;

	$g_libraryURL		= (string)$configure->library_url;

	$g_no_javascript_msg = (string)$configure->no_javascript_msg;

	$g_request_notifier_lastrun = (string)$configure->request_notifier->last_run;
?>
