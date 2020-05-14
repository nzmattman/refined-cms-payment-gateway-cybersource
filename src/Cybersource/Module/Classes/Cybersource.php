<?php

namespace RefinedDigital\PaymentGateways\Cybersource\Module\Classes;

use RefinedDigital\CMS\Modules\Core\Classes\PaymentGateway;
use RefinedDigital\CMS\Modules\Core\Contracts\PaymentGatewayInterface;

use Omnipay\Omnipay;
use RefinedDigital\FormBuilder\Module\Http\Repositories\FormBuilderRepository;
use RefinedDigital\FormBuilder\Module\Http\Repositories\FormsRepository;

class Cybersource extends PaymentGateway implements PaymentGatewayInterface {

    protected $view = 'payment-gateways-cybersource::form';

    public function process($request, $form, $emailData)
    {
        $gateway = Omnipay::create('Cybersource');
        // $gateway->setProfileId();
        // $gateway->setAccessKey();
        // $gateway->setSecretKey();
        $formBuilderRepository = new FormBuilderRepository();
        $formRepo = new FormsRepository($formBuilderRepository);
        $fields = $formRepo->formatFieldsByName($request, $form);

        $card = $gateway->createCard([
            'card' => [
                'firstName' => $fields->{'First Name'},
                'lastName' => $fields->{'Last Name'},
                'billingAddress1' => $fields->Address,
                'billingCity' => $fields->{'Address 2'},
                'billingState' => $fields->State,
                'billingPostcode' => $fields->Postcode,
                'number' => '4111111111111111',
                'expiryMonth' => '12',
                'expiryYear' => '2025',
                'cvv' => '123',
            ]
        ]);

        help()->trace($card);
        exit();

        $args = [
            'amount' => $this->total,
            'currency' => $this->currency,
            'description' => $this->description,
            'email' => $fields->Email,
        ];



        $response = $gateway->purchase($args)->send();
        help()->trace($fields);
        help()->trace($gateway->getDefaultParameters());
        help()->trace($args);
        help()->trace($response);
        /*$gateway = Omnipay::create('Stripe');
        $gateway->setApiKey(env('STRIPE_SECRET'));

        $args = [
            'amount' => $this->total,
            'currency' => $this->currency,
            'token' => $request->get('stripeToken'),
            'description' => $this->description,
        ];

        if (sizeof($this->metaData)) {
            $args['metadata'] = $this->metaData;
        }

        $response = $gateway
            ->purchase($args)
            ->send();

        $transaction = $this->logTransaction($form, $emailData, $response);

        $return = new \stdClass();
        $return->success = $response->isSuccessful();
        $return->transaction = $transaction;
        $return->message = $response->getMessage();

        return $return;*/
    }
}
