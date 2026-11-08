let currentUser = null;
let SYSTEM_DEFAULT_AVATAR = '';

function stripCache(path) {
  return String(path || '').split('?')[0].trim();
}

function bustCache(path) {
  const clean = stripCache(path);
  const finalPath = clean || SYSTEM_DEFAULT_AVATAR || '';
  if (!finalPath) return '';
  const separator = finalPath.includes('?') ? '&' : '?';
  return `${finalPath}${separator}t=${Date.now()}`;
}

function applyTheme(theme) {
  const body = document.body;
  if (!body) return;

  body.classList.remove('theme-light-mode', 'theme-dark-mode');

  if (theme === 'light') {
    body.classList.add('theme-light-mode');
  } else if (theme === 'dark') {
    body.classList.add('theme-dark-mode');
  }
}

function updateThemeButton(btn, theme) {
  if (!btn) return;

  btn.dataset.theme = theme;

  if (theme === 'light') {
    btn.textContent = '☀ Light';
  } else if (theme === 'dark') {
    btn.textContent = '🌙 Dark';
  } else {
    btn.textContent = '🌗 Theme';
  }
}

function resetThemeToDefault() {
  localStorage.setItem('site_theme', 'default');
  applyTheme('default');
  updateThemeButton(document.getElementById('themeToggleBtn'), 'default');
}

function initThemeOnLoginSession() {
  const alreadyInitialized = sessionStorage.getItem('theme_initialized');

  if (!alreadyInitialized) {
    localStorage.setItem('site_theme', 'default');
    sessionStorage.setItem('theme_initialized', '1');
  }

  const savedTheme = localStorage.getItem('site_theme') || 'default';
  applyTheme(savedTheme);
  updateThemeButton(document.getElementById('themeToggleBtn'), savedTheme);
}

function bindThemeToggle() {
  const btn = document.getElementById('themeToggleBtn');
  if (!btn || btn.dataset.bound === '1') return;

  btn.dataset.bound = '1';

  btn.addEventListener('click', () => {
    const currentTheme = btn.dataset.theme || 'default';
    let nextTheme = 'light';

    if (currentTheme === 'light') {
      nextTheme = 'dark';
    } else if (currentTheme === 'dark') {
      nextTheme = 'default';
    }

    localStorage.setItem('site_theme', nextTheme);
    applyTheme(nextTheme);
    updateThemeButton(btn, nextTheme);
  });
}

window.showProfileEditMode = function () {
  const profileViewBox = document.getElementById('profileViewBox');
  const profileForm = document.getElementById('profileForm');
  const currentPassword = document.getElementById('currentPassword');

  if (profileViewBox) profileViewBox.style.display = 'none';
  if (profileForm) profileForm.style.display = 'block';
  if (currentPassword) currentPassword.focus();
};

window.hideProfileEditMode = function () {
  const profileViewBox = document.getElementById('profileViewBox');
  const profileForm = document.getElementById('profileForm');
  const currentPassword = document.getElementById('currentPassword');
  const profilePassword = document.getElementById('profilePassword');
  const profileConfirmPassword = document.getElementById('profileConfirmPassword');
  const profileAvatar = document.getElementById('profileAvatar');

  if (profileViewBox) profileViewBox.style.display = 'block';
  if (profileForm) profileForm.style.display = 'none';

  if (currentPassword) currentPassword.value = '';
  if (profilePassword) profilePassword.value = '';
  if (profileConfirmPassword) profileConfirmPassword.value = '';
  if (profileAvatar) profileAvatar.value = '';

  if (currentUser) {
    updateHeaderUserInfo(currentUser);
    fillProfileView(currentUser);
    fillProfileForm(currentUser);
  }
};

async function safeJsonFetch(url, options = {}) {
  const res = await fetch(url, {
    cache: 'no-store',
    credentials: 'same-origin',
    ...options
  });

  const text = await res.text();

  try {
    return JSON.parse(text);
  } catch (error) {
    console.error('Invalid JSON:', url, text);
    throw new Error(`Invalid JSON response from ${url}`);
  }
}

async function loadSession(requiredRole = null) {
  try {
    const result = await safeJsonFetch('php/session_me.php');

    SYSTEM_DEFAULT_AVATAR = stripCache(result.default_avatar || '');

    if (!result.logged_in) {
      window.location.href = 'login.html';
      return null;
    }

    if (requiredRole && result.user.role !== requiredRole) {
      window.location.href = 'login.html';
      return null;
    }

    currentUser = result.user;

    updateHeaderUserInfo(currentUser);
    fillProfileView(currentUser);
    fillProfileForm(currentUser);
    bindProfileForm();
    bindProfileAvatarPreview();
    initThemeOnLoginSession();
    bindThemeToggle();
    bindLogoutButton();

    return currentUser;
  } catch (error) {
    console.error('loadSession error:', error);
    window.location.href = 'login.html';
    return null;
  }
}

