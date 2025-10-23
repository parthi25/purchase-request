<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Request & Tracker</title>

    <!-- Fav Icon -->
    <link rel="icon" type="image/x-icon" href="./assets/brand/favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

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
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-logo">
            <img src="./assets/brand/jr.png" alt="Logo" width="240">
            <p>Purchase Request & Tracker</p>
        </div>

        <div class="login-card">
            <div class="login-header">
                <h2 class="mb-0">Login</h2>
            </div>

            <div class="login-body">
                <div id="error-box" class="alert alert-danger text-center" style="display:none;"></div>

                <form id="login-form">
                    <div class="mb-3">
                        <label for="id_username" class="form-label">Username <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" id="id_username" class="form-control" placeholder="Enter your username" required>
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
            <p style="color: darkblue;">© <span id="year"></span> JeyaRama Group.</p>
        </div>
    </div>

    <script>
        document.getElementById('year').textContent = new Date().getFullYear();

        const form = document.getElementById("login-form");
        const errorBox = document.getElementById("error-box");

        form.addEventListener("submit", async e => {
            e.preventDefault();
            errorBox.style.display = "none";

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
                    switch(data.data.role){
                        case "admin": window.location.href = './pages/admin.php'; break;
                        case "buyer": window.location.href = './pages/buyer.php'; break;
                        case "B_Head": window.location.href = './pages/buyer-head.php'; break;
                        case "PO_Head": window.location.href = './pages/po-head.php'; break;
                        case "PO_Team_Member": window.location.href = './pages/po-member.php'; break;
                        default: window.location.href = './pages/po-head.php';
                    }
                } else {
                    errorBox.textContent = data.message;
                    errorBox.style.display = "block";
                }
            } catch (err) {
                console.error(err);
                errorBox.textContent = "Something went wrong. Please try again.";
                errorBox.style.display = "block";
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>