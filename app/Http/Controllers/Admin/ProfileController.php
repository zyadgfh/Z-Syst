<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\HasUploader;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    use HasUploader;

    public function index()
    {
        $user = User::where('id', Auth::user()->id)->first();

        return view('admin.profile.index', compact('user'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'image' => 'nullable|image|max:2048',
        ]);
        $user = User::findOrFail($id);

        // IDOR: users may only update their own profile unless superadmin
        if ((int) $request->user()->id !== (int) $user->id && ! $request->user()->hasRole('superadmin')) {
            abort(403, 'Forbidden');
        }

        $updateData = $request->only(['name', 'email']);
        $updateData['image'] = $request->hasFile('image') ? $this->upload($request, 'image', $user->image) : $user->image;

        if ($request->filled('password') || $request->filled('current_password')) {
            $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);
            if (! Hash::check($request->current_password, $user->password)) {
                return response()->json(__('Current Password does not match with old password'), 422);
            }
            $updateData['password'] = Hash::make($request->password);
        }
        $user->update($updateData);

        return response()->json([
            'message' => __('Profile updated successfully'),
            'redirect' => route('admin.profiles.index'),
        ]);
    }
}
