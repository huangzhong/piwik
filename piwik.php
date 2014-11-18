<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @package Piwik
 */

use Piwik\Url;
use Piwik\Tracker\Requests;
use Piwik\Tracker;
use Piwik\Tracker\Queue;
use Piwik\Tracker\Queue\Processor as QueueProcessor;

// Note: if you wish to debug the Tracking API please see this documentation:
// http://developer.piwik.org/api-reference/tracking-api#debugging-the-tracker

define('PIWIK_ENABLE_TRACKING', true);

if (!defined('PIWIK_DOCUMENT_ROOT')) {
    define('PIWIK_DOCUMENT_ROOT', dirname(__FILE__) == '/' ? '' : dirname(__FILE__));
}

if (file_exists(PIWIK_DOCUMENT_ROOT . '/bootstrap.php')) {
    require_once PIWIK_DOCUMENT_ROOT . '/bootstrap.php';
}

$GLOBALS['PIWIK_TRACKER_MODE'] = true;
error_reporting(E_ALL | E_NOTICE);
@ini_set('xdebug.show_exception_trace', 0);
@ini_set('magic_quotes_runtime', 0);

if (!defined('PIWIK_USER_PATH')) {
    define('PIWIK_USER_PATH', PIWIK_DOCUMENT_ROOT);
}
if (!defined('PIWIK_INCLUDE_PATH')) {
    define('PIWIK_INCLUDE_PATH', PIWIK_DOCUMENT_ROOT);
}

@ignore_user_abort(true);

if (file_exists(PIWIK_INCLUDE_PATH . '/vendor/autoload.php')) {
    $vendorDirectory = PIWIK_INCLUDE_PATH . '/vendor';
} else {
    $vendorDirectory = PIWIK_INCLUDE_PATH . '/../..';
}
require_once $vendorDirectory . '/autoload.php';

require_once PIWIK_INCLUDE_PATH . '/core/Plugin/Controller.php';
require_once PIWIK_INCLUDE_PATH . '/core/Plugin/ControllerAdmin.php';

\Piwik\Plugin\ControllerAdmin::disableEacceleratorIfEnabled();

require_once PIWIK_INCLUDE_PATH . '/libs/upgradephp/upgrade.php';
require_once PIWIK_INCLUDE_PATH . '/core/testMinimumPhpVersion.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/Queue.php';
require_once PIWIK_INCLUDE_PATH . '/core/Singleton.php';
require_once PIWIK_INCLUDE_PATH . '/core/Plugin/Manager.php';
require_once PIWIK_INCLUDE_PATH . '/core/Plugin.php';
require_once PIWIK_INCLUDE_PATH . '/core/Common.php';
require_once PIWIK_INCLUDE_PATH . '/core/Piwik.php';
require_once PIWIK_INCLUDE_PATH . '/core/IP.php';
require_once PIWIK_INCLUDE_PATH . '/core/UrlHelper.php';
require_once PIWIK_INCLUDE_PATH . '/core/Url.php';
require_once PIWIK_INCLUDE_PATH . '/core/SettingsPiwik.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker.php';
require_once PIWIK_INCLUDE_PATH . '/core/Config.php';
require_once PIWIK_INCLUDE_PATH . '/core/Translate.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/Cache.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/Db.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/Db/DbException.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/IgnoreCookie.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/VisitInterface.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/Visit.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/GoalManager.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/PageUrl.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/TableLogAction.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/Action.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/ActionPageview.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/Request.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/VisitExcluded.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/VisitorNotFoundInDb.php';
require_once PIWIK_INCLUDE_PATH . '/core/CacheFile.php';
require_once PIWIK_INCLUDE_PATH . '/core/Filesystem.php';
require_once PIWIK_INCLUDE_PATH . '/core/Cookie.php';

session_cache_limiter('nocache');
@date_default_timezone_set('UTC');

$tracker = new Tracker();

if (!$tracker->isEnabled()) {
    exit;
}

ob_start();
$tracker->setUp(); // could be moved to handler->init() but don't think it belongs there

try {

    $requests = new Requests();
    $queue    = new Queue();

    if ($queue->isEnabled()) {

        $handler  = new Queue\Handler($queue);
        $response = new Queue\Response();

    } else {

        if ($requests->isUsingBulkRequest()) {
            $response = new Tracker\BulkTracking\Response();
            $handler  = new Tracker\BulkTracking\Handler();
        } else {
            $response = new Tracker\Response();
            $handler  = new Tracker\Handler();
        }
    }

    $handler->init($tracker, $requests, $response);
    $handler->process($tracker, $requests, $response);
    $handler->finish($tracker, $requests, $response);

} catch (Exception $e) {
    echo "Error:" . $e->getMessage();
    exit(1);
}

ob_end_flush();