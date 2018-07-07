<?php

namespace Grapesc\GrapeFluid;

use Nette\DI\CompilerExtension;


/**
 * @author Kulíšek Patrik <kulisek@grapesc.cz>
 */
class AssetLoaderExtension extends CompilerExtension
{

	/** @var array */
	protected $defaults = [
        "wwwDir" => null,
        "assetsDir" => "assets",
        "dirPerm" => 0511,
        "debug" => false
	];


	public function loadConfiguration()
	{
		$config = $this->validateConfig($this->defaults, $this->config['config']);
		$builder = $this->getContainerBuilder();

		$packages = $this->config;
		unset($packages['config']);

		$builder->addDefinition($this->prefix('assets'))
			->setFactory('Grapesc\\GrapeFluid\\AssetRepository', [$config, $packages]);

		$builder->addDefinition($this->prefix("collector"))
			->setFactory('Grapesc\\GrapeFluid\\ScriptCollector');

		$builder->addDefinition($this->prefix('control'))
			->setFactory('Grapesc\\GrapeFluid\\AssetsControl\\AssetsControl');
	}
	
}