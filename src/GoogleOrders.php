<?php


namespace Hkonnet\LaravelGoogleShopping;


use Google_Service_ShoppingContent_OrdersCustomBatchRequestEntryShipLineItemsShipmentInfo;

class GoogleOrders extends BaseClass
{
    private $nonce = 0; // used by newOperationId()

    public function __construct(){
        parent::__construct();
        $this->nonce = time();
    }
    /**
     * Lists the unacknowledged orders for {@code $this->session->merchantId},
     * printing out each in turn.
     */
    public function listUnacknowledgedOrders($parameters = []) {
        $orders = [];
        $parameters['acknowledged'] = false;
        do {
            $resp = $this->requestService->orders->listOrders($this->merchantId, $parameters);
            if (empty($resp->getResources())) {
                // No Orders
                return false;
            }
            foreach ($resp->getResources() as $order) {
                $orders[] = $order;
//                $this->printOrder($order);
            }
            $parameters['pageToken'] = $resp->getNextPageToken();
        } while (!empty($parameters['pageToken']));
        return $orders;
    }
    /**
     * Lists the all orders for {@code $this->session->merchantId},
     * printing out each in turn.
     */
    public function listOrders($parameters = []) {
        $orders = [];
        do {
            $resp = $this->requestService->orders->listOrders($this->merchantId, $parameters);
            if (empty($resp->getResources())) {
                // No Orders
                return false;
            }
            foreach ($resp->getResources() as $order) {
                $orders[] = $order;
//                $this->printOrder($order);
            }
            $parameters['pageToken'] = $resp->getNextPageToken();
        } while (!empty($parameters['pageToken']));
        return $orders;
    }

    /**
     * Acknowledges order {@code $orderId}, which allows us to filter it out
     * when looking for new orders.
     *
     * @param string $orderId the order ID of the order to acknowledge
     * @return reponse from Google
     */
    public function acknowledge($orderId) {
        $req = new \Google_Service_ShoppingContent_OrdersAcknowledgeRequest();

        $req->setOperationId($this->newOperationId());
        $resp = $this->requestService->orders->acknowledge($this->merchantId, $orderId, $req);

        return $resp;
    }

    /**
     * Retrieves the order information for the (Google-supplied) {@code $orderId}.
     *
     * @param string $orderId the order ID of the order to retrieve
     * @return Google_Service_ShoppingContent_Order
     */
    public function getOrder($orderId) {
//        printf('Retrieving order %s... ', $orderId);
        $order = $this->requestService->orders->get($this->merchantId, $orderId);
//        print "done.\n\n";
        return $order;
    }
    /**
     * Retrieves the order information for the (merchant-supplied)
     * {@code $merchantOrderId}.
     *
     * @param string $merchantOrderId the merchant order ID of the order to
     *     retrieve
     * @return Google_Service_ShoppingContent_Order
     */
    public function getByMerchantOrderId($merchantOrderId) {
//        printf('Retrieving merchant order %s... ', $merchantOrderId);
        $resp = $this->requestService->orders->getbymerchantorderid($this->merchantId, $merchantOrderId);
//        print "done.\n\n";
        return $resp->getOrder();
    }

    /**
     * Updates the merchant order ID for {@code $orderId}.
     *
     * @param string $orderId the order ID of the order to update
     * @param string $merchantOrderId the new merchant order ID of the order
     *
     * @return Response from google
     */
    public function updateMerchantOrderId($orderId, $merchantOrderId) {
//        printf("Updating merchant order ID to %s... ", $merchantOrderId);
        $req = new \Google_Service_ShoppingContent_OrdersUpdateMerchantOrderIdRequest();
        $req->setOperationId($this->newOperationId());
        $req->setMerchantOrderId($merchantOrderId);
        $resp = $this->requestService->orders->updatemerchantorderid( $this->merchantId, $orderId, $req);
       return $resp;
//        printf("done (%s).\n", $resp->getExecutionStatus());
//        print "\n";
    }

    /**
     * Cancels a line item from the order {@code $orderId}.
     *
     * @param string $orderId the order ID of the order to update
     * @param string $lineItemId the ID of the line item to cancel
     * @param int $quantity amount of the line item to cancel
     * @param string $reason a value from a Google-defined enum (see docs)
     * @param string $reasonText free-form text explaining the cancellation
     *
     * @return response from Google
     * @see https://developers.google.com/shopping-content/v2/reference/v2/orders/cancellineitem
     */
    public function cancelLineItem($orderId, $lineItemId, $quantity, $reason, $reasonText) {
//        printf("Cancelling %d of item %s... ", $quantity, $lineItemId);
        $req = new \Google_Service_ShoppingContent_OrdersCancelLineItemRequest();
        $req->setLineItemId($lineItemId);
        $req->setQuantity($quantity);
        $req->setReason($reason);
        $req->setReasonText($reasonText);
        $req->operationId = $this->newOperationId();
        $resp = $this->requestService->orders->cancellineitem($this->merchantId, $orderId, $req);
        return $resp;
//        printf("done (%s).\n", $resp->getExecutionStatus());
//        print "\n";
    }

    /**
     * Cancels a line item from the order {@code $orderId}.
     *
     * @param string $orderId the order ID of the order to update
     * @param string $reason a value from a Google-defined enum (see docs)
     * @param string $reasonText free-form text explaining the cancellation
     *
     * @return response from Google
     * @see https://developers.google.com/shopping-content/v2/reference/v2/orders/cancellineitem
     */
    public function cancelOrder($orderId, $reason, $reasonText){
        $req = new \Google_Service_ShoppingContent_OrdersCancelRequest();
        $req->setReason($reason);
        $req->setReasonText($reasonText);
        $req->operationId = $this->newOperationId();
        $resp = $this->requestService->orders->cancel($this->merchantId, $orderId, $req);

        return $resp;
    }


    /**
     * Marks a line item from the order {@code $orderId} as having shipped.
     * This method uses the pending quantity of the item.  It returns the
     * shipping request so we can access the randomly-generated tracking and
     * shipping IDs.
     *
     * @param string $orderId the order ID of the order to update
     * @param string $carrier of the order
     * @param string $shipmentId merchant shipment id
     * @param array $lineitems contain all lineitem ids and qty
     * @return Google_Service_ShoppingContent_OrdersShipLineItemsResponse
     */
    public function shipLineItemAll($orderId, $carrier, $shipmentId, $tracingId,$products=[]) {

        $req = new \Google_Service_ShoppingContent_OrdersShipLineItemsRequest();
        $item = new \Google_Service_ShoppingContent_OrderShipmentLineItemShipment();
        $items = [];
        foreach ($products as $product){
            $item->setProductId($product['product_id']);
            $item->setQuantity($product['qty']);
            $items[] = $item;
        }
        $req->setLineItems($items);

        $shipping_info = new \Google_Service_ShoppingContent_OrdersCustomBatchRequestEntryShipLineItemsShipmentInfo();
        $shipping_info->setCarrier(strtoupper($carrier));
        $shipping_info->setShipmentId($shipmentId);
        $shipping_info->setTrackingId($tracingId);

        $req->setShipmentInfos([$shipping_info]);

        $req->operationId = $this->newOperationId();
        $resp = $this->requestService->orders->shiplineitems($this->merchantId, $orderId, $req);

        return $resp;
    }

    /**
     * Operation IDs (even across operations) must be unique over the lifetime
     * of an order, so that Google can detect and reject duplicate requests.
     * Since we're sending requests sequentially and not retrying, we just use
     * a simple nonce that's incremented each time.
     */
    private function newOperationId() {
        return strval($this->nonce++);
    }
}