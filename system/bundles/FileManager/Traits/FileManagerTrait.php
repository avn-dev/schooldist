<?php

namespace FileManager\Traits;

use FileManager\Entity\File;
use Illuminate\Support\Arr;

trait FileManagerTrait
{

    /**
     * FileManager-Pfad von Entity überschreiben
     *
     * @return string
     * @see \FileManager\Entity\File::getPath()
     */
    public function getFileManagerEntityPath(): string
    {
        return \Util::getCleanFilename(get_class($this));
    }

    /**
     * @param string $sTag
     * @return File[]
     */
    public function getFiles(string $sTag = null)
    {

        // Trait wird auch im TcBasic-Proxy verwendet
        $oEntity = $this;
        if ($this instanceof \Core\Proxy\WDBasicAbstract) {
            $oEntity = $this->oEntity;
        }

        $oRepository = File::getRepository();
        return $oRepository->getByEntityAndTag($oEntity, $sTag);

    }

    /**
     * @param string $sTag
     * @return File|null
     */
    public function getFirstFile(string $sTag = null)
    {

        $aFiles = $this->getFiles($sTag);
        if (empty($aFiles)) {
            return null;
        }

        return Arr::first($aFiles);

    }

    /**
     * @param string|null $sTag
     * @return File|null
     */
    public function getRandomFile(string $sTag = null)
    {

        $aFiles = $this->getFiles($sTag);
        if (empty($aFiles)) {
            return null;
        }

        return Arr::random($aFiles);

    }
}

