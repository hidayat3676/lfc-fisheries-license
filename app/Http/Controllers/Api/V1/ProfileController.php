<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->isAdminStaff()) {
            $user->load('adminProfile.district');
            $profile = $user->adminProfile;
        } else {
            $user->load('citizenProfile.residenceDistrict');
            $profile = $user->citizenProfile;
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'user_type' => $user->user_type,
                'profile_complete' => $user->isAdminStaff() ? true : $user->hasCompleteCitizenProfile(),
            ],
            'profile' => $profile,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->isAdminStaff()) {
            return $this->updateAdminProfile($request, $user);
        }

        return $this->updateCitizenProfile($request, $user);
    }

    private function updateAdminProfile(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'father_name' => ['nullable', 'string', 'max:120'],
            'cnic' => ['nullable', 'string', 'max:20'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'mobile' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:80'],
            'designation_label' => ['nullable', 'string', 'max:120'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
        ]);

        $user->update([
            'name' => $data['full_name'],
            'mobile' => $data['mobile'],
        ]);

        $profile = $user->adminProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'district_id' => $data['district_id'] ?? null,
                'full_name' => $data['full_name'],
                'father_name' => $data['father_name'] ?? null,
                'cnic' => $data['cnic'] ?? null,
                'dob' => $data['dob'] ?? null,
                'gender' => $data['gender'] ?? null,
                'designation_label' => $data['designation_label'] ?? null,
                'mobile' => $data['mobile'],
                'address' => $data['address'] ?? null,
                'emergency_contact' => $data['emergency_contact'] ?? null,
            ]
        );

        $profile->load('district');

        return response()->json([
            'message' => 'Admin profile updated',
            'profile_complete' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'user_type' => $user->user_type,
                'profile_complete' => true,
            ],
            'profile' => $profile,
        ]);
    }

    private function updateCitizenProfile(Request $request, User $user): JsonResponse
    {
        // Support either 'profile_pic' or 'photo' (file or input)
        $file = $request->file('profile_pic') ?? $request->file('photo');
        $rawPic = $request->input('profile_pic') ?? $request->input('photo') ?? $file;

        $input = $request->all();
        if ($file) {
            $input['profile_pic'] = $file;
        } elseif ($rawPic !== null) {
            $input['profile_pic'] = $rawPic;
        }

        if ($file instanceof \Illuminate\Http\UploadedFile) {
            $profilePicRules = ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'];
        } else {
            $profilePicRules = [
                'required',
                function ($attribute, $value, $fail) use ($user) {
                    if (! is_string($value) || trim($value) === '') {
                        $fail('The profile pic must be an image file or base64 image string.');

                        return;
                    }

                    $currentPhotoPath = $user->citizenProfile()->value('photo_path');
                    if ($currentPhotoPath && (
                        $value === $currentPhotoPath ||
                        str_ends_with($value, $currentPhotoPath)
                    )) {
                        return;
                    }

                    if (preg_match('/^data:image\/(jpeg|png|jpg|webp);base64,([A-Za-z0-9+\/=\r\n]+)$/', $value, $matches)) {
                        $decoded = base64_decode($matches[2], true);
                        if ($decoded !== false && strlen($decoded) <= 5 * 1024 * 1024) {
                            return;
                        }
                    }

                    $decoded = base64_decode($value, true);
                    if ($decoded !== false && strlen($decoded) <= 5 * 1024 * 1024) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_buffer($finfo, $decoded);
                        finfo_close($finfo);
                        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                            return;
                        }
                    }

                    $fail('The profile pic must be a valid image file (jpeg, png, jpg, webp) up to 5MB.');
                },
            ];
        }

        $validator = Validator::make($input, [
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
            'profile_pic' => $profilePicRules,
        ], [
            'cnic.regex' => 'CNIC must be in format 12345-1234567-1.',
            'profile_pic.required' => 'The profile pic field is required.',
            'profile_pic.image' => 'The profile pic must be an image.',
            'profile_pic.mimes' => 'The profile pic must be a file of type: jpeg, png, jpg, webp.',
            'profile_pic.max' => 'The profile pic must not be greater than 5MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $oldPhoto = $user->citizenProfile()->value('photo_path');

        $photoPath = null;
        if ($file instanceof \Illuminate\Http\UploadedFile) {
            $photoPath = $file->store('profiles', 'public');
        } elseif (isset($input['profile_pic']) && is_string($input['profile_pic'])) {
            $val = $input['profile_pic'];
            if ($oldPhoto && (
                $val === $oldPhoto ||
                str_ends_with($val, $oldPhoto)
            )) {
                $photoPath = $oldPhoto;
            } elseif (preg_match('/^data:image\/(jpeg|png|jpg|webp);base64,([A-Za-z0-9+\/=\r\n]+)$/', $val, $matches)) {
                $ext = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
                $filename = 'profiles/'.Str::random(40).'.'.$ext;
                Storage::disk('public')->put($filename, base64_decode($matches[2]));
                $photoPath = $filename;
            } else {
                $decoded = base64_decode($val, true);
                if ($decoded !== false) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_buffer($finfo, $decoded);
                    finfo_close($finfo);
                    $ext = match ($mime) {
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                        default => 'jpg',
                    };
                    $filename = 'profiles/'.Str::random(40).'.'.$ext;
                    Storage::disk('public')->put($filename, $decoded);
                    $photoPath = $filename;
                }
            }
        }

        if ($photoPath && $oldPhoto && $photoPath !== $oldPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        $user->update([
            'name' => $data['full_name'],
            'mobile' => $data['mobile'],
        ]);

        $profileData = collect($data)->except(['mobile', 'profile_pic', 'photo'])->all();
        if ($photoPath) {
            $profileData['photo_path'] = $photoPath;
        }

        $profile = $user->citizenProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $profileData
        );

        $profile->load('residenceDistrict');

        return response()->json([
            'message' => 'Profile updated',
            'profile_complete' => $user->fresh()->load('citizenProfile')->hasCompleteCitizenProfile(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'user_type' => $user->user_type,
                'profile_complete' => $user->fresh()->hasCompleteCitizenProfile(),
            ],
            'profile' => $profile,
            'districts_hint' => District::query()->where('is_active', true)->count(),
        ]);
    }

    public function updatePatternLock(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'pattern' => ['required_if:enabled,true,1', 'nullable', 'string', 'min:4', 'max:20'],
        ], [
            'pattern.required_if' => 'Please provide a pattern sequence of at least 4 nodes.',
        ]);

        if ($request->boolean('enabled')) {
            $user->update([
                'pattern_lock_enabled' => true,
                'pattern_lock_hash' => \Illuminate\Support\Facades\Hash::make($data['pattern']),
            ]);
            $msg = 'Pattern lock enabled.';
        } else {
            $user->update([
                'pattern_lock_enabled' => false,
                'pattern_lock_hash' => null,
            ]);
            $msg = 'Pattern lock disabled.';
        }

        return response()->json([
            'status' => 'success',
            'message' => $msg,
            'user' => [
                'id' => $user->id,
                'pattern_lock_enabled' => (bool) $user->pattern_lock_enabled,
                'has_pattern_lock' => $user->hasPatternLock(),
            ],
        ]);
    }
}

