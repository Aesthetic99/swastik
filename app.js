// app.js - UI enhancements: order totals, polling, and toasts
document.addEventListener('DOMContentLoaded', function(){
  // Utility
  function fmt(v){
    return '$' + Number(v).toFixed(2);
  }

  // Toast helper using Bootstrap
  function showToast(message, title = 'Info', delay = 3000){
    const containerId = 'toast-container';
    let container = document.getElementById(containerId);
    if(!container){
      container = document.createElement('div');
      container.id = containerId;
      container.className = 'position-fixed bottom-0 end-0 p-3';
      document.body.appendChild(container);
    }

    const toastEl = document.createElement('div');
    toastEl.className = 'toast align-items-center text-bg-white border-0';
    toastEl.setAttribute('role','alert');
    toastEl.setAttribute('aria-live','assertive');
    toastEl.setAttribute('aria-atomic','true');
    toastEl.style.minWidth = '220px';

    toastEl.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>`;

    container.appendChild(toastEl);
    const bsToast = new bootstrap.Toast(toastEl, {delay: delay});
    bsToast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
  }

  // Take Order page enhancements
  const qtyInputs = document.querySelectorAll('.qty-input');
  if(qtyInputs.length){
    const totalEl = document.getElementById('order-total');
    const placeBtn = document.getElementById('place-order-btn');
    const reviewBtn = document.getElementById('review-order-btn');
    const summaryEl = document.getElementById('order-summary');
    const selectedCountBadge = document.getElementById('selected-count-badge');
    const searchInput = document.getElementById('menu-search');
    const reviewModalEl = document.getElementById('reviewOrderModal');
    const reviewListEl = document.getElementById('review-order-list');
    const reviewTotalEl = document.getElementById('review-order-total');
    const confirmBtn = document.getElementById('confirm-place-order-btn');
    const orderForm = document.getElementById('order-form');
    let reviewModal = null;

    if(reviewModalEl){
      reviewModal = new bootstrap.Modal(reviewModalEl);
    }

    function getSelectedRows(){
      const rows = [];
      qtyInputs.forEach(input => {
        const qty = Number(input.value) || 0;
        if(qty > 0){
          const row = input.closest('tr');
          rows.push({
            name: row.children[0]?.textContent?.trim() || 'Item',
            category: row.children[1]?.textContent?.trim() || '-',
            price: Number(input.dataset.price) || 0,
            qty,
            subtotal: qty * (Number(input.dataset.price) || 0)
          });
        }
      });
      return rows;
    }

    function renderSummary(){
      const selected = getSelectedRows();
      if(selectedCountBadge){
        const count = selected.reduce((sum, item) => sum + item.qty, 0);
        selectedCountBadge.textContent = `${count} item${count === 1 ? '' : 's'} selected`;
      }

      if(summaryEl){
        if(!selected.length){
          summaryEl.innerHTML = 'No items selected yet.';
        } else {
          summaryEl.innerHTML = selected.map(item => `
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <div class="fw-semibold">${item.name}</div>
                <div class="small text-muted">${item.qty} x ${fmt(item.price)}</div>
              </div>
              <div class="fw-semibold">${fmt(item.subtotal)}</div>
            </div>
          `).join('');
        }
      }

      if(reviewListEl){
        reviewListEl.innerHTML = selected.length
          ? `<div class="list-group">${selected.map(item => `
              <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold">${item.name}</div>
                  <div class="small text-muted">${item.category} · ${item.qty} x ${fmt(item.price)}</div>
                </div>
                <div class="fw-semibold">${fmt(item.subtotal)}</div>
              </div>
            `).join('')}</div>`
          : '<div class="alert alert-info mb-0">No items selected.</div>';
      }

      if(reviewTotalEl && totalEl){
        reviewTotalEl.textContent = totalEl.textContent;
      }
    }

    function recalc(){
      let total = 0;
      let selected = 0;
      qtyInputs.forEach(input => {
        const qty = Number(input.value) || 0;
        const price = Number(input.dataset.price) || 0;
        const row = input.closest('tr');
        const subtotal = qty * price;
        const subEl = row.querySelector('.item-subtotal');
        if(subEl) subEl.textContent = fmt(subtotal);
        total += subtotal;
        selected += qty;
      });
      if(totalEl) totalEl.textContent = fmt(total);
      if(placeBtn) placeBtn.disabled = (total <= 0);
      if(reviewBtn) reviewBtn.disabled = (total <= 0);
      if(confirmBtn) confirmBtn.disabled = (total <= 0);
      renderSummary();
    }

    qtyInputs.forEach(i => i.addEventListener('input', recalc));
    recalc();

    if(searchInput){
      searchInput.addEventListener('input', () => {
        const q = searchInput.value.toLowerCase().trim();
        document.querySelectorAll('#menu-table tbody tr').forEach(row => {
          const hay = (row.dataset.search || row.textContent || '').toLowerCase();
          row.style.display = hay.includes(q) ? '' : 'none';
        });
      });
    }

    if(reviewBtn && reviewModal){
      reviewBtn.addEventListener('click', () => {
        renderSummary();
        reviewModal.show();
      });
    }

    if(confirmBtn && orderForm){
      confirmBtn.addEventListener('click', () => {
        window.__skipOrderReview = true;
        orderForm.submit();
      });
    }

    if(orderForm){
      orderForm.addEventListener('submit', (e) => {
        if(window.__skipOrderReview){
          window.__skipOrderReview = false;
          return;
        }
        if(!window.__skipOrderReview){
          e.preventDefault();
          renderSummary();
          if(reviewModal) reviewModal.show();
        }
      });
    }

    // Show success toast if ?success=1
    if(window.location.search.includes('success=1')){
      showToast('Order placed successfully!', 'Success');
      // remove query param from URL
      if(history.replaceState) history.replaceState(null, '', window.location.pathname);
    }
  }

  // View Orders: periodic polling to refresh table
  const ordersBody = document.getElementById('orders-body');
  if(ordersBody){
    let lastSnapshot = '';
    async function fetchOrders(){
      try{
        const res = await fetch('fetch_orders.php', {credentials: 'same-origin'});
        if(!res.ok) return;
        const data = await res.json();
        // Build HTML
        let html = '';
        data.forEach(order => {
          html += `<tr>
            <td>Table ${order.table_number}</td>
            <td>${order.order_items}</td>
            <td>${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</td>
            <td>${order.order_time}</td>
            <td>
              <form method="POST" class="d-flex gap-2">
                <input type="hidden" name="order_id" value="${order.id}">
                <select name="status" class="form-select form-select-sm">`;

          // Role-specific options are best handled server-side; keep simple here
          if(order.status === 'pending'){
            html += `<option value="preparing">Preparing</option><option value="ready">Ready</option>`;
          } else if(order.status === 'preparing'){
            html += `<option value="ready">Ready</option>`;
          } else if(order.status === 'ready'){
            html += `<option value="served">Served</option>`;
          }

          html += `</select>
                <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
              </form>
            </td>
          </tr>`;
        });

        if(html !== lastSnapshot){
          ordersBody.innerHTML = html;
          lastSnapshot = html;
        }
      }catch(e){
        // ignore
      }
    }

    fetchOrders();
    setInterval(fetchOrders, 5000);
  }

  // Manage Tables: handle add/delete/status change via AJAX
  const tablesTable = document.getElementById('tables-table');
  if(tablesTable){
    // Add table form
    const addForm = document.getElementById('add-table-form');
    if(addForm){
      addForm.addEventListener('submit', async (e)=>{
        e.preventDefault();
        const formData = new FormData(addForm);
        formData.append('action','add_table');
        try{
          const res = await fetch('manage_tables.php', {method:'POST', body: formData, credentials:'same-origin'});
          const data = await res.json();
          if(data.success){
            // Insert new row
            const tbody = tablesTable.querySelector('tbody');
            const tr = document.createElement('tr');
            tr.setAttribute('data-id', data.table.id);
            tr.innerHTML = `
              <td>${data.table.table_number}</td>
              <td><span class="badge bg-success table-status">Available</span></td>
              <td>
                <div class="d-flex gap-2">
                  <select class="form-select form-select-sm status-select" style="width:140px;">
                    <option value="available" selected>Available</option>
                    <option value="occupied">Occupied</option>
                    <option value="reserved">Reserved</option>
                  </select>
                  <button class="btn btn-sm btn-danger btn-delete-table">Delete</button>
                </div>
              </td>`;
            tbody.prepend(tr);
            // reset form and hide modal
            addForm.reset();
            const modalEl = document.getElementById('addTableModal');
            const bs = bootstrap.Modal.getInstance(modalEl);
            bs.hide();
            showToast('Table added', 'Success');
          } else {
            showToast('Failed to add table: '+(data.error||''),'Error');
          }
        }catch(err){showToast('Network error','Error')}
      });
    }

    // Delegate click for delete and change status
    tablesTable.addEventListener('click', async (e)=>{
      if(e.target.classList.contains('btn-delete-table')){
        const tr = e.target.closest('tr');
        const id = tr.getAttribute('data-id');
        if(!confirm('Delete this table?')) return;
        const form = new FormData(); form.append('action','delete_table'); form.append('table_id', id);
        try{
          const res = await fetch('manage_tables.php',{method:'POST',body:form,credentials:'same-origin'});
          const data = await res.json();
          if(data.success){
            tr.remove();
            showToast('Table deleted','Success');
          } else showToast('Delete failed','Error');
        }catch(err){showToast('Network error','Error')}
      }
    });

    // Status change
    tablesTable.addEventListener('change', async (e)=>{
      if(e.target.classList.contains('status-select')){
        const select = e.target;
        const tr = select.closest('tr');
        const id = tr.getAttribute('data-id');
        const status = select.value;
        const form = new FormData(); form.append('action','toggle_status'); form.append('table_id', id); form.append('status', status);
        try{
          const res = await fetch('manage_tables.php',{method:'POST',body:form,credentials:'same-origin'});
          const data = await res.json();
          if(data.success){
            const badge = tr.querySelector('.table-status');
            if(badge){
              badge.textContent = status.charAt(0).toUpperCase()+status.slice(1);
              badge.className = status==='available'? 'badge bg-success table-status' : (status==='occupied'? 'badge bg-warning text-dark table-status' : 'badge bg-secondary table-status');
            }
            showToast('Status updated','Success');
          } else showToast('Update failed','Error');
        }catch(err){showToast('Network error','Error')}
      }
    });
  }

  // Manage Users: add/edit/delete via modal + AJAX
  const usersTable = document.getElementById('users-table');
  if(usersTable){
    const userForm = document.getElementById('user-form');
    const userModalEl = document.getElementById('userModal');
    const userModalTitle = document.getElementById('userModalTitle');
    const userAction = document.getElementById('user_action');
    const userIdInput = document.getElementById('user_id');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const roleInput = document.getElementById('role');
    const passwordHelp = document.getElementById('password-help');
    const userModal = userModalEl ? new bootstrap.Modal(userModalEl) : null;

    usersTable.addEventListener('click', (e) => {
      const row = e.target.closest('tr');
      if(!row) return;

      if(e.target.classList.contains('btn-edit-user')){
        userAction.value = 'edit_user';
        userModalTitle.textContent = 'Edit User';
        userIdInput.value = row.dataset.id;
        usernameInput.value = row.dataset.username || '';
        roleInput.value = row.dataset.role || 'waiter';
        passwordInput.value = '';
        passwordInput.placeholder = 'Leave blank to keep current password';
        if(passwordHelp) passwordHelp.textContent = 'optional for edits';
        userModal.show();
      }

      if(e.target.classList.contains('btn-delete-user')){
        if(!confirm('Delete this user?')) return;
        const form = new FormData();
        form.append('action', 'delete_user');
        form.append('user_id', row.dataset.id);
        fetch('manage_users.php', {method:'POST', body: form, credentials:'same-origin'})
          .then(r => r.json())
          .then(data => {
            if(data.success){
              row.remove();
              showToast('User deleted', 'Success');
            } else {
              showToast(data.error || 'Failed to delete user', 'Error');
            }
          })
          .catch(() => showToast('Network error', 'Error'));
      }
    });

    if(userForm){
      userForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(userForm);
        try{
          const res = await fetch('manage_users.php', {method:'POST', body: formData, credentials:'same-origin'});
          const data = await res.json();
          if(data.success){
            const tbody = usersTable.querySelector('tbody');
            if(userAction.value === 'add_user'){
              const tr = document.createElement('tr');
              tr.setAttribute('data-id', data.user.id);
              tr.setAttribute('data-username', data.user.username);
              tr.setAttribute('data-role', data.user.role);
              tr.innerHTML = `
                <td>${data.user.id}</td>
                <td class="user-username">${data.user.username}</td>
                <td class="user-role"><span class="badge bg-info text-dark">${data.user.role.charAt(0).toUpperCase() + data.user.role.slice(1)}</span></td>
                <td>
                  <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-user">Edit</button>
                    <button type="button" class="btn btn-sm btn-danger btn-delete-user">Delete</button>
                  </div>
                </td>`;
              tbody.prepend(tr);
              showToast('User added', 'Success');
            } else {
              const row = usersTable.querySelector(`tr[data-id="${data.user.id}"]`);
              if(row){
                row.dataset.username = data.user.username;
                row.dataset.role = data.user.role;
                row.querySelector('.user-username').textContent = data.user.username;
                row.querySelector('.user-role').innerHTML = `<span class="badge bg-info text-dark">${data.user.role.charAt(0).toUpperCase() + data.user.role.slice(1)}</span>`;
              }
              showToast('User updated', 'Success');
            }
            userForm.reset();
            userAction.value = 'add_user';
            userModalTitle.textContent = 'Add User';
            if(passwordHelp) passwordHelp.textContent = 'required for new users';
            userModal.hide();
          } else {
            showToast(data.error || 'Operation failed', 'Error');
          }
        }catch(err){
          showToast('Network error', 'Error');
        }
      });
    }
  }

  // Manage Menu: add/edit/delete + search
  const menuItemsTable = document.getElementById('menu-items-table');
  if(menuItemsTable){
    const menuForm = document.getElementById('menu-form');
    const menuModalEl = document.getElementById('menuModal');
    const menuModalTitle = document.getElementById('menuModalTitle');
    const menuAction = document.getElementById('menu_action');
    const itemIdInput = document.getElementById('item_id');
    const menuNameInput = document.getElementById('menu_name');
    const menuCategoryInput = document.getElementById('menu_category');
    const menuPriceInput = document.getElementById('menu_price');
    const menuFilter = document.getElementById('menu-filter');
    const menuModal = menuModalEl ? new bootstrap.Modal(menuModalEl) : null;

    if(menuFilter){
      menuFilter.addEventListener('input', () => {
        const q = menuFilter.value.toLowerCase().trim();
        menuItemsTable.querySelectorAll('tbody tr').forEach(row => {
          const hay = `${row.dataset.name || ''} ${row.dataset.category || ''}`.toLowerCase();
          row.style.display = hay.includes(q) ? '' : 'none';
        });
      });
    }

    menuItemsTable.addEventListener('click', (e) => {
      const row = e.target.closest('tr');
      if(!row) return;

      if(e.target.classList.contains('btn-edit-item')){
        menuAction.value = 'edit_item';
        menuModalTitle.textContent = 'Edit Item';
        itemIdInput.value = row.dataset.id;
        menuNameInput.value = row.dataset.name || '';
        menuCategoryInput.value = row.dataset.category || '';
        menuPriceInput.value = row.dataset.price || '';
        menuModal.show();
      }

      if(e.target.classList.contains('btn-delete-item')){
        if(!confirm('Delete this menu item?')) return;
        const form = new FormData();
        form.append('action', 'delete_item');
        form.append('item_id', row.dataset.id);
        fetch('manage_menu.php', {method:'POST', body: form, credentials:'same-origin'})
          .then(r => r.json())
          .then(data => {
            if(data.success){
              row.remove();
              showToast('Menu item deleted', 'Success');
            } else {
              showToast(data.error || 'Failed to delete item', 'Error');
            }
          })
          .catch(() => showToast('Network error', 'Error'));
      }
    });

    if(menuForm){
      menuForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(menuForm);
        try{
          const res = await fetch('manage_menu.php', {method:'POST', body: formData, credentials:'same-origin'});
          const data = await res.json();
          if(data.success){
            const tbody = menuItemsTable.querySelector('tbody');
            if(menuAction.value === 'add_item'){
              const tr = document.createElement('tr');
              tr.setAttribute('data-id', data.item.id);
              tr.setAttribute('data-name', data.item.name);
              tr.setAttribute('data-category', data.item.category || '');
              tr.setAttribute('data-price', data.item.price);
              tr.innerHTML = `
                <td class="item-name">${data.item.name}</td>
                <td class="item-category">${data.item.category || '-'}</td>
                <td class="item-price">${fmt(data.item.price)}</td>
                <td>
                  <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-item">Edit</button>
                    <button type="button" class="btn btn-sm btn-danger btn-delete-item">Delete</button>
                  </div>
                </td>`;
              tbody.prepend(tr);
              showToast('Menu item added', 'Success');
            } else {
              const row = menuItemsTable.querySelector(`tr[data-id="${data.item.id}"]`);
              if(row){
                row.dataset.name = data.item.name;
                row.dataset.category = data.item.category || '';
                row.dataset.price = data.item.price;
                row.querySelector('.item-name').textContent = data.item.name;
                row.querySelector('.item-category').textContent = data.item.category || '-';
                row.querySelector('.item-price').textContent = fmt(data.item.price);
              }
              showToast('Menu item updated', 'Success');
            }
            menuForm.reset();
            menuAction.value = 'add_item';
            menuModalTitle.textContent = 'Add Item';
            menuModal.hide();
          } else {
            showToast(data.error || 'Operation failed', 'Error');
          }
        }catch(err){
          showToast('Network error', 'Error');
        }
      });
    }
  }

});
