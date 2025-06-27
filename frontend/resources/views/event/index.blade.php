@extends('layouts.user_type.auth')

@section('content')

<main class="main-content position-relative max-height-vh-100 h-100 mt-1 border-radius-lg ">
  <div class="container-fluid py-4">
    <div class="row">
      <div class="col-12">
        <div class="card mb-4">
          <div class="card-header pb-0">
            <div class="d-flex flex-row justify-content-between">
              <div>
                <h5 class="mb-0">All Events</h5>
              </div>
              <a href="#" class="btn bg-gradient-primary btn-sm mb-0" id="btn-add-event" type="button">+&nbsp; New Event</a>
            </div>
          </div>
          <div class="card-body px-0 pt-0 pb-2">
            <div class="table-responsive p-0">
              <table class="table align-items-center mb-0">
                <thead>
                  <tr>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Event</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tanggal</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Lokasi</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                    <th id="th-action" class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Actions</th>
                  </tr>
                </thead>
                <tbody id="events-table-body">
                  <!-- Akan diisi oleh JavaScript -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Detail Event -->
    <div class="modal fade" id="eventDetailModal" tabindex="-1" aria-labelledby="eventDetailLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="eventDetailLabel">Detail Event</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="event-detail-body">
          <!-- Akan diisi JS -->
        </div>
      </div>
    </div>
  </div>

    <!-- Modal Tambah/Edit Event -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form id="event-form" enctype="multipart/form-data">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="eventModalLabel">Tambah/Edit Event</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="event-name" class="form-label">Nama Event</label>
                <input type="text" class="form-control" id="event-name" required>
              </div>
              <div class="mb-3">
                <label for="event-description" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="event-description" required></textarea>
              </div>
              <div class="mb-3">
                <label for="event-date" class="form-label">Tanggal</label>
                <input type="date" class="form-control" id="event-date" required>
              </div>
              <div class="mb-3">
                <label for="event-time" class="form-label">Waktu</label>
                <input type="time" class="form-control" id="event-time" required>
              </div>
              <div class="mb-3">
                <label for="event-location" class="form-label">Lokasi</label>
                <input type="text" class="form-control" id="event-location" required>
              </div>
              <div class="mb-3">
                <label for="event-speaker" class="form-label">Pemateri</label>
                <input type="text" class="form-control" id="event-speaker" required>
              </div>
              <div class="mb-3">
                <label for="event-poster" class="form-label">Poster</label>
                <input type="file" class="form-control" id="event-poster" accept="image/*">
              </div>
              <div class="mb-3">
                <label for="event-price" class="form-label">Harga</label>
                <input type="number" class="form-control" id="event-price" min="0" required>
              </div>
              <div class="mb-3">
                <label for="event-max" class="form-label">Max Peserta</label>
                <input type="number" class="form-control" id="event-max" min="1" required>
              </div>
              <div class="mb-3">
                <label for="event-status" class="form-label">Status</label>
                <select class="form-control" id="event-status" required>
                  <option value="1">Active</option>
                  <option value="0">Inactive</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
            
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

@push('dashboard')
<script>
function renderEvents(events) {
  const user = JSON.parse(localStorage.getItem('user'));
  let html = '';
  events.forEach(event => {
    html += `
      <tr class="event-row" data-id="${event.id}" style="cursor:pointer">
        <td>
          <div class="d-flex px-2 py-1">
            <div>
              <img src="http://localhost:3000${event.poster || '/uploads/default-poster.jpg'}" class="avatar avatar-sm me-3" alt="poster">
            </div>
            <div class="d-flex flex-column justify-content-center">
              <h6 class="mb-0 text-sm">${event.name}</h6>
              <p class="text-xs text-secondary mb-0">${event.description ? event.description.substring(0, 40) + '...' : ''}</p>
            </div>
          </div>
        </td>
        <td>
          <span class="text-xs font-weight-bold">${event.date}</span>
        </td>
        <td>
          <span class="text-xs font-weight-bold">${event.location}</span>
        </td>
        <td>
          ${
            event.is_active == 1
              ? '<span class="badge badge-sm bg-gradient-success">Active</span>'
              : '<span class="badge badge-sm bg-gradient-secondary">Inactive</span>'
          }
        </td>
        ${
          user && user.role_id == 4
            ? `<td class="text-center" style="cursor:default">
                <div class="d-flex align-items-center justify-content-center gap-2">
                  <a href="javascript:;" class="btn btn-link text-dark px-3 mb-0" onclick="openEventModal('${event.id}')">
                    <i class="fas fa-pencil-alt text-dark me-2"></i>Edit
                  </a>
                </div>
              </td>`
            : ''
        }
      </tr>
    `;
  });
  document.getElementById('events-table-body').innerHTML = html;

  document.querySelectorAll('.event-row').forEach(row => {
  const cells = row.querySelectorAll('td:not(:last-child)');
  
  cells.forEach(cell => {
    cell.onclick = function(e) {
      const id = row.getAttribute('data-id');
      showEventDetail(events.find(ev => ev.id == id));
    };
  });
});
}

function formatDateForInput(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  const month = ('0' + (d.getMonth() + 1)).slice(-2);
  const day = ('0' + d.getDate()).slice(-2);
  return d.getFullYear() + '-' + month + '-' + day;
}

