<?php

namespace Grapesc\GrapeFluid;

use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Utils\Arrays;
use Nette\Utils\Finder;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * @author Kulíšek Patrik <kulisek@grapesc.cz>
 * @author Mira Jakes <jakes@grapesc.cz>
 */
class AssetRepository
{

	/** @var array */
	private $assets = [];

	/** @var array */
	private $config;

	/** @var Cache */
	private $cache;

	/** @var bool */
	private $deployed = false;


	public function __construct(array $config, array $assets = [], IStorage $IStorage)
	{
		$this->config = $config;
		$this->assets = $assets;
		$this->cache  = new Cache($IStorage, "Fluid.Assets");

		$this->sortAssets();
		$this->removeDisabledAssets();
	}


	/**
	 * @return array - with sorted assets
	 */
	public function getAssets()
	{
		return $this->assets;
	}


	/**
	 * Zajišťuje prvotní přenos assetů do produkční složky (www/assets)
	 * Sestavený seznam (dle config.neon) ukládá do cache a v případě debug modu kontroluje změny v jednotlivých souborech
	 *
	 * Pozor:
	 * V případě vyplého debug modu je potřeba vždy pro změnu assetů smazat cache
	 *
	 *
	 * @param bool $force
	 * @return array
	 */
	public function deployAssets($force = false)
	{
		if ($this->deployed) {
			return [];
		}

		$files = ($force ? null : $this->cache->load("assets"));

		if (!$this->isDebug() && $files !== null) {
			return [];
		}

		$list = $this->getFileList();

		$deployed = [];
		$tempCache = [];
		$tempLive = [];
		$individual = false;

		if ($files !== null) {
			foreach($files as $limit => $paths) {
				foreach ($paths as $path => $info) {
					if ($info['type'] == 'copy') {
						continue;
					}
					$tempCache[$path] = $info;
				}
			}

			foreach($list as $limit => $paths) {
				foreach ($paths as $path => $info) {
					if ($info['type'] == 'copy') {
						continue;
					}
					$tempLive[$path] = $info;
				}
			}
		}

		if ($files === null) {
			$process = $list;
		} else {
			$process = $this->getAssetsDifferencies($tempCache, $tempLive);
			$individual = true;
		}

		if (!empty($process)) {
			$wwwAssetDir = $this->getAssetsPublicDirectory();

			if (!file_exists($wwwAssetDir)) {
				mkdir($wwwAssetDir, $this->getDirectoryPermissions(), true);
			}

			if (!$individual) {
				foreach ($process as $limit => $files)
				{
					foreach ($files as $path => $info) {
                        $this->deployAsset($path, $info);
						$deployed[] = $path;
					}
				}
			} else {
				foreach ($process as $path => $info) {
				    $this->deployAsset($path, $info);
				}
			}

			$this->cache->save("assets", $list);
		}

		$this->deployed = true;
		return $deployed;
	}


	/**
	 * Zkopíruje veškeré soubory z určené upload složky
	 * Neovlivňuje stav cache
	 */
	public function forceDeployUpload()
	{
		$deployed = [];

		foreach ($this->getFileList() as $limit => $files)
		{
			foreach ($files as $path => $info) {
				if (($info['asset'] == "upload" && $info['type'] == "copy")) {
				    $this->deployAsset($path, $info);
					$deployed[] = $path;
				}
			}
		}

		return $deployed;
	}


