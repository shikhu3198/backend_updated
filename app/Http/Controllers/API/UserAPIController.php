<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\CustomFieldRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UploadRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Response;
use Prettus\Validator\Exceptions\ValidatorException;

class UserAPIController extends Controller
{
    private $userRepository;
    private $uploadRepository;
    private $roleRepository;
    private $customFieldRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserRepository $userRepository, UploadRepository $uploadRepository, RoleRepository $roleRepository, CustomFieldRepository $customFieldRepo)
    {
        $this->userRepository = $userRepository;
        $this->uploadRepository = $uploadRepository;
        $this->roleRepository = $roleRepository;
        $this->customFieldRepository = $customFieldRepo;
    }

    function login(Request $request)
    {
        $otp = $request->input('isOtpVerified');
        if($otp == 'true')
        {
            $user = User::all()->where('phone', $request->input('phone'))->where('is_active','1')->first();
            
            if (!$user) {
                $data = null;
                return $this->sendResponse($data,'Phone number not registered');                           
            }
            else
            {
                return $this->sendResponse($user, 'User retrieved successfully');
            } 
        }
        Log::warning($request->input('device_token'));

        if (auth()->attempt(['email' => $request->input('email'), 'password' => $request->input('password')])) {

            $login = [
            'password' => $request->input('password')
        ];

        $phone = $request->input('phone') ?? null;
        $pass = $request->input('password') ?? null;
        $email = $request->input('email') ?? null;

        if (is_null($pass) || (is_null($email) && is_null($phone))) {
            return $this->sendResponse([
                'error' => 'Unauthenticated user',
                'code' => 401,
            ], 'User not logged');
        }

        if ((!empty($email)) && !is_null($email)) {
            $login['email'] = $request->input('email');
        } else if (!empty($phone) && !is_null($phone)) {
            $login['phone'] = $phone;
        }

        if (auth()->attempt($login)) {

                // Authentication passed...
                $user = auth()->user();
                $user->device_token = $request->input('device_token','');
                $user->save();
                return $this->sendResponse($user, 'User retrieved successfully');
            }
        }
        return $this->sendResponse([
            'error' => 'Unauthenticated user',
            'code' => 401,
        ], 'User not logged');

    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return
     */
    function register(Request $request)
    {
        $user = User::all()->where('email', $request->input('email'))->orWhere('phone', $request->input('phone'))->first();

        if ($user) {
            return $this->sendError('Account already exists.');
        } 

        $user = new User;
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->phone = $request->input('phone') ?? null;
        $user->password = Hash::make($request->input('password'));
        $user->api_token = str_random(60);
        $user->save();

        $defaultRoles = $this->roleRepository->findByField('default', '1');
        $defaultRoles = $defaultRoles->pluck('name')->toArray();
        $user->assignRole($defaultRoles);

        $user->addMediaFromUrl("https://na.ui-avatars.com/api/?name=" . str_replace(" ", "+", $user->name))
            ->withCustomProperties(['uuid' => bcrypt(str_random())])
            ->toMediaCollection('avatar');

        return $this->sendResponse($user, 'User retrieved successfully');
    }

    function logout(Request $request)
    {
        $user = $this->userRepository->findByField('api_token', $request->input('api_token'))->first();
        if (!$user) {
            return $this->sendResponse([
                'error' => true,
                'code' => 404,
            ], 'User not found');
        }
        try {
            auth()->logout();
        } catch (ValidatorException $e) {
            return $this->sendResponse([
                'error' => true,
                'code' => 404,
            ], 'User not found');
        }
        return $this->sendResponse($user['name'], 'User logout successfully');

    }

    function user(Request $request)
    {
        $user = $this->userRepository->findByField('api_token', $request->input('api_token'))->first();

        if (!$user) {
            return $this->sendResponse([
                'error' => true,
                'code' => 404,
            ], 'User not found');
        }

        return $this->sendResponse($user, 'User retrieved successfully');
    }

    public function settings(Request $request)
    {
        $settings = setting()->all();
        
        if($settings['currency_right'] == "0")
        {
            $settings['currency_right'] = false;   
        }
        else
        {
            $settings['currency_right'] = true;
        }
        
        Log::warning($settings);
        $settings = array_intersect_key($settings,
            [
                'default_tax' => '',
                'default_currency' => '',
                'app_name' => '',
                'currency_right' => '',
                'enable_paypal' => '',
                'enable_stripe' => '',
                'main_color' => '',
                'main_dark_color' => '',
                'second_color' => '',
                'second_dark_color' => '',
                'accent_color' => '',
                'accent_dark_color' => '',
                'scaffold_dark_color' => '',
                'scaffold_color' => '',
                'google_maps_key' => '',
                'mobile_language' => '',
                'app_version' => '',
                'enable_version' => '',
                'block_checkout' => '',
                'block_checkout_msg' => '',
                'town_area_lat' => '',
                'town_area_long' => '',
                'town_area_distance' => '',
                'town_area_message' => '',
                'message_close' => '',
                'announcement' => '',
            ]
        );
        Log::warning($settings);
        if (!$settings) {
            return $this->sendResponse([
                'error' => true,
                'code' => 404,
            ], 'Settings not found');
        }

        return $this->sendResponse($settings, 'Settings retrieved successfully');
    }

    /**
     * Update the specified User in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return Response
     */
    public function update($id, Request $request)
    {
        $user = $this->userRepository->findWithoutFail($id);

        if (empty($user)) {
            return $this->sendResponse([
                'error' => true,
                'code' => 404,
            ], 'User not found');
        }
        $input = $request->except(['password', 'api_token']);
        try {
            if($request->has('device_token')){
                $user = $this->userRepository->update($request->only('device_token'), $id);
            }else{
                $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->userRepository->model());
                $user = $this->userRepository->update($input, $id);

                foreach (getCustomFieldsValues($customFields, $request) as $value) {
                    $user->customFieldsValues()
                        ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
                }
            }
        } catch (ValidatorException $e) {
            return $this->sendResponse([
                'error' => true,
                'code' => 404,
            ], $e->getMessage());
        }

        return $this->sendResponse($user, __('lang.updated_successfully', ['operator' => __('lang.user')]));
    }

    function sendResetLinkEmail(Request $request)
    {
        $this->validate($request, ['email' => 'required|email']);

        $response = Password::broker()->sendResetLink(
            $request->only('email')
        );

        if ($response == Password::RESET_LINK_SENT) {
            return $this->sendResponse(true, 'Reset link was sent successfully');
        } else {
            return $this->sendError([
                'error' => 'Reset link not sent',
                'code' => 401,
            ], 'Reset link not sent');
        }

    }

    /**
     * Reset password using sms.
     *
     * @param array $data
     * @return
     */
    function smsPasswordReset(Request $request)
    {
        $phone = $request->input('phone') ?? null;
        $pass = $request->input('password') ?? null;

        if (is_null($phone) || is_null($pass)) {
            return $this->sendResponse([
                'error' => 'Bad Request',
                'code' => 400,
            ], 'Please provide phone number and password');
        }

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return $this->sendResponse([
                'error' => 'Not Found',
                'code' => 404,
            ], 'User not found');
        }

        $user->password = Hash::make($pass);
        $user->update();

        return $this->sendResponse($user, 'Your password has been reset successfully');
    }
}
