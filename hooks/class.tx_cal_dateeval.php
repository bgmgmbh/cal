<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2008 Foundation for Evangelism
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

class tx_cal_dateeval {
	
	/**
	 * Javascript evaluation for cal date fields. Transforms various date 
	 * formats into the standard date format just like the evaluation 
	 * performed on regular TYPO3 date fields.
	 *
	 * @return	JavaScript code for evaluating the date field.
	 * @todo 	Add evaluations similar to what the backend already uses,
	 *			converting periods and slashes into dashes and taking US date
	 *			format into account.
	 */
	function returnFieldJS() {

		return '
			//Convert the date to a timstamp using standard TYPO3 methods
			value = evalFunc.input("date", value);
			//Convert the timestamp back to human readable using standard TYPO3 methods
			value = evalFunc.output("date", value, null);
			return value;
		';
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/hooks/class.tx_cal_dateeval.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cal/hooks/class.tx_cal_dateeval.php']);
}
?>