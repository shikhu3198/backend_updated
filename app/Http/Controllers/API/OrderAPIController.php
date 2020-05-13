<?php

namespace App\Http\Controllers\API;

use App\Criteria\Users\DriversCriteria; 
    
use App\Criteria\Users\DriversOfRestaurantCriteria;
use App\Events\OrderChangedEvent;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\NewOrder;
use App\Notifications\StatusChangedOrder;
use App\Notifications\AssignedOrder;
use App\Repositories\CartRepository;
use App\Repositories\FoodOrderRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\UserRepository;
use Braintree\Gateway;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;
use Stripe\Stripe;
use Stripe\Token;
// use App\Models\Notification;
use App\Models\DeliveryAddress;
use App\Repositories\DeliveryAddressRepository;
use Twilio\Rest\Client; 
use App\Models\User;    
use App\Repositories\CustomFieldRepository; 

/**
 * Class OrderController
 * @package App\Http\Controllers\API
 */
class OrderAPIController extends Controller
{
    /** @var  OrderRepository */
    private $orderRepository;
    /** @var  FoodOrderRepository */
    private $foodOrderRepository;
    /** @var  CartRepository */
    private $cartRepository;
    /** @var  UserRepository */
    private $userRepository;
    /** @var  PaymentRepository */
    private $paymentRepository;
    /** @var  NotificationRepository */
    private $notificationRepository;

    private $deliveryAddressRepository;

    public function __construct(OrderRepository $orderRepo, FoodOrderRepository $foodOrderRepository, CartRepository $cartRepo, PaymentRepository $paymentRepo, NotificationRepository $notificationRepo, UserRepository $userRepository, DeliveryAddressRepository $deliveryAddressRepo)
    {
        $this->orderRepository = $orderRepo;
        $this->foodOrderRepository = $foodOrderRepository;
        $this->cartRepository = $cartRepo;
        $this->userRepository = $userRepository;
        $this->paymentRepository = $paymentRepo;
        $this->notificationRepository = $notificationRepo;
        $this->deliveryAddressRepository = $deliveryAddressRepo;
    }

    /**
     * Display a listing of the Order.
     * GET|HEAD /orders
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        Log::error("get orders");
        try {
            $this->orderRepository->pushCriteria(new RequestCriteria($request));
            $this->orderRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            Flash::error($e->getMessage());
        }
        $orders = $this->orderRepository->all();

        return $this->sendResponse($orders->toArray(), 'Orders retrieved successfully');
    }

    /**
     * Display the specified Order.
     * GET|HEAD /orders/{id}
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        /** @var Order $order */
        if (!empty($this->orderRepository)) {
            try {
                $this->orderRepository->pushCriteria(new RequestCriteria($request));
                $this->orderRepository->pushCriteria(new LimitOffsetCriteria($request));
            } catch (RepositoryException $e) {
                Flash::error($e->getMessage());
            }
            $order = $this->orderRepository->findWithoutFail($id);
        }

        if (empty($order)) {
            return $this->sendError('Order not found');
        }

