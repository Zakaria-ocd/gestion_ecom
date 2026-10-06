<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class usersController extends Controller
{
    public function index()
    {

        return response()->json(User::all());
    }

    public function show($id)
    {
        $user = User::query()
            ->select(['id', 'username', 'email', 'role', 'image', 'created_at'])
            ->find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    public function showUsers(Request $request)
    {

        return response()->json(User::limit($request->limit)->get());
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        if ($request->user()->role !== 'admin' && $request->user()->id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:50',
            'email' => 'required|string|email|max:100|unique:users,email,'.$user->id,
            'current_password' => $request->user()->role === 'admin'
                ? 'nullable|string'
                : 'required_with:password|string',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->user()->role !== 'admin' &&
            ($request->filled('password') || $request->email !== $user->email) &&
            ! Hash::check((string) $request->current_password, $request->user()->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->username = $request->username;
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'image' => $user->image,
            ],
        ]);
    }

    public function deleteUser(Request $request, $id = null)
    {
        $user = User::find($id ?? $request->id);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ], 200);
    }

    public function showImage(Request $request)
    {
        $filename = Storage::exists("users/{$request->image}");

        if ($filename) {
            $file = Storage::get("users/{$request->image}");
            $mimeType = Storage::mimeType("users/{$request->image}");

            return response($file, 200)->header('Content-Type', $mimeType);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Image not found',
            ], 404);
        }
    }

    public function showImageById(Request $request, $id)
    {
        $user = User::find($id);
        if (! $user || ! $user->image) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found',
            ], 404);
        }
        $filename = Storage::exists("users/{$user->image}");

        if ($filename) {
            $file = Storage::get("users/{$user->image}");
            $mimeType = Storage::mimeType("users/{$user->image}");

            return response($file, 200)->header('Content-Type', $mimeType);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Image not found',
            ], 404);
        }
    }

    public function uploadImage(Request $request)
    {
        try {
            $validated = $request->validate([
                'image' => 'required|image|mimes:jpeg,jpg,png,webp,avif|max:5120',
                'user_id' => 'required|exists:users,id',
            ]);

            $user = User::findOrFail($validated['user_id']);
            if ($request->user()->role !== 'admin' && $request->user()->id !== $user->id) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            if ($user->image && Storage::exists("users/{$user->image}")) {
                Storage::delete("users/{$user->image}");
            }

            $path = $request->file('image')->store('users');
            $filename = basename($path);

            $user->image = $filename;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data' => [
                    'image_url' => url("/api/users/imageById/{$user->id}"),
                    'filename' => $filename,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Image upload failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function deleteImage(Request $request, $id = null)
    {
        try {
            $user = User::findOrFail($id ?? $request->id);

            if (! $user->image) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has no image to delete',
                ], 404);
            }

            if (Storage::exists("users/{$user->image}")) {
                Storage::delete("users/{$user->image}");
            }

            $user->image = null;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: '.$e->getMessage(),
            ], 500);
        }
    }
}
