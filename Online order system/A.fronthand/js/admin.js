document.addEventListener('DOMContentLoaded', async () => {
  const pendingPaymentBody = document.getElementById('pendingPaymentBody');
  const adminProductTableBody = document.getElementById('adminProductTableBody');
  const adminProductList = document.getElementById('adminProductList');
  const adminStatusBody = document.getElementById('adminStatusBody');
  const adminHistoryBody = document.getElementById('adminHistoryBody');
  const adminUserBody = document.getElementById('adminUserBody');
  const adminBlacklistBody = document.getElementById('adminBlacklistBody');
  const refundRequestBody = document.getElementById('refundRequestBody');

  const productForm = document.getElementById('productForm');
  const resetProductFormBtn = document.getElementById('resetProductFormBtn');
  const productMediaInput = document.getElementById('productMedia');
  const mediaPreviewWrap = document.getElementById('mediaPreviewWrap');

  const detailModal = document.getElementById('detailModal');
  const modalBody = document.getElementById('modalBody');
  const closeModalBtn = document.getElementById('closeModalBtn');

  const adminSearchKeyword = document.getElementById('adminSearchKeyword');
  const adminSearchBtn = document.getElementById('adminSearchBtn');
  const adminSearchResetBtn = document.getElementById('adminSearchResetBtn');
  const adminSearchBody = document.getElementById('adminSearchBody');

  const tabButtons = document.querySelectorAll('.tab-btn');
  const tabContents = document.querySelectorAll('.tab-content');

  function esc(text) {
    return String(text ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function normalizeRows(data) {
    if (Array.isArray(data)) return data;
    if (data && Array.isArray(data.rows)) return data.rows;
    return [];
  }

  function bustCache(path) {
    const clean = String(path || '').split('?')[0].trim();
    const fallback = window.DEFAULT_AVATAR_PATH || 'uploads/default.png';
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
    } catch {
      console.error('Invalid JSON from:', url, text);
      throw new Error(`Invalid JSON response from ${url}`);
    }
  }

  let modalMediaList = [];
  let modalMediaIndex = 0;
  let modalMediaTitle = 'Media';

  tabButtons.forEach((btn) => {
    btn.addEventListener('click', async () => {
      tabButtons.forEach((b) => b.classList.remove('active'));
      tabContents.forEach((c) => c.classList.remove('active'));
      btn.classList.add('active');

      const target = document.getElementById(btn.dataset.tab);
      if (target) target.classList.add('active');

      if (btn.dataset.tab === 'userListTab') await loadAdminUsers();
      if (btn.dataset.tab === 'blacklistTab') await loadAdminBlacklist();
    });
  });

  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', () => detailModal.classList.add('hidden'));
  }

  function renderSingleMediaViewer(extraHtml = '') {
    if (!modalBody) return;

    if (!Array.isArray(modalMediaList) || modalMediaList.length === 0) {
      modalBody.innerHTML = `
        <h3 style="margin-bottom:10px;">${esc(modalMediaTitle)}</h3>
        ${extraHtml}
        <p>No media found.</p>
      `;
      return;
    }

    const current = modalMediaList[modalMediaIndex];
    let mediaHtml = '';

    if (current.media_type === 'image') {
      mediaHtml = `<img src="${bustCache(current.media_path)}" style="max-width:100%;max-height:420px;display:block;margin:0 auto;border-radius:8px;">`;
    } else {
      mediaHtml = `
        <video controls style="max-width:100%;max-height:420px;display:block;margin:0 auto;border-radius:8px;">
          <source src="${bustCache(current.media_path)}">
        </video>
      `;
    }

    const prevDisabled = modalMediaIndex === 0;
    const nextDisabled = modalMediaIndex === modalMediaList.length - 1;

    modalBody.innerHTML = `
      <h3 style="margin-bottom:12px;">${esc(modalMediaTitle)}</h3>
      ${extraHtml}
      <div style="text-align:center;margin-top:12px;">${mediaHtml}</div>
      <div style="margin-top:14px;display:flex;justify-content:center;align-items:center;gap:12px;flex-wrap:wrap;">
        <button type="button" id="mediaPrevBtn" ${prevDisabled ? 'disabled' : ''}>Previous</button>
        <span style="font-weight:600;">${modalMediaIndex + 1} / ${modalMediaList.length}</span>
        <button type="button" id="mediaNextBtn" ${nextDisabled ? 'disabled' : ''}>Next</button>
      </div>
    `;

    const prevBtn = document.getElementById('mediaPrevBtn');
    const nextBtn = document.getElementById('mediaNextBtn');

    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        if (modalMediaIndex > 0) {
          modalMediaIndex--;
          renderSingleMediaViewer(extraHtml);
        }
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        if (modalMediaIndex < modalMediaList.length - 1) {
          modalMediaIndex++;
          renderSingleMediaViewer(extraHtml);
        }
      });
    }
  }

  async function loadRefundRequests() {
    if (!refundRequestBody) return;

    try {
      const data = await fetchJson('php/admin_get_refund_requests.php');
      const rows = normalizeRows(data);

      refundRequestBody.innerHTML = '';

      if (rows.length === 0) {
        refundRequestBody.innerHTML = `<tr><td colspan="8">No refund requests.</td></tr>`;
        return;
      }

      rows.forEach((order) => {
        refundRequestBody.innerHTML += `
          <tr>
            <td>${order.order_id}</td>
            <td>${order.customer_id}</td>
            <td>${order.total_items ?? '-'}</td>
            <td>RM ${parseFloat(order.total_amount || 0).toFixed(2)}</td>
            <td>${esc(order.refund_uploaded_at ?? '-')}</td>
            <td>${order.refund_proof_path ? `<a href="${bustCache(order.refund_proof_path)}" target="_blank">View Proof</a>` : '-'}</td>
            <td>
              <button class="action-btn approve-btn" onclick="window.refundAction(${order.order_id}, 'approve')">Approve</button>
              <button class="action-btn reject-btn" onclick="window.refundAction(${order.order_id}, 'reject')">Reject</button>
            </td>
            <td><button class="action-btn detail-btn" onclick="window.viewDetails(${order.order_id})">Details</button></td>
          </tr>
        `;
      });
    } catch (error) {
      console.error(error);
      refundRequestBody.innerHTML = `<tr><td colspan="8">Failed to load refund requests.</td></tr>`;
    }
  }

  window.refundAction = async function (orderId, action) {
    try {
      const formData = new FormData();
      formData.append('order_id', orderId);
      formData.append('action', action);

      const result = await fetchJson('php/admin_refund_action.php', {
        method: 'POST',
        body: formData
      });

      alert(result.message || 'Unknown response.');

      if (result.success) {
        await loadRefundRequests();
        await loadAdminOrderHistory();
      }
    } catch (error) {
      console.error(error);
      alert('Refund action failed.');
    }
  };

  if (productMediaInput && mediaPreviewWrap) {
    productMediaInput.addEventListener('change', () => {
      mediaPreviewWrap.innerHTML = '';
      const files = Array.from(productMediaInput.files || []);

      files.forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'media-preview-item';

        const label = document.createElement('div');
        label.style.marginTop = '6px';
        label.style.fontSize = '12px';
        label.style.color = '#6b7280';
        label.textContent = index === 0 ? 'First image = cover' : 'Extra media';

        if (file.type.startsWith('image/')) {
          const img = document.createElement('img');
          img.src = URL.createObjectURL(file);
          item.appendChild(img);
        } else if (file.type.startsWith('video/')) {
          const video = document.createElement('video');
          video.src = URL.createObjectURL(file);
          video.controls = true;
          item.appendChild(video);
        } else {
          const txt = document.createElement('div');
          txt.textContent = file.name;
          item.appendChild(txt);
        }

        item.appendChild(label);
        mediaPreviewWrap.appendChild(item);
      });
    });
  }

  if (productForm) {
    productForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      try {
        const formData = new FormData();
        formData.append('product_id', document.getElementById('productId')?.value || '');
        formData.append('product_name', document.getElementById('productName')?.value || '');
        formData.append('product_price', document.getElementById('productPrice')?.value || '');
        formData.append('product_rating', document.getElementById('productRating')?.value || '');
        formData.append('product_stock', document.getElementById('productStock')?.value || '');
        formData.append('publish_date', document.getElementById('publishDate')?.value || '');
        formData.append('category', document.getElementById('productCategory')?.value || '');

        if (productMediaInput && productMediaInput.files.length > 0) {
          Array.from(productMediaInput.files).forEach((file) => {
            formData.append('media_files[]', file);
          });
        }

        const result = await fetchJson('php/admin_save_product.php', {
          method: 'POST',
          body: formData
        });

        alert(result.message || 'Unknown response.');

        if (result.success) {
          productForm.reset();
          if (mediaPreviewWrap) mediaPreviewWrap.innerHTML = '';
          const productId = document.getElementById('productId');
          if (productId) productId.value = '';
          await loadAdminProducts();
          await loadAdminOrderSystem();
        }
      } catch (error) {
        console.error(error);
        alert('Failed to save product.');
      }
    });
  }

  if (resetProductFormBtn) {
    resetProductFormBtn.addEventListener('click', () => {
      if (productForm) productForm.reset();
      if (mediaPreviewWrap) mediaPreviewWrap.innerHTML = '';
      const productId = document.getElementById('productId');
      if (productId) productId.value = '';
    });
  }

  async function loadPendingPayments() {
    if (!pendingPaymentBody) return;

    try {
      const data = await fetchJson('php/admin_get_pending_payments.php');
      const rows = normalizeRows(data);

      pendingPaymentBody.innerHTML = '';

      if (rows.length === 0) {
        pendingPaymentBody.innerHTML = `<tr><td colspan="9">No pending payments.</td></tr>`;
        return;
      }

      rows.forEach((order) => {
        pendingPaymentBody.innerHTML += `
          <tr>
            <td>${order.order_id}</td>
            <td>${order.customer_id}</td>
            <td>${esc(order.product_name ?? '-')}</td>
            <td>${order.quantity ?? '-'}</td>
            <td>RM ${parseFloat(order.total_amount || 0).toFixed(2)}</td>
            <td>${esc(order.payment_uploaded_at ?? '-')}</td>
            <td>${order.payment_proof_path ? `<a href="${bustCache(order.payment_proof_path)}" target="_blank">View Proof</a>` : '-'}</td>
            <td>
              <button class="action-btn approve-btn" onclick="window.paymentAction(${order.order_id}, 'approve')">Approve</button>
              <button class="action-btn reject-btn" onclick="window.paymentAction(${order.order_id}, 'reject')">Reject</button>
            </td>
            <td><button class="action-btn detail-btn" onclick="window.viewDetails(${order.order_id})">Details</button></td>
          </tr>
        `;
      });
    } catch (error) {
      console.error(error);
      pendingPaymentBody.innerHTML = `<tr><td colspan="9">Failed to load.</td></tr>`;
    }
  }

  window.paymentAction = async function (orderId, action) {
    try {
      const formData = new FormData();
      formData.append('order_id', orderId);
      formData.append('action', action);

      const result = await fetchJson('php/admin_payment_action.php', {
        method: 'POST',
        body: formData
      });

      alert(result.message || 'Unknown response.');

      if (result.success) {
        await loadPendingPayments();
        await loadAdminOrderStatus();
        await loadAdminOrderHistory();
      }
    } catch (error) {
      console.error(error);
      alert('Payment action failed.');
    }
  };

  async function loadAdminProducts() {
    if (!adminProductTableBody) return;

    try {
      const data = await fetchJson('php/admin_get_products.php');
      const rows = normalizeRows(data);

      adminProductTableBody.innerHTML = '';

      if (rows.length === 0) {
        adminProductTableBody.innerHTML = `<tr><td colspan="9">No products found.</td></tr>`;
        return;
      }

      rows.forEach((product) => {
        const safeProduct = encodeURIComponent(JSON.stringify(product));
        const cover = product.cover_image && product.cover_image !== ''
          ? bustCache(product.cover_image)
          : bustCache(window.DEFAULT_AVATAR_PATH || 'uploads/default.png');
        const safeName = String(product.product_name || 'Product').replace(/'/g, "\\'");

        adminProductTableBody.innerHTML += `
          <tr>
            <td>${product.product_id}</td>
            <td>
              <img
                src="${cover}"
                class="preview-image"
                alt="${esc(product.product_name)}"
                style="cursor:pointer"
                onclick="window.previewProductMedia(${product.product_id}, '${safeName}')"
                title="Click to view product media"
              >
            </td>
            <td>${esc(product.product_name)}</td>
            <td>RM ${parseFloat(product.product_price).toFixed(2)}</td>
            <td>${esc(product.category ?? '-')}</td>
            <td>${esc(product.product_rating)}</td>
            <td>${esc(product.product_stock)}</td>
            <td>${esc(product.publish_date)}</td>
            <td>
              <button class="action-btn edit-btn" onclick="window.editProductFromEncoded('${safeProduct}')">Edit</button>
              <button class="action-btn delete-btn" onclick="window.deleteProduct(${product.product_id})">Delete</button>
            </td>
          </tr>
        `;
      });
    } catch (error) {
      console.error(error);
      adminProductTableBody.innerHTML = `<tr><td colspan="9">Failed to load products.</td></tr>`;
    }
  }

  window.editProductFromEncoded = function (encodedProduct) {
    const product = JSON.parse(decodeURIComponent(encodedProduct));
    document.getElementById('productId').value = product.product_id;
    document.getElementById('productName').value = product.product_name;
    document.getElementById('productPrice').value = product.product_price;
    document.getElementById('productRating').value = product.product_rating;
    document.getElementById('productStock').value = product.product_stock;
    document.getElementById('publishDate').value = product.publish_date;
    document.getElementById('productCategory').value = product.category || '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  window.deleteProduct = async function (productId) {
    if (!confirm('Are you sure to delete this product?')) return;

    try {
      const formData = new FormData();
      formData.append('product_id', productId);

      const result = await fetchJson('php/admin_delete_product.php', {
        method: 'POST',
        body: formData
      });

      alert(result.message || 'Unknown response.');

      if (result.success) {
        await loadAdminProducts();
        await loadAdminOrderSystem();
      }
    } catch (error) {
      console.error(error);
      alert('Delete product failed.');
    }
  };

  async function loadAdminOrderSystem() {
    if (!adminProductList) return;

    try {
      const data = await fetchJson('php/get_products.php');
      const rows = normalizeRows(data);

      adminProductList.innerHTML = '';

      if (rows.length === 0) {
        adminProductList.innerHTML = `<p>No products available.</p>`;
        return;
      }

      rows.forEach((product) => {
        const cover = product.cover_image || product.image_path || window.DEFAULT_AVATAR_PATH || 'uploads/default.png';

        adminProductList.innerHTML += `
          <div class="product-card">
            <img src="${bustCache(cover)}" alt="${esc(product.product_name)}">
            <h3>${esc(product.product_name)}</h3>
            <p><strong>Publish Date:</strong> ${esc(product.publish_date)}</p>
            <p><strong>Price:</strong> RM ${parseFloat(product.product_price).toFixed(2)}</p>
            <p><strong>Category:</strong> ${esc(product.category ?? '-')}</p>
            <p><strong>Rating:</strong> ${esc(product.product_rating)}</p>
            <p><strong>Stock Left:</strong> ${esc(product.product_stock)}</p>
          </div>
        `;
      });
    } catch (error) {
      console.error(error);
      adminProductList.innerHTML = `<p>Failed to load products.</p>`;
    }
  }

  async function loadAdminOrderStatus() {
    if (!adminStatusBody) return;

    try {
      const data = await fetchJson('php/admin_get_orders.php');
      const rows = normalizeRows(data);

      adminStatusBody.innerHTML = '';

      if (rows.length === 0) {
        adminStatusBody.innerHTML = `<tr><td colspan="7">No unpaid or pending orders.</td></tr>`;
        return;
      }

      rows.forEach((order) => {
        adminStatusBody.innerHTML += `
          <tr>
            <td>${order.order_id}</td>
            <td>${order.customer_id}</td>
            <td>${esc(order.product_name ?? '-')}</td>
            <td>${order.quantity ?? '-'}</td>
            <td>${esc(order.status)}</td>
            <td>${esc(order.ordered_at)}</td>
            <td><button class="action-btn detail-btn" onclick="window.viewDetails(${order.order_id})">Details</button></td>
          </tr>
        `;
      });
    } catch (error) {
      console.error(error);
      adminStatusBody.innerHTML = `<tr><td colspan="7">Failed to load.</td></tr>`;
    }
  }

  async function loadAdminOrderHistory() {
    if (!adminHistoryBody) return;

    try {
      const data = await fetchJson('php/admin_get_history.php');
      const rows = normalizeRows(data);

      adminHistoryBody.innerHTML = '';

      if (!rows || rows.length === 0) {
        adminHistoryBody.innerHTML = `<tr><td colspan="6">No paid order history.</td></tr>`;
        return;
      }

      rows.forEach((order) => {
        adminHistoryBody.innerHTML += `
          <tr>
            <td>${order.order_id}</td>
            <td>${order.customer_id}</td>
            <td>${esc(order.payment_uploaded_at ?? '-')}</td>
            <td>${esc(order.approved_at ?? '-')}</td>
            <td>RM ${parseFloat(order.total_amount || 0).toFixed(2)}</td>
            <td>
              <button class="action-btn detail-btn" onclick="window.viewDetails(${order.order_id})">Details</button>
            </td>
          </tr>
        `;
      });
    } catch (error) {
      console.error(error);
      adminHistoryBody.innerHTML = `<tr><td colspan="6">Failed to load history.</td></tr>`;
    }
  }

  async function searchAdminOrders() {
    const keyword = adminSearchKeyword.value.trim();

    if (!keyword) {
      adminSearchBody.innerHTML = `<tr><td colspan="7">Please enter keyword.</td></tr>`;
      return;
    }

    try {
      const result = await fetchJson(`php/search_orders.php?scope=admin&keyword=${encodeURIComponent(keyword)}`);
      adminSearchBody.innerHTML = '';

      if (!result.success || !Array.isArray(result.rows) || result.rows.length === 0) {
        adminSearchBody.innerHTML = `<tr><td colspan="7">No result.</td></tr>`;
        return;
      }

      result.rows.forEach((order) => {
        adminSearchBody.innerHTML += `
          <tr>
            <td>${order.order_id}</td>
            <td>${esc(order.customer_name ?? '-')}</td>
            <td>${order.customer_id}</td>
            <td>RM ${parseFloat(order.total_amount || 0).toFixed(2)}</td>
            <td>${esc(order.status ?? '-')}</td>
            <td>${esc(order.ordered_at ?? '-')}</td>
            <td>
              <button class="action-btn detail-btn" onclick="window.viewDetails(${order.order_id})">Details</button>
            </td>
          </tr>
        `;
      });
    } catch (error) {
      console.error(error);
      adminSearchBody.innerHTML = `<tr><td colspan="7">Search failed.</td></tr>`;
    }
  }

  function resetAdminSearch() {
    adminSearchKeyword.value = '';
    adminSearchBody.innerHTML = `<tr><td colspan="7">Please enter keyword and click search.</td></tr>`;
  }

  async function loadAdminUsers() {
    if (!adminUserBody) return;

    try {
      const result = await fetchJson('php/admin_get_users.php');
      adminUserBody.innerHTML = '';

      if (!result.success || !Array.isArray(result.rows) || result.rows.length === 0) {
        adminUserBody.innerHTML = `<tr><td colspan="8">No users found.</td></tr>`;
        return;
      }

      result.rows.forEach((user) => {
        const avatar = user.avatar ? bustCache(user.avatar) : bustCache(window.DEFAULT_AVATAR_PATH || 'uploads/default.png');
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

  async function loadAdminBlacklist() {
    if (!adminBlacklistBody) return;

    try {
      const result = await fetchJson('php/admin_get_blacklist.php');
      adminBlacklistBody.innerHTML = '';

      if (!result.success || !Array.isArray(result.rows) || result.rows.length === 0) {
        adminBlacklistBody.innerHTML = `<tr><td colspan="7">No blacklist records.</td></tr>`;
        return;
      }

      result.rows.forEach((row) => {
        adminBlacklistBody.innerHTML += `
          <tr>
            <td>${row.blacklist_id}</td>
            <td>${row.user_id ?? '-'}</td>
            <td>${esc(row.full_name ?? '-')}</td>
            <td>${esc(row.email)}</td>
            <td>${esc(row.reason ?? '-')}</td>
            <td>${esc(row.blacklisted_at ?? '-')}</td>
            <td>
              <button class="action-btn detail-btn" onclick="window.unblacklistById(${row.blacklist_id})">Unblacklist</button>
            </td>
          </tr>
        `;
      });
    } catch (error) {
      console.error(error);
      adminBlacklistBody.innerHTML = `<tr><td colspan="7">Failed to load blacklist.</td></tr>`;
    }
  }

  window.loadAdminUsers = loadAdminUsers;
  window.loadAdminBlacklist = loadAdminBlacklist;

  window.blacklistUser = async function (userId) {
    const reason = prompt('Reason for blacklist:', '') || '';

    try {
      const body = new URLSearchParams({ user_id: userId, reason });
      const result = await fetchJson('php/admin_blacklist_user.php', {
        method: 'POST',
        body
      });

      alert(result.message || 'Done.');

      if (result.success) {
        await loadAdminUsers();
        await loadAdminBlacklist();
      }
    } catch (error) {
      console.error(error);
      alert('Blacklist failed.');
    }
  };

  window.unblacklistById = async function (blacklistId) {
    const note = prompt('Unblacklist note:', '') || '';

    try {
      const body = new URLSearchParams({ blacklist_id: blacklistId, note });
      const result = await fetchJson('php/admin_unblacklist_user.php', {
        method: 'POST',
        body
      });

      alert(result.message || 'Done.');

      if (result.success) {
        await loadAdminUsers();
        await loadAdminBlacklist();
      }
    } catch (error) {
      console.error(error);
      alert('Unblacklist failed.');
    }
  };

  window.unblacklistByUserEmail = async function (email) {
    try {
      const result = await fetchJson('php/admin_get_blacklist.php');

      if (!result.success || !Array.isArray(result.rows)) {
        alert('Failed to load blacklist records.');
        return;
      }

      const row = result.rows.find((item) => String(item.email).toLowerCase() === String(email).toLowerCase());

      if (!row) {
        alert('Blacklist record not found.');
        await loadAdminUsers();
        await loadAdminBlacklist();
        return;
      }

      await window.unblacklistById(row.blacklist_id);
    } catch (error) {
      console.error(error);
      alert('Unblacklist failed.');
    }
  };

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
        await loadAdminUsers();
        await loadAdminBlacklist();
      }
    } catch (error) {
      console.error(error);
      alert('Promote to admin failed.');
    }
  };

  window.viewDetails = async function (orderId) {
    try {
      const data = await fetchJson(`php/get_order_details.php?order_id=${orderId}`);

      if (data.success === false) {
        alert(data.message || 'Failed to load order details.');
        return;
      }

      let itemsHtml = '<p>No items found.</p>';

      if (Array.isArray(data.items) && data.items.length > 0) {
        itemsHtml = `
          <div class="table-wrap" style="margin-top:12px;">
            <table>
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Price</th>
                  <th>Qty</th>
                  <th>Subtotal</th>
                </tr>
              </thead>
              <tbody>
                ${data.items.map(item => `
                  <tr>
                    <td>${esc(item.product_name)}</td>
                    <td>RM ${parseFloat(item.product_price || 0).toFixed(2)}</td>
                    <td>${item.quantity}</td>
                    <td>RM ${parseFloat(item.subtotal || 0).toFixed(2)}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        `;
      }

      modalBody.innerHTML = `
        <h3 style="margin-bottom:12px;">Order Details</h3>
        <p><strong>Order ID:</strong> ${data.order_id}</p>
        <p><strong>Customer ID:</strong> ${data.customer_id}</p>
        <p><strong>Status:</strong> ${esc(data.status ?? '-')}</p>
        <p><strong>Total Items:</strong> ${data.total_items ?? 0}</p>
        <p><strong>Total Amount:</strong> RM ${parseFloat(data.total_amount || 0).toFixed(2)}</p>
        <p><strong>Ordered At:</strong> ${esc(data.ordered_at ?? '-')}</p>
        ${itemsHtml}
      `;

      detailModal.classList.remove('hidden');
    } catch (error) {
      console.error(error);
      alert('Failed to load order details.');
    }
  };

  window.previewProductMedia = async function (productId, productName = 'Product') {
    try {
      const data = await fetchJson(`php/get_product_media.php?product_id=${productId}`);

      if (data.success === false) {
        alert(data.message || 'Failed to load product media.');
        return;
      }

      modalMediaTitle = productName;
      modalMediaList = normalizeRows(data);
      modalMediaIndex = 0;

      renderSingleMediaViewer('');
      detailModal.classList.remove('hidden');
    } catch (error) {
      console.error(error);
      alert('Failed to load product media.');
    }
  };

  if (adminSearchBtn) adminSearchBtn.addEventListener('click', searchAdminOrders);
  if (adminSearchResetBtn) adminSearchResetBtn.addEventListener('click', resetAdminSearch);

  await loadSession('admin');

  await loadPendingPayments();
  await loadRefundRequests();
  await loadAdminProducts();
  await loadAdminOrderSystem();
  await loadAdminOrderStatus();
  await loadAdminOrderHistory();
});