document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('forgotPasswordForm');
  const btn = document.getElementById('sendResetBtn');

  const questionBox = document.getElementById('questionBox');
  const answerSection = document.getElementById('answerSection');
  const answerInput = document.getElementById('securityAnswer');
  const newPasswordInput = document.getElementById('newPassword');

  let currentStep = 1;

  if (!form) {
    console.error('forgotPasswordForm not found');
    return;
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (btn) {
      btn.disabled = true;
      btn.textContent = currentStep === 1 ? 'Checking...' : 'Resetting...';
    }

    try {
      const formData = new FormData(form);

      let url = '';
      if (currentStep === 1) {
        url = 'php/forgot_password.php';
      } else {
        url = 'php/reset_password.php';
      }

      const res = await fetch(url, {
        method: 'POST',
        body: formData
      });

      const text = await res.text();
      console.log(url + ' response:', text);

      let result;
      try {
        result = JSON.parse(text);
      } catch {
        alert(url + ' did not return valid JSON.');
        return;
      }

      if (!result.success) {
        alert(result.message || 'Unknown response.');
        return;
      }

      if (currentStep === 1) {
        if (questionBox) {
          questionBox.textContent = result.question || '';
        }

        if (answerSection) {
          answerSection.style.display = 'block';
        }

        if (answerInput) {
          answerInput.required = true;
        }

        if (newPasswordInput) {
          newPasswordInput.required = true;
        }

        currentStep = 2;

        alert('Security question loaded. Please answer it and enter your new password.');

        if (btn) {
          btn.textContent = 'Reset Password';
        }
      } else {
        alert(result.message || 'Password reset successful.');
        window.location.href = 'login.html';
      }

    } catch (error) {
      console.error('forgot/reset password error:', error);
      alert('Failed to process password reset.');
    } finally {
      if (btn) {
        btn.disabled = false;

        if (currentStep === 1) {
          btn.textContent = 'Get Security Question';
        } else {
          btn.textContent = 'Reset Password';
        }
      }
    }
  });
});