<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Kaltura Client API.
 *
 * @package   local_yukaltura
 * @copyright (C) 2018 Kaltura Inc.
 * @copyright (C) 2018-2021 Yamaguchi University (gh-cc@mlex.cc.yamaguchi-u.ac.jp)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

error_reporting(E_STRICT);

require_once(dirname(__FILE__) . "/../KalturaClientBase.php");
require_once(dirname(__FILE__) . "/../KalturaEnums.php");
require_once(dirname(__FILE__) . "/../KalturaTypes.php");
require_once(dirname(__FILE__) . "/KalturaBusinessProcessNotificationClientPlugin.php");

/**
 * Kaltura Client API.
 *
 * @package   local_yukaltura
 * @copyright (C) 2018 Kaltura Inc.
 * @copyright (C) 2018-2021 Yamaguchi University (gh-cc@ml.cc.yamaguchi-u.ac.jp)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class KalturaActivitiBusinessProcessServerOrderBy extends KalturaEnumBase {
    /** @var order of created timestamp */
    const CREATED_AT_ASC = "+createdAt";
    /** @var order of updated timestamp */
    const UPDATED_AT_ASC = "+updatedAt";
    /** @var order of created timestamp */
    const CREATED_AT_DESC = "-createdAt";
    /** @var order of updated timestamp */
    const UPDATED_AT_DESC = "-updatedAt";
}

/**
 * Kaltura Client API.
 *
 * @package   local_yukaltura
 * @copyright (C) 2018 Kaltura Inc.
 * @copyright (C) 2018-2021 Yamaguchi University (gh-cc@ml.cc.yamaguchi-u.ac.jp)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class KalturaActivitiBusinessProcessServerProtocol extends KalturaEnumBase {
    /** @var HTTPS protocol */
    const HTTP = "http";
    /** @var HTTP protocol */
    const HTTPS = "https";
}

/**
 * Kaltura Client API.
 *
 * @package   local_yukaltura
 * @copyright (C) 2018 Kaltura Inc.
 * @copyright (C) 2018-2021 Yamaguchi University (gh-cc@ml.cc.yamaguchi-u.ac.jp)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class KalturaActivitiBusinessProcessServer extends KalturaBusinessProcessServer {
    /** @var string */
    public $host = null;

    /** @var int */
    public $port = null;

    /** @var KalturaActivitiBusinessProcessServerProtocol */
    public $protocol = null;

    /** @var string */
    public $username = null;

    /** @var string */
    public $password = null;
}

/**
 * Kaltura Client API.
 *
 * @package   local_yukaltura
 * @copyright (C) 2018 Kaltura Inc.
 * @copyright (C) 2018-2021 Yamaguchi University (gh-cc@ml.cc.yamaguchi-u.ac.jp)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class KalturaActivitiBusinessProcessServerBaseFilter extends KalturaBusinessProcessServerFilter {
}

/**
 * Kaltura Client API.
 *
 * @package   local_yukaltura
 * @copyright (C) 2018 Kaltura Inc.
 * @copyright (C) 2018-2021 Yamaguchi University (gh-cc@ml.cc.yamaguchi-u.ac.jp)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class KalturaActivitiBusinessProcessServerFilter extends KalturaActivitiBusinessProcessServerBaseFilter {
}

/**
 * Kaltura Client API.
 *
 * @package   local_yukaltura
 * @copyright (C) 2018 Kaltura Inc.
 * @copyright (C) 2018-2021 Yamaguchi University (gh-cc@ml.cc.yamaguchi-u.ac.jp)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class KalturaActivitiBusinessProcessNotificationClientPlugin extends KalturaClientPlugin {
    /**
     * Constructor of Kaltura ActivityBusinessProcessNotificationClientPlugin.
     * @param KalturaClient $client - instance of KalturaClient.
     */
    public function __construct(KalturaClient $client) {
        parent::__construct($client);
    }

    /**
     * This function gets Activity Business Process Notification Client Plugin.
     * @param KalturaClient $client - instance of Kaltura Client
     * @return object - KalturaActivitiBusinessProcessNotificationClientPlugin
     */
    public static function get(KalturaClient $client) {
        return new KalturaActivitiBusinessProcessNotificationClientPlugin($client);
    }

    /**
     * This function get service class.
     * @return array - array of KalturaServiceBase.
     */
    public function getServices() {
        $services = array();
        return $services;
    }

    /**
     * Get Class name.
     * @return string - class name.
     */
    public function getName() {
        return 'activitiBusinessProcessNotification';
    }
}
