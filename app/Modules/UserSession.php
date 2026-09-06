<?php

namespace App\Modules;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserSession
{
    private User $user;

    private array $userSession;

    /**
     * @throws \Exception
     */
    public function __construct(int $id)
    {
        $user = User::query()->where('id', $id)->first();
        if ($user == null)
        {
            throw new \Exception("Can't find user with id {$id}");
        }

        $this->user = $user;

        $session = null;
        try
        {
            $session = Cache::get('user_session_' . $user->id);
        }
        catch (\Exception $ex)
        {
            Log::error("Session error: {$ex->getMessage()}");
            Cache::delete('user_session_' . $user->id);
        }
        if ($session !== null)
        {
            $session = json_decode($session, true);
            if (($session['id'] ?? null) === null ||
                ($session['auth_key'] ?? null) === null ||
                ($session['session_key'] ?? null) === null ||
                ($session['tcp_session'] ?? null) === null)
            {
                $this->userSession = $this->blankSession();
            }
            else
            {
                $this->userSession = $session;
            }
        }
        else
        {
            $this->userSession = $this->blankSession();
        }
    }

    public function setAuthKey() : string
    {
        $this->userSession['logged_in'] = false;
        $this->userSession['auth_key'] = hash("sha256", $this->user->id . openssl_random_pseudo_bytes(32));
        $this->userSession['session_key'] = '';
        Cache::set('user_session_' . $this->user->id, json_encode($this->userSession));
        return $this->userSession['auth_key'];
    }

    public function getAuthKey() : string
    {
        return $this->userSession['auth_key'];
    }

    public function setSessionKey() : string
    {
        $this->userSession['session_key'] = hash("sha256", $this->userSession['auth_key'] . openssl_random_pseudo_bytes(32));
        Cache::set('user_session_' . $this->user->id, json_encode($this->userSession));
        return $this->userSession['session_key'];
    }

    public function getSessionKey() : string
    {
        return $this->userSession['session_key'];
    }

    public function loggedIn() : void
    {
        $this->userSession['logged_in'] = true;
        Cache::set('user_session_' . $this->user->id, json_encode($this->userSession));
    }

    public function getSessionUser() : User
    {
        return $this->user;
    }

    public function isLoggedIn() : bool
    {
        return (bool)$this->userSession['logged_in'];
    }

    private function blankSession() : array
    {
        return [
            'id' => $this->user->id,
            'auth_key' => '',
            'session_key' => '',
            'tcp_session' => '',
            'logged_in' => false,
        ];
    }

    public function setTcpSession(string $sessionId) : void
    {
        $this->userSession['tcp_session'] = $sessionId;
        Cache::set('user_session_' . $this->user->id, json_encode($this->userSession));
    }

    public function getTcpSession() : string
    {
        return $this->userSession['tcp_session'];
    }
}