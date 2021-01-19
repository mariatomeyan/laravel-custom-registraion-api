<?php

namespace App\Http\Controllers;


use App\Jobs\ProcessDemoPaymentStoring;
use Illuminate\Support\Facades\Http;
use App\Models\Account;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class RegisterController extends Controller
{
    private $user;

    public function __construct()
    {
    }

    public function store(Request $request)
    {
        try {
            $this->user = User::firstOrCreate(
                [
                    'firstname' => $request->firstname,
                    'lastname'  => $request->lastname,
                    'telephone' => $request->telephone
                ]
            );

           $this->attachAddressToUser(collect($request->address));
           $account = $this->storeAccount(collect($request->account));
           $response = $this->storeDemoPaymentOnRemoteServer($account);

           if($response->successful()) {

               $payment_data_id =  collect($response->json())->get('paymentDataId');
               $this->updateAccountsPaymentDataId($account,  $payment_data_id);

               return response()->json(['message' => 'You have been registered successfully!', 'paymentDataId' => $payment_data_id]);

           } elseif ($response->failed()) {
               return response()->json(['message' => 'Oops,  something went wrong!', $response->json()]);
           }

        } catch (\Exception $exception) {
            return response()->json([ 'success'=> false, 'message' => $exception->getMessage(), 'code' => $exception->getCode()]);
        }
    }

    private function updateAccountsPaymentDataId(Account $account, $dataId)
    {
        $account->payment_data_id = $dataId;
        return $account->save();
    }

    private function storeDemoPaymentOnRemoteServer(Account $account)
    {
        $wrapped_account = new \App\Http\Resources\Account($account);
        return Http::post(env('DEMO_STORAGE_API'), collect($wrapped_account)->toArray() );
    }


    private function storeAccount($data)
    {
        return Account::firstOrCreate(
            [
                'iban' => $data->get('iban')
            ],
            [
                'iban' => $data->get('iban'),
                'owner' =>  $data->get('owner'),
                'customer_id' => $this->user->id
            ]
        );

    }

    private function attachAddressToUser($data)
    {
        $address = $this->storeAddress($data);
        $this->user->addresses()->attach($address);
    }

    private function storeAddress($data)
    {
      return Address::firstOrCreate(
            [
                'zip_code' => $data->get('zip_code'),
                'city' => $data->get('city'),
                'house_number' => $data->get('house_number')
            ],
            [
                'zip_code' => $data->get('zip_code'),
                'street' => $data->get('street'),
                'city' => $data->get('city'),
                'house_number' => $data->get('house_number')
            ]);
    }
}
