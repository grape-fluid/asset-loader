<?php

namespace Grapesc\GrapeFluid;

use Nette\DI\CompilerExtension;


/**
 * @author Kulíšek Patrik <kulisek@grapesc.cz>
 */
class AssetLoaderExtension extends CompilerExtension
{
	
	/** @var array */
	private $params;
	
	
	public function __construct($appDir, $assetsDir, $dirPerm = 0777, $debug = false)
	{
		$this->params = [
			"appDir"    => $appDir,
			"assetsDir" => $assetsDir,
			"dirPerm"   => $dirPerm,
			"debug"     => $debug
		];
	}
	
	
	public function loadConfiguration()
	{
		$config  = $this->getConfig();
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('assets'))
			->setFactory('Grapesc\\GrapeFluid\\AssetRepository', [$this->params, $config]);
	}
	
}