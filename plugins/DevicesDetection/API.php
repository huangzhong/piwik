<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\DevicesDetection;

use DeviceDetector\Parser\Device\DeviceParserAbstract;
use Piwik\Archive;
use Piwik\DataTable;
use Piwik\Metrics;
use Piwik\Piwik;

/**
 * The DevicesDetection API lets you access reports on your visitors devices, brands, models, Operating system, Browsers.
 * @method static \Piwik\Plugins\DevicesDetection\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /**
     * @param string $name
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param string $segment
     * @return DataTable
     */
    protected function getDataTable($name, $idSite, $period, $date, $segment)
    {
        Piwik::checkUserHasViewAccess($idSite);
        $archive = Archive::build($idSite, $period, $date, $segment);
        $dataTable = $archive->getDataTable($name);
        $dataTable->filter('Sort', array(Metrics::INDEX_NB_VISITS));
        $dataTable->queueFilter('ReplaceColumnNames');
        $dataTable->queueFilter('ReplaceSummaryRowLabel');
        return $dataTable;
    }

    /**
     * Gets datatable displaying number of visits by device type (eg. desktop, smartphone, tablet)
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getType($idSite, $period, $date, $segment = false)
    {
        $dataTable = $this->getDataTable('DevicesDetection_types', $idSite, $period, $date, $segment);
        // ensure all device types are in the list
        $this->ensureDefaultRowsInTable($dataTable);
        $dataTable->filter('ColumnCallbackAddMetadata', array('label', 'logo', __NAMESPACE__ . '\getDeviceTypeLogo'));
        $dataTable->filter('ColumnCallbackReplace', array('label', __NAMESPACE__ . '\getDeviceTypeLabel'));
        return $dataTable;
    }

    protected function ensureDefaultRowsInTable($dataTable)
    {
        $requiredRows = array_fill(0, count(DeviceParserAbstract::getAvailableDeviceTypes()), Metrics::INDEX_NB_VISITS);

        $dataTables = array($dataTable);

        if (!($dataTable instanceof DataTable\Map)) {
            foreach ($dataTables as $table) {
                if ($table->getRowsCount() == 0) {
                    continue;
                }
                foreach ($requiredRows as $requiredRow => $key) {
                    $row = $table->getRowFromLabel($requiredRow);
                    if (empty($row)) {
                        $table->addRowsFromSimpleArray(array(
                            array('label' => $requiredRow, $key => 0)
                        ));
                    }
                }
            }
        }
    }

    /**
     * Gets datatable displaying number of visits by device manufacturer name
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getBrand($idSite, $period, $date, $segment = false)
    {
        $dataTable = $this->getDataTable('DevicesDetection_brands', $idSite, $period, $date, $segment);
        $dataTable->filter('ColumnCallbackReplace', array('label', __NAMESPACE__ . '\getDeviceBrandLabel'));
        $dataTable->filter('ColumnCallbackAddMetadata', array('label', 'logo', __NAMESPACE__ . '\getBrandLogo'));
        return $dataTable;
    }

    /**
     * Gets datatable displaying number of visits by device model
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getModel($idSite, $period, $date, $segment = false)
    {
        $dataTable = $this->getDataTable('DevicesDetection_models', $idSite, $period, $date, $segment);
        $dataTable->filter('ColumnCallbackReplace', array('label', __NAMESPACE__ . '\getModelName'));
        return $dataTable;
    }

    /**
     * Gets datatable displaying number of visits by OS family (eg. Windows, Android, Linux)
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getOsFamilies($idSite, $period, $date, $segment = false)
    {
        $dataTable = $this->getDataTable('DevicesDetection_os', $idSite, $period, $date, $segment);
        $dataTable->filter('GroupBy', array('label', __NAMESPACE__ . '\getOSFamilyFullName'));
        $dataTable->filter('ColumnCallbackAddMetadata', array('label', 'logo', __NAMESPACE__ . '\getOsFamilyLogo'));
        return $dataTable;
    }

    /**
     * Gets datatable displaying number of visits by OS version (eg. Android 4.0, Windows 7)
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getOsVersions($idSite, $period, $date, $segment = false)
    {
        $dataTable = $this->getDataTable('DevicesDetection_osVersions', $idSite, $period, $date, $segment);
        $dataTable->filter('ColumnCallbackAddMetadata', array('label', 'logo', __NAMESPACE__ . '\getOsLogo'));
        // use GroupBy filter to avoid duplicate rows if old (UserSettings) and new (DevicesDetection) reports were combined
        $dataTable->filter('GroupBy', array('label', __NAMESPACE__ . '\getOsFullName'));
        return $dataTable;
    }

    /**
     * Gets datatable displaying number of visits by Browser family (eg. Firefox, InternetExplorer)
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     *
     * @deprecated since 2.9.0   Use {@link getBrowsers} instead.
     */
    public function getBrowserFamilies($idSite, $period, $date, $segment = false)
    {
        return $this->getBrowsers($idSite, $period, $date, $segment);
    }

    /**
     * Gets datatable displaying number of visits by Browser (Without version)
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getBrowsers($idSite, $period, $date, $segment = false)
    {
        $dataTable = $this->getDataTable('DevicesDetection_browsers', $idSite, $period, $date, $segment);
        $dataTable->filter('GroupBy', array('label', __NAMESPACE__ . '\getBrowserName'));
        $dataTable->filter('ColumnCallbackAddMetadata', array('label', 'logo', __NAMESPACE__ . '\getBrowserFamilyLogo'));
        return $dataTable;
    }

    /**
     * Gets datatable displaying number of visits by Browser version (eg. Firefox 20, Safari 6.0)
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getBrowserVersions($idSite, $period, $date, $segment = false)
    {
        $dataTable = $this->getDataTable('DevicesDetection_browserVersions', $idSite, $period, $date, $segment);
        $dataTable->filter('ColumnCallbackAddMetadata', array('label', 'logo', __NAMESPACE__ . '\getBrowserLogo'));
        $dataTable->filter('ColumnCallbackReplace', array('label', __NAMESPACE__ . '\getBrowserNameWithVersion'));
        return $dataTable;
    }

    /**
     * Gets datatable displaying number of visits by Browser engine (eg. Trident, Gecko, Blink,...)
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getBrowserEngines($idSite, $period, $date, $segment = false)
    {
        $dataTable = $this->getDataTable('DevicesDetection_browserEngines', $idSite, $period, $date, $segment);
        // use GroupBy filter to avoid duplicate rows if old (UserSettings) and new (DevicesDetection) reports were combined
        $dataTable->filter('GroupBy', array('label',  __NAMESPACE__ . '\getBrowserEngineName'));
        return $dataTable;
    }
}
