<?php
namespace AppBundle\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class Imager
{
    protected $config;
    protected $cachePath;
    protected $requestStack;
    protected $baseUrl;

    function __construct(RequestStack $requestStack, $config, $cachePath = 'media/cache')
    {
        $this->requestStack = $requestStack;
        $this->cachePath = $cachePath.'/imager';
        $this->config = $config;

        $request = $requestStack->getCurrentRequest();
        $protocol = $request->getScheme() . '://';
        $host = $request->getHttpHost();
        $basePath = $request->getBasePath();
        $this->baseUrl = $protocol .$host .$basePath;
    }

    public function filter($filename, $thumb_key)
    {
        $filterConfig = $this->config['filter_sets'][$thumb_key];
        $rootPath = $this->config['root_path'];
        $webPath = $this->config['web_path'];

        $baseUrl = (null === $webPath) ? 'web' : $webPath;
        $relativePath = '/'.$this->cachePath.'/'.$thumb_key.$filename;
        $resultPath = $rootPath.'/../'.$baseUrl.$relativePath;
        $originalPath = $rootPath.'/../'.$baseUrl.$filename;

        $filesystem = new Filesystem();
        if($filesystem->exists($resultPath)) {
            //return $relativePath;
            return $this->baseUrl.$webPath.$relativePath;
        }

        if(!$filesystem->exists(dirname($resultPath))) {
            $filesystem->mkdir(dirname($resultPath),0755);
        }

        $result = $this->resizeImage(
            $originalPath,
            $resultPath,
            $filterConfig['width'],
            $filterConfig['height'],
            $filterConfig['crop_mode'],
            $filterConfig['quality'],
            false
        );

        if($result === false) {
            return $filename;
        }

        return $this->baseUrl.$webPath.$relativePath;

        //return $relativePath;
    }

    protected function thumbnail($filename, $filter)
    {
        $relativePath = '/'.$this->cachePath.'/'.$filter->filter_key.$filename;
        $resultPath = $rootPath.'/../'.$baseUrl.$relativePath;
        $originalPath = $rootPath.'/../'.$baseUrl.$filename;

        $filesystem = new Filesystem();
        if($filesystem->exists($resultPath)){
            return $relativePath;
        }

        if(!$filesystem->exists(dirname($resultPath))){
            $filesystem->mkdir(dirname($resultPath),0755);
        }

        $result = $this->resizeImage($originalPath, $resultPath, $filter->width, $filter->height, $filter->crop_mode, $filter->quality, false);

        if($result === false){
            return $filename;
        }

        return $relativePath;
    }

    private function memory_get_available() {
        $limit = null;
        if (!isset($limit)) {
            $inilimit = trim(ini_get('memory_limit'));
            if (empty($inilimit)) {  // no limit set
                $limit = false;
            } elseif (ctype_digit($inilimit)) {
                $limit = (int) $inilimit;
            } else {
                $limit = (int) substr($inilimit, 0, -1);
                switch (strtolower(substr($inilimit, -1))) {
                    case 'g':
                        $limit *= 1024;
                    case 'm':
                        $limit *= 1024;
                    case 'k':
                        $limit *= 1024;
                }
            }
        }
        if ($limit !== false) {
            if ($limit < 0) {
                return false;  // no memory upper limit set in php.ini
            } else {
                return $limit - memory_get_usage(true);
            }
        } else {
            return false;
        }
    }
    /**
     * Checks whether sufficient memory is available to load and process an image.
     */
    private function checkMemory($imagepath) {
        $memory_available = $this->memory_get_available();
        if ($memory_available !== false) {
            if($imagedata = @getimagesize($imagepath)){
                //
            } else {
                return false;
            }
            if ($imagedata === false) {
                return false;
            }
            if (!isset($imagedata['channels'])) {  // assume RGB (i.e. 3 channels)
                $imagedata['channels'] = 3;
            }
            if (!isset($imagedata['bits'])) {  // assume 8 bits per channel
                $imagedata['bits'] = 8;
            }
            $memory_required = (int)ceil($imagedata[0] * $imagedata[1] * $imagedata['channels'] * $imagedata['bits'] / 8);
            if ($memory_required >= $memory_available) {
                $msg = 'LOW_MEMORY_EXCEPTION';
                throw new Exception($msg,99);
            }
        }
    }

    private function checkUrlExists($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($code == 200){
            $status = true;
        }else{
            $status = false;
        }
        curl_close($ch);
        return $status;
    }

