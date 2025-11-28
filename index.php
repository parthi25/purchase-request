<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Request & Tracker</title>

    <!-- Fav Icon -->
    <link rel="icon" type="image/x-icon" href="./assets/brand/favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="./assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="./assets/css/font-awesome-6.0.0.min.css">

    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f4ff;
            margin: 0;
        }

        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo p {
            color: #1e3c72;
        }

        .login-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            padding: 20px;
            text-align: center;
            font-weight: bold;
            overflow: hidden;
        }

        .login-body {
            padding: 30px;
            color: #1e3c72;
            position: relative;
            z-index: 2;
        }

        .form-control {
            border-radius: 5px;
            padding: 12px;
        }

        .btn-login {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            border-radius: 16px;
            padding: 12px;
            font-weight: bold;
            width: 100%;
            color: white;
            text-align: center;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #16325c, #234d91);
        }

        .btn-login::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 5%;
            height: 100%;
            background: linear-gradient(120deg, rgba(255, 255, 255, 0.6), rgba(255, 255, 255, 0.1));
            transform: skewX(-25deg);
            animation: shine 2.5s ease-in-out infinite;
            z-index: 10;
            pointer-events: none;
        }

        @keyframes shine {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .input-group-text i { color: #1e3c72 !important; }

        .alert { margin-bottom: 20px; }
        
        .login-logo i { color: #1e3c72; }
        
        .login-header i { opacity: 0.9; }
        
        #error-box i { margin-right: 5px; }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-logo">
            <img src="./assets/brand/jr.png" alt="Logo" width="240">
            <p><i class="fas fa-shopping-cart me-2"></i>Purchase Request & Tracker</p>
        </div>

        <div class="login-card">
            <div class="login-header">
                <h2 class="mb-0"><i class="fas fa-user-circle me-2"></i>Login</h2>
            </div>

            <div class="login-body">
                <div id="error-box" class="alert alert-danger text-center" style="display:none;">
                    <i class="fas fa-exclamation-circle me-2"></i><span id="error-text"></span>
                </div>

                <form id="login-form">
                    <div class="mb-3">
                        <label for="id_username" class="form-label">Username or Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" id="id_username" class="form-control" placeholder="Enter your username or email" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="id_password" class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" id="id_password" class="form-control" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-4">
            <p style="color: darkblue;"><i class="fas fa-copyright me-1"></i><span id="year"></span> JeyaRama Group.</p>
        </div>
    </div>

    <script>
        document.getElementById('year').textContent = new Date().getFullYear();

        const form = document.getElementById("login-form");
        const errorBox = document.getElementById("error-box");

        form.addEventListener("submit", async e => {
            e.preventDefault();
            errorBox.style.display = "none";
            document.getElementById('error-text').textContent = '';

            const username = document.getElementById("id_username").value.trim();
            const password = document.getElementById("id_password").value;

            try {
                const res = await fetch("./auth/login.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ username, password })
                });
                const data = await res.json();

                if (data.status === "success") {
                    // Use initial page URL from login response, or fallback to default
                    const role = data.data.role;
                    let redirectUrl = './pages/po-head.php'; // Default fallback
                    
                    // Priority 1: Use initial_page_url from database settings
                    if (data.data.initial_page_url) {
                        redirectUrl = './pages/' + data.data.initial_page_url;
                    } else if (role) {
                        // Priority 2: Fallback to default based on role
                        const defaultUrls = {
                            'admin': './pages/admin.php',
                            'buyer': './pages/buyer.php',
                            'B_Head': './pages/buyer-head.php',
                            'PO_Head': './pages/po-head.php',
                            'PO_Team_Member': './pages/po-member.php',
                            'super_admin': './pages/admin.php',
                            'master': './pages/admin.php'
                        };
                        redirectUrl = defaultUrls[role] || './pages/po-head.php';
                    }
                    
                    // Redirect to the determined page
                    window.location.href = redirectUrl;
                } else {
                    document.getElementById('error-text').textContent = data.message;
                    errorBox.style.display = "block";
                }
            } catch (err) {
                console.error(err);
                document.getElementById('error-text').textContent = "Something went wrong. Please try again.";
                errorBox.style.display = "block";
            }
        });
    </script>

    <script src="./assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>