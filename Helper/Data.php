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
 * @package MageINIC_Checkout
 * @copyright Copyright (c) 2023 MageINIC (https://www.mageinic.com/)
 * @license https://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author MageINIC <support@mageinic.com>
 */

namespace MageINIC\Core\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Review\Model\ReviewFactory;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Data
 * @package MageINIC\Core\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var Http
     */
    private $request;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ReviewFactory
     */
    private $reviewFactory;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param ReviewFactory $reviewFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Http $request,
        StoreManagerInterface $storeManager,
        ReviewFactory $reviewFactory
    ) {
        $this->request          = $request;
        $this->storeManager     = $storeManager;
        $this->reviewFactory    = $reviewFactory;
        parent::__construct($context);
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrentStoreId()
    {
        $storeId = $this->request->getParam('store_id');
        if ($storeId) {
            $result = $storeId;
        } else {
            $result = $this->storeManager->getStore()->getId();
        }
        return $result;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * Get Rating Summary
     */
    public function getRatingSummary($product)
    {
        $this->reviewFactory->create()->getEntitySummary($product, $this->getCurrentStoreId());
        $ratingSummary = $product->getRatingSummary()->getRatingSummary(); 
        return $ratingSummary;
    }

    /**
     * Store ID
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Store Code
     * @return mixed
     */
    public function getStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
    }

    /**
     * Get Store Phone Number
     * @return mixed
     */
    public function getStorePhone()
    {
        return $this->scopeConfig->getValue(
            'general/store_information/phone',
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId()
        );
    }
}