        return $this->sendResponse($order->toArray(), 'Order retrieved successfully');


    }

    /**
     * Store a newly created Order in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $payment = $request->only('payment');
        if (isset($payment['payment']) && $payment['payment']['method']) {
            if ($payment['payment']['method'] == "Credit Card (Stripe Gateway)") {
                return $this->stripPayment($request);
            } else {
                return $this->cashPayment($request);

            }
        }
    }

    private function stripPayment(Request $request)
    {
        $input = $request->all();
        $amount = 0;
        try {
            $user = $this->userRepository->findWithoutFail($input['user_id']);
            if (empty($user)) {
                return $this->sendError('User not found');
            }
            $stripeToken = Token::create(array(
                "card" => array(
                    "number" => $input['stripe_number'],
                    "exp_month" => $input['stripe_exp_month'],
                    "exp_year" => $input['stripe_exp_year'],
                    "cvc" => $input['stripe_cvc'],
                    "name" => $user->name,
                )
            ));
            if ($stripeToken->created > 0) {
                $order = $this->orderRepository->create(
                    $request->only('user_id', 'order_status_id', 'tax', 'delivery_address_id','delivery_fee')
                );
                foreach ($input['foods'] as $foodOrder) {
                    $foodOrder['order_id'] = $order->id;
                    $amount += $foodOrder['price'] * $foodOrder['quantity'];
                    $this->foodOrderRepository->create($foodOrder);
                }
                $amountWithTax = $amount + ($amount * $order->tax / 100);
                $charge = $user->charge((int)($amountWithTax * 100), ['source' => $stripeToken]);
                $payment = $this->paymentRepository->create([
                    "user_id" => $input['user_id'],
                    "description" => trans("lang.payment_order_done"),
                    "price" => $amountWithTax,
                    "status" => $charge->status, // $charge->status
                    "method" => $input['payment']['method'],
                ]);
                $this->orderRepository->update(['payment_id' => $payment->id], $order->id);

                $this->cartRepository->deleteWhere(['user_id' => $order->user_id]);

                Notification::send($order->foodOrders[0]->food->restaurant->users, new NewOrder($order));

                 $updatedData  = (object) array("is_new_order"=> 1);
                try {
                    file_put_contents("order.json", json_encode($updatedData));
                } catch(\ErrorException $e) {
                        
                }
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($order->toArray(), __('lang.saved_successfully', ['operator' => __('lang.order')]));
    }

    private function cashPayment(Request $request)
    {
        $input = $request->all();
        $input_foods = $input['foods'];

        $amount = 0;
        try {

            $user = $this->userRepository->findWithoutFail($input['user_id']);
            if (empty($user)) {
                return $this->sendError('Order not found');
            }
                
            if($request->input('delivery_address_id') == '0') {

                  $delivery_address['description']  =  "Current Location";
                  $delivery_address['address'] = $request->address;
                  $delivery_address['latitude'] = $request->latitude;
                  $delivery_address['longitude'] = $request->longitude;
                  $delivery_address['user_id'] = $request->user_id;
                  $delivery_address['is_default'] = 0;
                  $delivery_address['is_display'] = 0;
                  $address_id = $this->deliveryAddressRepository->create($delivery_address);
                  $request['delivery_address_id'] = $address_id->id;
            }

            $order = $this->orderRepository->create(
                $request->only('user_id', 'order_status_id', 'tax', 'delivery_address_id','delivery_fee')
            );
            foreach ($input['foods'] as $foodOrder) {
                $foodOrder['order_id'] = $order->id;
                $amount += $foodOrder['price'] * $foodOrder['quantity'];
                $this->foodOrderRepository->create($foodOrder);
            }
            $amountWithTax = $amount + ($amount * $order->tax / 100);
            $payment = $this->paymentRepository->create([
                "user_id" => $input['user_id'],
                "description" => trans("lang.payment_order_waiting"),
                "price" => $amountWithTax,
                "status" => 'Waiting for Client',
                "method" => $input['payment']['method'],
            ]);

            $this->orderRepository->update(['payment_id' => $payment->id], $order->id);

            /* Add Driver ID 31/03/2020 */  
                    
                $getOrder = $this->orderRepository->findWithoutFail($order->id);    
                $getrestaurantid = $getOrder->foodOrders()->first();    
                $getrestaurantid = isset($getrestaurantid) ? $getrestaurantid->food['restaurant_id'] : 0;   
                $drivers = $this->userRepository->getByCriteria(new DriversOfRestaurantCriteria($getrestaurantid))->pluck('name', 'id')->toArray(); 
                    
                if(count($drivers) > 0)
                {    
                foreach ($drivers as $key => $value) { 

                    $orders = Order::where('order_status_id', '!=' , '5')->where('driver_id',$key)->get(); 
                    
                    $count[$key] = count($orders);  
                }   
                $min_driver_id = array_keys($count,min($count));    
                $this->orderRepository->update(['driver_id'=>implode(',', $min_driver_id)],$order->id); 
                    
                if ($min_driver_id != '' && isset($min_driver_id)) {    
                    $order_data = $this->orderRepository->find($order->id); 
                    $user = $this->userRepository->find($order_data->driver_id,['device_token']);   
                        
                    // if (setting('enable_notifications',false)){ 
                    //     sendNotification($user['device_token'], 
                    //         __('lang.notification_order_assigned_title'),   
                    //         __('lang.notification_order_assigned_description',['order_id'=>$order->id]));   
                    // }   
                    // $this->notificationRepository->create([ 
                    //     "title" => trans("lang.notification_order_assigned_description", ['order_id' => $order->id]),   
                    //     "user_id" => $order_data->driver_id,    
                    //     "notification_type_id" => 2,    
                    // ]); 

                    if (setting('enable_notifications', false)) {
                            
                            Notification::send([$order->user], new StatusChangedOrder($order));
                            
                            $driver = $this->userRepository->findWithoutFail($order_data->driver_id);

                            if (!empty($driver)) {
                                Notification::send([$driver], new AssignedOrder($order));
                            }
                        
                        }
                    }

                }                       
                /* end code */

                $updatedData  = (object) array("is_new_order"=> 1);
                try {
                    file_put_contents("order.json", json_encode($updatedData));
                } catch(\ErrorException $e) {
                    
                }   
            $this->cartRepository->deleteWhere(['user_id' => $order->user_id]);

            Notification::send($order->foodOrders[0]->food->restaurant->users, new NewOrder($order));

            //23-03-2020
                /*$phoneData = Order::with("user")->with("orderStatus")->with('payment')
                ->join("food_orders", "orders.id", "=", "food_orders.order_id")
                ->join("foods", "foods.id", "=", "food_orders.food_id")
                ->join("restaurants", "restaurants.id", "=", "foods.restaurant_id");
                foreach ($input['foods'] as $key => $value) {
                    $phoneData = $phoneData->where('foods.id', $value['food_id']);
                }
                $phoneData = $phoneData->groupBy('restaurants.id')
                            ->select('restaurants.phone')->get();
                
                $data = array('users' => $phoneData[0]['phone'],'body' => "מזל טוב, יש לך הזמנה חדשה!
ניתן לצפות בפרטי ההזמנה בטאבלט שברשותך.
דייני הזמנות");*/

                $foodId = explode(',', $input_foods[0]['food_id']);
         
                $phoneData = Order::select('custom_field_values.value as phone')
                    ->join("food_orders", "orders.id", "=", "food_orders.order_id")
                    ->join("foods", "foods.id", "=", "food_orders.food_id")
                    ->join("restaurants", "restaurants.id", "=", "foods.restaurant_id")
                    ->join("user_restaurants", "user_restaurants.restaurant_id", "=", "restaurants.id")
                    ->join("users", "users.id", "=", "user_restaurants.user_id")
                    ->join("custom_field_values", "custom_field_values.customizable_id", "=", "users.id")
                    ->join("custom_fields", "custom_fields.id", "=", "custom_field_values.custom_field_id")
                    ->whereIn('foods.id',$foodId)
                    ->groupBy('restaurants.id')
                    ->get()->toArray();

                foreach ($phoneData as $key => $value) {
                    $phoneNumer = $value['phone'];

                    $data = array('users' => $phoneNumer,'body' => "מזל טוב, יש לך הזמנה חדשה!
        ניתן לצפות בפרטי ההזמנה בטאבלט שברשותך.
        דייני הזמנות");
                
                    // $msg_status = $this->sendCustomMessage($data);
                    
                    // if($msg_status == true){
                    //     $order->msg_status = "sent";
                    // }else{
                    //     $order->msg_status = "failed";
                    // }
                }
            
                //23-03-2020
                $updatedData  = (object) array("is_new_order"=> 1);
                try {
                    file_put_contents("order.json", json_encode($updatedData));
                } catch(\ErrorException $e) {
                    
                }   
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }
        
        return $this->sendResponse($order->toArray(), __('lang.saved_successfully', ['operator' => __('lang.order')]));
    }

    /**
     * Update the specified Order in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        $order = $this->orderRepository->findWithoutFail($id);

        if (empty($order)) {
            return $this->sendError('Order not found');
        }
        $input = $request->all();

        try {
            $order = $this->orderRepository->update($input, $id);
            if ($input['order_status_id'] == 5 && !empty($order)) {
                $this->paymentRepository->update(['status' => 'Paid'], $order['payment_id']);
                event(new OrderChangedEvent($order));
            }
            Notification::send([$order->user], new StatusChangedOrder($order));

        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($order->toArray(), __('lang.saved_successfully', ['operator' => __('lang.order')]));
    }

    /** 
     * Sends sms to user using Twilio's programmable sms client 
     * @param String $message Body of sms   
     * @param Number $recipients Number of recipient    
     */ 
    private function sendMessage($message, $recipients) 
    {   
        $recipients = "+972 0547286062";    
        $account_sid = env("TWILIO_SID");   
        $auth_token = env("TWILIO_AUTH_TOKEN"); 
        $twilio_number = env("TWILIO_NUMBER");  
        $client = new Client($account_sid, $auth_token);    
        $message = $client->messages->create($recipients, ['from' => $twilio_number, 'body' => $message]);  
        return $message->status;    
    }   

    /* end code */
}
