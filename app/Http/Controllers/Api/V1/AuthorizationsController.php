<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use App\Http\Requests\Api\V1\SocialAuthorizationRequest;
use App\Http\Requests\Api\V1\AuthorizationRequest;
use Auth as auth;

class AuthorizationsController extends Controller
{
    //User third party application login
    public function socialStore($type, SocialAuthorizationRequest $request){
        $driver = \Socialite::driver($type);

        try{
            if ($code = $request->code){
                $response = $driver->getAccessTokenResponse($code);
                $token = Arr::get($response, 'access_token');
            }else{
                $token = $request->access_token;

                if($type == 'weixin'){
                    $driver->setOpenId($request->openid);
                }
            }

            $oauthUser = $driver->userFromToken($token);
        }catch(\Exception $e){
            throw new AuthenticationException('Cannot get user data due to incorrect input.');
        }

        switch($type){
            case 'weixin':
                $unionid = $oauthUser->offsetExists('unionid') ? $oauthUser->offsetGet('unionid') : null;
                if($unionid){
                    $user = User::where('weixin_unionid', $unionid)->first();
                }else{
                    $user = User::where('weixin_openid', $oauthUser->getId())->first();
                }

                //If there is no user found in database, create it
                if(!$user){
                    $user = User::create([
                        'name' => $oauthUser->getNickname(),
                        'avatar' => $oauthUser->getAvatar(),
                        'weixin_openid' => $oauthUser->getId(),
                        'weixin_unionid' => $unionid,
                    ]);
                }

                break;

        }

        //get the user logined and return the access token (JWT token)
        $token = auth('api')->login($user);
        return $this->respondWithToken($token)->setStatusCode(201);
    }

    //User login
    public function store(AuthorizationRequest $request){
        $username = $request->username;

        //Check if username is am email or a phone number
        filter_var($username, FILTER_VALIDATE_EMAIL) ? $credentials['email'] = $username : $credentials['phone'] = $username;

        $credentials['password'] = $request->password;

        //We have set auth guard api driver as JWT, when we use \Auth::guard('api') to login
        //It will return a jwt token which contains expire and refresh time and user's id
        //This token is encoded in 'base64' and is not stored in server, when user revisits the
        //application, the token will be uploaded to server.
        //Auth middleware will decode it and check if it is expired
        //If it is expired but still within the refresh time. it will be refresed and
        //being given a new token to continue using without inputing credentials data,
        //Ohterwise user need to re-input the username and password to login
        if(!$token = \Auth::guard('api')->attempt($credentials)){
            throw new AuthenticationException('Username or password is incorrect.');
        }

        // return response()->json([
        //     'access_token' => $token,
        //     'token_type' => 'Bearer',
        //     'expireds_in' => \Auth::guard('api')->factory()->getTTL() * 60,
        // ])->setStatusCode(201);

        return $this->respondWithToken($token)->setStatusCode(201);
    }

    /*
    Both update and destroy method require old token being uploaded
    We can add an Authorization header to do it
    */

    //Refresh(update) login access token
    public function update(){
        $token = auth('api')->refresh();
        return $this->respondWithToken($token);
    }

    //Delete the login access token
    public function destroy(){
        auth('api')->logout();
        return response(null, 204);
    }

    //Return the access token after user successfully logining(normal and weixin login)
    protected function respondWithToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expireds_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

}
