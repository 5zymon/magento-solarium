<?php
/**
 * JeroenVermeulen_Solarium
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this Module to
 * newer versions in the future.
 *
 * @category    JeroenVermeulen
 * @package     JeroenVermeulen_Solarium
 * @copyright   Copyright (c) 2014 Jeroen Vermeulen (http://www.jeroenvermeulen.eu)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class JeroenVermeulen_Solarium_Model_Resource_CatalogSearch_Fulltext extends Mage_CatalogSearch_Model_Resource_Fulltext
{

    /**
     * This function is called when a visitor searches
     *
     * @param Mage_CatalogSearch_Model_Fulltext $object
     * @param string $queryText
     * @param Mage_CatalogSearch_Model_Query $query
     * @return JeroenVermeulen_Solarium_Model_Resource_CatalogSearch_Fulltext
     */
    public function prepareResult( $object, $queryText, $query ) {
        // If the query is already processed, this means Magento has cached the search result already.
        if ( !$query->getIsProcessed() ) {
            if ( JeroenVermeulen_Solarium_Model_Engine::isEnabled( $query->getStoreId() ) ) {
                $adapter           = $this->_getWriteAdapter();
                $searchResultTable = $this->getTable( 'catalogsearch/result' );
                /** @var JeroenVermeulen_Solarium_Model_Engine $engine */
                $engine            = Mage::getSingleton( 'jeroenvermeulen_solarium/engine' );
                if ( $engine->isWorking() ) {
                    $searchResult = $engine->search( $query->getStoreId(), $queryText );
                    if ( false !== $searchResult ) {
                        if ( 0 == count($searchResult) ) {
                            // No results, we need to check if the index is empty.
                            if ( $engine->isEmpty( $query->getStoreId() ) ) {
                                Mage::Log( sprintf('%s - Warning: index is empty', __CLASS__), Zend_Log::WARN );
                            } else {
                                $query->setIsProcessed( 1 );
                            }
                        } else {
                            foreach ( $searchResult as $data ) {
                                $data[ 'query_id' ] = $query->getId();
                                $adapter->insertOnDuplicate( $searchResultTable, $data, array( 'relevance' ) );
                            }
                            $query->setIsProcessed( 1 );
                        }
                    }
                }
            }
            if ( !$query->getIsProcessed() ) {
                Mage::log( 'Solr disabled or something went wrong, fallback to Magento Fulltext Search', Zend_Log::WARN );
                return parent::prepareResult( $object, $queryText, $query );
            }
        }
        return $this;
    }

}