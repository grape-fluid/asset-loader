<?php

namespace Grapesc\GrapeFluid\AssetsControl;

use Grapesc\GrapeFluid\AssetRepository;
use Grapesc\GrapeFluid\CoreModule\Model\SettingModel;
use Grapesc\GrapeFluid\ScriptCollector;
use Nette\Application\UI\Control;


/**
 * @author Kulíšek Patrik <kulisek@grapesc.cz>
 */
class AssetsControl extends Control
{
	
	/** @var AssetRepository */
	private $assets;

	/** @var SettingModel */
	private $settingModel;

	/** @var ScriptCollector */
	private $collector;

	
	public function __construct(AssetRepository $assets, SettingModel $settingModel, ScriptCollector $collector)
	{
		parent::__construct();
		$this->assets = $assets;
		$this->settingModel = $settingModel;
		$this->collector = $collector;
		$this->assets->deployAssets();
	}
	

	public function renderHead()
	{
		$this->template->setFile(__DIR__ . '/head.latte');
		$this->template->setting = $this->settingModel;
		$this->template->assets = $this->assets->getForCurrentLink("css", $this->getPresenter()->getAction(true));
		$this->template->assetsPublicDirectory = $this->template->basePath . "/components/";
		$this->template->render();
	}


	public function renderFooter()
	{
		$this->template->setFile(__DIR__ . "/footer.latte");
		$this->template->setting = $this->settingModel;
		$this->template->assets = $this->assets->getForCurrentLink("js", $this->getPresenter()->getAction(true));
		$this->template->assetsPublicDirectory = $this->template->basePath . "/components/";
		$this->template->render();
		$this->collector->render();
	}

}
