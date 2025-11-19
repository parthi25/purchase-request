<?php include '../common/layout.php'; ?>
  <div class="max-w-6xl mx-auto">

    <!-- Header -->
    <div class="text-center mb-8">
      <h1 class="text-4xl font-bold text-primary mb-2">Update Profile</h1>
      <p class="text-base-content">Update your email and password</p>
    </div>

    <!-- Profile Form Card -->
    <div class="card bg-base-100 shadow-xl mb-8">
      <div class="card-body space-y-4">
        <h2 class="card-title text-2xl">Profile Information</h2>

        <div id="alert-container"></div>

        <form id="profileForm" class="space-y-4 mt-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text font-semibold">Email</span>
            </label>
            <input type="email" id="email" class="input input-bordered w-full" required readonly>
          </div>

          <div class="divider">Change Password (Optional)</div>

          <div class="form-control">
            <label class="label">
              <span class="label-text font-semibold">Old Password</span>
            </label>
            <input type="password" id="old_password" class="input input-bordered w-full">
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text font-semibold">New Password</span>
            </label>
            <input type="password" id="new_password" class="input input-bordered w-full">
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text font-semibold">Confirm New Password</span>
            </label>
            <input type="password" id="confirm_password" class="input input-bordered w-full">
          </div>

          <div class="card-actions justify-end mt-6">
            <button type="submit" class="btn btn-primary w-full">Update Profile</button>
          </div>
        </form>
      </div>
    </div>

  </div>

    <script>
        const form = document.getElementById('profileForm');
        const alertContainer = document.getElementById('alert-container');

        // Load current email from API
        axios.get('/p_r/api/get-profile.php')
            .then(res => {
                if(res.data.status === 'success') {
                    document.getElementById('email').value = res.data.data.email;
                }
            })
            .catch(err => {
                const msg = err.response?.data?.message || 'Failed to load profile data';
                showAlert(msg, 'error');
            });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            alertContainer.innerHTML = '';

            const email = document.getElementById('email').value;
            const oldPassword = document.getElementById('old_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            // Client-side validation
            if (!email) {
                showAlert('Email is required', 'warning');
                return;
            }

            if (oldPassword || newPassword || confirmPassword) {
                if (!oldPassword || !newPassword || !confirmPassword) {
                    showAlert('To change password, fill all password fields', 'warning');
                    return;
                }

                if (newPassword !== confirmPassword) {
                    showAlert('New passwords do not match', 'warning');
                    return;
                }
            }

            const payload = {
                email: email,
                old_password: oldPassword,
                new_password: newPassword,
                confirm_password: confirmPassword
            };

            try {
                const response = await axios.post('/p_r/api/change-password.php', payload);
                showAlert(response.data.message, response.data.status);
                if(response.data.status === 'success') {
                    document.getElementById('old_password').value = '';
                    document.getElementById('new_password').value = '';
                    document.getElementById('confirm_password').value = '';
                }
            } catch(err) {
                const msg = err.response?.data?.message || 'An error occurred';
                showAlert(msg, 'error');
            }
        });

        function showAlert(message, type) {
            alertContainer.innerHTML = `
                <div class="alert alert-${type}">
                    <div>
                        <span>${message}</span>
                    </div>
                </div>
            `;
        }
    </script>
<?php include '../common/layout-footer.php'; ?>
