<?php
    /**
     * @Thomas-Athanasiou
     *
     * @author Thomas Athanasiou {thomas@hippiemonkeys.com}
     * @link https://hippiemonkeys.com
     * @link https://github.com/Thomas-Athanasiou
     * @copyright Copyright (c) 2023 Hippiemonkeys Web Inteligence EE All Rights Reserved.
     * @license http://www.gnu.org/licenses/ GNU General Public License, version 3
     * @package Hippiemonkeys_ModificationMagentoGoogleGtag
     */

    declare(strict_types=1);

    namespace Hippiemonkeys\ModificationMagentoGoogleGtag\Block;

    use Magento\Cookie\Helper\Cookie,
        Magento\GoogleGtag\Block\Ga as ParentGa,
        Magento\Framework\Api\SearchCriteriaBuilder,
        Magento\Framework\Serialize\SerializerInterface,
        Magento\Framework\View\Element\Template\Context,
        Magento\GoogleGtag\Model\Config\GtagConfig,
        Magento\Sales\Api\OrderRepositoryInterface,
        Magento\Sales\Model\Order,
        Magento\Store\Model\Store,
        Magento\Store\Model\StoreManagerInterface,
        Hippiemonkeys\Core\Api\Helper\ConfigInterface;

    class Ga
    extends ParentGa
    {
        /**
         * Constructor
         *
         * @access public
         *
         * @param \Magento\Framework\View\Element\Template\Context $context
         * @param \Magento\GoogleGtag\Model\Config\GtagConfig $gtagConfig
         * @param \Magento\Cookie\Helper\Cookie $cookieHelper
         * @param \Magento\Framework\Serialize\SerializerInterface $serializer
         * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
         * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
         * @param \Hippiemonkeys\Core\Api\Helper\ConfigInterface $config
         * @param array $data
         */
        public function __construct(
            Context $context,
            GtagConfig $gtagConfig,
            Cookie $cookieHelper,
            SerializerInterface $serializer,
            SearchCriteriaBuilder $searchCriteriaBuilder,
            OrderRepositoryInterface $orderRepository,
            ConfigInterface $config,
            array $data = []
        )
        {
            $this->_orderRepository = $orderRepository;
            $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
            $this->_config = $config;
            parent::__construct($context, $gtagConfig, $cookieHelper, $serializer, $searchCriteriaBuilder, $orderRepository, $data);
        }

        /**
         * {@inheritdoc}
         */
        public function getOrdersTrackingData(): array
        {
            $result = [];
            if($this->getIsActive())
            {
                $orderIds = $this->getOrderIds();
                if (empty($orderIds) || !is_array($orderIds))
                {
                    return $result;
                }

                $collection = $this->getOrderRepository()->getList(
                    $this->getSearchCriteriaBuilder()->addFilter('entity_id', $orderIds, 'in')->create()
                );

                foreach ($collection->getItems() as $order)
                {
                    if($order instanceof Order)
                    {
                        foreach ($order->getAllVisibleItems() as $item)
                        {
                            $result['products'][] = [
                                'item_id' => $this->escapeJsQuote($item->getSku()),
                                'item_name' =>  $this->escapeJsQuote($item->getName()),
                                'price' => (float) number_format((float) $item->getPrice(), 2),
                                'quantity' => (int) $item->getQtyOrdered(),
                            ];
                        }

                        $orderData = [
                            'transaction_id' =>  $order->getIncrementId(),
                            'value' => (float) number_format((float) $order->getGrandTotal(), 2),
                            'tax' => (float) number_format((float) $order->getTaxAmount(), 2),
                            'shipping' => (float) number_format((float) $order->getShippingAmount(), 2),
                            'currency' => $order->getOrderCurrencyCode()
                        ];

                        $store = $this->getStoreManager()->getStore();
                        if($store instanceof Store)
                        {
                            $orderData['affiliation'] = $this->escapeJsQuote($store->getFrontendName());
                        }

                        $result['orders'][] = $orderData;
                        $result['currency'] = $order->getOrderCurrencyCode();
                    }
                }
            }
            else
            {
                $result = parent::getOrdersTrackingData();
            }

            return $result;
        }

        /**
         * Gets Is Active flag
         *
         * @access protected
         *
         * @return bool
         */
        protected function getIsActive(): bool
        {
            return $this->getConfig()->getIsActive();
        }

        /**
         * Gets Store Manager
         *
         * @access protected
         *
         * @return \Magento\Store\Model\StoreManagerInterface
         */
        protected function getStoreManager(): StoreManagerInterface
        {
            return $this->_storeManager;
        }

        /**
         * Order Repository property
         *
         * @access private
         *
         * @var \Magento\Sales\Api\OrderRepositoryInterface $_orderRepository
         */
        private $_orderRepository;

        /**
         * Gets Order Repository
         *
         * @access protected
         *
         * @return \Magento\Sales\Api\OrderRepositoryInterface
         */
        protected function getOrderRepository(): OrderRepositoryInterface
        {
            return $this->_orderRepository;
        }

        /**
         * Search Criteria Builder property
         *
         * @access private
         *
         * @var \Magento\Framework\Api\SearchCriteriaBuilder $_searchCriteriaBuilder
         */
        private $_searchCriteriaBuilder;

        /**
         * Gets Search Criteria Builder
         *
         * @access protected
         *
         * @return \Magento\Framework\Api\SearchCriteriaBuilder
         */
        protected function getSearchCriteriaBuilder(): SearchCriteriaBuilder
        {
            return $this->_searchCriteriaBuilder;
        }

        /**
         * Config property
         *
         * @access private
         *
         * @var \Hippiemonkeys\Core\Api\Helper\ConfigInterface $_config
         */
        private $_config;

        /**
         * Gets Config
         *
         * @access protected
         *
         * @return \Hippiemonkeys\Core\Api\Helper\ConfigInterface
         */
        protected function getConfig(): ConfigInterface
        {
            return $this->_config;
        }
    }
?>