	/**
	 * Vrací seznamy souborů dle assetů v config.neon
	 * - Rozdělené dle limitů
	 * - Ořezané o assety označené jako "disabled"
	 * - Seřazené dle "order" pravidel
	 *
	 * Jednotlivé soubory poté obsahují datum změny, typ a název assetu
	 * a případně pomocný klíč "negation" sloužící pro getForCurrentLink
	 *
	 * @return array
	 */
	public function getFileList()
	{
		$list = [];

		foreach ($this->assets as $asset => $types) {
			$limits = [];
			$negation = [];
			$files = [];

			foreach ($types as $type => $records) {
				if ($type == "limit") {
					foreach ($records as $limit) {
						// TODO: Rework - toto je temporary support pro nové limity
						if (!is_array($limit)) {
							$limit = ["link" => $limit];
						}
						if ((substr($limit['link'], 0, 1)) == "!") {
							$negation[] = substr($limit['link'], 1);
						} else {
							$limits[] = json_encode($limit);
						}
					}
				} elseif (in_array($type, ["js", "css", "copy"])) {
					foreach ($records as $file) {
						if (is_array($file) && isset($file[0]) && isset($file[1])) {
							$file[0] = $this->preparePath($file[0]);
							$file[1] = $this->preparePath($file[1]);
							if (basename($file[0]) == "*") {
								if (!is_dir(dirname($file[0]))) {
									continue;
								}

								/* @todo: Podpora pro kopírování 1:1 podsložky */
								$found = Finder::findFiles(basename($file[0]))->from(dirname($file[0]));

								/**
								 * @var string $path
								 * @var \SplFileInfo $info
								 */
								foreach ($found as $path => $info) {
									if (basename($file[1]) == "*") {
										$destination = str_replace("*", $info->getFilename(), $file[1]);
									} else {
										$destination = $file[1] . DIRECTORY_SEPARATOR . $info->getFilename();
									}
									if (!(array_key_exists($path, $files) && in_array($files[$path]['type'], ["css", "js"]))) {
										$files[$path] = [
											"time" => filemtime($path),
											"type" => $type,
											"asset" => $asset,
											"destination" => $destination
										];
									}
								}
								continue;
							} elseif (file_exists($file[0])) {
								$files[$file[0]] = [
									"time" => filemtime($file[0]),
									"type" => $type,
									"asset" => $asset,
									"destination" => $file[1]
								];
								continue;
							} else {
								continue;
							}
						} elseif (filter_var($file, FILTER_VALIDATE_URL) !== false) {
							$files[$file] = [ "time" => 0, "type" => $type, "asset" => $asset ];
							continue;
						} elseif (!file_exists(dirname($this->preparePath($file)))) {
							continue;
						} else {
							$file = $this->preparePath($file);
							if (!is_dir(dirname($file))) {
								continue;
							}
						}

						$found = Finder::findFiles(basename($file))->from(dirname($file));
						foreach ($found as $path => $info) {
							if (pathinfo($path, PATHINFO_EXTENSION) != $type && $type != "copy") {
								continue;
							}
							$files[$path] = [ "time" => filemtime($path), "type" => $type, "asset" => $asset ];
						}
					}
				}
			}

			foreach ($files as $key => $file) {
				$files[$key]['negation'] = $negation;
			}

			if (empty($limits)) {
				$everywhere = json_encode(["link" => "*"]);
				if (array_key_exists($everywhere, $list)) {
					$list[$everywhere] = array_merge($list[$everywhere], $files);
				} else {
					$list[$everywhere] = $files;
				}
			} else {
				foreach ($limits as $limit) {
					if (array_key_exists($limit, $list)) {
						$list[$limit] = array_merge($list[$limit], $files);
					} else {
						$list[$limit] = $files;
					}
				}
			}
		}
		return $list;
	}


	/**
	 * Vrací seznam assetů pro aktuální odkaz / akci ($link) a použití ($type) = css, js
	 * Prvotně se snaží najít záznam v cache, pokud selže, sestaví si nový seznam a ten do cache uloží
	 *
	 * @param string $type (css / js)
	 * @param $actualLink
	 * @return mixed|NULL
	 * @throws \Exception
	 * @throws \Throwable
	 */
	public function getForCurrentLink($type, $actualLink)
	{
		return $this->cache->load($actualLink . "_" . $type, function() use ($type, $actualLink) {
			$assets = [];
			foreach ($this->getFileList() as $limit => $files) {
				// TODO: Rework - toto je temporary support pro nové limity
				$limit = json_decode($limit, true);

				// Limit * na link se nevztahuje na administraci, assety pro Admin modul je treba vzdy explicitne uvest
				// TODO: Seznam modulu, u kterych se musi explicitne udavat limity pro linky
				if (($limit['link'] == "*" || $this->matchLinks($limit['link'], $actualLink)) AND !($limit['link'] == "*" AND $this->matchLinks(":Admin:.*", $actualLink))) {
					foreach ($files as $path => $desc) {
						if (isset($desc['negation'])) {
							foreach ($desc['negation'] as $negation) {
								if ($this->matchLinks($negation, $actualLink)) {
									continue 2;
								}
							}
						}

						$isUrl = filter_var($path, FILTER_VALIDATE_URL);
						if ((pathinfo($path, PATHINFO_EXTENSION) == $type && $desc['type'] != "copy") || ($isUrl && $desc['type'] == $type)) {
							if (isset($limit['auth'])) {
								$desc += ["auth" => $limit['auth']];
							}
							if (isset($limit['option'])) {
								$desc += ["option" => $limit['option']];
							}
							if (isset($desc['destination'])) {
								$destination = $desc['destination'];
								unset($desc['destination']);
								$assets[$this->getAssetsDirectory() . str_replace($this->getAssetsPublicDirectory(), "", $destination)] = $desc;
								continue;
							}

							if ($isUrl) {
								$assets[$path] = $desc + ["url" => true];
							} else {
								$htmlPath = $this->getAssetsDirectory() . DIRECTORY_SEPARATOR .
									$desc['asset'] . DIRECTORY_SEPARATOR . $desc['type'] .
									DIRECTORY_SEPARATOR . basename($path);
								$assets[$htmlPath] = $desc;
							}
						}
					}
				}
			}
			return $assets;
		});
	}