// Modal Edit Event (pakai modal tambah, isi data)
let editingEventId = null;
function openEventModal(id) {
  editingEventId = id;
  fetch(`http://localhost:3000/api/events/${id}`, {
    headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
  })
  .then(handleApiResponse)
  .then(event => {
    if (!event) return;
    document.getElementById('event-form').reset();
    document.getElementById('event-name').value = event.name;
    document.getElementById('event-description').value = event.description;
    document.getElementById('event-date').value = formatDateForInput(event.date);
    document.getElementById('event-time').value = event.time;
    document.getElementById('event-location').value = event.location;
    document.getElementById('event-speaker').value = event.speaker;
    document.getElementById('event-price').value = event.price;
    document.getElementById('event-max').value = event.max_participants;
    document.getElementById('event-status').value = event.is_active;
    // Poster tidak bisa diisi otomatis (security browser)
    var modal = new bootstrap.Modal(document.getElementById('eventModal'));
    modal.show();
  });
}

// Modal tambah event (reset form & id)
document.getElementById('btn-add-event').onclick = function() {
  editingEventId = null;
  document.getElementById('event-form').reset();
  document.getElementById('event-status').value = 1;
  var modal = new bootstrap.Modal(document.getElementById('eventModal'));
  modal.show();
};

// Submit form tambah/edit event
document.getElementById('event-form').onsubmit = function(e) {
  e.preventDefault();
  const formData = new FormData();
  formData.append('name', document.getElementById('event-name').value);
  formData.append('description', document.getElementById('event-description').value);
  formData.append('date', document.getElementById('event-date').value);
  formData.append('time', document.getElementById('event-time').value);
  formData.append('location', document.getElementById('event-location').value);
  formData.append('speaker', document.getElementById('event-speaker').value);
  if (document.getElementById('event-poster').files[0]) {
    formData.append('poster', document.getElementById('event-poster').files[0]);
  }
  formData.append('price', document.getElementById('event-price').value);
  formData.append('max_participants', document.getElementById('event-max').value);
  formData.append('is_active', document.getElementById('event-status').value);
  formData.append('created_by', JSON.parse(localStorage.getItem('user')).id);

  let url = 'http://localhost:3000/api/events';
  let method = 'POST';
  if (editingEventId) {
    url += '/' + editingEventId;
    method = 'PUT';
  }

  fetch(url, {
    method: method,
    headers: { Authorization: 'Bearer ' + localStorage.getItem('token') },
    body: formData
  })
  .then(handleApiResponse)
  .then(() => {
    var modal = bootstrap.Modal.getInstance(document.getElementById('eventModal'));
    modal.hide();
    loadEvents();
    editingEventId = null;
  });
};

// Detail event
function showEventDetail(event) {
  const user = JSON.parse(localStorage.getItem('user'));

  // Ambil sisa tiket dari backend
  fetch(`http://localhost:3000/api/events/${event.id}/available-tickets`, {
    headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
  })
  .then(res => res.json())
  .then(ticketData => {
    let btnBuy = '';
    if (user && user.role_id != 4 && event.is_active == 1) {
      if (ticketData.available <= 0) {
        btnBuy = `<button class="btn btn-secondary" disabled>Ticket Sold Out</button>`;
      } else {
        btnBuy = `<a href="/buy/${event.id}" class="btn btn-primary">Buy Ticket</a>`;
      }
    }

    document.getElementById('event-detail-body').innerHTML = `
      <img src="http://localhost:3000${event.poster || '/uploads/default-poster.jpg'}" class="img-fluid rounded mb-3" alt="poster">
      <h5>${event.name}</h5>
      <p>${event.description}</p>
      <ul class="list-unstyled mb-0">
        <li><b>Tanggal:</b> ${event.date}</li>
        <li><b>Waktu:</b> ${event.time}</li>
        <li><b>Lokasi:</b> ${event.location}</li>
        <li><b>Pemateri:</b> ${event.speaker}</li>
        <li><b>Harga:</b> Rp${event.price}</li>
        <li><b>Status:</b> ${event.is_active == 1 ? 'Aktif' : 'Tidak Aktif'}</li>
        <li><b>Sisa Tiket:</b> ${ticketData.available}</li>
      </ul>
      <div class="mt-3">
        ${btnBuy}
      </div>
    `;
    var modal = new bootstrap.Modal(document.getElementById('eventDetailModal'));
    modal.show();
  });
}

// Load events dari backend
function loadEvents() {
  fetch('http://localhost:3000/api/events', {
    headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
  })
  .then(handleApiResponse)
  .then(data => {
    if (!data) return;
    const user = JSON.parse(localStorage.getItem('user'));
    
    // Filter events based on user role
    let eventsToShow = data.events;
    if (!user || user.role_id != 4) {
      eventsToShow = data.events.filter(event => event.is_active == 1);
    }
    
    renderEvents(eventsToShow);
  });
}

// Sembunyikan tombol dan kolom action jika bukan role 4
window.onload = function() {
  const user = JSON.parse(localStorage.getItem('user'));
  if (!user || user.role_id != 4) {
    document.getElementById('btn-add-event').style.display = 'none';
    document.getElementById('th-action').style.display = 'none';
  }
  loadEvents();
};
</script>
@endpush

@endsection