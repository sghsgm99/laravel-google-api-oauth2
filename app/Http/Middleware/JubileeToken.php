<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Site;

class JubileeToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->input('token');
        $site = Site::where('api_jubilee_key', '=', $token)->first();
        $request->merge(array("site" => $site));

        if ( empty($site) || empty($token) ) {
            return abort(404);
        }

        return $next($request);
    }
}
