<?php
/**
 * MageINIC
 * Copyright (C) 2023 MageINIC <support@mageinic.com>
 *
 * NOTICE OF LICENSE
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://opensource.org/licenses/gpl-3.0.html.
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category MageINIC
 * @package MageINIC_Core
 * @copyright Copyright (c) 2023 MageINIC (https://www.mageinic.com/)
 * @license https://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author MageINIC <support@mageinic.com>
 */

namespace MageINIC\Core\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Image\Factory as ImageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Image\AdapterFactory;

/**
 * Class Image
 * @package MageINIC\Core\Helper
 */
class Image extends AbstractHelper
{
    /**
     * @var WriteInterface
     */
    private $directory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AdapterFactory
     */
    private $adapterFactory;

    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * Image constructor.
     *
     * @param Context $context
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param AdapterFactory $adapterFactory
     * @param ImageFactory $imageFactory
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        AdapterFactory $adapterFactory,
        ImageFactory $imageFactory
    ) {
        $this->filesystem = $filesystem;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->storeManager = $storeManager;
        $this->adapterFactory = $adapterFactory;
        $this->imageFactory = $imageFactory;
        parent::__construct($context);
    }

    /**
     * @param $imageName
     * @param $directory
     * @param $width
     * @param $height
     * @return false|string
     */
    public function getResize($imageName, $directory, $width = 250, $height = 250)
    {
        try {
            $isPlaceHolderImage = false;
            $realPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)
                ->getAbsolutePath($directory . '/' . $imageName);
            if (!$this->directory->isFile($realPath) || !$this->directory->isExist($realPath)) {
                $isPlaceHolderImage = true;
                $imageName = $this->getPlaceHolderImage();
                $realPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)
                    ->getAbsolutePath('catalog/product/placeholder/'.$imageName);
                if (!$this->directory->isFile($realPath) || !$this->directory->isExist($realPath)) {
                    return false;
                }
            }
            $targetDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)
                ->getAbsolutePath('resized/' . $directory . '/' . $width . 'x' . $height);
            $pathTargetDir = $this->directory->getRelativePath($targetDir);
            if (!$this->directory->isExist($pathTargetDir)) {
                $this->directory->create($pathTargetDir);
            }
            if (!$this->directory->isExist($pathTargetDir)) {
                return false;
            }

            $image = $this->adapterFactory->create();
            $image->open($realPath);
            $image->keepAspectRatio(true);
            $image->resize($width, $height);
            $dest = $targetDir . '/' . pathinfo($realPath, PATHINFO_BASENAME);
            $image->save($dest);

            if ($this->directory->isFile($this->directory->getRelativePath($dest))) {
                if ($isPlaceHolderImage) {
                    $explodeImage = explode('/', $this->getPlaceHolderImage());
                    if (isset($explodeImage[1])) {
                        $imageName = $explodeImage[1];
                    } else {
                        $imageName = $this->getPlaceHolderImage();
                    }
                }
                return $this->storeManager->getStore()->getBaseUrl(
                        UrlInterface::URL_TYPE_MEDIA
                    ) . 'resized/' . $directory. '/'. $width .'x'. $height .'/'. $imageName;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getPlaceHolderImage()
    {
        return $this->scopeConfig->getValue('catalog/placeholder/image_placeholder');
    }

    /**
     * Create resized images of different sizes.
     *
     * @param string $image
     * @param string $width
     * @param string $height
     * @return string|null
     */
    public function resizeImage(string $image, string $width, string $height)
    {
        try {
            $explodeImagePath = explode("/", $image);
            $parentFolder = $explodeImagePath[count($explodeImagePath) - 2];
            $imageName = end($explodeImagePath);
            $absolutePath = $this->directory->getAbsolutePath();
            if ($parentFolder != "media") {
                $originalImagePath = $absolutePath . $parentFolder . '/' . $imageName;
            } else {
                $originalImagePath = $absolutePath . $imageName;
            }
            if ($this->directory->isFile($originalImagePath)) {
                $dispretionPath = static::getDispersionPath($imageName);
                if ($parentFolder != "media") {
                    $imgSmallPath = 'resized/' .
                        $parentFolder . '/' .
                        $width . '/' .
                        $height .
                        str_replace(
                            '\\',
                            '/',
                            self::_addDirSeparator($dispretionPath)
                        ) . $imageName;
                } else {
                    $imgSmallPath = 'resized/' .
                        $width . '/' .
                        $height .
                        str_replace(
                            '\\',
                            '/',
                            self::_addDirSeparator($dispretionPath)
                        ) . $imageName;
                }
                $imageAssetPath = $absolutePath . $imgSmallPath;
                $alreadyResized = $this->directory->isExist($imageAssetPath);
                if (!$alreadyResized) {
                    $parentFolderName = $parentFolder != "media" ? $parentFolder : null;
                    $imageParams = static::getImageParams($imageName, $width, $height, $parentFolderName);
                    $this->makeImage($originalImagePath, $imageParams);
                }
                return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $imgSmallPath;
            } else {
                $error = __('Cannot resize image "%1" - original image not found', $originalImagePath);
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Make image.
     *
     * @param string $mediastoragefilename
     * @param array $imageParams
     * @return \Magento\Framework\Image
     */
    private function makeImage(string $mediastoragefilename, array $imageParams = []): \Magento\Framework\Image
    {
        $absolutePath = $this->directory->getAbsolutePath();
        $dispretionPath = static::getDispersionPath($imageParams['image']);
        if ($imageParams['parent_folder'] !== null) {
            $imageAssetPath = $absolutePath . 'resized/' .
                $imageParams['parent_folder'] . '/' .
                $imageParams['image_width']. '/' .
                $imageParams['image_height'].
                str_replace(
                    '\\',
                    '/',
                    self::_addDirSeparator($dispretionPath)
                ) . $imageParams['image'];
        } else {
            $imageAssetPath = $absolutePath . 'resized/' .
                $imageParams['image_width']. '/' .
                $imageParams['image_height'].
                str_replace(
                    '\\',
                    '/',
                    self::_addDirSeparator($dispretionPath)
                ) . $imageParams['image'];
        }
        $image = $this->imageFactory->create($mediastoragefilename);
        $image->keepAspectRatio($imageParams['keep_aspect_ratio']);
        $image->keepFrame($imageParams['keep_frame']);
        $image->keepTransparency($imageParams['keep_transparency']);
        $image->constrainOnly($imageParams['constrain_only']);
        $image->backgroundColor($imageParams['background']);
        $image->quality($imageParams['quality']);
        $image->resize($imageParams['image_width'], $imageParams['image_height']);
        $image->save($imageAssetPath);
        return $image;
    }

    /**
     * Get dispersion path
     *
     * @param string $fileName
     * @return string
     */
    private static function getDispersionPath(string $fileName)
    {
        $char = 0;
        $dispersionPath = '';
        while ($char < 2 && ($fileName && $char < strlen($fileName))) {
            if (empty($dispersionPath)) {
                $dispersionPath = '/' . ('.' == $fileName[$char] ? '_' : $fileName[$char]);
            } else {
                $dispersionPath = self::_addDirSeparator(
                        $dispersionPath
                    ) . ('.' == $fileName[$char] ? '_' : $fileName[$char]);
            }
            $char++;
        }
        return $dispersionPath;
    }

    /**
     * Add directory separator
     *
     * @param string $dir
     * @return string
     */
    private static function _addDirSeparator(string $dir)
    {
        if (!$dir || substr($dir, -1) != '/') {
            $dir .= '/';
        }
        return $dir;
    }

    /**
     * Prepare image resize params
     *
     * @param string $imageName
     * @param string $width
     * @param string $height
     * @param string|null $parentFolderName
     * @return array
     */
    private static function getImageParams(
        string $imageName,
        string $width,
        string $height,
        string|null $parentFolderName
    ) {
        $imageParams = [
            'parent_folder' => $parentFolderName,
            'image' => $imageName,
            'image_height' => $height,
            'image_width' => $width,
            'background' =>
                array (
                    0 => 255,
                    1 => 255,
                    2 => 255,
                ),
            'quality' => 80,
            'keep_aspect_ratio' => 1,
            'keep_frame' => 1,
            'keep_transparency' => 1,
            'constrain_only' => 1,
        ];
        return $imageParams;
    }
}
