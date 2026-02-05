<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\SessionService;
use App\Services\PermissionService;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $authService;
    protected $sessionService;
    protected $permissionService;
    protected $roleService;

    public function __construct(AuthService $authService, SessionService $sessionService, PermissionService $permissionService, RoleService $roleService)
    {
        $this->authService = $authService;
        $this->sessionService = $sessionService;
        $this->permissionService = $permissionService;
        $this->roleService = $roleService;
    }

    /**
     * Show login form.
     */
    public function showLogin()
    {
        if ($this->sessionService->isAuthenticated()) {
            return redirect('/dashboard');
        }
        
        return view('auth.login');
    }

    /**
     * Process login request.
     * 
     * Thin pass-through to AuthService->authenticate().
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->authService->authenticate(
            $request->input('email'),
            $request->input('password')
        );

        if ($result['success']) {
            $this->sessionService->createSession($result['user']);
        }

        return response()->json($result);
    }

    /**
     * Process logout request.
     * 
     * Thin pass-through to SessionService->destroySession().
     */
    public function logout()
    {
        $this->sessionService->destroySession();

        return redirect('/');
    }

    /**
     * Get current user's permissions.
     * 
     * Thin pass-through to PermissionService->getUserPermissions().
     */
    public function getPermissions()
    {
        if (!$this->sessionService->isAuthenticated()) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated'
            ], 401);
        }

        $userId = $this->sessionService->getCurrentUserId();
        $permissions = $this->permissionService->getUserPermissions($userId);
        $roles = $this->roleService->getUserRoles($userId);

        return response()->json([
            'success' => true,
            'permissions' => $permissions,
            'roles' => $roles
        ]);
    }

    /**
     * Get current authenticated user info.
     */
    public function getCurrentUser()
    {
        if (!$this->sessionService->isAuthenticated()) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => session('user_id'),
                'email' => session('user_email'),
                'role' => session('user_role')
            ]
        ]);
    }
}
