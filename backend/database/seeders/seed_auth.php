<?php
// Database seeder for authentication system
// Seeds roles, admin user, user_roles, and permissions

$pdo = new PDO('pgsql:host=localhost;port=5432;dbname=opf_cd', 'postgres', 'password123');

echo "Starting authentication seeder...\n";

// 1. Seed Roles
echo "\n1. Seeding roles...\n";
$roles = [
    ['name' => 'Admin', 'description' => 'Full system access with all permissions'],
    ['name' => 'Project Manager', 'description' => 'Manage projects, tasks, and milestones'],
    ['name' => 'Finance', 'description' => 'Manage finances, expenses, and accounts'],
    ['name' => 'Sales', 'description' => 'Manage sales pipeline and opportunities'],
    ['name' => 'Viewer', 'description' => 'Read-only access to dashboards and reports']
];

foreach ($roles as $role) {
    try {
        $stmt = $pdo->prepare("INSERT INTO roles (name, description) VALUES (:name, :description)");
        $stmt->execute($role);
        echo "  ✓ Created role: {$role['name']}\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate key') !== false) {
            echo "  - Role already exists: {$role['name']}\n";
        } else {
            echo "  ✗ Error creating role {$role['name']}: " . $e->getMessage() . "\n";
        }
    }
}

