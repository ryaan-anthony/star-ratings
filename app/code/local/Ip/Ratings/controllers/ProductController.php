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
 * Review controller
 *
 * @category   Mage
 * @package    Mage_Review
 * @author     Magento Core Team <core@magentocommerce.com>
 */
 
require_once 'Mage/Review/controllers/ProductController.php';
class Ip_Ratings_ProductController extends Mage_Review_ProductController
{

  

    /**
     * Submit new review action
     *
     */
    public function postAction()
    {
		
        if ($data = Mage::getSingleton('review/session')->getFormData(true)) {
            $rating = array();
            if (isset($data['ratings']) && is_array($data['ratings'])) {
                $rating = $data['ratings'];
            }
        } else {
            $data   = $this->getRequest()->getPost();
            $rating = $this->getRequest()->getParam('ratings', array());
        }

        if (($product = $this->_initProduct()) && !empty($data)) {
            $session    = Mage::getSingleton('core/session');
            /* @var $session Mage_Core_Model_Session */
            $review     = Mage::getModel('review/review')->setData($data);
            /* @var $review Mage_Review_Model_Review */

			try {
				$review->setEntityId($review->getEntityIdByCode(Mage_Review_Model_Review::ENTITY_PRODUCT_CODE))
					->setEntityPkValue($product->getId())
					->setStatusId(Mage_Review_Model_Review::STATUS_APPROVED)
					->setCustomerId(Mage::getSingleton('customer/session')->getCustomerId())
					->setStoreId(Mage::app()->getStore()->getId())
					->setStores(array(Mage::app()->getStore()->getId()))
					->save();

				foreach ($rating as $ratingId => $optionId) {
					Mage::getModel('rating/rating')
					->setRatingId($ratingId)
					->setReviewId($review->getId())
					->setCustomerId(Mage::getSingleton('customer/session')->getCustomerId())
					->addOptionVote($optionId, $product->getId());
				}

				$review->aggregate();
				$rating_option = array(
					16 => 1,
					17 => 2,
					18 => 3,
					19 => 4,
					20 => 5
				);
														
				if($rating_option[reset($rating)] > 2){
					$session->addSuccess($this->__('Thank you for submitting your rating!'));
				} else {
					$session->addSuccess($this->__("We're sorry you had a bad experience. Please <a href='".Mage::getUrl('contact')."'>let us know</a> if we can be of further assistance."));
				}
			}
			catch (Exception $e) {
				$session->setFormData($data);
				$session->addError($this->__('Unable to post the review.'));
			}
            
        }

        if ($redirectUrl = Mage::getSingleton('review/session')->getRedirectUrl(true)) {
            $this->_redirectUrl($redirectUrl);
            return;
        }
        $this->_redirectReferer();
    }

}
