<?php

namespace Grapesc\GrapeFluid;

use Grapesc\GrapeFluid\Options\EnableAllAssetOptions;
use Grapesc\GrapeFluid\Options\IAssetOptions;
use Nette\DI\CompilerExtension;
use Nette\DI\Statement;


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
        "debug" => false,
        "options" => []
	];

	/** @var array */
	private $options = [];


	public function loadConfiguration()
	{
		$config = $this->validateConfig($this->defaults, $this->config['config']);
		$builder = $this->getContainerBuilder();
		$optionClasses = $config['options'];
		unset($config['options']);

		if (empty($optionClasses)) {
            $optionClasses[] = EnableAllAssetOptions::class;
        }

        foreach ($optionClasses as $key => $class) {
            if (!class_exists($class)) {
                throw new AssetOptionException($class . " not found");
            } else {
                $reflection = new \ReflectionClass($class);
                if (!$reflection->implementsInterface(IAssetOptions::class)) {
                    throw new AssetOptionException("Class $class must implement " . IAssetOptions::class);
                }

                $definition = $this->prefix('option.' . strtolower($reflection->getShortName()));
                $builder->addDefinition($definition)
                    ->setFactory($class);

                $this->options[] = "@" . $definition;
            }
        }

        $packages = $this->config;
        unset($packages['config']);

		$builder->addDefinition($this->prefix('assets'))
			->setFactory('Grapesc\\GrapeFluid\\AssetRepository', [$config, $packages]);

		$builder->addDefinition($this->prefix("collector"))
			->setFactory('Grapesc\\GrapeFluid\\ScriptCollector');

		$builder->addDefinition($this->prefix('control'))
			->setFactory('Grapesc\\GrapeFluid\\AssetsControl\\AssetsControl');
	}


    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();
        $builder->getDefinition("assets.control")->addSetup(new Statement('$service->setOptionClasses(?)', [$this->options]));
    }
	
}