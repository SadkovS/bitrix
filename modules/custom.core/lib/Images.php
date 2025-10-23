<?php

namespace Custom\Core;

class Images {
    protected $defaultImageSizes = [
        '1140w' => [
            'width'  => 1140,
            'height' => 456
        ],
        '924w'  => [
            'width'  => 924,
            'height' => 530,
        ],
        '708w'  => [
            'width'  => 708,
            'height' => 402,
        ],
        '540w'  => [
            'width'  => 540,
            'height' => 310,
        ],
        '370w'  => [
            'width'  => 370,
            'height' => 210,
        ],
        '300w'  => [
            'width'  => 300,
            'height' => 170
        ],
        'thumb' => [
            'width'  => 76,
            'height' => 50,
        ]
    ];

    function processImageResizes($fileID)
    {
        if ((int)$fileID <= 0) {
            return [];
        }

        $results = [];

        foreach ($this->defaultImageSizes as $sizeName => $sizeParams) {
            $width      = (int)($sizeParams['width'] ?? 0);
            $height     = (int)($sizeParams['height'] ?? 0);
            $resizeType = BX_RESIZE_IMAGE_EXACT;
            $quality    = $sizeParams['quality'] ?? 95;

            $resized = \CFile::ResizeImageGet(
                $fileID,
                ['width' => $width, 'height' => $height],
                $resizeType,
                true,
                false,
                false,
                $quality
            );

            if ($resized) {
                $results[$sizeName] = [
                    'src'    => $resized['src'],
                    'width'  => $resized['width'],
                    'height' => $resized['height']
                ];
            }
        }

        return $results;
    }
}