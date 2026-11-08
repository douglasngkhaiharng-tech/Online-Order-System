document.addEventListener('DOMContentLoaded', () => {
  const adminHistoryBody = document.getElementById('adminHistoryBody');
  const detailModal = document.getElementById('detailModal');
  const modalBody = document.getElementById('modalBody');
  const tabButtons = document.querySelectorAll('.tab-btn');

  if (!adminHistoryBody) return;

  function esc(text) {
    return String(text ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function toNumber(value) {
    const num = Number(value);
    return Number.isFinite(num) ? num : 0;
  }

  function normalizeRows(data) {
    if (Array.isArray(data)) return data;
    if (data && Array.isArray(data.rows)) return data.rows;
    return [];
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

  async function loadAdminOrderHistoryFixed() {
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
            <td>RM ${toNumber(order.total_amount).toFixed(2)}</td>
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
                    <td>RM ${toNumber(item.product_price).toFixed(2)}</td>
                    <td>${item.quantity}</td>
                    <td>RM ${toNumber(item.subtotal).toFixed(2)}</td>
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
        <p><strong>Total Amount:</strong> RM ${toNumber(data.total_amount).toFixed(2)}</p>
        <p><strong>Ordered At:</strong> ${esc(data.ordered_at ?? '-')}</p>
        ${itemsHtml}
      `;

      if (detailModal) {
        detailModal.classList.remove('hidden');
      }
    } catch (error) {
      console.error(error);
      alert('Failed to load order details.');
    }
  };

  tabButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      if (btn.dataset.tab === 'orderHistoryTab') {
        setTimeout(() => {
          loadAdminOrderHistoryFixed();
        }, 120);
      }
    });
  });

  const activeHistoryTab = document.querySelector('.tab-btn.active[data-tab="orderHistoryTab"]');
  if (activeHistoryTab) {
    loadAdminOrderHistoryFixed();
  }
});