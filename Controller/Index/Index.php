<?php
namespace Review\Reminder\Controller\Index;

use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;

class Index extends \Magento\Framework\App\Action\Action
{
	
	protected $scopeConfig;
	protected $orderRepository;
	protected $searchCriteriaBuilder;
	protected $sortBuilder;
	
   
 
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
 
    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;
 
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;
	protected $customerRepository;
	protected $_shipmentCollection;
	protected $shipmentRepository;
	protected $resultJsonFactory;
	
	const XML_PATH_REMINDER_DAYS = 'review/general/reminder_days';

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
		\Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
		\Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
		\Magento\Framework\Api\SortOrderBuilder $sortBuilder,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
		CustomerRepository $customerRepository,
        \Review\Reminder\Model\Mail\TransportBuilder $transportBuilder,
		\Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollection,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
		
		)
	{
		
		$this->scopeConfig = $scopeConfig;
		$this->shipmentRepository = $shipmentRepository;
		$this->orderRepository = $orderRepository;
		$this->searchCriteriaBuilder = $searchCriteriaBuilder;
		$this->sortBuilder = $sortBuilder;
		$this->_storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder; 
		$this->customerRepository = $customerRepository;
		$this->_shipmentCollection = $shipmentCollection;
		 $this->resultJsonFactory = $resultJsonFactory;
		return parent::__construct($context);
	}

	public function execute()
	{
		
		
		
			$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
			$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/templog.log');
			$logger = new \Zend\Log\Logger();
			$logger->addWriter($writer);
			$x = $this->scopeConfig->getValue(self::XML_PATH_REMINDER_DAYS, $storeScope);
			
			$x = 4; //number of days in the past

			$past_stamp = time() - $x*24*60*60;

			$past_date = date('Y-m-d', $past_stamp);
			
			$dateStart = $past_date.' 00:00:00';
			$dateEnd = $past_date.' 23:59:59';
			
			$logger->info("past_date:". $past_date );
			
			
			
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$imagewidth=200;
			$imageheight=200;
			$imageHelper  = $objectManager->get('\Magento\Catalog\Helper\Image');
			
			
			
			$searchCriteria = $this->searchCriteriaBuilder
								//->addFilter('status','pending','neq')
								->addSortOrder($this->sortBuilder->setField('entity_id')
								->setDescendingDirection()->create())
								->setPageSize(10000)->setCurrentPage(1)->create();
								

			$to = date("Y-m-d h:i:s"); // current date
			

			$shipmentList = $this->shipmentRepository->getList($searchCriteria);
			
			$shipmentList->addFieldToFilter('created_at', array('from' => $dateStart, 'to' => $dateEnd));
			
			
			$shipments = $shipmentList->getItems();
			
			
			
			
			  
			
			
			
			
			//foreach($orders as $order){
				
				
				
				foreach($shipments as $shipment)
				{
					
					/* $productData = [];
					$i = 0; */
					/* foreach ($shipment->getItemsCollection() as $item) {
						if($order->getCustomerId() != ""){	
							$id = $item->getOrderItem()->getProductId();
							$productData[$i]['customer_email'] = $order->getCustomerEmail();
							$productData[$i]['customer_id'] = $order->getCustomerId();
							$productData[$i]['customer_firstname'] = $order->getCustomerFirstname();
							$productData[$i]['product_name'] = $item->getOrderItem()->getName();
							$product = $objectManager->create('Magento\Catalog\Model\Product')->load($id);					 					 
							$productData[$i]['$product_image_url'] = $imageHelper->init($product, 'product_page_image_small')->setImageFile($product->getFile())->resize($imagewidth, $imageheight)->getUrl();
							
							$i++;
						}
						
						
						
																		
					} */
					
					
					$order = $shipment->getOrder();
					$this->_storeManager->setCurrentStore($order->getStoreId());
					
					if($order->getCustomerId() != ""){

						$customer = $this->customerRepository->get($order->getCustomerEmail());
						$review_reminder = $customer->getCustomAttribute('review_reminder')->getValue();
						if( $review_reminder != "0"){
					
							$items = $shipment->getItemsCollection();
							$senderInfo['email'] = "sales@example.com";
							$senderInfo['name'] = 'Sales';
							$this->inlineTranslation->suspend();
							$_transportBuilder = $this->_transportBuilder;
							$_transportBuilder->clearFrom();
							$_transportBuilder->clearSubject();
							$_transportBuilder->clearMessageId();
							$_transportBuilder->clearBody();
							$_transportBuilder->clearRecipients();	
							$_transportBuilder  =  $this->_transportBuilder->setTemplateIdentifier(1)
							->setTemplateOptions(
								[
									'area' => \Magento\Framework\App\Area::AREA_FRONTEND, 
									'store' => $order->getStoreId(),
								]
							)
							->setTemplateVars(array("items" => $items , "order"=>$order))
							->setFrom($senderInfo)
							->addTo($order->getCustomerEmail(),$order->getCustomerFirstname());
							try {
								$transport = $_transportBuilder->getTransport();
								$transport->sendMessage();        
								$this->inlineTranslation->resume();  
								
							}
							
							
							catch(Exception $e) {
							  $logger->info("error:". $e->getMessage() );
							   $resultJson = $this->resultJsonFactory->create();

								return $resultJson->setData(['success' => "0"]);	
							}
							
							
						}							
					}
				
					
				}
				
				
			
					
			//}
		
		
	}
}