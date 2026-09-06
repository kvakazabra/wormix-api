<?php

namespace App\Http\Middleware;

use App\Modules\UserSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next) : Response
    {
        //Token format {user_id}.{session_key}
        $authKey = $request->header('X-SESSION-KEY');

        if (!$authKey)
        {
            return $this->error();
        }

        $authKey = explode('.', $authKey);

        if (count($authKey) !== 2)
        {
            return $this->error();
        }

        try
        {
            $session = new UserSession((int)$authKey[0]);
            if ($session->getSessionKey() !== $authKey[1])
            {
                return $this->error();
            }
        }
        catch (\Exception)
        {
            return $this->error();
        }

        $request->json()->add(['internal_user_id' => (int)$authKey[0]]);
        return $next($request);
    }

    public function error()
    {
        return \response([
            'message' => 'Access Denied'
        ], 403);
    }
}