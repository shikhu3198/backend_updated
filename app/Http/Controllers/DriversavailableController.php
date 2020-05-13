<?php

namespace App\Http\Controllers;

use App\DataTables\DriversDataTable;
use App\Models\User;
use App\Http\Requests\CreateDriversRequest;
use App\Http\Requests\UpdateDriversRequest;
use App\Repositories\CustomFieldRepository;
use App\Repositories\UserRepository;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Prettus\Validator\Exceptions\ValidatorException;

class DriversavailableController extends Controller
{
    /** @var  UserRepository */
    private $userRepository;

    /**
     * @var RoleRepository
     */
    
    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;

    public function __construct(UserRepository $driversRepo, CustomFieldRepository $customFieldRepo)
    {
        parent::__construct();
        $this->userRepository = $driversRepo;
        $this->customFieldRepository = $customFieldRepo;
    }

    /**
     * Display a listing of the User.
     *
     * @param UserDataTable $userDataTable
     * @return Response
     */
    public function index(DriversDataTable $driversDataTable)
    {
        return $driversDataTable->render('settings.drivers.index');
    }

    public function checkActiveOrNot(Request $request,$id)
    {
        $input = $request->all();

        $update_data = array(
            'is_active' => $input['active']
        );
        $user_id = $input['user_id'];
        
        $res = User::where('id', $user_id)->update($update_data);
        
    }
    public function create()
    {

    }
    public function store(Request $request)
    {

    }
    public function show($id)
    {

    }
    public function edit($id)
    {

    }

    public function update($id, Request $request)
    {
    }
}
