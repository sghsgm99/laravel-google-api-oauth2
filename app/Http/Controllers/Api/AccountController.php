<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAccountRequest;
use App\Http\Requests\UpdateAccountReportRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Http\Resources\UserResource;
use App\Models\Account;
use App\Models\Enums\RoleTypeEnum;
use App\Models\RoleSetupTemplate;
use App\Models\Services\UserService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class AccountController extends Controller
{
    public static function apiRoutes()
    {

        // view/add facebook page to business account
        Route::get('/accounts/{account}/facebook/add-page/{page_id}', [AccountController::class, 'addFacebookPage']);
        Route::get('/accounts/{account}/facebook/view-pages', [AccountController::class, 'viewFacebookPage']);
        Route::get('/accounts/{account}/facebook/assign-page/{page_id}', [AccountController::class, 'assignFacebookUsertoPage']);

        Route::post('accounts', [AccountController::class, 'create']);

        Route::put('accounts/{account}/report', [AccountController::class, 'updateReport']);
        Route::put('accounts/{account}', [AccountController::class, 'update']);
        Route::delete('accounts/{account}', [AccountController::class, 'delete']);
        Route::get('accounts/{account}', [AccountController::class, 'get']);
        Route::get('accounts', [AccountController::class, 'getCollection']);
        
    }

    public function getCollection()
    {
        return AccountResource::collection(Account::all());
    }

    public function get(Account $account)
    {
        return new AccountResource($account);
    }

    public function create(CreateAccountRequest $request)
    {
        $user = UserService::create(
            $request->validated()['first_name'],
            $request->validated()['last_name'],
            $request->validated()['email'],
            $request->validated()['password'],
            RoleSetupTemplate::whereRoleId(RoleTypeEnum::ADMINISTRATOR())->first(),
            $request->validated()['is_owner'],
        );

        return ResponseService::successCreate('User was created.', new UserResource($user));
    }

    public function update(UpdateAccountRequest $request, Account $account)
    {
        $account->Service()->update(
            $request->validated()['company_name'],
            $request->validated()['facebook_app_id'] ?? null,
            $request->validated()['facebook_app_secret'] ?? null,
            $request->validated()['facebook_business_manager_id'] ?? null,
            $request->validated()['facebook_access_token'] ?? null,
        );

        return ResponseService::successCreate('Account was updated.', new AccountResource($account));
    }

    public function addFacebookPage(Account $account, $page_id)
    {   
        $act = $account->Service()->addFacebookPage(
            $account,
            $page_id
        );

        return $act;
    }

    public function viewFacebookPage(Account $account)
    {
        $act = $account->Service()->viewFacebookPage(
            $account
        );

        return $act;
    }

    public function assignFacebookUsertoPage(Account $account, $page_id)
    {
        $act = $account->Service()->assignFacebookUsertoPage(
            $account,
            $page_id
        );

        return $act;
    }

    public function updateReport(UpdateAccountReportRequest $request, Account $account)
    {
        $account->Service()->updateReport($request->validated()['report_token'] ?? null);

        return ResponseService::successCreate(
            'Account was updated.',
            new AccountResource($account)
        );
    }
    
}
