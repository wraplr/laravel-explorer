<?php

namespace Wraplr\LaravelExplorer\App\Helpers;

class Image
{
    private static $imageMimeTypes = [
        'image/gif' => IMG_GIF,
        'image/jpeg' => IMG_JPG,
        'image/png' => IMG_PNG,
        'image/webp' => IMG_WEBP,
        'image/x-ms-bmp' => IMG_BMP,
    ];

    private $imageRes = false;
    private $imageType = 0;
    private $orientation = 1;

    const CUT_LEFT = 1;
    const CUT_CENTER = 2;
    const CUT_RIGHT = 3;
    const CUT_TOP = 1;
    const CUT_MIDDLE = 2;
    const CUT_BOTTOM = 3;

    const PAD_LEFT = 1;
    const PAD_CENTER = 2;
    const PAD_RIGHT = 3;
    const PAD_TOP = 1;
    const PAD_MIDDLE = 2;
    const PAD_BOTTOM = 3;
    
    const FLIP_NONE = 0x00;
    const FLIP_HORIZONTAL = 0x01;
    const FLIP_VERTICAL = 0x02;

    const SHARPEN_NONE = 0;
    const SHARPEN_SOFT = 1;
    const SHARPEN_BRIGHT = 2;

    public function __construct()
    {
    }

    public function __destruct()
    {
    }
    
    public function open($imagePath)
    {
        if (!is_file($imagePath)) {
            return false;
        }

        $imageInfo = getimagesize($imagePath);

        if ($imageInfo === false) {
            return false;
        }

        // store image_type
        $this->imageType = $imageInfo[2];

        // trying to read exif data
        if (exif_imagetype($imagePath) === IMAGETYPE_JPEG) {
            // sometimes it fails with wrong exif data
            $exifData = @exif_read_data($imagePath);

            if ($exifData !== false) {
                $exifDataLower = array_change_key_case($exifData, CASE_LOWER);

                if (isset($exifDataLower['orientation'])) {
                    $this->orientation = $exifDataLower['orientation'];
                }
            }
        }

        switch ($this->imageType) {
        case IMAGETYPE_JPEG: {
            return ($this->imageRes = @imagecreatefromjpeg($imagePath));
        }
        case IMAGETYPE_WEBP: {
            return ($this->imageRes = @imagecreatefromwebp($imagePath));
        }
        case IMAGETYPE_GIF: {
            return ($this->imageRes = @imagecreatefromgif($imagePath));
        }
        case IMAGETYPE_PNG: {
            return ($this->imageRes = @imagecreatefrompng($imagePath));
        }
        case IMAGETYPE_BMP: {
            return ($this->imageRes = @imagecreatefrombmp($imagePath));
        }
        }

        return ($this->imageRes = null);
    }
    
    public function close()
    {
        if ($this->imageRes) {
            // destroy res
            imagedestroy($this->imageRes);

            // set it to false
            $this->imageRes = false;
        }
    }

    public function save($imagePath, $quality)
    {
        if (!$this->imageRes) {
            return false;
        }

        $dirPath = dirname($imagePath);

        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        switch ($this->imageType) {
        case IMAGETYPE_JPEG: {
            return imagejpeg($this->imageRes, $imagePath, $quality);
        }
        case IMAGETYPE_WEBP: {
            return imagewebp($this->imageRes, $imagePath, $quality);
        }
        case IMAGETYPE_GIF: {
            return imagegif($this->imageRes, $imagePath);
        }
        case IMAGETYPE_PNG: {
            return imagepng($this->imageRes, $imagePath);
        }
        case IMAGETYPE_BMP: {
            return imagebmp($this->imageRes, $imagePath);
        }
        }

        return false;
    }
    