// 2. Seed Admin User
echo "\n2. Seeding admin user...\n";
$hashedPassword = password_hash('password', PASSWORD_DEFAULT);
try {
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password_hash, role, is_active, created_at, updated_at) 
        VALUES (:email, :password_hash, 'admin', true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ON CONFLICT (email) DO NOTHING
        RETURNING id
    ");
    $stmt->execute([
        'email' => 'admin@opf-cd.local',
        'password_hash' => $hashedPassword
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        echo "  ✓ Created admin user: admin@opf-cd.local\n";
        $adminUserId = $result['id'];
    } else {
        // User already exists, fetch ID
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => 'admin@opf-cd.local']);
        $adminUserId = $stmt->fetchColumn();
        echo "  - Admin user already exists\n";
    }
} catch (PDOException $e) {
    echo "  ✗ Error creating admin user: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Assign Admin Role to Admin User
echo "\n3. Assigning admin role...\n";
try {
    $adminRoleStmt = $pdo->query("SELECT id FROM roles WHERE name = 'Admin'");
    $adminRoleId = $adminRoleStmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        INSERT INTO user_roles (user_id, role_id) 
        VALUES (:user_id, :role_id)
        ON CONFLICT (user_id, role_id) DO NOTHING
    ");
    $stmt->execute([
        'user_id' => $adminUserId,
        'role_id' => $adminRoleId
    ]);
    echo "  ✓ Assigned Admin role to admin user\n";
} catch (PDOException $e) {
    echo "  ✗ Error assigning role: " . $e->getMessage() . "\n";
}

// 4. Seed Permissions
echo "\n4. Seeding permissions...\n";

// Get role IDs
$roleIds = [];
foreach ($roles as $role) {
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = :name");
    $stmt->execute(['name' => $role['name']]);
    $roleIds[$role['name']] = $stmt->fetchColumn();
}

// Permission definitions based on PRODUCTION_ROADMAP.md
$permissions = [
    // Admin - Full access to everything
    'Admin' => [
        ['resource' => 'projects', 'action' => 'view'],
        ['resource' => 'projects', 'action' => 'create'],
        ['resource' => 'projects', 'action' => 'edit'],
        ['resource' => 'projects', 'action' => 'delete'],
        ['resource' => 'projects', 'action' => 'manage'],
        ['resource' => 'tasks', 'action' => 'view'],
        ['resource' => 'tasks', 'action' => 'create'],
        ['resource' => 'tasks', 'action' => 'edit'],
        ['resource' => 'tasks', 'action' => 'delete'],
        ['resource' => 'milestones', 'action' => 'view'],
        ['resource' => 'milestones', 'action' => 'create'],
        ['resource' => 'milestones', 'action' => 'edit'],
        ['resource' => 'milestones', 'action' => 'delete'],
        ['resource' => 'expenses', 'action' => 'view'],
        ['resource' => 'expenses', 'action' => 'create'],
        ['resource' => 'expenses', 'action' => 'edit'],
        ['resource' => 'expenses', 'action' => 'delete'],
        ['resource' => 'accounts', 'action' => 'view'],
        ['resource' => 'accounts', 'action' => 'create'],
        ['resource' => 'accounts', 'action' => 'edit'],
        ['resource' => 'accounts', 'action' => 'delete'],
        ['resource' => 'cash_transactions', 'action' => 'view'],
        ['resource' => 'cash_transactions', 'action' => 'create'],
        ['resource' => 'cash_transactions', 'action' => 'edit'],
        ['resource' => 'cash_transactions', 'action' => 'delete'],
        ['resource' => 'opportunities', 'action' => 'view'],
        ['resource' => 'opportunities', 'action' => 'create'],
        ['resource' => 'opportunities', 'action' => 'edit'],
        ['resource' => 'opportunities', 'action' => 'delete'],
        ['resource' => 'users', 'action' => 'view'],
        ['resource' => 'users', 'action' => 'create'],
        ['resource' => 'users', 'action' => 'edit'],
        ['resource' => 'users', 'action' => 'delete'],
        ['resource' => 'roles', 'action' => 'manage'],
        ['resource' => 'permissions', 'action' => 'manage'],
        ['resource' => 'dashboards', 'action' => 'view'],
    ],
    
    // Project Manager - Projects, tasks, milestones, view dashboards
    'Project Manager' => [
        ['resource' => 'projects', 'action' => 'view'],
        ['resource' => 'projects', 'action' => 'create'],
        ['resource' => 'projects', 'action' => 'edit'],
        ['resource' => 'tasks', 'action' => 'view'],
        ['resource' => 'tasks', 'action' => 'create'],
        ['resource' => 'tasks', 'action' => 'edit'],
        ['resource' => 'tasks', 'action' => 'delete'],
        ['resource' => 'milestones', 'action' => 'view'],
        ['resource' => 'milestones', 'action' => 'create'],
        ['resource' => 'milestones', 'action' => 'edit'],
        ['resource' => 'dashboards', 'action' => 'view'],
    ],
    
    // Finance - Expenses, accounts, cash transactions, view dashboards
    'Finance' => [
        ['resource' => 'expenses', 'action' => 'view'],
        ['resource' => 'expenses', 'action' => 'create'],
        ['resource' => 'expenses', 'action' => 'edit'],
        ['resource' => 'expenses', 'action' => 'delete'],
        ['resource' => 'accounts', 'action' => 'view'],
        ['resource' => 'accounts', 'action' => 'create'],
        ['resource' => 'accounts', 'action' => 'edit'],
        ['resource' => 'accounts', 'action' => 'delete'],
        ['resource' => 'cash_transactions', 'action' => 'view'],
        ['resource' => 'cash_transactions', 'action' => 'create'],
        ['resource' => 'cash_transactions', 'action' => 'edit'],
        ['resource' => 'cash_transactions', 'action' => 'delete'],
        ['resource' => 'dashboards', 'action' => 'view'],
    ],
    
    // Sales - Opportunities, view dashboards
    'Sales' => [
        ['resource' => 'opportunities', 'action' => 'view'],
        ['resource' => 'opportunities', 'action' => 'create'],
        ['resource' => 'opportunities', 'action' => 'edit'],
        ['resource' => 'opportunities', 'action' => 'delete'],
        ['resource' => 'dashboards', 'action' => 'view'],
    ],
    
    // Viewer - Read-only access to dashboards
    'Viewer' => [
        ['resource' => 'projects', 'action' => 'view'],
        ['resource' => 'tasks', 'action' => 'view'],
        ['resource' => 'milestones', 'action' => 'view'],
        ['resource' => 'expenses', 'action' => 'view'],
        ['resource' => 'accounts', 'action' => 'view'],
        ['resource' => 'cash_transactions', 'action' => 'view'],
        ['resource' => 'opportunities', 'action' => 'view'],
        ['resource' => 'dashboards', 'action' => 'view'],
    ],
];

$permissionCount = 0;
foreach ($permissions as $roleName => $perms) {
    $roleId = $roleIds[$roleName];
    echo "  Seeding permissions for: $roleName\n";
    
    foreach ($perms as $perm) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO permissions (role_id, resource, action) 
                VALUES (:role_id, :resource, :action)
                ON CONFLICT (role_id, resource, action) DO NOTHING
            ");
            $stmt->execute([
                'role_id' => $roleId,
                'resource' => $perm['resource'],
                'action' => $perm['action']
            ]);
            $permissionCount++;
        } catch (PDOException $e) {
            echo "    ✗ Error creating permission: " . $e->getMessage() . "\n";
        }
    }
}
echo "  ✓ Created $permissionCount permissions\n";

echo "\n=== Authentication Seeding Complete ===\n";
echo "Admin Credentials:\n";
echo "  Email: admin@opf-cd.local\n";
echo "  Password: password\n";
