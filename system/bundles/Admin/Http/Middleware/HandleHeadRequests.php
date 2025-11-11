<?php

namespace Admin\Http\Middleware;

/**
 * Die Middleware dient nur dazu um zu schauen, ob eine Resource verfügbar ist. Dadurch das bei uns alles im iframe
 * geladen wird, würde ansonsten innerhalb des Tabs wieder auf die Login-Seite weitergeleitet werden. Das hier soll die
 * letzte Stufe vor dem Content der Resource sein, d.h. bis hier hin sollten alle Access-Überprüfungen stattgefunden haben.
 * Verhindert auch dass Gui2-Sessions geschrieben werden
 */
class HandleHeadRequests
{
	public function handle($request, \Closure $next)
	{
		if ($request->isMethod('HEAD')) {
			return response('Ok', 200);
		}

		return $next($request);
	}
}