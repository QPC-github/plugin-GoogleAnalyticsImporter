<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\System;

use Piwik\Plugins\GoogleAnalyticsImporter\tests\Fixtures\ImportedFromGoogle;
use Piwik\Tests\Framework\TestCase\SystemTestCase;

class ImportTest extends SystemTestCase
{
    private static $CONVERSION_AWARE_VISIT_METRICS = [
        'nb_uniq_visitors',
        'nb_visits',
        'nb_actions',
        'sum_visit_length',
        'bounce_count',
        'nb_visits_converted',
        'nb_conversions',
        'revenue',
        'goals',
    ];

    /**
     * @var ImportedFromGoogle
     */
    public static $fixture;

    /**
     * @dataProvider getApiTestsToRun
     */
    public function testApi($api, $params)
    {
        $this->runApiTests($api, $params);
    }

    public function getApiTestsToRun()
    {
        $apiToTest = 'Referrers'; // TODO: change to 'all'

        return [
            [$apiToTest, [
                'idSite' => self::$fixture->idSite,
                'date' => '2018-12-03',
                'periods' => ['day', 'week', 'month', 'year'],
            ]],
        ];
    }

    /**
     * @dependsOn testApi
     * @dataProvider getTestDataForTestApiColumns
     */
    public function testApiColumns($method, $columns)
    {
        $expectedApiColumns = self::getExpectedApiColumns();
        if (empty($expectedApiColumns[$method])) {
            throw new \Exception("No expected columns for $method");
        }

        $this->assertEquals($expectedApiColumns[$method], $columns);
    }

    public static function getOutputPrefix()
    {
        return '';
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }

    public function getTestDataForTestApiColumns()
    {
        $tests = [];

        $checkedApiMethods = [];

        $expectedPath = PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/tests/System/expected';
        $contents = scandir($expectedPath);
        foreach ($contents as $filename) {
            if (!preg_match('/([^_]+)_day.xml$/', $filename, $matches)) {
                continue;
            }

            $method = $matches[1];
            if (!empty($checkedApiMethods[$method])) {
                continue;
            }

            $importedPath = $expectedPath . '/' . $filename;

            $columns = $this->getColumnsFromXml($importedPath);
            if (empty($columns)) {
                continue;
            }

            $tests[] = [$method, $columns];
        }

        return $tests;
    }

    private function getColumnsFromXml($importedPath)
    {
        $contents = file_get_contents($importedPath);
        $element = new \SimpleXMLElement($contents);

        if (empty($element->row)
            || empty($element->row[0])
        ) {
            return null;
        }

        $row = $element->row[0];
        $children = $row->children();

        $tagNames = [];
        for ($i = 0; $i != $children->count(); ++$i) {
            $tagName = $children[$i]->getName();
            if ($tagName == 'segment' || $tagName == 'subtable' || $tagName == 'label') {
                continue;
            }
            $tagNames[] = $tagName;
        }
        return $tagNames;
    }

    private static function getExpectedApiColumns()
    {
        return [
            'Referrers.getWebsites' => self::$CONVERSION_AWARE_VISIT_METRICS,
            'Referrers.getReferrerType' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['referer_type']),
            'Referrers.getAll' => self::$CONVERSION_AWARE_VISIT_METRICS,
            'Referrers.getKeywords' => self::$CONVERSION_AWARE_VISIT_METRICS,
            'Referrers.getKeywordsForPageUrl' => [],
            'Referrers.getKeywordsForPageTitle' => [],
            'Referrers.getSearchEnginesFromKeywordId' => self::$CONVERSION_AWARE_VISIT_METRICS,
            'Referrers.getSearchEngines' => array_merge(self::$CONVERSION_AWARE_VISIT_METRICS, ['url', 'logo']),
            'Referrers.getCampaigns' => self::$CONVERSION_AWARE_VISIT_METRICS,
            'Referrers.getKeywordsFromCampaignId' => self::$CONVERSION_AWARE_VISIT_METRICS,
            'Referrers.getUrlsFromWebsiteId' => self::$CONVERSION_AWARE_VISIT_METRICS,
            'Referrers.getSocials' => self::$CONVERSION_AWARE_VISIT_METRICS,
            'Referrers.getUrlsForSocial' => self::$CONVERSION_AWARE_VISIT_METRICS,
        ];
    }
}

ImportTest::$fixture = new ImportedFromGoogle();