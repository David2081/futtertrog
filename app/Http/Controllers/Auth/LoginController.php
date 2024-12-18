<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GitlabProvider;
use SocialiteProviders\Authentik\Provider;

dump("in Login");

/**
 * @OA\Post(
 *      path="/api/login",
 *      summary="Sign in",
 *      description="Login by email, password",
 *      operationId="login",
 *      @OA\RequestBody(
 *          required=true,
 *          description="Pass user credentials",
 *          @OA\JsonContent(
 *              title="LoginRequest",
 *              required={"email","password"},
 *              @OA\Property(property="email", type="string", format="email", example="user1@mail.com"),
 *              @OA\Property(property="password", type="string", format="password", example="PassWord12345"),
 *          ),
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Success",
 *          @OA\JsonContent(
 *              title="LoginResponse",
 *              @OA\Property(property="user", type="object", ref="#/components/schemas/User"),
 *          )
 *      ),
 *      @OA\Response(
 *          response=422,
 *          description="Validation error",
 *          @OA\JsonContent(
 *             title="LoginValidationResponse",
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                property="errors",
 *                type="object",
 *                @OA\Property(
 *                   property="email",
 *                   type="array",
 *                   @OA\Items(
 *                      type="string",
 *                      example={"The email field is required.","The email must be a valid email address."},
 *                   )
 *                )
 *             )
 *          )
 *       ),
 *      @OA\Response( response="default", ref="#/components/responses/Default" ),
 * ),
 *
 * @OA\Post(
 *      path="/api/logout",
 *      summary="Logout",
 *      description="Logout user",
 *      operationId="logout",
 *      security={ {"bearer": {} }},
 *
 *      @OA\Response(
 *         response=200,
 *         description="Success"
 *      ),
 *
 *      @OA\Response(
 *         response=401,
 *         description="Returns when user is not authenticated",
 *         @OA\JsonContent(
 *            @OA\Property(property="message", type="string", example="Not authorized"),
 *         )
 *      )
 * )
 */
class LoginController extends Controller implements HasMiddleware
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected string $redirectTo = '/dashboard';

    public static function middleware(): array
    {
        return [
            new Middleware('guest', except: ['logout']),
        ];
    }

    /**
     * Obtain the user information from GitLab.
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function handleGitlabCallback(Request $request, Session $session): JsonResponse|RedirectResponse
    {
        /** @var GitlabProvider $gitlabProvider */
        $gitlabProvider = Socialite::driver('gitlab');

        $gitlabUser = $gitlabProvider->user();

        /** @var User $user */
        $user = User::withTrashed()->firstOrNew(
            [
                'email' => $gitlabUser->getEmail(),
            ],
            [
                'name' => $gitlabUser->getName(),
                'password' => Hash::make($gitlabUser->getId()),
            ]
        );

        abort_if($user->deleted_at !== null, Response::HTTP_UNAUTHORIZED);

        $user->save();

        Auth::login($user, $session->pull('remember_gitlab'));

        return $this->sendLoginResponse($request);
    }

    /**
     * Redirect the user to the GitLab authentication page.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToGitlab(Request $request, Session $session): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $session->put('remember_gitlab', $request->filled('remember'));

        /** @var GitlabProvider $gitlabProvider */
        $gitlabProvider = Socialite::driver('gitlab');

        return $gitlabProvider->redirect();
    }

    /**
     * Obtain the user information from Authentik.
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function handleAuthentikCallback(Request $request, Session $session): JsonResponse|RedirectResponse
    {
        /** @var GitlabProvider $gitlabProvider */
        $gitlabProvider = Socialite::driver('authentik');

        dump($gitlabProvider); // user = null
        dump($gitlabProvider->getBaseUrl());
        //dump($gitlabProvider->getTokenFields("123"));
        dump($gitlabProvider->getTokenFields("123"));

        abort_if(true, Response::HTTP_UNAUTHORIZED);


//         $gitlabUser = $gitlabProvider->user();

//         /** @var User $user */
//         $user = User::withTrashed()->firstOrNew(
//             [
//                 'email' => $gitlabUser->getEmail(),
//             ],
//             [
//                 'name' => $gitlabUser->getName(),
//                 'password' => Hash::make($gitlabUser->getId()),
//             ]
//         );

//         abort_if($user->deleted_at !== null, Response::HTTP_UNAUTHORIZED);

// //        $user->save();

//         Auth::login($user, $session->pull('remember_gitlab'));

//         return $this->sendLoginResponse($request);
    }

    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToAuthentik(Request $request, Session $session): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $session->put('remember_gitlab', $request->filled('remember'));

        /** @var GitlabProvider $gitlabProvider */
        $gitlabProvider = Socialite::driver('authentik');

        return $gitlabProvider->redirect();
    }


    /**
     * Send the response after the user was authenticated.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    protected function sendLoginResponse(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return $this->guard()->user();
        }

        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        if ($response = $this->authenticated($request, $this->guard()->user())) {
            return $response;
        }

        return redirect()->intended($this->redirectPath());
    }

    /**
     * Validate the user login request.
     *
     * @param Request $request
     * @return void
     */
    protected function validateLogin(Request $request): void
    {
        $request->validate(
            [
                $this->username() => 'required|string',
                'password' => 'required',
            ]
        );
    }

    protected function loggedOut(Request $request): RedirectResponse
    {
        return redirect()->route('login');
    }
}
