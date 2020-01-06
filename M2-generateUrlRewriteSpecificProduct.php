<?php
/**
 *
 * Regenerate URL rewrite for specific product
 * How to run: put the file in root folder and run it as below
 * php M2-generateUrlRewriteSpecificProduct.php p=1,3,...
 * with p=product_id1,product_id2,...
 *
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '5G');
error_reporting(E_ALL);

$productInputFound = false;
$pids = '';
if ($argv) {
    foreach ($argv as $v) {
        if(strpos($v, 'p=') !== false) {
            $pv = explode('=', $v);
            if(count($pv) == 2 && !empty($pv[1])) {
                $productInputFound = true;
                $pids = $pv[1];
            }
        }
    }
}

if(!$productInputFound) {
    echo 'Product IDs are required!!!' . "\n";
    exit;
}

use Magento\Framework\App\Bootstrap;
require 'app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');
//Initial
$resConn = $objectManager->create('\Magento\Framework\App\ResourceConnection');
$productCollectionFactory = $objectManager->create("\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory");
$productUrlPathGenerator = $objectManager->create("\Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator");
$productActionFactory = $objectManager->create("\Magento\Catalog\Model\ResourceModel\Product\ActionFactory");
$productAction = $productActionFactory->create();
$productUrlRewriteGeneratorFactory = $objectManager->create("\Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGeneratorFactory");
$productUrlRewriteGenerator = $productUrlRewriteGeneratorFactory->create();
$urlPersist = $objectManager->create("\Magento\UrlRewrite\Model\UrlPersistInterface");

/**
 * Start generating url
 */
process($pids);

/**
 * @param array $ids
 * @param array $storeIds
 */
function process($ids, $storeIds = []) {
    $ids = explode(',', $ids);
    if(!count($ids)) {
        echo "Product IDs are required!" . "\n";
        return;
    }

	if (!count($storeIds)) {
		$storeIds = getAllStoreIds();
	}
	foreach ($storeIds as $storeId) {

	    echo "Store ID: " . $storeId . "\n";
		
		$productCollection = getProductCollection($ids, $storeId);
		$pageCount         = $productCollection->getLastPageNumber();
		
		$currentPage       = 1;
		while ($currentPage <= $pageCount) {
			$productCollection->clear();
			$productCollection->setCurPage($currentPage);
			foreach ($productCollection as $product) {				
				echo "Generating url rewrite for Product ID: " . $product->getId() . "\n";
                do_process($product, $storeId);
			}
			$currentPage++;
		}		
	}

    echo "Done!" . "\n";
}

/**
 * @return array
 */
function getAllStoreIds() {
	global $resConn;
	$result = [];
    $connection = $resConn->getConnection();

	$sql = $connection->select()
		->from($resConn->getTableName('store'), array('store_id', 'code'))
		->order('store_id', 'ASC');
	$queryResult = $connection->fetchAll($sql);

	foreach ($queryResult as $row) {
		$result[] = $row['store_id'];
	}
	return $result;
}

/**
 * @param array $ids
 * @param int $storeId
 * @return mixed
 */
function getProductCollection($ids = [], $storeId = 0) {
	global $productCollectionFactory;
	$pageSize = 1000;	
	
	$productCollection = $productCollectionFactory->create();

	$productCollection->setStore($storeId)
		->addStoreFilter($storeId)
		->addAttributeToSelect('name')
		->addAttributeToSelect('visibility')
		->addAttributeToSelect('url_key')
		->addAttributeToSelect('url_path')
		->addAttributeToSelect('status')
		->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
		->setPageSize($pageSize);

	if (count($ids) > 0) {
		$productCollection->addIdFilter($ids);
	}

	return $productCollection;
}

/**
 * @param $product
 * @param $storeId
 */
function do_process($product, $storeId) {
	global $productUrlPathGenerator, $productAction, $productUrlRewriteGenerator, $urlPersist;
	
	try {
		$product->setData('save_rewrites_history', true);
		$product->setData('url_path', null)
			->setData('url_key', null)
			->setStoreId($storeId);

		$generatedKey = $productUrlPathGenerator->getUrlKey($product);		
		$product->setData('url_key', $generatedKey);

		$productAction->updateAttributes(
		    [$product->getId()],
            [
                'url_path' => null,
                'url_key' => $generatedKey
            ],
			$storeId
		);

		$productUrlRewriteResult = $productUrlRewriteGenerator->generate($product);
		$productUrlRewriteResult = clearProductUrlRewrites($productUrlRewriteResult);

		try {			
			$urlPersist->replace($productUrlRewriteResult);
		} catch (\Exception $e) {
			echo $e->getMessage() . ' Product ID: ' . $product->getId() . "\n";
		}
		
	} catch (\Exception $e) {
		echo $e->getMessage() . ' Product ID: ' . $product->getId() . "\n";
	}
}

/**
 * @param $productUrlRewrites
 * @return mixed
 */
function clearProductUrlRewrites($productUrlRewrites) {
	$paths = [];
	foreach ($productUrlRewrites as $key => $urlRewrite) {
		$path = clearRequestPath($urlRewrite->getRequestPath());
		if (!in_array($path, $paths)) {
			$productUrlRewrites[$key]->setRequestPath($path);
			$paths[] = $path;
		} else {
			unset($productUrlRewrites[$key]);
		}
	}
	return $productUrlRewrites;
}

/**
 * @param $requestPath
 * @return mixed
 */
function clearRequestPath($requestPath) {
	return str_replace(['//', './'], ['/', '/'], ltrim(ltrim($requestPath, '/'), '.'));
}