<?php

namespace App\Services;

use App\Models\Center;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class SuperAdminService
{
    public static function create(array $data): User
    {
        $existing = User::where('center_id', $data['center_id'])
            ->where('role', 'center_manager')->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'This center already has a manager']
            );        }

        $manager = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'center_manager',
            'center_id' => $data['center_id'],
            'is_approved' => true,
            'active' => true,
            'email_verified_at' => now(),
        ]);

        $manager->assignRole('center_manager');

        return $manager;
    }

    public static function update(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update([
            'name'        => $data['name'] ?? $user->name,
            'email'       => $data['email'] ?? $user->email,
            'phone'       => $data['phone'] ?? $user->phone,
            'password'    => $data['password'] ?? $user->password,
            'center_id'   => $data['center_id'] ?? $user->center_id,
            'is_approved' => $data['is_approved'] ?? $user->is_approved,
            'active'      => $data['active'] ?? $user->active,
        ]);

        return $user;
    }

    public static function delete(User $user): void
    {
        if ($user->role !== 'center_manager') {
            throw ValidationException::withMessages([
                'name' => 'this user is not center manger',])        ;}

        $user->delete();
    }

    public function createCenter(array $data): Center
    {
        if (Center::where('name', $data['name'])->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Center with this name already exists.',
            ]);
        }

        if (
            Center::where('latitude', $data['latitude'])
            ->where('longitude', $data['longitude'])
            ->exists()
        ) {
            throw ValidationException::withMessages([
                'coordinates' => 'A center with these coordinates already exists.',
            ]);
        }

        return Center::create($data);
    }

   public function updateCenter(int $id, array $data): Center
{
    try {
        $center = Center::findOrFail($id);
    } catch (ModelNotFoundException $e) {
        throw ValidationException::withMessages([
            'id' => "Center with ID {$id} not found.",
        ]);
    }

    if (isset($data['latitude']) && isset($data['longitude'])) {
        $exists = Center::where('id', '!=', $id)
            ->where('latitude', $data['latitude'])
            ->where('longitude', $data['longitude'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'coordinates' => 'A center with these coordinates already exists.',
            ]);
        }
    }

        $center->update([
            'name' => $data['name'] ?? $center->name,
            'latitude' => $data['latitude'] ?? $center->latitude,
            'longitude' => $data['longitude'] ?? $center->longitude,
        ]);

        return $center;
}




    public function deleteCenter(int $id)
    {
        $center = Center::findOrFail($id);
        if (! $center) {
            throw ValidationException::withMessages([
                'center' => ['Center not found.']
            ]);
        }

        $center->delete();
    }
}
