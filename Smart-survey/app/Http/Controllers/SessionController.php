<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    /**
     * Show the session management page.
     */
    public function index(Request $request)
    {
        $currentSessionId = $request->session()->getId();

        $sessions = DB::table('sessions')
            ->where('user_id', Auth::id())
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) use ($currentSessionId) {
                $payload = @unserialize(base64_decode($session->payload));

                return (object) [
                    'id'              => $session->id,
                    'ip_address'      => $session->ip_address,
                    'user_agent'      => $session->user_agent,
                    'last_activity'   => $session->last_activity,
                    'last_active_at'  => \Carbon\Carbon::createFromTimestamp($session->last_activity),
                    'is_current'      => $session->id === $currentSessionId,
                    'device'          => $this->parseDevice($session->user_agent),
                    'browser'         => $this->parseBrowser($session->user_agent),
                    'os'              => $this->parseOS($session->user_agent),
                ];
            });

        return view('sessions.index', compact('sessions', 'currentSessionId'));
    }

    /**
     * Revoke a specific session by ID.
     */
    public function destroy(Request $request, $sessionId)
    {
        // Prevent user from deleting another user's session
        $session = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$session) {
            abort(403, 'Unauthorized');
        }

        DB::table('sessions')->where('id', $sessionId)->delete();

        // If destroying current session, logout
        if ($sessionId === $request->session()->getId()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('success', 'You have been logged out.');
        }

        return back()->with('success', 'Session has been revoked successfully.');
    }

    /**
     * Revoke all other sessions (keep current session).
     */
    public function destroyOthers(Request $request)
    {
        $currentSessionId = $request->session()->getId();

        DB::table('sessions')
            ->where('user_id', Auth::id())
            ->where('id', '!=', $currentSessionId)
            ->delete();

        return back()->with('success', 'All other sessions have been logged out successfully.');
    }

    // =========================================================
    // Helpers — Parse User Agent
    // =========================================================

    private function parseDevice(?string $ua): string
    {
        if (!$ua) return 'Unknown Device';

        if (preg_match('/Mobile|Android|iPhone|iPad/i', $ua)) {
            if (preg_match('/iPad/i', $ua)) return 'Tablet';
            return 'Mobile';
        }
        return 'Desktop';
    }

    private function parseBrowser(?string $ua): string
    {
        if (!$ua) return 'Unknown';
        if (str_contains($ua, 'Edg'))    return 'Microsoft Edge';
        if (str_contains($ua, 'Chrome') && !str_contains($ua, 'Chromium')) return 'Google Chrome';
        if (str_contains($ua, 'Firefox')) return 'Mozilla Firefox';
        if (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) return 'Safari';
        if (str_contains($ua, 'Opera') || str_contains($ua, 'OPR')) return 'Opera';
        if (str_contains($ua, 'MSIE') || str_contains($ua, 'Trident')) return 'Internet Explorer';
        return 'Unknown Browser';
    }

    private function parseOS(?string $ua): string
    {
        if (!$ua) return 'Unknown';
        if (str_contains($ua, 'Windows NT 10')) return 'Windows 10/11';
        if (str_contains($ua, 'Windows'))       return 'Windows';
        if (str_contains($ua, 'Mac OS X'))      return 'macOS';
        if (str_contains($ua, 'iPhone'))        return 'iOS';
        if (str_contains($ua, 'iPad'))          return 'iPadOS';
        if (str_contains($ua, 'Android'))       return 'Android';
        if (str_contains($ua, 'Linux'))         return 'Linux';
        return 'Unknown OS';
    }
}
