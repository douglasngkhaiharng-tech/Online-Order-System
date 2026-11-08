document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('signupForm');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(form);

    const password = String(formData.get('password') || '');
    const confirm = String(formData.get('confirm_password') || '');
    const avatar = formData.get('avatar');

    if (password !== confirm) {
      alert('Passwords do not match.');
      return;
    }

    if (!avatar || avatar.size === 0) {
      alert('Please upload a profile photo.');
      return;
    }

    try {
      const res = await fetch('php/signup.php', {
        method: 'POST',
        body: formData
      });

      const text = await res.text();
      console.log('signup.php response:', text);

      let result;
      try {
        result = JSON.parse(text);
      } catch {
        alert('signup.php did not return valid JSON.');
        return;
      }

      alert(result.message || 'Unknown response.');

      if (result.success) {
        window.location.href = 'login.html';
      }
    } catch (err) {
      console.error(err);
      alert('Error occurred.');
    }
  });
});