	/**
	 * Odstrani assety z public slozky
	 * @param null|string $relativePath
	 * @param OutputInterface|null $output
	 */
	public function clean($relativePath = null, OutputInterface $output = null)
	{
		$directory = realpath($this->getAssetsPublicDirectory() . $relativePath);

		if (!is_dir($directory)) {
			return;
		}

		foreach (Finder::find('*')->in($directory) AS $item) {
			/* @var $item \SplFileInfo */
			if ($item->isDir() AND $item->getRealPath() != $this->getUploadPath()) {
				$relativeDirectoryPath = ltrim(str_replace(realpath($this->getAssetsPublicDirectory()), "", $item->getPathname()), DIRECTORY_SEPARATOR);
				if (!$relativePath AND $output) {
					$output->writeln("<comment>Deleting {$item->getPathname()}</comment>");
				}
				$this->clean($relativeDirectoryPath, $output);
				if (!@rmdir($item->getPathname()) AND $output) {
					$output->writeln("<error>Can`t delete directory {$item->getPathname()}</error>");
				}
			} elseif ($item->isFile()) {
				if (!@unlink($item->getPathname()) AND $output) {
					$output->writeln("<error>Can`t delete file {$item->getPathname()}</error>");
				}
			} elseif ($item->getRealPath() == $this->getUploadPath()) {
				if ($output) {
					$output->writeln("<info>Skipping {$item->getPathname()}</info>");
				}
			}
		}

		if (!$relativePath) {
			$this->cache->clean([Cache::NAMESPACES => ["Fluid.Assets"]]);
		}
	}


	/**
	 * @return bool|string
	 */
	public function getUploadPath()
	{
		return realpath(substr($this->assets['upload']['copy'][0][1], 0, -1));
	}


	/**
	 * Sort assets by written rules in config.neon
	 *
	 * @return void
	 */
	private function sortAssets()
	{
		$defaultSort = [];
		$beforeAfterSort = [];
		$startSort = [];
		$endSort = [];

		// Partitioning assets into corresponding temporary arrays and unificating format
		foreach ($this->assets as $name => $asset) {
			if (array_key_exists("order", $asset)) {
				if (is_array($asset['order'])) {
					if (array_key_exists("type", $asset['order'])) {
						if ($asset['order']['type'] == "before" || $asset['order']['type'] == "after") {
							$beforeAfterSort[$name] = $asset;
						} elseif ($asset['order']['type'] == "start") {
							$startSort[$name] = $asset;
						} elseif ($asset['order']['type'] == "end") {
							$endSort[$name] = $asset;
						}
					} elseif (array_key_exists("before", $asset['order'])) {
						$asset['order']['type'] = "before";
						$asset['order']['position'] = $asset['order']["before"];
						unset($asset['before']);
						$beforeAfterSort[$name] = $asset;
					} elseif (array_key_exists("after", $asset['order'])) {
						$asset['order']['type'] = "after";
						$asset['order']['position'] = $asset['order']["after"];
						unset($asset['after']);
						$beforeAfterSort[$name] = $asset;
					}
				} elseif (is_string($asset['order'])) {
					if ($asset['order'] == "start") {
						$startSort[$name] = $asset;
					} elseif ($asset['order'] == "end") {
						$endSort[$name] = $asset;
					}
				}
			} else {
				$defaultSort[$name] = $asset;
			}
		}

		// Merge "start / default / end" temporary arrays into 1
		$temporaryMergedSort = $startSort + $defaultSort + $endSort;

		// Combine them with "before / after" sorting
		if (!empty($beforeAfterSort)) {
			while (!empty($beforeAfterSort)) {
				foreach ($beforeAfterSort as $secondaryName => $secondaryAsset) {
					if (isset($secondaryAsset['order']['position'])) {
						if (array_key_exists($secondaryAsset['order']['position'], $temporaryMergedSort)) {
							if ($secondaryAsset['order']['type'] == "after") {
								Arrays::insertAfter($temporaryMergedSort, $secondaryAsset['order']['position'], [ $secondaryName => $secondaryAsset ]);
							} elseif ($secondaryAsset['order']['type'] == "before") {
								Arrays::insertBefore($temporaryMergedSort, $secondaryAsset['order']['position'], [ $secondaryName => $secondaryAsset ]);
							}
							unset($beforeAfterSort[$secondaryName]);
						}
					}
				}
			}
		}

		$this->assets = $temporaryMergedSort;
	}


