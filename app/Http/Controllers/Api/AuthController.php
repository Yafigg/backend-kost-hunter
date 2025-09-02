<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register new user
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'role' => 'required|in:owner,society',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => $request->role,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Update last login
        $user->update(['last_login' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }

    /**
     * Get all users (for admin purposes)
     */
    public function getAllUsers(Request $request)
    {
        $users = User::select('id', 'name', 'email', 'phone', 'role', 'created_at', 'last_login')
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'data' => $users
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // Debug: Log request info
        Log::info('Update Profile Request', [
            'user_id' => $user->id,
            'has_file' => $request->hasFile('avatar'),
            'files' => $request->allFiles()
        ]);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Prepare data to update
        $updateData = [];
        
        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }
        
        if ($request->has('phone')) {
            $updateData['phone'] = $request->phone;
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            try {
                $file = $request->file('avatar');
                Log::info('Avatar file details', [
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType()
                ]);

                // Delete old avatar if exists
                if ($user->avatar) {
                    $oldAvatarPath = 'public/' . $user->avatar;
                    if (Storage::exists($oldAvatarPath)) {
                        Storage::delete($oldAvatarPath);
                        Log::info('Old avatar deleted: ' . $oldAvatarPath);
                    }
                }

                // Store new avatar
                $avatarPath = $file->store('avatars', 'public');
                $updateData['avatar'] = $avatarPath;
                
                Log::info('New avatar stored: ' . $avatarPath);

            } catch (\Exception $e) {
                Log::error('Avatar upload failed: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Avatar upload failed: ' . $e->getMessage()
                ], 500);
            }
        }

        // Update user
        $user->update($updateData);
        
        // Refresh user data from database
        $user->refresh();

        Log::info('User updated', [
            'user_id' => $user->id,
            'updated_data' => $updateData,
            'final_avatar' => $user->avatar
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Change user password (for logged in users)
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // Check current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Check if email exists for forgot password
     */
    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak ditemukan dalam sistem'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email ditemukan, silakan lanjutkan ke step berikutnya',
            'data' => [
                'email' => $user->email,
                'name' => $user->name
            ]
        ]);
    }

    /**
     * Reset password (for forgot password flow)
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak ditemukan'
            ], 404);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil direset. Silakan login dengan password baru.'
        ]);
    }

    /**
     * Get avatar URL
     */
    public function getAvatarUrl($avatarPath)
    {
        if (!$avatarPath) {
            return null;
        }
        
        return url('storage/' . $avatarPath);
    }
}