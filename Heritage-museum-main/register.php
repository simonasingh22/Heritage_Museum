<?php 
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $captcha = $_POST['captcha'];

        // Basic email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format.";
            header("Location: register.php");
            exit();
        }

        // CAPTCHA verification
        if (!isset($_SESSION['captcha']) || strtolower($captcha) != strtolower($_SESSION['captcha'])) {
            $_SESSION['error'] = "Invalid captcha! Please try again.";
            header("Location: register.php");
            exit();
        }

        // Check if username or email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $_SESSION['error'] = "Username or email already exists.";
            header("Location: register.php");
            exit();
        }
        $check_stmt->close();

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION['user'] = $username;
            $_SESSION['user_id'] = $stmt->insert_id;

            unset($_SESSION['captcha']);
            $_SESSION['success'] = "Registration successful! You can now login.";
            header("Location: login.php");
            exit();
        } else {
            throw new Exception("Error: " . $stmt->error);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Registration failed: " . $e->getMessage();
        header("Location: register.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Heritage Museum</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-group { margin-bottom: 1rem; }
        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-group i {
            position: absolute;
            left: 1rem;
            color: #8B4513;
        }
        .input-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        .input-group input:focus {
            border-color: #8B4513;
            box-shadow: 0 0 0 2px rgba(139, 69, 19, 0.1);
            outline: none;
        }
        .captcha-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 8px;
        }
        .captcha-group img {
            height: 60px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .refresh-btn {
            background: #8B4513;
            color: white;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .refresh-btn:hover {
            background: #A0522D;
            transform: rotate(180deg);
        }
    </style>
</head>
<body class="bg-[#F5F5DC] font-['Inter']">

<!-- Navbar -->
<nav class="bg-[#8B4513] text-[#F5F5DC] py-4">
    <div class="container mx-auto px-4 flex justify-between items-center">
        <div class="text-2xl font-semibold">Heritage Museum</div>
        <div class="space-x-6">
            <a href="index.php" class="hover:text-[#DEB887]">Home</a>
            <a href="about.php" class="hover:text-[#DEB887]">About</a>
            <a href="exhibitions.php" class="hover:text-[#DEB887]">Exhibitions</a>
            <a href="tickets.php" class="hover:text-[#DEB887]">Tickets</a>
            <a href="contact.php" class="hover:text-[#DEB887]">Contact</a>
        </div>
    </div>
</nav>

<!-- Registration Form -->
<section class="py-16 bg-[#F5F5DC]">
    <div class="container mx-auto px-4">
        <div class="max-w-md mx-auto">
            <div class="vintage-card p-6 bg-white rounded-lg shadow-lg">
                <h2 class="text-3xl font-semibold text-center mb-6">Create Account</h2>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label class="block mb-1">Username</label>
                        <input type="text" name="username" class="w-full p-2 border rounded" placeholder="Enter username" required>
                    </div>
                    <div>
                        <label class="block mb-1">Email</label>
                        <input type="email" name="email" class="w-full p-2 border rounded" placeholder="Enter email address" required>
                    </div>
                    <div>
                        <label class="block mb-1">Password</label>
                        <input type="password" name="password" class="w-full p-2 border rounded" placeholder="Create a password" required>
                    </div>

                    <div class="form-group">
                        <div class="captcha-group">
                            <img src="includes/captcha.php?v=<?php echo time(); ?>" alt="CAPTCHA" id="captcha-image">
                            <button type="button" onclick="refreshCaptcha()" class="refresh-btn">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <div class="input-group">
                            <i class="fas fa-shield-alt"></i>
                            <input type="text" id="captcha" name="captcha" placeholder="Enter CAPTCHA" required>
                        </div>
                    </div>

                    <div class="flex items-center text-sm">
                        <input type="checkbox" class="mr-2" required>
                        <label>I agree to the <a href="#" class="text-[#8B4513] underline">Terms</a> and <a href="#" class="text-[#8B4513] underline">Privacy Policy</a></label>
                    </div>

                    <button type="submit" class="w-full bg-[#8B4513] text-white py-2 rounded hover:bg-[#A0522D] transition">Create Account</button>
                </form>

                <p class="mt-6 text-center text-gray-600 text-sm">
                    Already have an account? <a href="login.php" class="text-[#8B4513] underline">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Cultural Lines -->
<div class="fixed top-0 left-0 w-full h-2 bg-gradient-to-r from-[#FF9933] via-white to-[#138808]"></div>
<div class="fixed bottom-0 left-0 w-full h-2 bg-gradient-to-r from-[#FF9933] via-white to-[#138808]"></div>
<div class="fixed left-0 top-0 h-full w-2 bg-gradient-to-b from-[#FF9933] via-white to-[#138808]"></div>
<div class="fixed right-0 top-0 h-full w-2 bg-gradient-to-b from-[#FF9933] via-white to-[#138808]"></div>

<!-- Footer -->
<footer class="bg-[#8B4513] text-[#F5F5DC] py-8">
    <div class="container mx-auto px-4 text-center">
        <p>&copy; 2025 Museum of Indian Heritage. All rights reserved.</p>
    </div>
</footer>

<script>
    function refreshCaptcha() {
        document.getElementById('captcha-image').src = 'includes/captcha.php?' + Date.now();
    }
</script>
</body>
</html>