	/**
	 * Remove assets disabled by config.neon
	 *
	 * @return void
	 */
	private function removeDisabledAssets()
	{
		foreach ($this->assets as $name => $asset) {
			if ((array_key_exists("disabled", $asset) && $asset['disabled'] === true) || (array_key_exists("disable", $asset) && $asset['disable'] === true)) {
				unset($this->assets[$name]);
			}
		}
	}


	/**
	 * @param string $path
	 * @param array $info
	 */
	private function deployAsset($path, $info)
	{
		$subfolder = $info['asset'] . DIRECTORY_SEPARATOR . $info['type'];
		$this->deployFile($path, (array_key_exists("destination", $info) ? $info['destination'] : null), $subfolder);
	}


	/**
	 * Zkopíruje soubor ($path) do produkční složky
	 *
	 * @param $path - Cesta soubory ke kopirovani
	 * @param $destination - Cilova slozka, kam soubor zkopirujeme (pokud neni param null)
	 * @param $subfolder - Podsložka ve veřejné asset složce
	 */
	private function deployFile($path, $destination = null, $subfolder = null)
	{
		if (file_exists($path)) {
			if ($destination == null) {
				$deploy = $this->getAssetsPublicDirectory() . DIRECTORY_SEPARATOR . $subfolder . DIRECTORY_SEPARATOR . basename($path);
			} else {
				$deploy = $destination;
			}

			if (!file_exists(dirname($deploy))) {
				mkdir(dirname($deploy), $this->getDirectoryPermissions(), true);
			}
			copy($path, $deploy);
		}
	}


	/**
	 * @param $pattern
	 * @param $subject
	 * @return false|int
	 */
	private function matchLinks($pattern, $subject) {
		return preg_match("/^" . $pattern . "/", $subject);
	}


	/**
	 * Převede lomítka v zadané cestě dle platných lomítek na daném prostředí
	 *
	 * @param $path
	 * @return mixed
	 */
	private function getEnvironmentPath($path)
	{
		return preg_replace('/(\/+)/', DIRECTORY_SEPARATOR, str_replace(["\\"], "/", $path));
	}


	/**
	 * @return bool
	 */
	private function isDebug()
	{
		return $this->config['debug'];
	}


	/**
	 * @return string
	 */
	private function getDirectoryPermissions()
	{
		return $this->config['dirPerm'];
	}


	/**
	 * @return string
	 */
	private function getAssetsPublicDirectory()
	{
		return $this->config['wwwDir'] . DIRECTORY_SEPARATOR . $this->config['assetsDir'];
	}


	private function getAssetsDirectory()
	{
		return $this->config['assetsDir'];
	}


	/**
	 * @param $path
	 * @return string
	 */
	private function preparePath($path)
	{
		$path = $this->getEnvironmentPath($path);

		if (strpos($path, "&") !== false) {
			return str_replace('&', $this->getAssetsPublicDirectory(), $path);
		}

		return $path;
	}


	private function getAssetsDifferencies($cachedAssets, $freshAssets)
	{
		$process = [];

		foreach ($cachedAssets as $cPath => $cInfo) {
			foreach ($freshAssets as $fPath => $fInfo) {
				if ($cPath == $fPath) {
					if ($cInfo['time'] != $fInfo['time']) {
						$process[$fPath] = $fInfo;
					}
					unset($cachedAssets[$cPath]);
					unset($freshAssets[$fPath]);
				}
			}
		}

		if (!empty($cachedAssets)) {
			// @todo: disabled / removed assets - deal with them
		}

		if (!empty($freshAssets)) {
			$this->cache->clean([ Cache::NAMESPACES => ["Fluid.Assets"] ]);
			$process = $process + $freshAssets;
		}

		return $process;
	}

}
