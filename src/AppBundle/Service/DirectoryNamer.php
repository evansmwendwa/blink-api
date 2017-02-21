<?php
namespace AppBundle\Service;

use Vich\UploaderBundle\Naming\DirectoryNamerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Symfony\Component\HttpFoundation\RequestStack;

class DirectoryNamer implements DirectoryNamerInterface
{
    private $requestStack;
    private $uploadPath;

    public function __construct(RequestStack $requestStack, $uploadPath) {
        $this->requestStack = $requestStack;
        $this->uploadPath = (string)$uploadPath;
    }

    public function directoryName($entity, PropertyMapping $mapping) {
        $date = new \DateTimeImmutable();
        $path = $date->format('Y/m');

        $relativePath = $path.'/'.$entity->getImageName();
        $entity->setImagePath($relativePath);

        return $path;
    }

    public function getUploadsUrl() {
        $request = $this->requestStack->getCurrentRequest();

        $protocol = $request->getScheme() . '://';
        $host = $request->getHttpHost();
        $basePath = $request->getBasePath();

        $url = $protocol .$host .$basePath .$this->uploadPath;

        return $url;
    }
}
