<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->with('createdBy');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q
                ->where('full_name', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
            );
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone'     => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email'     => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'role'      => ['required', Rule::in(
                // Prevent privilege escalation: can only assign lower roles
                collect(RoleEnum::cases())
                    ->filter(fn (RoleEnum $r) => $actor->role->canManage($r))
                    ->map(fn (RoleEnum $r) => $r->value)
                    ->toArray()
            )],
            'password'  => ['required', 'confirmed', Password::min(8)],
        ]);

        User::create([
            'full_name'  => $validated['full_name'],
            'phone'      => $validated['phone'],
            'email'      => $validated['email'] ?? null,
            'role'       => $validated['role'],
            'password'   => Hash::make($validated['password']),
            'is_active'  => true,
            'created_by' => $actor->id,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر جدید با موفقیت ایجاد شد.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        // Prevent editing users of equal or higher rank
        if (! $actor->role->canManage($user->role)) {
            abort(403, 'شما مجاز به ویرایش این کاربر نیستید.');
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone'     => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'email'     => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'      => ['required', Rule::in(
                collect(RoleEnum::cases())
                    ->filter(fn (RoleEnum $r) => $actor->role->canManage($r))
                    ->map(fn (RoleEnum $r) => $r->value)
                    ->toArray()
            )],
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'اطلاعات کاربر به‌روزرسانی شد.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $actor = request()->user();

        if (! $actor->role->canManage($user->role)) {
            abort(403);
        }

        if ($user->id === $actor->id) {
            return back()->with('error', 'نمی‌توانید حساب خودتان را حذف کنید.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر حذف شد.');
    }

    /** Toggle active/inactive status. */
    public function toggle(User $user): RedirectResponse
    {
        $actor = request()->user();

        if (! $actor->role->canManage($user->role)) {
            abort(403);
        }

        if ($user->id === $actor->id) {
            return back()->with('error', 'نمی‌توانید حساب خودتان را غیرفعال کنید.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $label = $user->is_active ? 'فعال' : 'غیرفعال';

        return back()->with('success', "حساب کاربر {$label} شد.");
    }

    /**
     * Generate a temporary password, set force_password_change, and flash it.
     * The actor must then communicate it to the user out-of-band.
     */
    public function resetPassword(User $user): RedirectResponse
    {
        $actor = request()->user();

        if (! $actor->role->canManage($user->role)) {
            abort(403);
        }

        $temp = Str::password(12, symbols: false);

        $user->update([
            'password'              => Hash::make($temp),
            'force_password_change' => true,
        ]);

        return back()->with('temp_password', $temp)
            ->with('success', "رمز موقت برای {$user->full_name} تنظیم شد.");
    }
}
