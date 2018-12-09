<?php declare(strict_types = 1);

namespace WebChemistry\Images\S3;

use Nette\NotImplementedException;
use WebChemistry\Images\IImageStorage;
use WebChemistry\Images\Image\ImageSize;
use WebChemistry\Images\Resources\FileResource;
use WebChemistry\Images\Resources\IFileResource;
use WebChemistry\Images\Resources\IResource;
use WebChemistry\Images\Resources\Transfer\ITransferResource;

class S3Storage implements IImageStorage {

	const ORIGINAL = 'original';

	/** @var S3Facade */
	private $facade;

	/** @var null|string  */
	private $defaultImage;

	public function __construct(S3Facade $facade, ?string $defaultImage = null) {
		$this->facade = $facade;
		$this->defaultImage = $defaultImage;
	}

	public function createResource(string $id): IFileResource {
		return new FileResource($id);
	}

	/**
	 * {@inheritdoc}
	 */
	public function link(?IFileResource $resource): ?string {
		if ($resource === null && $this->defaultImage) {
			$default = $this->createResource($this->defaultImage);

			return $this->facade->link($default);
		}


		$defaultImage = $this->facade->getDefaultImage($resource) ? : $this->defaultImage;
		if (($location = $this->facade->link($resource)) === null && $defaultImage) {
			$default = $this->createResource($defaultImage);
			$default->setAliases($resource->getAliases());
			$location = $this->facade->link($default);
		}

		return $location;
	}

	/**
	 * {@inheritdoc}
	 */
	public function save(IResource $resource): IFileResource {
		if (!$resource instanceof ITransferResource) {
			return $this->createResource($resource->getId());
		}
		$resource->setSaved();

		return $this->facade->save($resource);
	}

	/**
	 * {@inheritdoc}
	 */
	public function copy(IFileResource $src, IFileResource $dest) {
		$this->facade->copy($src, $dest);
	}

	/**
	 * {@inheritdoc}
	 */
	public function move(IFileResource $src, IFileResource $dest) {
		$this->facade->move($src, $dest);
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete(IFileResource $resource) {
		$this->facade->delete($resource);
	}

	/**
	 * @param bool $backCompatibility
	 *
	 * @return void
	 */
	public function setBackCompatibility($backCompatibility = true) {
		$this->facade->setBackCompatibility($backCompatibility);
	}

	public function getImageSize(IFileResource $resource): ImageSize {
		throw new NotImplementedException('Method is not implemented yet.');
	}

}
