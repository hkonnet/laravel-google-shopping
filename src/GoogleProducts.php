<?php


namespace Hkonnet\LaravelGoogleShopping;


class GoogleProducts extends BaseClass
{
    /**
     * @param Google_Service_ShoppingContent_Product $product
     * @return mixed
     */
    public function insertProduct(Google_Service_ShoppingContent_Product $product) {
        $response = $this->service->products->insert($this->merchantId, $product);
        // Our example product generator does not set a product_type, so we should
        // get at least one warning.
//        $warnings = $response->getWarnings();
//        print ('Product created, there are ' . count($warnings) . " warnings\n");
//        foreach($warnings as $warning) {
//            printf(" [%s] %s\n", $warning->getReason(), $warning->getMessage());
//        }
        return $response;
    }

    public function updateProduct(Google_Service_ShoppingContent_Product $product) {
        // Let's fix the warning about product_type and update the product
        $product->setProductType('English/Classics');
        // Notice that we use insert. The products service does not have an update
        // method. Inserting a product with an ID that already exists means the same
        // as doing an update anyway.
        $response = $this->requestService->products->insert(
            $this->requestService->merchantId, $product);
        // We should no longer get the product_type warning.
//        $warnings = $response->getWarnings();
//        printf("Product updated, there are now %d warnings\n", count($warnings));
//        foreach($warnings as $warning) {
//            printf(" [%s] %s\n", $warning->getReason(), $warning->getMessage());
//        }
        return $response;
    }

}