    /**
     * Creates thumbnails
     */
    protected function resizeImage($sourcefile, $destination, $thumb_w, $thumb_h, $crop = false, $quality = 100  , $force_landscape = false)
    {
        // give this function some more time in case the default is low or elapsed
        set_time_limit(60);
        // confirm file_exists
        if(!$this->checkUrlExists($sourcefile) && !is_readable($sourcefile)){
            return false;
        }

        // check memory requirement for operation
        // TODO - rewrite logic to test for available memory and perform tests
        /*
        $memory_status = $this->checkMemory($sourcefile);
        if(!$memory_status){
            echo ' no memory: '.$memory_status;
            exit;
            return $sourcefile;
        }*/
        if( $sourcefile == '' || $destination == '' || $thumb_w <5) {
            return false;
        }
        $filename = $sourcefile;
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        switch($extension) {
            case 'png': case 'PNG':
            $source_img = @imagecreatefrompng($filename);
            break;
            case 'gif': case 'GIF':
            $source_img = @imagecreatefromgif($filename);
            break;
            case 'jpg': case 'jpeg': case 'JPG': case 'JPEG':
            $source_img = @imagecreatefromjpeg($filename);
            break;
            default:
                return false;
        }
        if (!$source_img) {
            return false;  // could not create image from file
        }
        // get dimensions for cropping and resizing
        $orig_w = imagesx($source_img);
        $orig_h = imagesy($source_img);
        if($orig_w <= $thumb_w && $crop == false ) {
            $thumb_w = $orig_w;
        }
        if (false && $thumb_w >= $orig_w && $thumb_h >= $orig_h) {  // nothing to do
            $thumb_img = $source_img;
        } else {
            $ratio_orig = $orig_w/$orig_h;  // width-to-height ratio of original image
            $ratio_thumb = $thumb_w/$thumb_h;  // width-to-height ratio of thumbnail image
            if ($crop) {  // resize with automatic centering, crop image if necessary
                if ($ratio_thumb > $ratio_orig) {  // crop top and bottom
                    $zoom = $orig_w / $thumb_w;  // zoom factor of original image w.r.t. thumbnail
                    $crop_h = floor($zoom * $thumb_h);
                    $crop_w = $orig_w;
                    $crop_x = 0;
                    $crop_y = floor(0.5 * ($orig_h - $crop_h));
                } else {  // crop left and right
                    $zoom = $orig_h / $thumb_h;  // zoom factor of original image w.r.t. thumbnail
                    $crop_h = $orig_h;
                    $crop_w = floor($zoom * $thumb_w);
                    $crop_x = floor(0.5 * ($orig_w - $crop_w));
                    $crop_y = 0;
                }
            } else {  // resize with fitting larger dimension, do not crop image
                $crop_w = $orig_w;
                $crop_h = $orig_h;
                $crop_x = 0;
                $crop_y = 0;
                if ($ratio_thumb > $ratio_orig) {  // fit height
                    $zoom = $orig_h / $thumb_h;
                    $thumb_w = floor($orig_w / $zoom);
                } else {  // fit width
                    $zoom = $orig_w / $thumb_w;
                    $thumb_h = floor($orig_h / $zoom);
                }
                if($force_landscape && $thumb_h > $thumb_w){
                    $thumb_h = floor(0.7 * $thumb_w);
                }
            }
            $thumb_img = imagecreatetruecolor($thumb_w, $thumb_h);
            $result = imagealphablending($thumb_img, false) && imagesavealpha($thumb_img, true);
            if (!imageistruecolor($source_img) && ($transparentindex = imagecolortransparent($source_img)) >= 0) {
                // convert color index transparency to alpha channel transparency
                if (imagecolorstotal($source_img) > $transparentindex) {  // transparent color is in palette
                    $transparentrgba = imagecolorsforindex($source_img, $transparentindex);
                } else {  // use white as transparent background color
                    $transparentrgba = array('red' => 255, 'green' => 255, 'blue' => 255);
                }
                // fill image with transparent color
                $transparentcolor = imagecolorallocatealpha($thumb_img, $transparentrgba['red'], $transparentrgba['green'], $transparentrgba['blue'], 127);
                imagefilledrectangle($thumb_img, 0, 0, $orig_w, $orig_h, $transparentcolor);
                imagecolordeallocate($thumb_img, $transparentcolor);
            }
            // resample image into thumbnail size
            $result = $result && imagecopyresampled($thumb_img, $source_img, 0, 0, $crop_x, $crop_y, $thumb_w, $thumb_h, $crop_w, $crop_h);
            imagedestroy($source_img);
            if ($result === false) {
                imagedestroy($thumb_img);
                return false;
            }
        }
        $result = imagejpeg($thumb_img, $destination, $quality);
        imagedestroy($thumb_img);
        return $result;
    }
}
