document.addEventListener('DOMContentLoaded', () => {
  const adminUserBody = document.getElementById('adminUserBody');
  const tabButtons = document.querySelectorAll('.tab-btn');

  if (!adminUserBody) return;

  function esc(text) {
    return String(text ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function bustCache(path) {
    const clean = String(path || '').split('?')[0].trim();
    const fallback = 'uploads/default.png';
    const finalPath = clean || fallback;
    const separator = finalPath.includes('?') ? '&' : '?';
    return `${finalPath}${separator}t=${Date.now()}`;
  }

  async function fetchJson(url, options = {}) {
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

  async function renderAdminUsersEnhanced() {
    try {
      const result = await fetchJson('php/admin_get_users.php');
      adminUserBody.innerHTML = '';

      if (!result.success || !Array.isArray(result.rows) || result.rows.length === 0) {
        adminUserBody.innerHTML = `<tr><td colspan="8">No users found.</td></tr>`;
        return;
      }

      result.rows.forEach((user) => {
        const avatar = user.avatar ? bustCache(user.avatar) : bustCache('uploads/default.png');
        const blacklisted = Number(user.is_blacklisted) === 1;
        const isManagerAdmin = Number(user.is_manager_admin || 0) === 1;
        const roleDisplay = esc(user.role_display || user.role || '-');

        let actionHtml = '-';

        if (String(user.role).toLowerCase() === 'customer') {
          if (blacklisted) {
            actionHtml = `
              <button class="action-btn detail-btn" onclick="window.unblacklistById(${user.blacklist_id})">Unblacklist</button>
            `;
          } else {
            actionHtml = `
              <button class="action-btn approve-btn" onclick="window.promoteCustomerToAdmin(${user.user_id})">Promote to Admin</button>
              <button class="action-btn reject-btn" onclick="window.blacklistUser(${user.user_id})">Blacklist</button>
            `;
          }
        }

        if (isManagerAdmin) {
          actionHtml = '-';
        }

        adminUserBody.innerHTML += `
          <tr>
            <td>${user.user_id}</td>
            <td><img src="${avatar}" class="preview-image" alt="avatar"></td>
            <td>${roleDisplay}</td>
            <td>${esc(user.full_name)}</td>
            <td>${esc(user.email)}</td>
            <td>${esc(user.phone)}</td>
            <td>${blacklisted ? 'Blacklisted' : 'Active'}</td>
            <td>${actionHtml}</td>
          </tr>
        `;
      });
    } catch (error) {
      console.error(error);
      adminUserBody.innerHTML = `<tr><td colspan="8">Failed to load users.</td></tr>`;
    }
  }

  window.promoteCustomerToAdmin = async function (userId) {
    const ok = confirm('Promote this customer to admin? Their avatar will switch to the admin default avatar.');
    if (!ok) return;

    try {
      const body = new URLSearchParams({ user_id: userId });

      const result = await fetchJson('php/promote_customer_to_admin.php', {
        method: 'POST',
        body
      });

      alert(result.message || 'Done.');

      if (result.success) {
        await renderAdminUsersEnhanced();
        if (typeof window.loadAdminBlacklist === 'function') {
          await window.loadAdminBlacklist();
        }
      }
    } catch (error) {
      console.error(error);
      alert('Promote to admin failed.');
    }
  };

  window.refreshAdminUserListEnhanced = renderAdminUsersEnhanced;

  tabButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      if (btn.dataset.tab === 'userListTab') {
        setTimeout(() => {
          renderAdminUsersEnhanced();
        }, 120);
      }
    });
  });

  const activeUserTab = document.querySelector('.tab-btn.active[data-tab="userListTab"]');
  if (activeUserTab) {
    renderAdminUsersEnhanced();
  }
});