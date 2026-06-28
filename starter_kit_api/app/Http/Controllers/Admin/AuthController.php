<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\AccountDeactivatedException;
use App\Exceptions\InvalidCredentialsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Resources\Admin\AdminResource;
use App\Http\Responses\ApiResponder;
use App\Models\User;
use App\Services\Admin\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class AuthController extends Controller
{
    use ApiResponder;

    /**
     * Name of the httpOnly cookie that carries the refresh token. The SPA never
     * reads it; the browser attaches it automatically on refresh/logout calls.
     */
    private const REFRESH_COOKIE = 'refresh_token';

    /**
     * Path the refresh cookie is scoped to, so it is only ever sent to the
     * admin auth endpoints rather than every API request.
     */
    private const REFRESH_COOKIE_PATH = '/api/admin';

    /**
     * Header a native (mobile) client sends on login to opt out of the cookie
     * transport and receive the refresh token in the JSON body instead.
     */
    private const MOBILE_CLIENT_HEADER = 'X-Client';

    /**
     * Header a native client uses to present its stored refresh token on the
     * refresh/logout endpoints (it has no cookie jar).
     */
    private const REFRESH_HEADER = 'X-Refresh-Token';

    public function __construct(protected AdminAuthService $authService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $result = $this->authService->login(
                email: $data['email'],
                password: $data['password'],
                deviceName: $data['device_name'] ?? null,
            );
        } catch (InvalidCredentialsException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (AccountDeactivatedException $e) {
            return $this->error($e->getMessage(), 403);
        }

        // Mobile clients opt in via the X-Client header; browsers get the cookie.
        return $this->authResponse($result, 'Login successful.', $this->isMobileClient($request));
    }

    public function refresh(Request $request): JsonResponse
    {
        $fromCookie = $request->cookie(self::REFRESH_COOKIE);
        $refreshToken = $fromCookie ?? $this->refreshTokenFromHeaderOrBody($request);

        // Mirror the transport the client used: cookie in -> cookie out (web);
        // header/body in -> JSON body out (mobile, which has no cookie jar).
        $wantsBody = $fromCookie === null;

        try {
            $result = $this->authService->refresh($refreshToken);
        } catch (InvalidCredentialsException $e) {
            return $this->error($e->getMessage(), 401)
                ->withCookie($this->forgetRefreshCookie());
        } catch (AccountDeactivatedException $e) {
            return $this->error($e->getMessage(), 403)
                ->withCookie($this->forgetRefreshCookie());
        }

        return $this->authResponse($result, 'Token refreshed.', $wantsBody);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout(
            user: $request->user(),
            token: $request->user()->currentAccessToken(),
            refreshPlainText: $request->cookie(self::REFRESH_COOKIE)
                ?? $this->refreshTokenFromHeaderOrBody($request),
        );

        return $this->success(message: 'Logged out.')
            ->withCookie($this->forgetRefreshCookie());
    }

    /**
     * Build the success envelope shared by login & refresh. The access token is
     * always in the JSON body. The refresh token is delivered either as an
     * httpOnly cookie (browsers) or in the JSON body (native apps), per $wantsBody.
     *
     * @param  array{access_token: string, refresh_token: string, user: User}  $result
     */
    private function authResponse(array $result, string $message, bool $wantsBody): JsonResponse
    {
        $data = [
            'access_token' => $result['access_token'],
            'admin' => AdminResource::make($result['user']),
        ];

        if ($wantsBody) {
            $data['refresh_token'] = $result['refresh_token'];

            return $this->success(data: $data, message: $message);
        }

        return $this->success(data: $data, message: $message)
            ->withCookie($this->makeRefreshCookie($result['refresh_token']));
    }

    private function isMobileClient(Request $request): bool
    {
        return strtolower((string) $request->header(self::MOBILE_CLIENT_HEADER)) === 'mobile';
    }

    /**
     * Pull a native client's refresh token from the X-Refresh-Token header,
     * falling back to a refresh_token field in the request body.
     */
    private function refreshTokenFromHeaderOrBody(Request $request): ?string
    {
        $token = $request->header(self::REFRESH_HEADER) ?? $request->input('refresh_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    private function makeRefreshCookie(string $value): Cookie
    {
        return cookie(
            name: self::REFRESH_COOKIE,
            value: $value,
            minutes: (int) config('sanctum.refresh_token_expiration'),
            path: self::REFRESH_COOKIE_PATH,
            domain: null,
            secure: ! app()->environment('local', 'testing'),
            httpOnly: true,
            raw: false,
            sameSite: 'strict',
        );
    }

    private function forgetRefreshCookie(): Cookie
    {
        return cookie()->forget(self::REFRESH_COOKIE, self::REFRESH_COOKIE_PATH);
    }
}
