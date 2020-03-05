<?php
class AW_Blog_Block_Html_Footer extends Mage_Page_Block_Html_Footer
{
    public function getCacheKeyInfo()
    {
        $cacheKeyInfo = parent::getCacheKeyInfo();
        $cacheKeyInfo[] = 'BLOG';
        return $cacheKeyInfo;
    }

    public function getCopyright()
    {
        $result = parent::getCopyright();
        $layout = $this->getLayout();
        $block = $layout->createBlock('blog/blog')
            ->setTemplate('aw_blog/copyright.phtml')
        ;
        $result = $block->toHtml() . $result;
        return $result;
    }
}