function updateHeaderUserInfo(user) {
  const userGreeting = document.getElementById('userGreeting');
  const headerAvatar = document.getElementById('headerAvatar');
  const profileAvatarPreview = document.getElementById('profileAvatarPreview');

  const avatarPath = stripCache(user.avatar || '') || SYSTEM_DEFAULT_AVATAR;

  if (userGreeting) {
    userGreeting.textContent = `${user.full_name} (${user.role})`;
  }

  if (headerAvatar) {
    headerAvatar.src = bustCache(avatarPath);
    headerAvatar.onerror = () => {
      headerAvatar.onerror = null;
      headerAvatar.src = bustCache(SYSTEM_DEFAULT_AVATAR);
    };
  }

  if (profileAvatarPreview) {
    profileAvatarPreview.src = bustCache(avatarPath);
    profileAvatarPreview.onerror = () => {
      profileAvatarPreview.onerror = null;
      profileAvatarPreview.src = bustCache(SYSTEM_DEFAULT_AVATAR);
    };
  }
}

function fillProfileView(user) {
  const viewFullName = document.getElementById('viewFullName');
  const viewEmail = document.getElementById('viewEmail');
  const viewPhone = document.getElementById('viewPhone');

  if (viewFullName) viewFullName.textContent = user.full_name || '-';
  if (viewEmail) viewEmail.textContent = user.email || '-';
  if (viewPhone) viewPhone.textContent = user.phone || '-';
}

function fillProfileForm(user) {
  const profileFullName = document.getElementById('profileFullName');
  const profileEmail = document.getElementById('profileEmail');
  const profilePhone = document.getElementById('profilePhone');

  if (profileFullName) profileFullName.value = user.full_name || '';
  if (profileEmail) profileEmail.value = user.email || '';
  if (profilePhone) profilePhone.value = user.phone || '';
}

function bindProfileAvatarPreview() {
  const avatarInput = document.getElementById('profileAvatar');
  const preview = document.getElementById('profileAvatarPreview');

  if (!avatarInput || !preview || avatarInput.dataset.bound === '1') return;

  avatarInput.dataset.bound = '1';

  avatarInput.addEventListener('change', () => {
    const file = avatarInput.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
      preview.src = e.target.result;
    };
    reader.readAsDataURL(file);
  });
}

function bindProfileForm() {
  const profileForm = document.getElementById('profileForm');
  if (!profileForm || profileForm.dataset.bound === '1') return;

  profileForm.dataset.bound = '1';

  profileForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    try {
      const formData = new FormData(profileForm);

      const currentPassword = String(formData.get('current_password') || '').trim();
      const password = String(formData.get('password') || '');
      const confirmPassword = String(formData.get('confirm_password') || '');

      if (currentPassword === '') {
        alert('Please enter current password.');
        return;
      }

      if (password !== '' || confirmPassword !== '') {
        if (password !== confirmPassword) {
          alert('Passwords do not match.');
          return;
        }

        if (password.length < 6) {
          alert('Password must be at least 6 characters.');
          return;
        }
      }

      const result = await safeJsonFetch('php/update_profile.php', {
        method: 'POST',
        body: formData
      });

      alert(result.message || 'Unknown response.');

      if (result.success) {
        await reloadProfile();
        window.hideProfileEditMode();
      }
    } catch (error) {
      console.error('Profile update error:', error);
      alert('Failed to update profile.');
    }
  });
}

async function reloadProfile() {
  try {
    const result = await safeJsonFetch('php/session_me.php');

    SYSTEM_DEFAULT_AVATAR = stripCache(result.default_avatar || SYSTEM_DEFAULT_AVATAR);

    if (result.logged_in && result.user) {
      currentUser = result.user;
      updateHeaderUserInfo(currentUser);
      fillProfileView(currentUser);
      fillProfileForm(currentUser);
    }
  } catch (error) {
    console.error('reloadProfile error:', error);
  }
}

async function handleLogout(e) {
  if (e) e.preventDefault();

  try {
    resetThemeToDefault();

    const result = await safeJsonFetch('php/logout.php', {
      method: 'POST',
      headers: { 'Accept': 'application/json' }
    });

    if (result.success) {
      sessionStorage.removeItem('theme_initialized');
      localStorage.setItem('site_theme', 'default');
      window.location.replace('login.html');
    } else {
      alert(result.message || 'Logout failed.');
    }
  } catch (error) {
    console.error('logout error:', error);
    alert('Logout failed.');
  }
}

function bindLogoutButton() {
  const logoutBtn = document.getElementById('logoutBtn');
  if (!logoutBtn || logoutBtn.dataset.bound === '1') return;

  logoutBtn.dataset.bound = '1';
  logoutBtn.addEventListener('click', handleLogout);
}

document.addEventListener('DOMContentLoaded', () => {
  bindThemeToggle();
  bindLogoutButton();
});