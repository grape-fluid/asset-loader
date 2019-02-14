<?php

namespace Tests\Cases;

require __DIR__ . '/../bootstrap.php';

use Grapesc\GrapeFluid\AssetRepository;
use Nette\Caching\Storages\DevNullStorage;
use Tester\TestCase;
use Tester\Assert;


class AssetsTest extends TestCase
{

	public function testAssetSorting()
	{
		$assetRepository = new AssetRepository([
			"assetsDir" => ""
		], $this->getSortConfig(), new DevNullStorage());

		Assert::equal(["asset_3", "asset_6", "asset_5", "asset_2", "asset_1", "asset_4"], array_keys($assetRepository->getAssets()));
	}


	public function testAssetLimit()
	{
		$assetRepository = new AssetRepository([
			"assetsDir" => ""
		], $this->getLimitConfig(), new DevNullStorage());

		$dummy1 = "/asset_2/css/dummy_1.css";
        $dummy2 = "/asset_2/css/dummy_2.css";
        $dummy3 = "/asset_1/css/dummy_3.css";
        $dummy4 = "/asset_3/css/dummy_4.css";
        $dummy5 = "/asset_4/css/dummy_5.css";

        $case1 = [$dummy1, $dummy2, $dummy3, $dummy4];
        $case2 = [$dummy1, $dummy2, $dummy4, $dummy5];
        asort($case1);
        asort($case2);

		$assert1 = array_keys($assetRepository->getForCurrentLink("css", ":Sample:Page:default"));
		asort($assert1);

		$assert2 = array_keys($assetRepository->getForCurrentLink("css", ":Sample:Test:default"));
		asort($assert2);

		Assert::equal(array_values($case1), array_values($assert1));
		Assert::equal(array_values($case2), array_values($assert2));
	}


	public function testAssetDisabled()
	{
		$assetRepository = new AssetRepository([
			"assetsDir" => ""
		], $this->getDisabledConfig(), new DevNullStorage());

		Assert::equal(["asset_1", "asset_2", "asset_5", "asset_6"], array_keys($assetRepository->getAssets()));
	}


	private function getSortConfig()
	{
		return require __DIR__ . "/../fixtures/AssetsSortConfig.php";
	}


	private function getLimitConfig()
	{
		return require __DIR__ . "/../fixtures/AssetsLimitConfig.php";
	}


	private function getDisabledConfig()
	{
		return require __DIR__ . "/../fixtures/AssetsDisabledConfig.php";
	}

}

(new AssetsTest)->run();
