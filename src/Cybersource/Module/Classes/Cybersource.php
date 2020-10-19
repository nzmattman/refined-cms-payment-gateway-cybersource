<?php

namespace RefinedDigital\Cybersource\Module\Classes;

use RefinedDigital\CMS\Modules\Core\Classes\PaymentGateway;
use RefinedDigital\CMS\Modules\Core\Contracts\PaymentGatewayInterface;

use RefinedDigital\FormBuilder\Module\Http\Repositories\FormBuilderRepository;
use RefinedDigital\FormBuilder\Module\Http\Repositories\FormsRepository;

/**
 * Class Cybersource
 * @package RefinedDigital\PaymentGateways\Cybersource\Module\Classes
 *
 *
 * // test end points
 * wsdl = "https://ics2wstest.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.120.wsdl"
 * nvp_wsdl = "https://ics2wstest.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_NVP_1.120.wsdl"
 *
 * // live end points
 * wsdl = "https://ics2ws.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.120.wsdl"
 * nvp_wsdl = "https://ics2ws.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_NVP_1.120.wsdl"
 */

class Cybersource extends PaymentGateway implements PaymentGatewayInterface {

  protected $view = 'payment-gateways-cybersource::form';

  public function process($request, $form, $emailData)
  {
    // todo: update to use Omnipay Cybersource

    $transaction = $this->logTransaction($form, $emailData, null);

    $success = false;
    $message = '';

    try {
      // cybresource code
      $properties = parse_ini_file('cybs.ini');
      $properties['merchant_id'] = env('CYBERSOURCE_MERCHANT');
      $properties['transaction_key'] = env('CYBERSOURCE_KEY');
      $properties['wsdl'] = env('CYBERSOURCE_WSDL', 'https://ics2ws.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.120.wsdl');
      $properties['nvp_wsdl'] = env('CYBERSOURCE_NVP_WSDL', 'https://ics2ws.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_NVP_1.120.wsdl');
      $client = new \CybsClient([], $properties);
      $cyber = $this->createRequest($transaction->id, $properties['merchant_id']);

      // Build a sale request (combining an auth and capture). In this example only
      // the amount is provided for the purchase total.
      $ccAuthService = new \stdClass();
      $ccAuthService->run = 'true';
      $cyber->ccAuthService = $ccAuthService;

      $ccCaptureService = new \stdClass();
      $ccCaptureService->run = 'true';
      $cyber->ccCaptureService = $ccCaptureService;


      $formBuilderRepository = new FormBuilderRepository();
      $formRepo = new FormsRepository($formBuilderRepository);
      $fields = $formRepo->formatFieldsByName($request, $form);

      $billTo = new \stdClass();
      $billTo->firstName = $fields['First Name'];
      $billTo->lastName = $fields['Last Name'];
      $billTo->street1 = $fields['Address'];
      $billTo->city = $fields['Address 2'];
      $billTo->state = $fields['State'];
      $billTo->postalCode = $fields['Postcode'];
      $billTo->country = config('products.orders.country_code');
      $billTo->email = $fields['Email'];
      $billTo->ipAddress = help()->getClientIp();
      $cyber->billTo = $billTo;

      $card = new \stdClass();
      $card->accountNumber = $request->input('c.num');
      $card->expirationMonth = $request->input('c.expiry_month');
      $card->expirationYear = $request->input('c.expiry_year');
      $cyber->card = $card;

      $purchaseTotals = new \stdClass();
      $purchaseTotals->currency = config('products.orders.currency');
      $purchaseTotals->grandTotalAmount = $emailData->cart->totals->total;
      $cyber->purchaseTotals = $purchaseTotals;


      $response = $client->runTransaction($cyber);
      if (isset($response->decision) && $response->decision === 'ACCEPT') {
        $success = true;
        $message = 'Payment Successful';

        $transaction->response = $response;
        $transaction->transaction_id = $response->receiptNumber;
        $transaction->save();
      }

    } catch(\Exception $error) {

      $transaction->response = $error;
      $transaction->save();

      $message = $error->getMessage();
    }

    $return = new \stdClass();
    $return->success = $success;
    $return->transaction = $transaction;
    $return->message = $message;

    return $return;
  }

  /*
  * Taken from CybsSoapClient from Cybersource SKD
  */
  private function createRequest($merchantReferenceCode, $merchantId)
  {
    $request = new \stdClass();
    $request->merchantID = $merchantId;
    $request->merchantReferenceCode = $merchantReferenceCode;
    $request->clientLibrary = \CybsClient::CLIENT_LIBRARY_VERSION;
    $request->clientLibraryVersion = phpversion();
    $request->clientEnvironment = php_uname();
    return $request;
  }
}
