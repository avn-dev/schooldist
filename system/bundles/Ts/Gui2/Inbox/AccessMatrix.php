<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ts\Gui2\Inbox;

/**
 * Description of AccessMatrix
 *
 * @author Mark Koopmann
 */
class AccessMatrix extends \Ext_TC_Access_Matrix {

	protected $_sItemTable = 'kolumbus_inboxlist';
	protected $_sItemNameField = 'name';
	protected $_sItemOrderbyField = 'position';
	protected $_sType = 'inbox';
	protected $aRight = ['thebing_admin_inbox', ''];

}