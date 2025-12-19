<?php
session_start();
require_once 'db.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if (empty($username) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } 
    else {
        // Check if username exists in database
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password in users table
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user['id']);
            
            if ($stmt->execute()) {
                $message = "Password has been reset successfully. You can now login with your new password.";
            } else {
                $error = "An error occurred. Please try again.";
            }
        } else {
            $error = "No account found with this username.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Heritage Museum</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-[#F5F5DC] font-['Inter']">
    <!-- Navbar -->
    <nav class="bg-[#8B4513] text-[#F5F5DC] py-4">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <div class="text-2xl font-['SF_Pro_Display'] tracking-tight">Heritage Museum</div>
            <div class="space-x-6">
                <a href="index.php" class="hover:text-[#DEB887]">Home</a>
                <a href="login.php" class="hover:text-[#DEB887]">Login</a>
            </div>
        </div>
    </nav>

    <!-- Reset Password Section -->
    <section class="py-16 bg-[#F5F5DC]">
        <div class="container mx-auto px-4">
            <div class="max-w-md mx-auto">
                <div class="vintage-card">
                    <h2 class="text-3xl font-['SF_Pro_Display'] tracking-tight text-center mb-8">Reset Password</h2>
                    
                    <?php if ($message): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <?php echo $message; ?>
                            <div class="mt-4 text-center">
                                <a href="login.php" class="text-[#8B4513] hover:text-[#A0522D]">Go to Login</a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-6">
                        <div>
                            <label class="block mb-2">Username</label>
                            <input type="text" name="username" class="w-full p-2 border rounded" placeholder="Enter your username" required>
                        </div>
                        <div>
                            <label class="block mb-2">New Password</label>
                            <input type="password" name="new_password" class="w-full p-2 border rounded" placeholder="Enter new password" required>
                        </div>
                        <div>
                            <label class="block mb-2">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="w-full p-2 border rounded" placeholder="Confirm new password" required>
                        </div>
                        <button type="submit" class="w-full vintage-button">
                            Reset Password
                        </button>
                    </form>
                    <div class="mt-6 text-center">
                        <p class="text-gray-600">Remember your password? <a href="login.php" class="text-[#8B4513] hover:text-[#A0522D]">Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-[#8B4513] text-[#F5F5DC] py-8">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <p>&copy; 2025 Museum of Indian Heritage. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>