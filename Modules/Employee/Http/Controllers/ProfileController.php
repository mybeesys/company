<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Modules\Employee\Http\Requests\UpdateProfileRequest;
use Modules\Employee\Services\EmployeeActions;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('employee::profile.edit', [
            'employee' => auth()->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = auth()->user();

        return DB::transaction(function () use ($request, $user) {
            try {
                $validated = $request->validated();
                $actions = new EmployeeActions(collect($validated));

                $data = [
                    'name' => $validated['name'],
                    'name_en' => $validated['name_en'],
                    'email' => $validated['email'],
                    'phone_number' => $validated['phone_number'] ?? null,
                ];

                if ($user->ems_access) {
                    $data['user_name'] = $validated['user_name'] ?? null;
                }

                if ($request->hasFile('image')) {
                    $data['image'] = $actions->storeImage($request->file('image'), $user->image);
                } elseif ($request->has('image_old') && ! $request->boolean('image_old')) {
                    if ($user->image) {
                        $oldPath = public_path('storage/tenant'.tenancy()->tenant->id.'/'.$user->image);
                        if (File::exists($oldPath)) {
                            File::delete($oldPath);
                        }
                    }
                    $data['image'] = null;
                }

                if (! empty($validated['password'])) {
                    $data['password'] = $validated['password'];
                }

                $user->update($data);

                return to_route('profile.edit')
                    ->with('success', __('employee::general.profile_updated'));
            } catch (\Throwable $e) {
                Log::error('Profile update failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                return redirect()->back()
                    ->withInput()
                    ->with('error', __('employee::responses.something_wrong_happened'));
            }
        });
    }
}
