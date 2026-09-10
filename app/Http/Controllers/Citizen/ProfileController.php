<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->load('citizenProfile');

        return view('citizen.profile.edit', [
            'user' => $user,
            'profile' => $user->citizenProfile,
            'districts' => District::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'father_name' => ['required', 'string', 'max:120'],
            'cnic' => ['required', 'regex:/^\d{5}-\d{7}-\d$/'],
            'dob' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'mobile' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'residence_district_id' => ['required', 'integer', 'exists:districts,id'],
            'province' => ['required', 'string', 'max:80'],
            'emergency_contact' => ['required', 'string', 'max:80'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'cnic.regex' => 'CNIC must be in format 12345-1234567-1.',
        ]);

        $user = $request->user();
        $user->update([
            'name' => $data['full_name'],
            'mobile' => $data['mobile'],
        ]);

        $profileData = [
            'full_name' => $data['full_name'],
            'father_name' => $data['father_name'],
            'cnic' => $data['cnic'],
            'dob' => $data['dob'],
            'gender' => $data['gender'],
            'address' => $data['address'],
            'residence_district_id' => $data['residence_district_id'],
            'province' => $data['province'],
            'emergency_contact' => $data['emergency_contact'],
        ];

        if ($request->hasFile('photo')) {
            $profileData['photo_path'] = $request->file('photo')->store('profiles', 'public');
        }

        $user->citizenProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $profileData
        );

        $redirect = $request->session()->pull('url.intended', route('citizen.dashboard'));

        return redirect($redirect)->with('status', 'Profile saved.');
    }

    public function updatePatternLock(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'pattern_lock_enabled' => ['required', 'boolean'],
            'pattern' => ['required_if:pattern_lock_enabled,1,true', 'nullable', 'string', 'min:4', 'max:20'],
        ], [
            'pattern.required_if' => 'Please draw a valid pattern sequence of at least 4 nodes.',
        ]);

        if ($request->boolean('pattern_lock_enabled')) {
            $user->update([
                'pattern_lock_enabled' => true,
                'pattern_lock_hash' => \Illuminate\Support\Facades\Hash::make($data['pattern']),
            ]);
            $msg = 'Pattern lock enabled successfully.';
        } else {
            $user->update([
                'pattern_lock_enabled' => false,
                'pattern_lock_hash' => null,
            ]);
            $msg = 'Pattern lock disabled successfully.';
        }

        return redirect()->route('citizen.profile.edit')->with('status', $msg);
    }
}