    public function resize($width, $height, $keepAr)
    {
        if (!$this->imageRes || ($width <= 0 && $height <= 0)) {
            return false;
        }
        
        if ($width == 0 || $height == 0) {
            if ($width == 0) {
                $width = (int)($height * ($this->width() / $this->height()));
            }

            if ($height == 0) {
                $height = (int)($width * ($this->height() / $this->width()));
            }
        } else if ($keepAr) {
            if ($this->width() / $this->height() > $width / $height)
                $height = (int)($width * ($this->height() / $this->width()));
            else
                $width = (int)($height * ($this->width() / $this->height()));
        }
        
        if (($imageRes = imagecreatetruecolor($width, $height)) === false) {
            return false;
        }

        if (imagecopyresampled($imageRes, $this->imageRes, 0, 0, 0, 0, $width, $height, $this->width(), $this->height()) === False) {
            imagedestroy($imageRes);

            return false;
        }
        
        // destroy the old one
        imagedestroy($this->imageRes);

        // use the new one
        $this->imageRes = $imageRes;
        
        return true;
    }
    
    public function crop($left, $top, $width, $height, $backgroundColor)
    {
        if (!$this->imageRes || !is_numeric($backgroundColor)) {
            return false;
        }

        // create new image
        if (($imageRes = imagecreatetruecolor($width, $height)) === false) {
            return false;
        }

        // allocate color
        if (($color = imagecolorallocate($imageRes, $this->getRed($backgroundColor), $this->getGreen($backgroundColor), $this->getBlue($backgroundColor))) === false) {
            // destroy image
            imagedestroy($imageRes);

            return false;
        }

        // fill image
        if (imagefill($imageRes, 0, 0, $color) === false) {
            // deallocate color
            imagecolordeallocate($imageRes, $color);

            // destroy image
            imagedestroy($imageRes);

            return false;
        }

        // deallocate color
        imagecolordeallocate($imageRes, $color);

        // calculate new width
        if ($left + $width > $this->width()) {
            $width = max(0, $this->width() - $left);
        }

        // calculate new height
        if ($top + $height > $this->height()) {
            $height = max(0, $this->height() - $top);
        }

        // copy image
        if (imagecopyresampled($imageRes, $this->imageRes, 0, 0, $left, $top, $width, $height, $width, $height) === false) {
            // destroy image
            imagedestroy($imageRes);

            return false;
        }
        
        // destroy the old one
        imagedestroy($this->imageRes);

        // use the new one
        $this->imageRes = $imageRes;
        
        return true;
    }

    public function cut($aspectRatioX, $aspectRatioY, $horizontalAlign, $verticalAlign)
    {
        if (!$this->imageRes || !is_numeric($aspectRatioX) || !is_numeric($aspectRatioY) || !in_array($horizontalAlign, [self::CUT_LEFT, self::CUT_CENTER, self::CUT_RIGHT]) || !in_array($verticalAlign, [self::CUT_TOP, self::CUT_MIDDLE, self::CUT_BOTTOM])) {
            return false;
        }

        // calculcat proportion
        $proportion = ($aspectRatioX / $aspectRatioY);

        if ($this->width() / $this->height() > $proportion) {
            $width = (int)($this->height() * $proportion);
            $height = $this->height();

            $top = 0;
            $left = 0;

            if ($horizontalAlign == self::CUT_LEFT) {
                $left = 0;
            } else if ($horizontalAlign == self::CUT_RIGHT) {
                $left = $this->width() - $width;
            } else if ($horizontalAlign == self::CUT_CENTER) {
                $left = (int)(($this->width() - $width) / 2);
            }
        } else {
            $width = $this->width();
            $height = (int)($this->width() / $proportion);

            $top = 0;
            $left = 0;

            if ($verticalAlign == self::CUT_TOP) {
                $top = 0;
            } else if ($verticalAlign == self::CUT_BOTTOM) {
                $top = $this->height() - $Height;
            } else if ($verticalAlign == self::CUT_MIDDLE) {
                $top = (int)(($this->height() - $height) / 2);
            }
        }
        
        // create new image
        if (($imageRes = imagecreatetruecolor($width, $height)) === false) {
            return false;
        }

        // copy image
        if (imagecopyresampled($imageRes, $this->imageRes, 0, 0, $left, $top, $width, $height, $width, $height) === false) {
            // destroy image
            imagedestroy($imageRes);

            return false;
        }
        
        // destroy the old one
        imagedestroy($this->imageRes);

        // use the new one
        $this->imageRes = $imageRes;
        
        return true;
    }

