<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Review
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Review form block
 *
 * @category   Mage
 * @package    Mage_Review
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Ip_Ratings_Block_Form extends Mage_Core_Block_Template
{
	
	private $product_reviews = array();
	
	private $the_product = array();
	
	private $customerSession = null;
		
    public function __construct()
    {
        $this->customerSession = Mage::getSingleton('customer/session');

        parent::__construct();

        $data =  Mage::getSingleton('review/session')->getFormData(true);
        $data = new Varien_Object($data);

        // add logged in customer name as nickname
        if (!$data->getNickname()) {
            $customer = $this->customerSession->getCustomer();
            if ($customer && $customer->getId()) {
                $data->setNickname($customer->getFirstname());
            }
        }

        $this->setAllowWriteReviewFlag($this->customerSession->isLoggedIn() || Mage::helper('review')->getIsGuestAllowToWrite());
        if (!$this->getAllowWriteReviewFlag) {
            $this->setLoginLink(
                Mage::getUrl('customer/account/login/', array(
                    Mage_Customer_Helper_Data::REFERER_QUERY_PARAM_NAME => Mage::helper('core')->urlEncode(
                        Mage::getUrl('*/*/*', array('_current' => true)) .
                        '#review-form')
                    )
                )
            );
        }
		
        $this->the_product = Mage::getModel('catalog/product')
			->load($this->getRequest()->getParam('id'));
		
		$this->product_reviews = Mage::getModel('review/review')
                ->getResourceCollection()
                ->addStoreFilter(Mage::app()->getStore()->getId())
                ->addEntityFilter('product', $this->the_product->getId())
                //->setDateOrder()
                ->addRateVotes();
		

        $this->setTemplate('ratings/form.phtml')
            ->assign('data', $data)
            ->assign('messages', Mage::getSingleton('review/session')->getMessages(true));
    }

    public function getProductInfo()
    {
		return $this->the_product;
    }

    public function getAction()
    {
        $productId = Mage::app()->getRequest()->getParam('id', false);
        return Mage::getUrl('review/product/post', array('id' => $productId));
    }

    public function getRatings()
    {
        $ratingCollection = Mage::getModel('rating/rating')
            ->getResourceCollection()
            ->addEntityFilter('product')
            ->setPositionOrder()
            ->addRatingPerStoreName(Mage::app()->getStore()->getId())
            ->setStoreFilter(Mage::app()->getStore()->getId())
            ->load()
            ->addOptionToItems();
        return $ratingCollection;
    }
	
	public function alreadyVoted(){
		foreach ($this->product_reviews->getItems() as $review) {
			foreach( $review->getRatingVotes() as $vote ) {
				if($vote->getCustomerId() == $this->customerSession->getId()){
					return true;
				}
			}
		}
		return false;
	}
	
	public function getRatingHtml()
	{
		$this->product_reviews->addStatusFilter(Mage_Review_Model_Review::STATUS_APPROVED);
		$avg = 0;
		$ratings = array();
		$rating_code = "Rating";
		foreach ($this->product_reviews->getItems() as $review) {
			foreach( $review->getRatingVotes() as $vote ) {
				$ratings[] = $vote->getValue();
				$rating_code = $vote->getRatingCode();
			}
		}		
		return "<strong>{$rating_code}</strong> <div class='summary-rating summary-rating-".ceil(array_sum($ratings)/count($ratings))."'></div>";
	}
}

?>