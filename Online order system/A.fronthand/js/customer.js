document.addEventListener('DOMContentLoaded', async () => {
  const productList = document.getElementById('productList');
  const topProductsList = document.getElementById('topProductsList');
  const wishlistList = document.getElementById('wishlistList');

  const cartTableBody = document.getElementById('cartTableBody');
  const cartTotalItems = document.getElementById('cartTotalItems');
  const cartTotalAmount = document.getElementById('cartTotalAmount');
  const checkoutCartBtn = document.getElementById('checkoutCartBtn');

  const statusTableBody = document.getElementById('statusTableBody');
  const historyTableBody = document.getElementById('historyTableBody');
  const customerSearchKeyword = document.getElementById('customerSearchKeyword');
  const customerSearchBtn = document.getElementById('customerSearchBtn');
  const customerSearchResetBtn = document.getElementById('customerSearchResetBtn');
  const customerSearchBody = document.getElementById('customerSearchBody');

  const categoryFilter = document.getElementById('categoryFilter');
  const sortFilter = document.getElementById('sortFilter');
  const reloadProductsBtn = document.getElementById('reloadProductsBtn');

  const detailModal = document.getElementById('detailModal');
  const modalBody = document.getElementById('modalBody');
  const closeModalBtn = document.getElementById('closeModalBtn');

  const viewFullName = document.getElementById('viewFullName');
  const viewEmail = document.getElementById('viewEmail');
  const viewPhone = document.getElementById('viewPhone');
  const viewAddress = document.getElementById('viewAddress');

  const profileForm = document.getElementById('profileForm');
  const profileViewBox = document.getElementById('profileViewBox');

  const profileFullName = document.getElementById('profileFullName');
  const profileEmail = document.getElementById('profileEmail');
  const profilePhone = document.getElementById('profilePhone');
  const profileAddress = document.getElementById('profileAddress');

  const profileAvatarPreview = document.getElementById('profileAvatarPreview');
  const headerAvatar = document.getElementById('headerAvatar');

  const tabButtons = document.querySelectorAll('.tab-btn');
  const tabContents = document.querySelectorAll('.tab-content');

  let allProducts = [];
  let modalMediaList = [];
  let modalMediaIndex = 0;
  let modalMediaTitle = 'Media';

  let paypalClientId = '';
  let paypalCurrency = 'MYR';
  let paypalSdkReady = false;

  function escapeHtml(text) {
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

  function toNumber(value) {
    const num = Number(value);
    return Number.isFinite(num) ? num : 0;
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

  function showProfileEditMode() {
    if (profileViewBox) profileViewBox.style.display = 'none';
    if (profileForm) profileForm.style.display = 'block';
  }

  function hideProfileEditMode() {
    if (profileForm) profileForm.style.display = 'none';
    if (profileViewBox) profileViewBox.style.display = 'block';
  }

  window.showProfileEditMode = showProfileEditMode;
  window.hideProfileEditMode = hideProfileEditMode;

  function renderStars(avg) {
    const rounded = Math.round(Number(avg || 0));
    let html = '';
    for (let i = 1; i <= 5; i++) {
      html += i <= rounded ? '★' : '☆';
    }
    return html;
  }

  async function loadPayPalClientConfig() {
    const result = await fetchJson('php/paypal_client_config.php');
    if (!result.success || !result.client_id) {
      throw new Error(result.message || 'PayPal client config not found.');
    }

    paypalClientId = result.client_id;
    paypalCurrency = result.currency || 'MYR';
  }

  async function loadProfile() {
    try {
      const data = await fetchJson('php/session_me.php');

      if (!data.logged_in || !data.user) return;

      const user = data.user;

      if (viewFullName) viewFullName.textContent = user.full_name || '-';
      if (viewEmail) viewEmail.textContent = user.email || '-';
      if (viewPhone) viewPhone.textContent = user.phone || '-';
      if (viewAddress) viewAddress.textContent = user.address || '-';

      if (profileFullName) profileFullName.value = user.full_name || '';
      if (profileEmail) profileEmail.value = user.email || '';
      if (profilePhone) profilePhone.value = user.phone || '';
      if (profileAddress) profileAddress.value = user.address || '';

      const avatarPath = user.avatar ? bustCache(user.avatar) : bustCache(data.default_avatar || 'uploads/default.png');

      if (profileAvatarPreview) profileAvatarPreview.src = avatarPath;
      if (headerAvatar) headerAvatar.src = avatarPath;

      if (profileForm && !profileForm.dataset.bound) {
        profileForm.dataset.bound = '1';

        profileForm.addEventListener('submit', async (e) => {
          e.preventDefault();

          const formData = new FormData(profileForm);

          try {
            const result = await fetchJson('php/update_profile.php', {
              method: 'POST',
              body: formData
            });

            alert(result.message || 'Updated');

            if (result.success) {
              hideProfileEditMode();
              await loadProfile();
            }
          } catch (err) {
            console.error(err);
            alert('Update failed');
          }
        });
      }
    } catch (error) {
      console.error('loadProfile error:', error);
    }
  }

  async function loadPayPalSdk() {
    if (paypalSdkReady && window.paypal) return;

    if (!paypalClientId) {
      await loadPayPalClientConfig();
    }

    await new Promise((resolve, reject) => {
      const existing = document.querySelector('script[data-paypal-sdk="1"]');
      if (existing && window.paypal) {
        paypalSdkReady = true;
        resolve();
        return;
      }

      const script = document.createElement('script');
      script.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(paypalClientId)}&currency=${encodeURIComponent(paypalCurrency)}&intent=capture`;
      script.async = true;
      script.dataset.paypalSdk = '1';

      script.onload = () => {
        paypalSdkReady = true;
        resolve();
      };

      script.onerror = () => reject(new Error('Failed to load PayPal SDK.'));
      document.head.appendChild(script);
    });
  }

  function buildCategoryOptions(products) {
    if (!categoryFilter) return;

    const currentValue = categoryFilter.value || 'ALL';
    const categories = [...new Set(
      products
        .map((p) => String(p.category || '').trim())
        .filter(Boolean)
    )];

    categoryFilter.innerHTML = `<option value="ALL">All Categories</option>`;

    categories.forEach((cat) => {
      categoryFilter.innerHTML += `<option value="${escapeHtml(cat)}">${escapeHtml(cat)}</option>`;
    });

    const exists = [...categoryFilter.options].some(opt => opt.value === currentValue);
    categoryFilter.value = exists ? currentValue : 'ALL';
  }

  function getSortedFilteredProducts() {
    let products = [...allProducts];

    const selectedCategory = categoryFilter ? categoryFilter.value : 'ALL';
    const sortValue = sortFilter ? sortFilter.value : 'default';

    if (selectedCategory !== 'ALL') {
      products = products.filter((p) => String(p.category || '') === selectedCategory);
    }

    if (sortValue === 'price_asc') {
      products.sort((a, b) => toNumber(a.product_price) - toNumber(b.product_price));
    } else if (sortValue === 'price_desc') {
      products.sort((a, b) => toNumber(b.product_price) - toNumber(a.product_price));
    } else if (sortValue === 'date_desc') {
      products.sort((a, b) => new Date(b.publish_date || '1970-01-01') - new Date(a.publish_date || '1970-01-01'));
    } else {
      products.sort((a, b) => Number(b.product_id || 0) - Number(a.product_id || 0));
    }

    return products;
  }

  async function loadReviews(productId) {
    try {
      const data = await fetchJson(`php/get_reviews.php?product_id=${productId}`);
      const rows = normalizeRows(data);

      if (!rows || rows.length === 0) {
        return '<div class="comment-box"><p>No reviews yet.</p></div>';
      }

      let html = '<div class="comment-box"><strong>Latest Reviews</strong>';

      rows.slice(0, 3).forEach((r) => {
        const rating = Math.max(1, Math.min(5, Number(r.rating || 0)));
        const reviewer = r.full_name ? `<strong>${escapeHtml(r.full_name)}</strong>: ` : '';
        html += `<p>${reviewer}${'★'.repeat(rating)}${'☆'.repeat(5 - rating)} - ${escapeHtml(r.comment)}</p>`;
      });

      html += '</div>';
      return html;
    } catch (error) {
      console.error(error);
      return '<div class="comment-box"><p>Failed to load reviews.</p></div>';
    }
  }

  async function renderProducts(container, products, mode = 'products', sectionKey = 'all') {
    if (!container) return;

    if (!Array.isArray(products) || products.length === 0) {
      container.innerHTML = `<p>No products found.</p>`;
      return;
    }

    let html = '';

    for (let index = 0; index < products.length; index++) {
      const product = products[index];
      const reviewsHtml = await loadReviews(product.product_id);
      const cover = product.cover_image ? bustCache(product.cover_image) : bustCache('uploads/default.png');
      const stock = Number(product.product_stock || 0);
      const cardKey = `${sectionKey}-${product.product_id}-${index}`;

      html += `
        <div class="product-card">
          <img src="${cover}" alt="${escapeHtml(product.product_name)}">
          <h3>${escapeHtml(product.product_name)}</h3>
          <p><strong>Category:</strong> ${escapeHtml(product.category || '-')}</p>
          <p><strong>Publish Date:</strong> ${escapeHtml(product.publish_date || '-')}</p>
          <p><strong>Price:</strong> RM ${toNumber(product.product_price).toFixed(2)}</p>
          <p><strong>Product Rating:</strong> ${escapeHtml(product.product_rating || '-')}</p>
          <p><strong>User Stars:</strong> ${renderStars(product.avg_user_rating || 0)} (${escapeHtml(product.review_count || 0)})</p>
          <p><strong>Stock Left:</strong> ${stock}</p>

          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
            <input type="number" min="1" max="${stock}" value="1" id="qty-${cardKey}" style="width:80px;">
            <button class="btn-buy" onclick="window.addToCart(${Number(product.product_id)}, '${cardKey}')">Add to Cart</button>
            ${mode === 'wishlist'
              ? `<button class="unsave-btn" onclick="window.removeFromWishlist(${Number(product.product_id)})">Unsave</button>`
              : `<button class="wishlist-btn" onclick="window.addToWishlist(${Number(product.product_id)})">❤️ Save</button>`
            }
          </div>

          <div style="margin-top:10px;">
            <select id="rating-${cardKey}">
              <option value="5">5⭐</option>
              <option value="4">4⭐</option>
              <option value="3">3⭐</option>
              <option value="2">2⭐</option>
              <option value="1">1⭐</option>
            </select>
            <input id="comment-${cardKey}" placeholder="Write review" style="width:100%;margin-top:8px;">
            <button style="margin-top:8px;" onclick="window.submitReview(${Number(product.product_id)}, '${cardKey}')">Submit Review</button>
          </div>

          ${reviewsHtml}
        </div>
      `;
    }

    container.innerHTML = html;
  }

  async function refreshProductArea() {
    const products = getSortedFilteredProducts();
    await renderProducts(productList, products, 'products', 'all');
  }

  async function loadAllProducts() {
    if (productList) productList.innerHTML = `<p>Loading...</p>`;

    const data = await fetchJson('php/get_products.php');

    if (!data.success) {
      if (productList) productList.innerHTML = `<p>${escapeHtml(data.message || 'Failed to load products.')}</p>`;
      allProducts = [];
      buildCategoryOptions([]);
      return;
    }

    allProducts = normalizeRows(data);
    buildCategoryOptions(allProducts);
    await refreshProductArea();
  }

  async function loadTopProducts() {
    if (topProductsList) topProductsList.innerHTML = `<p>Loading...</p>`;

    try {
      const data = await fetchJson('php/get_top_products.php');

      if (!data.success) {
        topProductsList.innerHTML = `<p>${escapeHtml(data.message || 'Failed to load top products.')}</p>`;
        return;
      }

      await renderProducts(topProductsList, normalizeRows(data), 'products', 'top');
    } catch (error) {
      console.error(error);
      if (topProductsList) topProductsList.innerHTML = `<p>Failed to load top products.</p>`;
    }
  }

  async function loadWishlist() {
    try {
      const data = await fetchJson('php/get_wishlist.php');
      await renderProducts(wishlistList, normalizeRows(data), 'wishlist', 'wishlist');
    } catch (error) {
      console.error(error);
      if (wishlistList) wishlistList.innerHTML = `<p>Failed to load wishlist.</p>`;
    }
  }

  async function loadCart() {
    try {
      const data = await fetchJson('php/get_cart.php');
      const rows = normalizeRows(data);

      if (!cartTableBody) return;
      cartTableBody.innerHTML = '';

      if (!rows || rows.length === 0) {
        cartTableBody.innerHTML = `<tr><td colspan="6">Your cart is empty.</td></tr>`;
        if (cartTotalItems) cartTotalItems.textContent = '0';
        if (cartTotalAmount) cartTotalAmount.textContent = '0.00';
        return;
      }

      rows.forEach((row) => {
        cartTableBody.innerHTML += `
          <tr>
            <td>${row.cart_id}</td>
            <td>${escapeHtml(row.product_name)}</td>
            <td>RM ${toNumber(row.product_price).toFixed(2)}</td>
            <td>
              <input type="number" min="1" max="${row.product_stock}" value="${row.quantity}" id="cartQty-${row.cart_id}" style="width:80px;">
            </td>
            <td>RM ${toNumber(row.subtotal).toFixed(2)}</td>
            <td>
              <button type="button" onclick="window.updateCartQuantity(${row.cart_id})">Update</button>
              <button type="button" onclick="window.removeCartItem(${row.cart_id})">Remove</button>
            </td>
          </tr>
        `;
      });

      if (cartTotalItems) cartTotalItems.textContent = String(data.summary?.total_items ?? 0);
      if (cartTotalAmount) cartTotalAmount.textContent = String(data.summary?.total_amount ?? '0.00');
    } catch (error) {
      console.error(error);
      if (cartTableBody) {
        cartTableBody.innerHTML = `<tr><td colspan="6">Failed to load cart.</td></tr>`;
      }
    }
  }

  async function searchOrders() {
    const keyword = customerSearchKeyword.value.trim();

    if (!keyword) {
      customerSearchBody.innerHTML = `<tr><td colspan="6">Enter Order ID / Customer ID</td></tr>`;
      return;
    }

    try {
      const data = await fetchJson(`php/search_orders.php?keyword=${encodeURIComponent(keyword)}&scope=customer`);
      customerSearchBody.innerHTML = '';

      if (!data.success || !Array.isArray(data.rows) || data.rows.length === 0) {
        customerSearchBody.innerHTML = `<tr><td colspan="6">No result</td></tr>`;
        return;
      }

      data.rows.forEach((row) => {
        customerSearchBody.innerHTML += `
          <tr>
            <td>${row.order_id}</td>
            <td>${row.total_items}</td>
            <td>RM ${toNumber(row.total_amount).toFixed(2)}</td>
            <td>${escapeHtml(row.status)}</td>
            <td>${escapeHtml(row.ordered_at ?? '-')}</td>
            <td><button type="button" onclick="window.viewDetails(${row.order_id})">Details</button></td>
          </tr>
        `;
      });
    } catch (error) {
      console.error(error);
      customerSearchBody.innerHTML = `<tr><td colspan="6">Search failed</td></tr>`;
    }
  }

  async function renderPayPalButtonsForStatusRows(rows) {
    try {
      await loadPayPalSdk();
    } catch (error) {
      console.error(error);
      return;
    }

    rows.forEach((order) => {
      if (order.status !== 'unpaid') return;

      const container = document.getElementById(`paypal-button-container-${order.order_id}`);
      if (!container || container.dataset.rendered === '1') return;

      container.dataset.rendered = '1';

      window.paypal.Buttons({
        style: {
          layout: 'vertical',
          shape: 'rect',
          label: 'paypal'
        },

        createOrder: async () => {
          const result = await fetchJson('php/create_paypal_order.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              local_order_id: order.order_id
            })
          });

          if (!result.success || !result.paypal_order_id) {
            throw new Error(result.message || 'Failed to create PayPal order.');
          }

          return result.paypal_order_id;
        },

        onApprove: async (data) => {
          const result = await fetchJson('php/capture_paypal_order.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              local_order_id: order.order_id,
              paypal_order_id: data.orderID
            })
          });

          alert(result.message || 'Payment completed.');

          if (result.success) {
            await loadStatus();
            await loadHistory();
            await loadAllProducts();
            await loadTopProducts();
          }
        },

        onError: (err) => {
          console.error('PayPal error:', err);
          alert('PayPal payment failed.');
        },

        onCancel: () => {
          alert('PayPal payment cancelled.');
        }
      }).render(`#paypal-button-container-${order.order_id}`);
    });
  }

  async function loadStatus() {
    try {
      const data = await fetchJson('php/get_customer_status.php');
      const rows = normalizeRows(data);

      statusTableBody.innerHTML = '';

      if (rows.length === 0) {
        statusTableBody.innerHTML = `<tr><td colspan="8">No orders</td></tr>`;
        return;
      }

      rows.forEach((order) => {
        let paymentHtml = '';
        let action = '';

        if (order.status === 'unpaid') {
          paymentHtml = `
            <div class="paypal-cell">
              <div id="paypal-button-container-${order.order_id}" class="paypal-btn-wrap"></div>
            </div>
          `;
          action = `<button type="button" onclick="window.cancelOrder(${order.order_id})">Cancel</button>`;
        } else {
          paymentHtml = 'Paid via PayPal / Waiting';
          action = '-';
        }

        statusTableBody.innerHTML += `
          <tr>
            <td>${order.order_id}</td>
            <td>${order.total_items}</td>
            <td>RM ${toNumber(order.total_amount).toFixed(2)}</td>
            <td>${escapeHtml(order.status)}</td>
            <td>${escapeHtml(order.ordered_at)}</td>
            <td>${paymentHtml}</td>
            <td>${action}</td>
            <td><button type="button" onclick="window.viewDetails(${order.order_id})">Details</button></td>
          </tr>
        `;
      });

      await renderPayPalButtonsForStatusRows(rows);
    } catch (error) {
      console.error(error);
      statusTableBody.innerHTML = `<tr><td colspan="8">Failed to load orders</td></tr>`;
    }
  }

  async function loadHistory() {
    try {
      const data = await fetchJson('php/get_customer_history.php');
      const rows = normalizeRows(data);

      historyTableBody.innerHTML = '';

      if (!rows || rows.length === 0) {
        historyTableBody.innerHTML = `<tr><td colspan="8">No history found</td></tr>`;
        return;
      }

      rows.forEach((o) => {
        let refundCell = '-';

        if (o.status === 'paid') {
          refundCell = `
            <input type="file" id="refund-${o.order_id}">
            <button onclick="window.requestRefund(${o.order_id})">Submit</button>
          `;
        } else if (o.status === 'refund_pending') {
          refundCell = 'Pending';
        } else if (o.status === 'refunded') {
          refundCell = 'Refunded';
        }

        historyTableBody.innerHTML += `
          <tr>
            <td>${o.order_id}</td>
            <td>${o.total_items}</td>
            <td>RM ${toNumber(o.total_amount).toFixed(2)}</td>
            <td>${escapeHtml(o.status)}</td>
            <td>${o.ordered_at ?? '-'}</td>
            <td>${o.approved_at ?? '-'}</td>
            <td>${refundCell}</td>
            <td>
              ${(o.status === 'paid' || o.status === 'refunded')
                ? `<button onclick="window.downloadReceipt(${o.order_id})">Receipt</button>`
                : ''
              }
              <button onclick="window.viewDetails(${o.order_id})">Details</button>
            </td>
          </tr>
        `;
      });
    } catch (error) {
      console.error(error);
      historyTableBody.innerHTML = `<tr><td colspan="8">Failed to load history</td></tr>`;
    }
  }

  window.downloadReceipt = function(orderId) {
    window.open(`php/generate_receipt.php?order_id=${orderId}`, '_blank');
  };

  window.addToCart = async function(productId, cardKey) {
    try {
      const qty = Number(document.getElementById(`qty-${cardKey}`)?.value || 1);

      const formData = new FormData();
      formData.append('product_id', productId);
      formData.append('quantity', qty);

      const result = await fetchJson('php/add_to_cart.php', {
        method: 'POST',
        body: formData
      });

      alert(result.message || 'Added to cart.');

      if (result.success) {
        await loadCart();
      }
    } catch (error) {
      console.error(error);
      alert('Failed to add to cart.');
    }
  };

  window.updateCartQuantity = async function(cartId) {
    try {
      const quantity = Number(document.getElementById(`cartQty-${cartId}`)?.value || 1);

      const body = new URLSearchParams({
        cart_id: cartId,
        quantity
      });

      const result = await fetchJson('php/update_cart_quantity.php', {
        method: 'POST',
        body
      });

      alert(result.message || 'Cart updated.');

      if (result.success) {
        await loadCart();
      }
    } catch (error) {
      console.error(error);
      alert('Failed to update cart.');
    }
  };

  window.removeCartItem = async function(cartId) {
    try {
      const body = new URLSearchParams({ cart_id: cartId });

      const result = await fetchJson('php/remove_from_cart.php', {
        method: 'POST',
        body
      });

      alert(result.message || 'Item removed.');

      if (result.success) {
        await loadCart();
      }
    } catch (error) {
      console.error(error);
      alert('Failed to remove cart item.');
    }
  };

  window.checkoutCart = async function() {
    try {
      const result = await fetchJson('php/checkout_cart.php', {
        method: 'POST'
      });

      alert(result.message || 'Checkout completed.');

      if (result.success) {
        await loadCart();
        await loadStatus();
        await loadHistory();
        await loadAllProducts();
        await loadTopProducts();
      }
    } catch (error) {
      console.error(error);
      alert('Failed to checkout cart.');
    }
  };

  window.cancelOrder = async function(orderId) {
    try {
      const body = new URLSearchParams({ order_id: orderId });
      const result = await fetchJson('php/cancel_order.php', {
        method: 'POST',
        body
      });

      alert(result.message || 'Unknown response.');

      if (result.success) {
        await loadStatus();
        await loadHistory();
        await loadAllProducts();
        await loadTopProducts();
      }
    } catch (error) {
      console.error(error);
      alert('Failed to cancel order.');
    }
  };

  window.requestRefund = async function(orderId) {
    const file = document.getElementById(`refund-${orderId}`)?.files?.[0];

    if (!file) {
      alert('Please upload refund proof first.');
      return;
    }

    try {
      const formData = new FormData();
      formData.append('order_id', orderId);
      formData.append('refund_proof', file);

      const result = await fetchJson('php/request_refund.php', {
        method: 'POST',
        body: formData
      });

      alert(result.message || 'Unknown response.');

      if (result.success) {
        await loadHistory();
        await loadStatus();
      }
    } catch (error) {
      console.error(error);
      alert('Failed to request refund.');
    }
  };

  window.addToWishlist = async function(productId) {
    try {
      const body = new URLSearchParams({ product_id: productId });
      const result = await fetchJson('php/add_to_wishlist.php', { method: 'POST', body });
      alert(result.message || 'Added to wishlist.');
      if (result.success) {
        await loadWishlist();
        await loadAllProducts();
      }
    } catch (error) {
      console.error(error);
      alert('Failed to add wishlist.');
    }
  };

  window.submitReview = async function(productId, cardKey) {
    try {
      const rating = document.getElementById(`rating-${cardKey}`)?.value || '5';
      const comment = document.getElementById(`comment-${cardKey}`)?.value || '';

      if (comment.trim() === '') {
        alert('Please write a comment.');
        return;
      }

      const body = new URLSearchParams({
        product_id: productId,
        rating,
        comment
      });

      const result = await fetchJson('php/submit_review.php', {
        method: 'POST',
        body
      });

      alert(result.message || 'Review submitted.');

      if (result.success) {
        await loadAllProducts();
        await loadTopProducts();
        await loadWishlist();
      }
    } catch (error) {
      console.error(error);
      alert('Failed to submit review.');
    }
  };

  window.removeFromWishlist = async function(productId) {
    try {
      const body = new URLSearchParams({ product_id: productId });
      const result = await fetchJson('php/remove_from_wishlist.php', {
        method: 'POST',
        body
      });

      alert(result.message || 'Removed from wishlist.');

      if (result.success) {
        await loadWishlist();
        await loadAllProducts();
      }
    } catch (error) {
      console.error(error);
      alert('Failed to remove wishlist item.');
    }
  };

  window.viewDetails = async function(orderId) {
    try {
      const data = await fetchJson(`php/get_order_details.php?order_id=${orderId}`);

      if (data.success === false) {
        alert(data.message || 'Failed to load order details.');
        return;
      }

      let itemsHtml = '';
      if (Array.isArray(data.items) && data.items.length > 0) {
        itemsHtml = `
          <div style="margin-top:12px;">
            <h4>Items</h4>
            <ul style="padding-left:20px;">
              ${data.items.map(item => `
                <li>
                  ${escapeHtml(item.product_name)} x ${item.quantity}
                  — RM ${toNumber(item.product_price).toFixed(2)}
                  (Subtotal: RM ${toNumber(item.subtotal).toFixed(2)})
                </li>
              `).join('')}
            </ul>
          </div>
        `;
      }

      modalBody.innerHTML = `
        <p><strong>Order ID:</strong> ${data.order_id}</p>
        <p><strong>Total Items:</strong> ${data.total_items}</p>
        <p><strong>Total Amount:</strong> RM ${toNumber(data.total_amount).toFixed(2)}</p>
        <p><strong>Status:</strong> ${escapeHtml(data.status)}</p>
        <p><strong>Ordered At:</strong> ${escapeHtml(data.ordered_at || '-')}</p>
        ${itemsHtml}
      `;

      detailModal.classList.remove('hidden');
    } catch (error) {
      console.error(error);
      alert('Failed to load order details.');
    }
  };

  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', () => {
      detailModal.classList.add('hidden');
    });
  }

  tabButtons.forEach((btn) => {
    btn.addEventListener('click', async () => {
      tabButtons.forEach((b) => b.classList.remove('active'));
      tabContents.forEach((c) => c.classList.remove('active'));

      btn.classList.add('active');

      const target = document.getElementById(btn.dataset.tab);
      if (target) target.classList.add('active');

      if (btn.dataset.tab === 'cartTab') await loadCart();
      if (btn.dataset.tab === 'statusTab') await loadStatus();
      if (btn.dataset.tab === 'historyTab') await loadHistory();
      if (btn.dataset.tab === 'wishlistTab') await loadWishlist();
    });
  });

  if (categoryFilter) {
    categoryFilter.addEventListener('change', refreshProductArea);
  }

  if (sortFilter) {
    sortFilter.addEventListener('change', refreshProductArea);
  }

  if (reloadProductsBtn) {
    reloadProductsBtn.addEventListener('click', async () => {
      await loadAllProducts();
      await loadTopProducts();
    });
  }

  if (customerSearchBtn) {
    customerSearchBtn.addEventListener('click', searchOrders);
  }

  if (customerSearchResetBtn) {
    customerSearchResetBtn.addEventListener('click', () => {
      customerSearchKeyword.value = '';
      customerSearchBody.innerHTML = `<tr><td colspan="6">Please search for an order.</td></tr>`;
    });
  }

  if (checkoutCartBtn) {
    checkoutCartBtn.addEventListener('click', window.checkoutCart);
  }

  await loadProfile();
  await loadAllProducts();
  await loadTopProducts();
  await loadWishlist();
  await loadCart();
  await loadStatus();
  await loadHistory();
});