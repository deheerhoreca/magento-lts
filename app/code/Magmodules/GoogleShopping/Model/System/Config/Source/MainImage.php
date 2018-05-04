<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\GoogleShopping\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class MainImage implements ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('First Image (default)')],
            ['value' => 'image', 'label' => __('Base Image')],
            ['value' => 'small_image', 'label' => __('Small Image')],
            ['value' => 'thumbnail', 'label' => __('Thumbnail')],
            ['value' => 'swatch_image', 'label' => __('Swatch Image')],
        ];
    }
}
