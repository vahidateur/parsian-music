<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\UserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Account management surface.
 *
 * Authorization is resolved through the named UserPolicy abilities before any
 * record is read or written; the controller body compares no role. The
 * privilege-escalation boundary for the role field comes from
 * `RoleEnum::assignableRoles()`, which is also what the forms render.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

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

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'assignableRoles' => $request->user()->role->assignableRoles(),
        ]);
    }

    public function store(StoreUserRequest $request, UserAction $action): RedirectResponse
    {
        $this->authorize('create', User::class);

        $action->create($request->validated(), $request->user());

        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر جدید با موفقیت ایجاد شد.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorize('update', $user);
        // The edit form carries the role field, so role assignment is authorized too.
        $this->authorize('assign', $user);
        $this->denySelfManagement($request, $user);

        return view('admin.users.edit', [
            'user' => $user,
            'assignableRoles' => $request->user()->role->assignableRoles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UserAction $action): RedirectResponse
    {
        $this->authorize('update', $user);
        $this->authorize('assign', $user);
        $this->denySelfManagement($request, $user);

        $action->update($user, $request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', 'اطلاعات کاربر به‌روزرسانی شد.');
    }

    public function destroy(Request $request, User $user, UserAction $action): RedirectResponse
    {
        $this->authorize('delete', $user);
        $this->denySelfManagement($request, $user);

        $action->delete($user);

        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر حذف شد.');
    }

    /** Toggle active/inactive status. */
    public function toggle(Request $request, User $user, UserAction $action): RedirectResponse
    {
        $this->authorize('toggle', $user);
        $this->denySelfManagement($request, $user);

        $action->toggle($user);

        $label = $user->is_active ? 'فعال' : 'غیرفعال';

        return back()->with('success', "حساب کاربر {$label} شد.");
    }

    /**
     * Generate a temporary password, set force_password_change, and flash it.
     * The actor must then communicate it to the user out-of-band.
     */
    public function resetPassword(Request $request, User $user, UserAction $action): RedirectResponse
    {
        $this->authorize('resetPassword', $user);
        $this->denySelfManagement($request, $user);

        $temp = $action->resetPassword($user);

        return back()->with('temp_password', $temp)
            ->with('success', "رمز موقت برای {$user->full_name} تنظیم شد.");
    }

    /**
     * An account is never managed from this module by its own owner.
     *
     * Super admins bypass every Gate check, so this boundary is an identity
     * comparison — never a role comparison — and the profile module stays the
     * only place where an actor edits their own account.
     */
    private function denySelfManagement(Request $request, User $user): void
    {
        abort_if($user->id === $request->user()->id, 403, 'حساب خودتان از این بخش قابل مدیریت نیست.');
    }
}
