<?
/*******************************************************************************
news.class.php
news object handles term table

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

*******************************************************************************/
class news
{
	function getNews($permission_level = 0)
	{
		global $g_dbConn;

		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$now = date("Y-m-d H:i:s",strtotime("now"));
			
				$sql = 	"SELECT news_id, news_text, font_class, begin_time, end_time, sort_order FROM news 
						 WHERE (permission_level = '$permission_level' OR permission_level is null) 
						 	AND ((begin_time IS NULL AND end_time IS NULL) OR (begin_time <= '$now' AND '$now' <= end_time))
						 ORDER BY sort_order
				"
				;
		}
	
		$rs = $g_dbConn->query($sql);
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }

		$news = null;
		while ($row = $rs->fetchRow())
		{
			$n['id'] 	= $row[0];
			$n['text'] 	= $row[1];
			$n['class']	= $row[2];
			
			$news[] = $n;
		}
		return $news;
	}


}
?>
