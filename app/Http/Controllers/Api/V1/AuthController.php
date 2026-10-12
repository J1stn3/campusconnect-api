<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function register(Request $request): JsonResponse
    {
        if ($request->has('role') && is_string($request->input('role'))) {
            $request->merge([
                'role' => strtolower(trim($request->input('role'))),
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['nullable', 'string', 'in:student,admin,faculty'],
            'student_id' => ['nullable', 'string', 'max:20'],
        ]);

        $role = $validated['role'] ?? 'student';
        $studentId = ! empty($validated['student_id'])
            ? $validated['student_id']
            : $this->generateIdForRole($role);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'student_id' => $studentId,
            'role' => $role,
        ]);

        $token = $this->issueToken($user);

        return $this->createdResponse([
            'user' => $this->formatUser($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Account created successfully. Welcome to CampusConnect!');
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->errorResponse(
                'Invalid credentials. Please check your email and password.',
                401
            );
        }

        $user->tokens()->delete();

        $token = $this->issueToken($user);

        return $this->successResponse([
            'user' => $this->formatUser($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], "Login successful. Welcome back, {$user->name}!");
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(
            null,
            'Logged out successfully. Your session has been ended.'
        );
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $count = $request->user()->tokens()->count();
        $request->user()->tokens()->delete();

        return $this->successResponse(
            null,
            "Logged out from {$count} device(s) successfully."
        );
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->formatUser($request->user()),
            'Profile retrieved successfully.'
        );
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'student_id' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $user->update($validated);

        return $this->successResponse(
            $this->formatUser($user->fresh()),
            'Profile updated successfully.'
        );
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'student_id' => $user->student_id,
            'role' => $user->role,
            'created_at' => $user->created_at->toDateTimeString(),
            'updated_at' => $user->updated_at->toDateTimeString(),
        ];
    }

    private function issueToken(User $user): string
    {
        return $user->createToken(
            'campusconnect-mobile',
            $this->tokenAbilities($user),
            now()->addDays(30)
        )->plainTextToken;
    }

    private function tokenAbilities(User $user): array
    {
        if ($user->isAdmin()) {
            return ['*'];
        }

        $abilities = [
            'announcements:read',
            'events:read',
            'events:rsvp',
            'schedule:read',
            'notifications:read',
            'notifications:write',
            'chatbot:use',
        ];

        if ($user->isFaculty()) {
            $abilities[] = 'events:write';
        }

        return $abilities;
    }

    private function generateIdForRole(string $role): string
    {
        if ($role === 'admin') {
            $existing = User::where('student_id', 'like', 'ADM-%')
                ->pluck('student_id')
                ->flip();

            $seq = 1;
            while ($existing->has(sprintf('ADM-%05d', $seq))) {
                $seq++;
            }

            return sprintf('ADM-%05d', $seq);
        }

        if ($role === 'faculty') {
            $existing = User::where('student_id', 'like', 'FAC-%')
                ->pluck('student_id')
                ->flip();

            $seq = 1;
            while ($existing->has(sprintf('FAC-%05d', $seq))) {
                $seq++;
            }

            return sprintf('FAC-%05d', $seq);
        }

        // Student role
        $year = date('Y');
        $prefix = "{$year}-";
        $existing = User::where('student_id', 'like', "{$prefix}%")
            ->pluck('student_id')
            ->flip();

        $seq = 1;
        while ($existing->has(sprintf('%s-%05d', $year, $seq))) {
            $seq++;
        }

        return sprintf('%s-%05d', $year, $seq);
    }
}

