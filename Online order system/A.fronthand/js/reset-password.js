const params = new URLSearchParams(window.location.search);
document.getElementById('token').value = params.get('token') || '';

document.getElementById('resetPasswordForm').addEventListener('submit', async (e) => {
  e.preventDefault();

  const formData = new FormData(e.target);

  const res = await fetch('php/reset_password.php', {
    method: 'POST',
    body: formData
  });

  const result = await res.json();
  alert(result.message);

  if (result.success) {
    window.location.href = 'login.html';
  }
});