@extends('layouts.user_type.auth')

@section('content')

<div id="not-for-you" style="display:none;">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4 mx-4">
                <div class="card-header pb-0">
                    <div class="d-flex flex-row justify-content-between">
                        <div>
                            <h5 class="mb-0">All Users</h5>
                        </div>
                        <a href="#" class="btn bg-gradient-primary btn-sm mb-0" id="btn-add-user" type="button">+&nbsp; New User</a>
                    </div>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">ID</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Name</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Email</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Role</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Creation Date</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Action</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body">
                                <!-- Data user akan diisi oleh JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit User (opsional, bisa dikembangkan) -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="user-form">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="userModalLabel">Tambah/Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="user-id">
          <div class="mb-3">
            <label for="user-name" class="form-label">Nama</label>
            <input type="text" class="form-control" id="user-name" required>
          </div>
          <div class="mb-3">
            <label for="user-email" class="form-label">Email</label>
            <input type="email" class="form-control" id="user-email" required>
          </div>
          <div class="mb-3">
            <label for="user-role" class="form-label">Role</label>
            <select class="form-control" id="user-role" required>
                <!-- Akan diisi otomatis oleh JS -->
            </select>
          </div>
          <div class="mb-3">
            <label for="user-status" class="form-label">Status</label>
            <select class="form-control" id="user-status" required>
                <option value="active">Aktif</option>
                <option value="inactive">Tidak Aktif</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="user-password" class="form-label">Password</label>
            <input type="password" class="form-control" id="user-password">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </div>
    </form>
  </div>
</div>

@endsection

@push('dashboard')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user || String(user.role_id) !== "2") {
        var modal = new bootstrap.Modal(document.getElementById('aksesDitolakModal'));
        modal.show();
        document.getElementById('btn-akses-ditolak-ok').onclick = function() {
            window.location.href = '/dashboard';
        };
        document.body.style.overflow = 'hidden';
        throw new Error('Akses ditolak');
    }
    document.getElementById('not-for-you').style.display = '';
});
</script>
<script>
let rolesMap = {};
window.onload = function() {
    loadRoles();
    loadUsers();

    document.getElementById('btn-add-user').onclick = function() {
        openUserModal();
    };

    document.getElementById('user-form').onsubmit = function(e) {
        e.preventDefault();
        saveUser();
    };
};

function loadRoles() {
    return fetch('http://localhost:3000/api/roles', {
        headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
    })
    .then(res => res.json())
    .then(data => {
        let html = '';
        rolesMap = {};
        data.roles.forEach(role => {
            rolesMap[role.id] = role.name;
            html += `<option value="${role.id}">${role.name}</option>`;
        });
        document.getElementById('user-role').innerHTML = html;
    });
}

function loadUsers() {
    fetch('http://localhost:3000/api/users', {
        headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
    })
    .then(res => res.json())
    .then(data => {
        // Filter hanya user dengan role_id 3 dan 4
        let keuangan = data.users.filter(u => u.role_id === 3);
        let panitia = data.users.filter(u => u.role_id === 4);

        // Urutkan masing-masing berdasarkan nama
        keuangan.sort((a, b) => a.name.localeCompare(b.name));
        panitia.sort((a, b) => a.name.localeCompare(b.name));

        // Gabungkan: role 3 (keuangan) di atas, role 4 (panitia) di bawah
        let users = keuangan.concat(panitia);

        let html = '';
        users.forEach((user, i) => {
            let statusBadge = user.status === 'active'
                ? `<span class="badge badge-sm bg-gradient-success">Active</span>`
                : `<span class="badge badge-sm bg-gradient-secondary">Inactive</span>`;
            html += `<tr>
                <td class="ps-4"><p class="text-xs font-weight-bold mb-0">${i+1}</p></td>
                <td class="text-center"><p class="text-xs font-weight-bold mb-0">${user.name}</p></td>
                <td class="text-center"><p class="text-xs font-weight-bold mb-0">${user.email}</p></td>
                <td class="text-center"><p class="text-xs font-weight-bold mb-0">${rolesMap[user.role_id] || user.role_id}</p></td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center"><span class="text-secondary text-xs font-weight-bold">${user.created_at || '-'}</span></td>
                <td class="text-center">
                    <a href="javascript:;" class="mx-3" onclick="openUserModal('${user.id}')"><i class="fas fa-user-edit text-secondary"></i></a>
                </td>
            </tr>`;
        });
        document.getElementById('users-table-body').innerHTML = html;
    });
}

function openUserModal(id = '') {
    document.getElementById('user-form').reset();
    document.getElementById('user-id').value = id || '';
    if (id) {
        // Edit mode: fetch user data
        fetch(`http://localhost:3000/api/users/${id}`, {
            headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
        })
        .then(res => res.json())
        .then(user => {
            loadRoles().then(() => {
                document.getElementById('user-name').value = user.name;
                document.getElementById('user-email').value = user.email;
                document.getElementById('user-role').value = user.role_id;
                document.getElementById('user-status').value = user.status;
            });
        });
        document.getElementById('userModalLabel').innerText = 'Edit User';
    } else {
        // Tambah user: pastikan roles sudah dimuat sebelum show modal
        loadRoles().then(() => {
            document.getElementById('userModalLabel').innerText = 'Tambah User';
            var modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.show();
        });
        return; // agar modal tidak double show
    }
    var modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
}

function saveUser() {
    const id = document.getElementById('user-id').value;
    const name = document.getElementById('user-name').value;
    const email = document.getElementById('user-email').value;
    const role_id = parseInt(document.getElementById('user-role').value); // pastikan angka
    const status = document.getElementById('user-status').value;
    const password = document.getElementById('user-password').value;

    const method = id ? 'PUT' : 'POST';
    const url = id ? `http://localhost:3000/api/users/${id}` : 'http://localhost:3000/api/users';

    // Hanya kirim password jika diisi (edit), wajib saat tambah
    let body = { name, email, role_id, status };
    if (!id || password) body.password = password;

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            Authorization: 'Bearer ' + localStorage.getItem('token')
        },
        body: JSON.stringify(body)
    })
    .then(handleApiResponse)
    .then(() => {
        var modal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
        modal.hide();
        loadUsers();
    });
}
</script>
@endpush