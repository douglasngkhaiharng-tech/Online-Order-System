document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('loginForm');

  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    try {
      const formData = new FormData(form);

      const res = await fetch('php/login.php', {
        method: 'POST',
        body: formData
      });

      const text = await res.text();
      console.log('login.php response:', text);

      let result;
      try {
        result = JSON.parse(text);
      } catch {
        alert('Server error: Invalid JSON response.');
        return;
      }

      alert(result.message || 'Login response received.');

      if (result.success) {
        if (result.role === 'admin') {
          window.location.href = 'admin.html';
        } else {
          window.location.href = 'customer.html';
        }
      }

    } catch (error) {
      console.error('login error:', error);
      alert('Login failed. Please try again.');
    }
  });
});