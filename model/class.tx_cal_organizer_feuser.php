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

require_once(t3lib_extMgm::extPath('cal').'model/class.tx_cal_location_model.php');

/**
 * Base model for the calendar organizer.  Provides basic model functionality that other
 * models can use or override by extending the class.  
 *
 * @author Mario Matzulla <mario@matzullas.de>
 * @package TYPO3
 * @subpackage cal
 */
class tx_cal_organizer_feuser extends tx_cal_location_model {
 	 
 	 /**
 	  * Constructor
	  * @param	array		$row		The value array
	  * @param	string		$pidList	The pid-list to search in
 	  */
 	 function tx_cal_organizer_feuser($row, $pidlist){
 	 	$this->type = 'tx_feuser';
		$this->objectType = 'organizer';
 	 	$this->tx_cal_location_model($this->type);
		$this->createOrganizer($row);
 	 }
	 
	 function renderOrganizer(){
		$cObj = &tx_cal_registry::Registry('basic','cobj');
		$page = $cObj->fileResource($this->conf['view.']['organizer.']['organizerTemplate4FEUser']);
		if ($page=='') {
			return '<h3>calendar: no organizer template file found:</h3>'.$this->conf['view.']['organizer.']['organizerTemplate4FEUser'];
		}
		$rems = array();
		$sims = array();
		$wrapped = array();
		$this->getMarker($page, $sims, $rems, $wrapped);

		return $cObj->substituteMarkerArrayCached($page, $sims, $rems, $wrapped);
	 }
	
	function createOrganizer($row){
	 	$this->setUid($row['uid']);
		$this->setName($row['username']);
		$this->setDescription($row['title']);
		$this->setStreet($row['address']);
		$this->setCity($row['city']);
		$this->setZip($row['zip']);
		$this->setCountry($row['country']);
		$this->setPhone($row['phone']);
		$this->setEmail($row['email']);
		$this->setImage($row['image']);
		$this->setLink($row['www']);
		$this->row = $row;
	 }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/model/class.tx_cal_organizer_feuser.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/model/class.tx_cal_organizer_feuser.php']);
}
?>