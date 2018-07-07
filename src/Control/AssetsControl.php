<?php

namespace Grapesc\GrapeFluid\AssetsControl;

use Grapesc\GrapeFluid\AssetRepository;
use Grapesc\GrapeFluid\Options\EnableAllAssetOptions;
use Grapesc\GrapeFluid\Options\IAssetOptions;
use Grapesc\GrapeFluid\ScriptCollector;
use Nette\Application\UI\Control;


/**
 * @author Kulíšek Patrik <kulisek@grapesc.cz>
 */
class AssetsControl extends Control
{
	
	/** @var AssetRepository */
	private $assets;

	/** @var IAssetOptions */
	private $options;

	/** @var ScriptCollector */
	private $collector;

	
	public function __construct(AssetRepository $assets, ScriptCollector $collector, IAssetOptions $options = null)
	{
		parent::__construct();
		$this->assets = $assets;
		$this->collector = $collector;
        $this->options = $options == null ? (new EnableAllAssetOptions) : $options;
		$this->assets->deployAssets();
	}


	public function renderCss()
    {
        $this->template->setFile(__DIR__ . '/head.latte');
        $this->template->options = $this->options;
        $this->template->assets = $this->assets->getForCurrentLink("css", $this->getPresenter()->getAction(true));
        $this->template->render();
    }


    public function renderJs()
    {
        $this->template->setFile(__DIR__ . "/footer.latte");
        $this->template->options = $this->options;
        $this->template->assets = $this->assets->getForCurrentLink("js", $this->getPresenter()->getAction(true));
        $this->template->render();
        $this->collector->render();
    }
	

	public function renderHead()
	{
        trigger_error("Method renderHead is deprecated, use renderCss instead", E_USER_DEPRECATED);
        $this->renderCss();
	}


	public function renderFooter()
	{
        trigger_error("Method renderFooter is deprecated, use renderJs instead", E_USER_DEPRECATED);
        $this->renderJs();
	}

}
