<?php
declare(strict_types=1);

/**
 * this is used solely to address the custom attribute issue
 * Note: Please disable this plugin adobe release a patch to address custom attribute bug for
 * v2.4.7
 *
 * */
namespace BCMarketplace\SegmentAccessControl\Plugin\NegotiableQuote\Customer\Model;



use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\CustomerFactory;

class CustomerRepository extends \Magento\NegotiableQuote\Model\Plugin\Customer\Model\CustomerRepository {

    private CustomerFactory $customerFactory;
    private LoggerInterface $logger;

    public function __construct(
        \Magento\NegotiableQuote\Model\ResourceModel\QuoteGrid $quoteGrid,
        \Magento\Customer\Api\CustomerNameGenerationInterface $customerViewHelper,
        \Magento\NegotiableQuote\Model\Purged\Extractor $extractor,
        \Magento\NegotiableQuote\Model\Purged\Handler $purgedContentsHandler,
        CustomerFactory $customerFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($quoteGrid, $customerViewHelper, $extractor, $purgedContentsHandler);
        $this->customerFactory = $customerFactory;
        $this->logger = $logger;
    }

    public function aroundSave(
        CustomerRepositoryInterface $subject,
        \Closure $proceed,
        CustomerInterface $customer,
                                    $passwordHash = null
    ) {
        $result = parent::aroundSave($subject, $proceed, $customer, $passwordHash);
        $customerModel = $this->customerFactory->create();

        try{
            $customerModel->setWebsiteId($customer->getWebsiteId());
            $customerModel->loadByEmail($customer->getEmail());
            $customAttributes = $customer->getCustomAttributes();
            foreach($customAttributes as $attribute) {
                $customerModel->setData($attribute->getAttributeCode(), $attribute->getValue());
            }
            /***
             * using this deprecated code was the less intrusive approach to get the custom attributes to save
             * Again, this is temporary bugfix and need to be removed on the next patch release.
             */
            $customerModel->save();
        }catch(\Exception $e){
           $this->logger->critical($e);
        }

        return $result;
    }
}
