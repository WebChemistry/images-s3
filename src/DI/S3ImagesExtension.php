<?php declare(strict_types = 1);

namespace WebChemistry\Images\S3\DI;

use Nette\DI\CompilerExtension;
use WebChemistry\Images\DI\DIHelper;
use WebChemistry\Images\IImageStorage;
use WebChemistry\Images\Modifiers\IModifiers;
use WebChemistry\Images\Modifiers\ModifierContainer;
use WebChemistry\Images\Resources\Meta\IResourceMetaFactory;
use WebChemistry\Images\Resources\Meta\ResourceMetaFactory;
use WebChemistry\Images\S3\S3Facade;
use WebChemistry\Images\S3\S3Storage;

class S3ImagesExtension extends CompilerExtension {

	/** @var array */
	public $defaults = [
		'enable' => false,
		'defaultImage' => null,
		'namespaceBC' => false,
		'config' => [
			'bucket' => null,
			'version' => 'latest',
			'region' => 'eu-west-1',
			'credentials' => [
				'key' => null,
				'secret' => null
			]
		],
		'aliases' => [],
		'modifiers' => [],
	];

	public function loadConfiguration() {
		$config = $this->validateConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		if (!$config['enable']) {
			return;
		}

		// AWS S3
		$modifiers = $builder->addDefinition($this->prefix('modifiers'))
			->setType(IModifiers::class)
			->setFactory(ModifierContainer::class)
			->setAutowired(false);

		DIHelper::addModifiersFromArray($modifiers, $config['modifiers']);
		DIHelper::addAliasesFromArray($modifiers, $config['aliases']);

		$resourceMetaFactory = $builder->addDefinition($this->prefix('resourceMetaFactory'))
			->setType(IResourceMetaFactory::class)
			->setFactory(ResourceMetaFactory::class, [$modifiers])
			->setAutowired(false);

		$builder->addDefinition($this->prefix('facade'))
			->setFactory(S3Facade::class, [$config, $modifiers, $resourceMetaFactory]);

		$def = $builder->addDefinition($this->prefix('storage'))
			->setType(IImageStorage::class)
			->setFactory(S3Storage::class, [
				'defaultImage' => $config['defaultImage'],
			])
			->setAutowired(false);

		if ($config['namespaceBC']) {
			$def->addSetup('setBackCompatibility');
		}
	}

	public function beforeCompile() {
		$builder = $this->getContainerBuilder();

		if (!$builder->getByType(IImageStorage::class)) {
			$builder->getDefinition($this->prefix('storage'))
				->setAutowired();
		}
	}

}
