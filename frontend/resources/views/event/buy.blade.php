@extends('layouts.user_type.auth')

@section('content')
<main class="main-content position-relative max-height-vh-100 h-100 mt-1 border-radius-lg">
  <div class="container-fluid py-4">
    <div class="row">
      <div class="col-12">
        <div class="card mb-4">
          <div class="card-header pb-0">
            <div class="d-flex flex-row justify-content-between">
              <div>
                <h5 class="mb-0">Buy Event Ticket</h5>
              </div>
              <a href="/event" class="btn bg-gradient-secondary btn-sm mb-0" type="button">
                <i class="fas fa-arrow-left me-2"></i>Back to Events
              </a>
            </div>
          </div>
          <div class="card-body">
            <div id="participant-alert" class="alert alert-danger d-none" role="alert"></div>
            <div id="content" style="display: none;">
              <div class="row">
                <div class="col-md-6">
                  <div class="card">
                    <div class="card-body" id="event-details">
                      <!-- Event details will be loaded here -->
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="card">
                    <div class="card-body">
                      <form id="ticket-form">
                        <div class="form-group">
                          <label for="total_tickets" class="form-control-label">Number of Tickets</label>
                          <input type="number" class="form-control" id="total_tickets" min="1" value="1" required>
                          <small class="form-text text-muted">Maximum tickets available: <span id="max-tickets"></span></small>
                        </div>
                        <div class="form-group mt-4">
                          <label class="form-control-label">Total Price</label>
                          <h4 class="text-gradient text-primary mt-2" id="total-price">Rp 0</h4>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                          <button type="submit" class="btn bg-gradient-primary">
                            <i class="fas fa-shopping-cart me-2"></i>Purchase Tickets
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                  <div id="participant-names-container" class="mb-3"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

@push('dashboard')
<script>
// Check authentication first
if (!localStorage.getItem('token')) {
    window.location.href = '/login';
} else {
    document.getElementById('content').style.display = 'block';
}

let event = null;
let ticketPrice = 0;

function renderParticipantInputs(count) {
    const container = document.getElementById('participant-names-container');
    container.innerHTML = '';
    for (let i = 1; i <= count; i++) {
        container.innerHTML += `
            <div class="form-group mb-2">
                <label for="participant_name_${i}" class="form-control-label">Participant Name #${i}</label>
                <input type="text" class="form-control" id="participant_name_${i}" name="participant_names[]" required>
            </div>
        `;
    }
}

function loadEventDetails() {
    const eventId = window.location.pathname.split('/').pop();
    const token = localStorage.getItem('token');
    
    fetch(`http://localhost:3000/api/events/${eventId}`, {
        headers: { 
            Authorization: 'Bearer ' + token 
        }
    })
    .then(response => response.json())
    .then(data => {
        event = data;
        ticketPrice = parseFloat(data.price);
        
        // Format date
        const eventDate = new Date(data.date);
        const formattedDate = eventDate.toLocaleDateString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        // Format time
        const formattedTime = data.time.substring(0, 5); // Get HH:mm only

        // Format price with thousand separator
        const formattedPrice = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(data.price);

        // Calculate initial total price
        const initialTotal = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(ticketPrice); // Default 1 ticket
        
        document.getElementById('event-details').innerHTML = `
            <div class="text-center mb-3">
              <img src="http://localhost:3000${data.poster || '/uploads/default-poster.jpg'}" 
                   class="img-fluid rounded" 
                   alt="poster" 
                   style="max-height: 300px;">
            </div>
            <h5 class="font-weight-bolder mb-0">${data.name}</h5>
            <p class="text-sm text-muted mb-3">${data.description}</p>
            <div class="d-flex align-items-center mb-2">
              <i class="fas fa-calendar text-primary me-2"></i>
              <span>${formattedDate}</span>
            </div>
            <div class="d-flex align-items-center mb-2">
              <i class="fas fa-clock text-primary me-2"></i>
              <span>${formattedTime} WIB</span>
            </div>
            <div class="d-flex align-items-center mb-2">
              <i class="fas fa-map-marker-alt text-primary me-2"></i>
              <span>${data.location}</span>
            </div>
            <div class="d-flex align-items-center mb-2">
              <i class="fas fa-tag text-primary me-2"></i>
              <span>${formattedPrice}</span>
            </div>
        `;
        
        fetch(`http://localhost:3000/api/events/${eventId}/available-tickets`, {
            headers: { Authorization: 'Bearer ' + token }
        })
        .then(res => res.json())
        .then(ticketData => {
            document.getElementById('max-tickets').textContent = ticketData.available;
            document.getElementById('total_tickets').max = ticketData.available;
        });
        document.getElementById('total-price').textContent = initialTotal;

        // Render initial participant name input(s)
        renderParticipantInputs(1);
    });
}

document.getElementById('total_tickets').addEventListener('input', function(e) {
    let count = parseInt(this.value) || 1;
    if (count < 1) count = 1;
    if (count > parseInt(this.max)) count = parseInt(this.max);
    this.value = count;
    renderParticipantInputs(count);

    const total = count * ticketPrice;
    const formattedTotal = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(total);
    document.getElementById('total-price').textContent = formattedTotal;
});

document.getElementById('ticket-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const user = JSON.parse(localStorage.getItem('user'));
    const totalTickets = parseInt(document.getElementById('total_tickets').value) || 1;
    const token = localStorage.getItem('token');
    const participantInputs = document.querySelectorAll('input[name="participant_names[]"]');
    const participant_names = Array.from(participantInputs).map(input => input.value);

    if (participant_names.some(name => name === "")) {
        showToast("Semua nama peserta wajib diisi!", "danger");
        return;
    }

    fetch('http://localhost:3000/api/registrations', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            Authorization: 'Bearer ' + token
        },
        body: JSON.stringify({
            event_id: event.id,
            users_id: user.id,
            total_tickets: totalTickets,
            participant_names: participant_names
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.id) {
            window.location.href = '/my-events';
        } else {
            alert(data.error || 'Failed to purchase tickets');
        }
    });
});

window.onload = loadEventDetails;
</script>
@endpush
@endsection