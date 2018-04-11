<?php

namespace App\Http\Controllers;

class AuthController extends Controller
{
    public function login()
    {
        session()->put('oauth_state', str_random());

        return redirect(
            'https://github.com/login/oauth/authorize' .
                '?client_id=' . config('services.github.client_id') .
                '&redirect_uri=' . urlencode(route('oauth')) .
                '&state=' . session('oauth_state')
        );
    }

    public function oauth()
    {
        if (session('oauth_state') !== request('state')) {
            abort(400, 'Invalid oauth state provided');
        }

        $request = curl_init('https://github.com/login/oauth/access_token');
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_POSTFIELDS, [
            'client_id'     => config('services.github.client_id'),
            'client_secret' => config('services.github.client_secret'),
            'code'          => request('code'),
            'redirect_uri'  => route('oauth'),
            'state'         => session('oauth_state'),
        ]);

        $response = curl_exec($request);

        curl_close($request);

        parse_str($response, $response);

        if (isset($response['error'])) {
            abort(400, $response['error_description']);
        } else {
            session()->forget('oauth_state');
            session()->put('oauth_access_token', $response['access_token']);

            return $this->redirectToApp();
        }
    }

    private function redirectToApp()
    {
        return redirect(
                'https://noeldemartin.github.io/wemake-services-test' .
                    '?access_token=' . session('oauth_access_token')
        );
    }
}
