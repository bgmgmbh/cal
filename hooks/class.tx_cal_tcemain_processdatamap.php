<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2005-2007 Mario Matzulla
 * (c) 2005-2007 Foundation for Evangelism
 * All rights reserved
 *
 * This file is part of the Web-Empowered Church (WEC)
 * (http://webempoweredchurch.org) ministry of the Foundation for Evangelism
 * (http://evangelize.org). The WEC is developing TYPO3-based
 * (http://typo3.org) free software for churches around the world. Our desire
 * is to use the Internet to help offer new life through Jesus Christ. Please
 * see http://WebEmpoweredChurch.org/Jesus.
 *
 * You can redistribute this file and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation;
 * either version 2 of the License, or (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This file is distributed in the hope that it will be useful for ministry,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the file!
 ***************************************************************/

define('ICALENDAR_PATH', 	t3lib_extMgm::extPath('cal').'model/class.tx_model_iCalendar.php');

/**
 * This hook extends the tcemain class.
 * It catches changes on tx_cal_event
 *
 * @author	Mario Matzulla <mario(at)matzullas.de>
 */
class tx_cal_tcemain_processdatamap {
	
	function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, &$tce) {
		/* If we have an existing calendar event */
		if ($table == 'tx_cal_event' && count($fieldArray)>1) {

			if($fieldArray['start_date']){
				$fieldArray['start_date'] = $this->convertBackendDateToYMD($fieldArray['start_date']);
			}

			if($fieldArray['end_date']){
				$fieldArray['end_date'] = $this->convertBackendDateToYMD($fieldArray['end_date']);
			}
			
			/* If the end date is blank or earlier than the start date */
			if($fieldArray['end_date'] < $fieldArray['start_date']) {
				$fieldArray['end_date'] = $fieldArray['start_date'];
			}
			
			if($fieldArray['until']){
				$fieldArray['until'] = $this->convertBackendDateToYMD($fieldArray['until']);
			}
			
			if($status != 'new'){
				require_once(t3lib_extMgm::extPath('cal').'controller/class.tx_cal_functions.php');
				require_once(t3lib_extMgm::extPath('cal').'/controller/class.tx_cal_api.php');
				$event = t3lib_BEfunc::getRecord ('tx_cal_event', $id);
				
				/* If we're in a workspace, don't notify anyone about the event */
				if($event['pid'] > 0) {
					if($fieldArray['calendar_id'] && $event['calendar_id'] != $fieldArray['calendar_id']){
						$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_cal_event_category_mm', 'uid_local='.intval($id));
					}
				
					/* Check Page TSConfig for a preview page that we should use */
					$pageTSConf = t3lib_befunc::getPagesTSconfig($event['pid']);
					if($pageTSConf['options.']['tx_cal_controller.']['pageIDForPlugin']) {
						$pageIDForPlugin = $pageTSConf['options.']['tx_cal_controller.']['pageIDForPlugin'];
					} else {
						$pageIDForPlugin = $event['pid'];
					}
				
					$page = t3lib_BEfunc::getRecord('pages', intval($pageIDForPlugin), 'doktype');
					if($page['doktype'] != 254) {
						/* Notify of changes to existing event */
						$tx_cal_api = t3lib_div :: makeInstanceClassName('tx_cal_api');
						$tx_cal_api = new $tx_cal_api();
						$tx_cal_api = &$tx_cal_api->tx_cal_api_without($pageIDForPlugin);
						
						$notificationService =& getNotificationService();
						$notificationService->controller->getDateTimeObject = new tx_cal_date($event['start_date'].'000000');
						$notificationService->notifyOfChanges($event, $fieldArray);
						if($fieldArray['send_invitation']){
							$notificationService->invite($event, $fieldArray);
							$fieldArray['send_invitation'] = 0;
						}
					}
				}
			}
   		}
   		
		if ($table == 'tx_cal_exception_event' && count($fieldArray)>1) {

			if($fieldArray['start_date']){
				$fieldArray['start_date'] = $this->convertBackendDateToYMD($fieldArray['start_date']);
			}
			
			if($fieldArray['end_date']){
				$fieldArray['end_date'] = $this->convertBackendDateToYMD($fieldArray['end_date']);
			}
			
			/* If the end date is blank or earlier than the start date */
			if($fieldArray['end_date'] < $fieldArray['start_date']) {
				$fieldArray['end_date'] = $fieldArray['start_date'];
			}
			
			if($fieldArray['until']){
				$fieldArray['until'] = $this->convertBackendDateToYMD($fieldArray['until']);
			}
		}
		
		/* If we're working with a calendar and an ICS file or URL has been posted, try to import it */
		if($table == 'tx_cal_calendar') {
			$calendar = t3lib_BEfunc::getRecord ('tx_cal_calendar', $id);
			
			require_once(t3lib_extMgm::extPath('cal').'service/class.tx_cal_icalendar_service.php');
			$service = t3lib_div::makeInstance('tx_cal_icalendar_service');
			
			if($calendar['type'] == 1 or $calendar['type'] == 2) {
				tx_cal_tcemain_processdatamap::processICS($calendar, $fieldArray, $service);
			}

		}
		
	}
	
	function processDatamap_afterDatabaseOperations($status, $table, $id, &$fieldArray, &$tcemain) {
		
		/* If we have a new calendar event */
		if ($table == 'tx_cal_event' && count($fieldArray)>1) {
			require_once(t3lib_extMgm::extPath('cal').'controller/class.tx_cal_functions.php');
			require_once(t3lib_extMgm::extPath('cal').'/controller/class.tx_cal_api.php');
			$event = t3lib_BEfunc::getRecord ('tx_cal_event', $status=='new'?$tcemain->substNEWwithIDs[$id]:$id);
			
			/* If we're in a workspace, don't notify anyone about the event */
			if($event['pid'] > 0) {
				/* Check Page TSConfig for a preview page that we should use */
				$pageTSConf = t3lib_befunc::getPagesTSconfig($event['pid']);
				if($pageTSConf['options.']['tx_cal_controller.']['pageIDForPlugin']) {
					$pageIDForPlugin = $pageTSConf['options.']['tx_cal_controller.']['pageIDForPlugin'];
				} else {
					$pageIDForPlugin = $event['pid'];
				}
			
				$page = t3lib_BEfunc::getRecord('pages', intval($pageIDForPlugin), "doktype");
				if($page['doktype'] != 254) {
					$tx_cal_api = t3lib_div :: makeInstanceClassName('tx_cal_api');
					$tx_cal_api = new $tx_cal_api();
					$tx_cal_api = &$tx_cal_api->tx_cal_api_without($pageIDForPlugin);

					if(!$event['ref_event_id']){
						$modelObj = &tx_cal_registry::Registry('basic','modelcontroller');
						$modelObj->updateEventAttendees($event['uid'],'tx_cal_phpicalendar');
					}

					if($status=='new' && $fieldArray['send_invitation']){				
						/* Notify of new event */
						$notificationService =& getNotificationService();
						$notificationService->controller->getDateTimeObject = new tx_cal_date($event['start_date'].'000000');
						$notificationService->notify($event);
			
						$notificationService->invite($fieldArray);
						$fieldArray['send_invitation'] = 0;
					}
					/* Schedule reminders for new and changed events */
					$offset = is_numeric($tx_cal_api->conf['view.']['event.']['remind.']['time']) ? $tx_cal_api->conf['view.']['event.']['remind.']['time'] * 60 : 0;
					//strtotime should be ok, b/c this event date shouldn't be in the past
					$reminderTimestamp =  strtotime($event['start_date']) + $event['start_time'] - $offset;
					$reminderService = &getReminderService();
					$reminderService->scheduleReminder($event['uid'], $reminderTimestamp);
				}
			}
		} 
	}
	
	function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, &$tce) {
		
		/**
		 * Demo code for using TCE to do custom validation of form elements.  The record is still
		 * saved but a bad combination of start date and end date will generate an error message.
		 */
		/*
		if($table == 'tx_cal_event') {
			$startTimestamp = $incomingFieldArray['start_date'] + $incomingFieldArray['start_time'];
			$endTimestamp = $incomingFieldArray['end_date'] + $incomingFieldArray['end_time'];
			
			if ($startTimestamp > $endTimestamp) {
				$tce->log('tx_cal_event', 2, $id, 0, 1, "Event end (".t3lib_BEfunc::datetime($endTimestamp).") is earlier than event start (".t3lib_BEfunc::datetime($startTimestamp).").", 1);
			}
		}
		*/

		if($table == 'tx_cal_event' || $table =="tx_cal_exeption_event") {
			
			$event = t3lib_BEfunc::getRecord($table, $id);
			if(intval($event['start_date'])==0 ){
				return;
			}

			/**
			 * If we have an event, check if a start and end time have been sent.
			 * If both are 0, then its an all day event.
			 */
			if (array_key_exists('start_time', $incomingFieldArray) && array_key_exists('end_time', $incomingFieldArray) &&
				$incomingFieldArray['start_time'] == 0 && $incomingFieldArray['end_time'] == 0) {
					
				$incomingFieldArray['allday'] = 1;
			}
			
			/** 
			 * If the recurring frequency has changed and recurrence rules are not
			 * already set, preset a reasonable value based on event start date/time.
			 * @todo 	Default date calculations do not take any timezone information into account.
			 */
			if($incomingFieldArray['freq'] != $event['freq']) {
				$date = $this->convertBackendDateToPear($incomingFieldArray['start_date']);
				$date->addSeconds($incomingFieldArray['start_time']);
				$dayArray = tx_cal_tcemain_processdatamap::getWeekdayOccurrence($date);
			
				/* If we're on the 4th occurrence or later, let's assume we want the last occurrence */
				if($dayArray[0] >= 4) {
					$dayArray[0] = -1;
				}
			
				switch($incomingFieldArray['freq']) {
					case 'week': /* Default Value = Day of the week when event starts. */
						if(!$incomingFieldArray['byday'] && !$event['byday']) {
							$incomingFieldArray['byday'] = strtolower($date->getDayName(true,2));
						}
						break;
					case 'month': /* Default Value = Day of the week and weekday occurrence when event starts */
						if(!$incomingFieldArray['byday'] && !$event['byday']) {
							$incomingFieldArray['byday'] = $dayArray[0].strtolower(substr($dayArray[1], 0, 2));
						}
						break;
					case 'year': /* Default Value = Day of the month and month when event starts */
						if(!$incomingFieldArray['bymonthday'] && !$event['bymonthday']) {
							$incomingFieldArray['bymonthday'] = $date->getDay();
						}
						
						if(!$incomingFieldArray['bymonth'] && !$event['bymonth']) {
							$incomingFieldArray['bymonth'] = $date->getMonth();
						}
						break;
				}
			}
		}
		
		if($table == 'tx_cal_category' && array_key_exists('calendar_id',$incomingFieldArray) && !strstr($id,'NEW')){
			$category = t3lib_BEfunc::getRecord ('tx_cal_category', $id);
			if($incomingFieldArray['calendar_id']!=$category['calendar_id']){
				$incomingFieldArray['parent_category']=0;
			}
		}
		
		/* If an existing calendar is updated */
		if($table == 'tx_cal_calendar' && array_key_exists('type',$incomingFieldArray) && !strstr($id,'NEW')){
			/* Get the calendar info from the db */
			$calendar = t3lib_BEfunc::getRecord ('tx_cal_calendar', $id);
						
			require_once(t3lib_extMgm::extPath('cal').'service/class.tx_cal_icalendar_service.php');
			$service = t3lib_div::makeInstance('tx_cal_icalendar_service');

   			// Here we have to check if the calendar belongs to the type
   			// problem with case 2 & 3 -> what to do with events of type database? delete them without warning? keep them and assign them to a default category?
   			switch ($incomingFieldArray['type']){
   				case 0: /* Standard */
					/* Delete any temporary events previously associated with this calendar */
					if($calendar['type']!=0){
						$service->deleteTemporaryEvents($id);
					}
   				break;
   				case 1: /* External URL or ICS file*/
   				case 2: /* ICS File */
					tx_cal_tcemain_processdatamap::processICS($calendar, $incomingFieldArray, $service);
				break;
   			}
		}
	}
	
	function processICS($calendar, &$fieldArray, &$service) {
		if($fieldArray['ics_file'] or $fieldArray['ext_url']) {
			if($fieldArray['ics_file']) {
				$url = t3lib_div::getFileAbsFileName('uploads/tx_cal/ics/'.$fieldArray['ics_file']);
			} elseif($fieldArray['ext_url']) {
				$url = $fieldArray['ext_url'];
			}
			
			$newMD5 = $service->updateEvents($calendar['uid'], $calendar['pid'], $url, $calendar['md5'], $calendar['cruser_id']);

			if($newMD5) {
				$fieldArray['md5'] = $newMD5;						
			}
			
			
			$service->scheduleUpdates($fieldArray['refresh'], $calendar['uid']);
		}
	}
	
	function getWeekdayOccurrence($date) { 
		return array(ceil($date->getDay() / 7), $date->getDayName());	
	}
	
	/**
	 * Converts a date from the backend (m-d-Y or d-m-Y) into a PEAR Date object.
	 *
	 * @param		string		The date to convert.
	 * @return		object		The date object.
	 */
	function convertBackendDateToPear($dateString) {
		$ymdString = $this->convertBackendDateToYMD($dateString);
		return new tx_cal_date($ymdString.'000000');
	}
	
	/**
	 * Converts a date from the backend (m-d-Y or d-m-Y) into the Ymd format.
	 *
	 * @param		string		The date to convert.
	 * @return		string		The date in Ymd format.
	 */
	function convertBackendDateToYMD($dateString) {
		// simple fallback conversion if JS fails for some reason
		$dateString = strtr($dateString,' ;.:_=/\\','--------');
		$dateArray = explode('-',$dateString);
		if(count($dateArray) > 1) {
			if($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] == '1'){
				$ymdString = sprintf("%04d", $dateArray[2]).sprintf("%02d", $dateArray[0]).sprintf("%02d", $dateArray[1]);
			}else{
				$ymdString = sprintf("%04d", $dateArray[2]).sprintf("%02d", $dateArray[1]).sprintf("%02d", $dateArray[0]);
			}
		} else {
			// We already had a YMD string
			$ymdString = $dateString;
		}
		return $ymdString;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/hooks/class.tx_cal_tcemain_processdatamap.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/hooks/class.tx_cal_tcemain_processdatamap.php']);
}
?>
