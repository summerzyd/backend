<?php
namespace App\Http\Middleware;

use Closure;
use Auth;

use App\Components\Config;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $path = $request->path();
        if (!empty($path)) {
            $permissions = Config::get('permissions');
            if (!empty($permissions) && isset($permissions[$path])) {
                $result = Auth::user()->can($permissions[$path]);
                if (!$result) {
                    return response(['res' => 5003, 'msg' => '没有访问权限'], 403);
                }
            }
        }
        return $next($request);
    }
}
