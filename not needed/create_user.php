<!-- <?php
require_once 'includes/db_connect.php';

echo "<h1>User Creation Script</h1>";

try {
    // --- User Data ---
    $users_to_create = [
        [
            'email' => 'josh.killey@gmail.com',
            'password' => 'AdminPassword123!', // The plain text password
            'first_name' => 'Admin',
            'last_name' => 'User',
            'job_title' => 'System Manager',
            'access_level' => 'admin'
        ],
        [
            'email' => 'user@logicalview.co.uk',
            'password' => 'UserPassword123!', // The plain text password
            'first_name' => 'Standard',
            'last_name' => 'User',
            'job_title' => 'Web Developer',
            'access_level' => 'user'
        ]
    ];

    // --- Prepare SQL Statement ---
    $stmt = $pdo->prepare(
        "INSERT INTO users (email, password, first_name, last_name, job_title, access_level)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    // --- Loop and Insert Each User ---
    foreach ($users_to_create as $user) {
        // HASH the password securely
        $hashed_password = password_hash($user['password'], PASSWORD_BCRYPT);

        // Execute the statement
        $stmt->execute([
            $user['email'],
            $hashed_password,
            $user['first_name'],
            $user['last_name'],
            $user['job_title'],
            $user['access_level']
        ]);

        echo "<p>Successfully created user: <strong>" . htmlspecialchars($user['email']) . "</strong> with password: <strong>" . htmlspecialchars($user['password']) . "</strong></p>";
    }

    echo "<h2>Script finished. Please DELETE THIS FILE NOW.</h2>";

} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}
?> -->