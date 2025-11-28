<?php include '../common/layout.php'; ?>
  <div class="max-w-6xl mx-auto">

    <!-- Header -->
    <div class="text-center mb-8">
      <h1 class="text-4xl font-bold text-primary mb-2">Update Profile</h1>
      <p class="text-base-content">Update your password</p>
    </div>

    <!-- Profile Form Card -->
    <div class="card bg-base-100 shadow-xl mb-8">
      <div class="card-body space-y-4">
        <h2 class="card-title text-2xl capitalize">Profile Information</h2>

        <div id="alert-container"></div>

        <form id="profileForm" class="space-y-4 mt-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text font-semibold">Email</span>
              <span class="label-text-alt text-gray-500">(Cannot be changed)</span>
            </label>
            <input type="email" id="email" class="input input-bordered w-full bg-base-200" disabled readonly>
          </div>

          <div class="divider">Change Password</div>

          <div class="form-control">
            <label class="label">
              <span class="label-text font-semibold">Old Password <span class="text-error">*</span></span>
            </label>
            <input type="password" id="old_password" class="input input-bordered w-full">
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text font-semibold">New Password <span class="text-error">*</span></span>
            </label>
            <input type="password" id="new_password" class="input input-bordered w-full">
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text font-semibold">Confirm New Password <span class="text-error">*</span></span>
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
        fetch('../api/get-profile.php')
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    document.getElementById('email').value = data.data.email;
                } else {
                    showAlert(data.message || 'Failed to load profile data', 'error');
                }
            })
            .catch(err => {
                showAlert('Failed to load profile data', 'error');
                console.error('Error loading profile:', err);
            });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            alertContainer.innerHTML = '';

            const oldPassword = document.getElementById('old_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            // Client-side validation
            if (!oldPassword || !newPassword || !confirmPassword) {
                showAlert('All password fields are required', 'warning');
                return;
            }

            if (newPassword !== confirmPassword) {
                showAlert('New passwords do not match', 'warning');
                return;
            }

            // Get CSRF token
            let csrfToken = '';
            try {
                const csrfResponse = await fetch('../auth/get-csrf-token.php');
                const csrfData = await csrfResponse.json();
                csrfToken = csrfData.status === 'success' ? csrfData.data.csrf_token : '';
            } catch (error) {
                console.error('Failed to get CSRF token:', error);
                showAlert('Failed to get security token. Please refresh the page.', 'error');
                return;
            }

            const payload = {
                old_password: oldPassword,
                new_password: newPassword,
                confirm_password: confirmPassword,
                csrf_token: csrfToken
            };

            try {
                const response = await fetch('../api/change-password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                showAlert(data.message, data.status);
                if(data.status === 'success') {
                    document.getElementById('old_password').value = '';
                    document.getElementById('new_password').value = '';
                    document.getElementById('confirm_password').value = '';
                }
            } catch(err) {
                showAlert('An error occurred while updating profile', 'error');
                console.error('Error updating profile:', err);
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
