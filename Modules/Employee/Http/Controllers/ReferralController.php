<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CentralReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Modules\Employee\Notifications\ReferralInviteNotification;

class ReferralController extends Controller
{
    public function __construct(protected CentralReferralService $referrals) {}

    public function index(Request $request): View
    {
        $employee = auth()->user();

        if (! $this->referrals->tablesReady()) {
            return view('employee::referrals.index', [
                'ready' => false,
                'enabled' => false,
            ]);
        }

        $referralCode = $this->referrals->codeForEmployee($employee);
        $deviceId = $this->referrals->deviceId($request);
        $deviceHash = $this->referrals->deviceHash($deviceId);

        if ($referralCode !== null) {
            $this->referrals->syncSenderDeviceHash((int) $referralCode->id, $deviceHash);
        }

        $response = view('employee::referrals.index', [
            'ready' => true,
            'enabled' => $this->referrals->programEnabled(),
            'referralCode' => $referralCode,
            'inviteUrl' => $referralCode ? $this->referrals->inviteUrl($referralCode->code) : null,
            'promotionalText' => $referralCode ? $this->referrals->promotionalText($referralCode) : null,
            'stats' => $referralCode ? $this->referrals->stats((int) $referralCode->id) : null,
            'recentInvitations' => $referralCode ? $this->referrals->recentInvitations((int) $referralCode->id) : collect(),
            'recentConversions' => $referralCode ? $this->referrals->recentConversions((int) $referralCode->id) : collect(),
            'deviceId' => $deviceId,
        ]);

        if (! $request->hasCookie(config('referrals.device_cookie', 'mb_device_id'))) {
            Cookie::queue(cookie(
                config('referrals.device_cookie', 'mb_device_id'),
                $deviceId,
                60 * 24 * 365,
                '/',
                null,
                $request->secure(),
                true,
                false,
                'lax',
            ));
        }

        return $response;
    }

    public function recordCopy(Request $request): JsonResponse
    {
        $employee = auth()->user();

        if (! $this->referrals->programEnabled()) {
            return response()->json(['message' => __('employee::referrals.program_disabled')], 403);
        }

        $referralCode = $this->referrals->codeForEmployee($employee);

        if ($referralCode === null) {
            return response()->json(['message' => __('employee::referrals.not_ready')], 422);
        }

        $deviceHash = $this->referrals->deviceHash($this->referrals->deviceId($request));

        $this->referrals->recordInvitation(
            (int) $referralCode->id,
            'copy',
            null,
            $deviceHash,
        );

        return response()->json([
            'message' => __('employee::referrals.copy_recorded'),
            'text' => $this->referrals->promotionalText($referralCode),
            'url' => $this->referrals->inviteUrl($referralCode->code),
        ]);
    }

    public function sendInvites(Request $request): RedirectResponse
    {
        $employee = auth()->user();

        if (! $this->referrals->programEnabled()) {
            return back()->with('error', __('employee::referrals.program_disabled'));
        }

        $validated = $request->validate([
            'emails' => ['required', 'string'],
        ]);

        $emails = collect(preg_split('/[\s,;]+/', $validated['emails']) ?: [])
            ->map(fn (string $email) => strtolower(trim($email)))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return back()->with('error', __('employee::referrals.invalid_emails'));
        }

        $referralCode = $this->referrals->codeForEmployee($employee);

        if ($referralCode === null) {
            return back()->with('error', __('employee::referrals.not_ready'));
        }

        $deviceHash = $this->referrals->deviceHash($this->referrals->deviceId($request));
        $inviteUrl = $this->referrals->inviteUrl($referralCode->code);
        $promotionalText = $this->referrals->promotionalText($referralCode);
        $referrerName = $referralCode->employee_name ?: $employee->translated_name;

        $this->referrals->recordInvitation(
            (int) $referralCode->id,
            'email',
            $emails->all(),
            $deviceHash,
        );

        foreach ($emails as $email) {
            Notification::route('mail', $email)->notify(
                new ReferralInviteNotification($referrerName, $promotionalText, $inviteUrl),
            );
        }

        return back()->with('success', __('employee::referrals.emails_sent', ['count' => $emails->count()]));
    }
}
