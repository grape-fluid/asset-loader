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
			"appDir" => "",
			"assetsDir" => ""
		], $this->getSortConfig(), new DevNullStorage());

		Assert::equal(["asset_3", "asset_6", "asset_5", "asset_2", "asset_1", "asset_4"], array_keys($assetRepository->getAssets()));
	}


	public function testAssetLimit()
	{
		$assetRepository = new AssetRepository([
			"appDir" => "",
			"assetsDir" => ""
		], $this->getLimitConfig(), new DevNullStorage());

		$dummy1 = realpath(__DIR__ . "/../fixtures/dummy_1.css");
		$dummy2 = realpath(__DIR__ . "/../fixtures/dummy_2.css");
		$dummy3 = realpath(__DIR__ . "/../fixtures/dummy_3.css");
		$dummy4 = realpath(__DIR__ . "/../fixtures/dummy_4.css");
		$dummy5 = realpath(__DIR__ . "/../fixtures/dummy_5.css");

		$assert1 = array_keys($assetRepository->getForCurrentLink("css", ":Sample:Page:default"));
		asort($assert1);

		$assert2 = array_keys($assetRepository->getForCurrentLink("css", ":Sample:Test:default"));
		asort($assert2);

		Assert::equal([$dummy1, $dummy2, $dummy3, $dummy4], array_values($assert1));
		Assert::equal([$dummy1, $dummy2, $dummy4, $dummy5], array_values($assert2));
	}


	public function testAssetDisabled()
	{
		$assetRepository = new AssetRepository([
			"appDir" => "",
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