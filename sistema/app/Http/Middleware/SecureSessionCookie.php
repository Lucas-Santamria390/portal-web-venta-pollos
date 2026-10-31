<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureSessionCookie
{
    /**
     * Marca la cookie de sesión como Secure cuando la petición llega por HTTPS.
     *
     * Se ejecuta después de TrustProxies, así que reconoce el esquema real
     * detrás del proxy de Tailscale Funnel. En HTTP local la cookie sigue
     * funcionando para no bloquear el acceso dentro de la red del local.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isSecure()) {
            config(['session.secure' => true]);
        }

        return $next($request);
    }
}
