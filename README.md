# GrapeFluid/AssetLoader <img align="right" height="40px" src="https://developers.grapesc.cz/logo_inline.png">

[![PHP from Packagist](https://img.shields.io/packagist/php-v/grape-fluid/asset-loader.svg?style=flat-square)](https://packagist.org/packages/grape-fluid/asset-loader)
[![Build Status](https://img.shields.io/travis/grape-fluid/asset-loader.svg?style=flat-square)](https://travis-ci.org/grape-fluid/asset-loader)
[![Code coverage](https://img.shields.io/coveralls/grape-fluid/asset-loader.svg?style=flat-square)](https://coveralls.io/r/grape-fluid/asset-loader)
[![Licence](https://img.shields.io/packagist/l/grape-fluid/asset-loader.svg?style=flat-square)](https://packagist.org/packages/grape-fluid/asset-loader)
[![Downloads this Month](https://img.shields.io/packagist/dm/grape-fluid/asset-loader.svg?style=flat-square)](https://packagist.org/packages/grape-fluid/asset-loader)
[![Downloads total](https://img.shields.io/packagist/dt/grape-fluid/asset-loader.svg?style=flat-square)](https://packagist.org/packages/grape-fluid/asset-loader)
[![Latest stable](https://img.shields.io/packagist/v/grape-fluid/asset-loader.svg?style=flat-square)](https://packagist.org/packages/grape-fluid/asset-loader)

Asset Loader for your Nette projects

## Dependencies

| Require           | Version      |
|-------------------|--------------|
| PHP               | `>= 5.6`     |
| nette/application | `^2.4`       |
| nette/di          | `^2.4`       |

| Require-dev       | Version      |
|-------------------|--------------|
| nette/tester      | `^2.0`       |
| tracy/tracy       | `^2.4`       |

## Quickstart

1. Install **Asset Loader** with composer

	```
	composer require grape-fluid/asset-loader
	```

2. Register **extension** (in neon)

	```
	extensions:
		assets: Grapesc\GrapeFluid\AssetLoaderExtension
	```
	
3. Set your **public directory** (mostly www) (in neon)

	```
	assets:
		config:
			wwwDir: %appDir%/../www
	```
	
4. Set-up your first package (see all options / features below)

	```
	assets:
		yourPackage:
			js:
				# will create file in wwwDir/assets/yourPackage/js/main.js
				- %appDir%/modules/WebsiteModule/assets/js/main.js
			css:
				# will create file in wwwDir/assets/yourPackage/css/styles.css
				- %appDir%/modules/StyleModule/assets/css/style.css
				# you can also use external / remote assets
				- 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.1/css/bootstrap.min.css'
			copy:
				# will copy all images from folder into wwwDir/assets/yourPackage/copy/
				- %appDir%/modules/StyleModule/assets/images/*
	```
	
5. Inject *AssetsControl* and create component in your **BasePresenter**

	``` 
    /** @var \Grapesc\GrapeFluid\AssetsControl\AssetsControl @inject */
    public $assets;
    
    
    protected function createComponentAssets()
    {
    	return $this->assets;
    }
	```
	
6. Render your assets in **template**

	``` 
    {control assets:css}
    {control assets:js}
	```
	
## Multiple files

If you have too many files (for ex. scripts) that you want to include in your templates, 
or just want to copy whole directory (for ex. with images),
you can use asterisk `*` character.

```
assets:
	yourPackage:
		js:
			- %appDir%/modules/WebsiteModule/assets/js/scripts/*
```

## Custom destination

If you need, for any reason, copy file into public directory with custom name or custom directory,
just specify asset as array. You can use `&` character that will be replaced with public assets directory folder.

```
assets:
	yourPackage:
		js:
			# [ source, destination ]
			- [ %appDir%/modules/WebsiteModule/assets/js/main.js, &/my/folder/test.js ]
```
	
You can also do the same with whole **directories**:

```
assets:
	yourPackage:
		js:
			# [ source, destination ]
			- [ %appDir%/modules/StyleModule/assets/images/*, &/images/ ]
```
	
## Options

Of course, quickstart doesn't do anything special but copy files and use them in your template files.
But it's getting more interesting with these options.

### Limits

You can limit **assets** by Nette-like links.
By default all defined assets are enabled everywhere.
**You can combine multiple limits.** By using limits Asset Loader will load only assets that matches your limit.
You can easily create assets for backend / frontend, but also for sub-frontend modules, etc.

#### How to limit asset for **module**?

```
assets:
	yourPackage:
		limit:
			- ":Module:.*"
```

#### How to limit asset for multiple **presenters**?

```
assets:
	yourPackage:
		limit:
			- ":Module:Presenter:.*"
			- ":Module:AnotherPresenter:.*"
```

#### Limiting by access rights ($user->isAllowed())
```
assets:
	yourPackage:
		# Will call $user->isAllowed('homepage')
		- ['link' = ':Module:Presenter.*', 'auth' = 'homepage']
```

#### Limiting by custom options

Create and define custom service that implements `\Grapesc\GrapeFluid\Options\IAssetOptions` interface as written below.
**We are automatically registering these as services** sou you don't have to.
**Inject through constructor** in these services also works.
Now you are able to handle limits in any way you want.
For example enable / disable assets from your back-office.

```
assets:
	config:
		options:
			# Must implement \Grapesc\GrapeFluid\Options\IAssetOptions
			- \Your\Class\That\Handles\SomeOptions
			- \Your\Class\That\Handles\AnotherOptions
	yourPackage:
		# Will call $class->getOption('inline.enabled') on every service
		- ['link' = ':Module:Presenter.*', 'option' = 'inline.enabled']
```

#### Using negation limit

Use `!` in front of limit

```
assets:
	yourPackage:
		limit:
			- "!:Module:Presenter:.*"
```

### Ordering

Sometimes, especially when using modules, it comes handy to sort / order your packages.

#### Start / End

```
assets:
	yourPackage:
		order: [start|end]
```

#### Before / After

```
assets:
	yourPackage:
		order:
			[before|after]: anotherPackageName
```

or

```
assets:
	yourPackage:
		order:
			type: [before|after]
			position: anotherPackageName
```

### Disabling

```
assets:
	yourPackage:
		[disable|disabled]: true
```


## Debug

By default Asset Loader operates in production mode, you can override this with following:

```
assets:
	config:
		debug: true
```

When debug mode is one, Asset Loader watch files for changes and deploy them automatically.
It also works if you add new files into your packages.

## Other

### Custom asset folder

```
assets:
	config:
		assetsDir: customDirectoryName
```

### Directory permissions

```
assets:
	config:
		dirPerm: 0511
```