    public function pad($aspectRatioX, $aspectRatioY, $horizontalAlign, $verticalAlign, $backgroundColor)
    {
        if (!$this->imageRes || !is_numeric($aspectRatioX) || !is_numeric($aspectRatioY) || !is_numeric($backgroundColor) || !in_array($horizontalAlign, [self::PAD_LEFT, self::PAD_CENTER, self::PAD_RIGHT]) || !in_array($verticalAlign, [self::PAD_TOP, self::PAD_MIDDLE, self::PAD_BOTTOM])) {
            return false;
        }

        // calculcat proportion
        $proportion = ($aspectRatioX / $aspectRatioY);

        if ($this->width() / $this->height() > $proportion) {
            $width = $this->width();
            $height = (int)($this->width() / $proportion);

            $top = 0;
            $left = 0;

            if ($verticalAlign == self::PAD_TOP) {
                $top = 0;
            } else if ($verticalAlign == self::PAD_BOTTOM) {
                $top = $height - $this->height();
            } else if ($verticalAlign == self::PAD_MIDDLE) {
                $top = (int)(($height - $this->height()) / 2);
            }
        } else {
            $width = (int)($this->height() * $proportion);
            $height = $this->height();

            $top = 0;
            $left = 0;

            if ($horizontalAlign == self::PAD_LEFT) {
                $left = 0;
            } else if ($horizontalAlign == self::PAD_RIGHT) {
                $left = $width - $this->width();
            } else if ($horizontalAlign == self::PAD_CENTER) {
                $left = (int)(($width - $this->width()) / 2);
            }
        }

        // create new image
        if (($imageRes = imagecreatetruecolor($width, $height)) === false) {
            return false;
        }

        // allocate color
        if (($color = imagecolorallocate($imageRes, $this->getRed($backgroundColor), $this->getGreen($backgroundColor), $this->getBlue($backgroundColor))) === false) {
            // destroy image
            imagedestroy($imageRes);

            return false;
        }

        // fill image
        if (imagefill($imageRes, 0, 0, $color) === false) {
            // deallocate color
            imagecolordeallocate($imageRes, $color);

            // destroy image
            imagedestroy($imageRes);

            return false;
        }

        // deallocate color
        imagecolordeallocate($imageRes, $color);

        // copy image
        if (imagecopyresampled($imageRes, $this->imageRes, $left, $top, 0, 0, $this->width(), $this->height(), $this->width(), $this->height()) === false) {
            // destroy image
            imagedestroy($imageRes);

            return false;
        }
        
        // destroy the old one
        imagedestroy($this->imageRes);

        // use the new one
        $this->imageRes = $imageRes;
        
        return true;
    }
    
    public function autoRotate()
    {
        if ($this->orientation == 1) {
            return true;
        }

        $transform = [
            1 => ['rotate' => 0,
                  'flip' => self::FLIP_NONE],
            2 => ['rotate' => 0,
                  'flip' => self::FLIP_HORIZONTAL],
            3 => ['rotate' => 180,
                  'flip' => self::FLIP_NONE],
            4 => ['rotate' => 0,
                  'flip' => self::FLIP_VERTICAL],
            5 => ['rotate' => 90,
                  'flip' => self::FLIP_HORIZONTAL],
            6 => ['rotate' => 270,
                  'flip' => self::FLIP_NONE],
            7 => ['rotate' => 270,
                  'flip' => self::FLIP_HORIZONTAL],
            8 => ['rotate' => 90,
                  'flip' => self::FLIP_NONE],
        ];

        if (!isset($transform[$this->orientation])) {
            return false;
        }

        // rotate it first
        if ($this->rotate($transform[$this->orientation]['rotate'], 0x000000) === false) {
            return false;
        }

        // then flip it
        if ($this->flip($transform[$this->orientation]['flip']) === false) {
            return false;
        }

        //  no more autoRotate
        $this->orientation = 1;

        return true;
    }

