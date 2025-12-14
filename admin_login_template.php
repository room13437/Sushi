<!-- Admin Login Form Template - สำหรับแทนที่ form ที่ใช้ JavaScript -->
<?php if (!$isLoggedIn): ?>
    <div class="login-container" id="login-form-container" style="display: flex;">
        <h2>🔒 เข้าสู่ระบบผู้ดูแลระบบ</h2>
        <?php if (isset($loginError)): ?>
            <div
                style="color: #f44336; margin-bottom: 15px; padding: 10px; background: #ffebee; border-radius: 8px; font-weight: 600;">
                ❌ <?php echo $loginError; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="username" required placeholder="ชื่อผู้ใช้" autofocus autocomplete="username"
                style="width: 100%; padding: 14px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 1rem; margin-bottom: 10px;">
            <div style="position: relative;">
                <input type="password" name="password" id="adminPassword" required placeholder="รหัสผ่าน"
                    style="width: 100%; padding: 14px; padding-right: 45px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 1rem;"
                    autocomplete="current-password">
                <button type="button" onclick="toggleAdminPassword()"
                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem; color: #666;">
                    <i class="fas fa-eye" id="adminToggleIcon"></i>
                </button>
            </div>
            <button type="submit" name="admin_login"
                style="width: 100%; padding: 14px; background: linear-gradient(135deg, #FF6F00, #FF8F00); color: white; border: none; border-radius: 12px; font-size: 1.1rem; font-weight: 700; cursor: pointer; margin-top: 10px; box-shadow: 0 4px 15px rgba(255, 111, 0, 0.3);">
                เข้าสู่ระบบ
            </button>
            <div style="margin-top: 10px; font-size: 0.85rem; color: #666; text-align: center;">
                ชื่อผู้ใช้: <strong>admin</strong> | รหัสผ่าน: <strong>AdminN_N</strong>
            </div>
        </form>
    </div>

    <script>
        // Toggle password visibility
        function toggleAdminPassword() {
            const passwordInput = document.getElementById('adminPassword');
            const toggleIcon = document.getElementById('adminToggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>

<?php else: ?>
    <!-- เนื้อหาหน้าจอหลังบ้านจะอยู่ที่นี่ -->
<?php endif; ?>