    public function rotate($degree, $backgroundColor)
    {
        if (!$this->imageRes || !is_numeric($backgroundColor)) {
            return false;
        }
        
        if ($degree == 0) {
            return true;
        }
        
        if (($color = imagecolorallocate($this->imageRes, $this->getRed($backgroundColor), $this->getGreen($backgroundColor), $this->getBlue($backgroundColor))) === false) {
            return false;
        }

        // rotate
        if (($imageRes = imagerotate($this->imageRes, $degree, $color)) === false) {
            // deallocate color
            imagecolordeallocate($this->imageRes, $color);

            return false;
        }

        // deallocate color
        imagecolordeallocate($this->imageRes, $color);

        // destroy the old one
        imagedestroy($this->imageRes);

        // use the new one
        $this->imageRes = $imageRes;
        
        return true;
    }
    
    public function sharpen($level)
    {
        if (!$this->imageRes) {
            return false;
        }

        if ($level == self::SHARPEN_SOFT) {
            $sharpenMatrix = [
                [-1.2, -1, -1.2],
                [-1, 20, -1],
                [-1.2, -1, -1.2],
            ];
        } else if ($level == self::SHARPEN_BRIGHT) {
            $sharpenMatrix = [
                [0.0, -1.0, 0.0],
                [-1.0, 5.0, -1.0],
                [0.0, -1.0, 0.0],
            ];
        } else {
            return false;
        }

        return imageconvolution($this->imageRes, $sharpenMatrix, array_sum(array_map('array_sum', $sharpenMatrix)), 0);
    }

    public function flip($orientation)
    {
        if ($this->imageRes == null || !in_array($orientation, [self::FLIP_NONE, self::FLIP_HORIZONTAL, self::FLIP_VERTICAL])) {
            return false;
        }
        
        if ($orientation == self::FLIP_NONE) {
            return true;
        }
        
        $srcLeft = 0;
        $srcTop = 0;
        $srcWidth = $this->width();
        $srcHeight = $this->height();

        if ($orientation & self::FLIP_HORIZONTAL) {
            $srcLeft = $this->width() - 1;
            $srcWidth = -$this->width();
        }

        if ($orientation & self::FLIP_VERTICAL) {
            $srcTop = $this->height() - 1;
            $srcHeight = -$this->height();
        }

        if (($imageRes = imagecreatetruecolor($this->width(), $this->height())) === false) {
            return false;
        }

        if (imagecopyresampled($imageRes, $this->imageRes, 0, 0, $srcLeft, $srcTop, $this->width(), $this->height(), $srcWidth, $srcHeight) === false) {
            imagedestroy($imageRes);

            return false;
        }
        
        // destroy the old one
        imagedestroy($this->imageRes);

        // use the new one
        $this->imageRes = $imageRes;
        
        return true;
    }

    private function width()
    {
        return imagesx($this->imageRes);
    }
    
    private function height()
    {
        return imagesy($this->imageRes);
    }

    private function getRed($color)
    {
        return (($color >> 16) & 0xFF);
    }

    private function getGreen($color)
    {
        return (($color >> 8) & 0xFF);
    }

    private function getBlue($color)
    {
        return ($color & 0xFF);
    }

    public static function imageType($mimeType)
    {
        if (!isset(self::$imageMimeTypes[$mimeType])) {
            return 0;
        }

        return self::$imageMimeTypes[$mimeType];
    }
}
