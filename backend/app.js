require('dotenv').config();
const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const multer = require('multer');
const path = require('path');
const QRCode = require('qrcode');

const db = require('./db'); // mysql2/promise pool
const authenticateToken = require('./middleware/auth');

const app = express();
const port = 3000;

// Middleware
const corsOptions = {
  origin: ['http://localhost:8000', 'http://127.0.0.1:8000'],
  methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization'],
  credentials: true
};
app.use(cors(corsOptions));
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// Static folder for poster images
app.use('/uploads', express.static(path.join(__dirname, 'uploads')));

// Static folder for certificates
app.use('/uploads/certificates', express.static(path.join(__dirname, 'uploads/certificates')));

// Multer setup for poster upload
const storage = multer.diskStorage({
  destination: function (req, file, cb) {
    cb(null, path.join(__dirname, 'uploads'));
  },
  filename: function (req, file, cb) {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
    cb(null, uniqueSuffix + '-' + file.originalname);
  }
});
const upload = multer({ storage: storage });

const certificateStorage = multer.diskStorage({
  destination: function (req, file, cb) {
    cb(null, path.join(__dirname, 'uploads/certificates'));
  },
  filename: function (req, file, cb) {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
    cb(null, uniqueSuffix + '-' + file.originalname);
  }
});
const certificateUpload = multer({ storage: certificateStorage });

app.get('/', (req, res) => {
  res.send('API is running!');
});

// 🔐 Register
app.post('/api/register', async (req, res) => {
  const { name, email, password, confirm_password } = req.body;

  if (!name || !email || !password || !confirm_password) {
    return res.status(400).json({ error: 'All fields are required.' });
  }

  if (password !== confirm_password) {
    return res.status(400).json({ error: 'Passwords do not match.' });
  }

  try {
    const hashedPassword = await bcrypt.hash(password, 10);
    const role_id = 1;
    const status = 'active';

    const sql = 'INSERT INTO users (name, email, password, role_id, status) VALUES (?, ?, ?, ?, ?)';
    await db.query(sql, [name, email, hashedPassword, role_id, status]);

    res.status(200).json({ message: 'User registered successfully' });
  } catch (err) {
    console.error('Register error:', err.message);
    res.status(500).json({ error: 'Server error during registration.' });
  }
});

// Login
app.post('/api/login', async (req, res) => {
  const { email, password } = req.body;

  if (!email || !password) {
    return res.status(400).json({ error: 'Email and password are required.' });
  }

  try {
    const [results] = await db.query('SELECT * FROM users WHERE email = ?', [email]);
    if (results.length === 0) return res.status(401).json({ error: 'Invalid credentials' });

    const user = results[0];

    // Check if user is inactive
    if (user.status === 'inactive') {
      return res.status(403).json({ error: 'Your account is inactive. Please contact administrator.' });
    }

    const match = await bcrypt.compare(password, user.password);
    if (!match) return res.status(401).json({ error: 'Invalid credentials' });

    const token = jwt.sign(
      { id: user.id, email: user.email, role_id: user.role_id },
      process.env.JWT_SECRET,
      { expiresIn: '1h' }
    );

    res.status(200).json({
      message: 'Login successful',
      token,
      user: { id: user.id, name: user.name, email: user.email, role_id: user.role_id }
    });
  } catch (err) {
    console.error('Login error:', err.message);
    res.status(500).json({ error: 'Server error during login.' });
  }
});

// GET all roles
app.get('/api/roles', authenticateToken, async (req, res) => {
  try {
    // Only get roles 3 (keuangan) and 4 (panitia)
    const [roles] = await db.query('SELECT id, name FROM roles WHERE id IN (3,4)');
    res.json({ roles });
  } catch (err) {
    res.status(500).json({ error: 'Server error fetching roles.' });
  }
});

// GET all users (admin only)
app.get('/api/users', authenticateToken, async (req, res) => {
  try {
    if (req.user.role_id !== 2) {
      return res.status(403).json({ error: 'Forbidden' });
    }
    // Only get users with role_id 3 (keuangan) or 4 (panitia)
    const [users] = await db.query(
      'SELECT id, name, email, role_id, status, created_at FROM users WHERE role_id IN (3,4)'
    );
    res.json({ users });
  } catch (err) {
    console.error('Get users error:', err.message);
    res.status(500).json({ error: 'Server error fetching users.' });
  }
});

// GET user by id (admin only)
app.get('/api/users/:id', authenticateToken, async (req, res) => {
  try {
    if (req.user.role_id !== 2) return res.status(403).json({ error: 'Forbidden' });
    const { id } = req.params;
    const [users] = await db.query('SELECT id, name, email, role_id, status FROM users WHERE id=?', [id]);
    if (!users.length) return res.status(404).json({ error: 'User tidak ditemukan.' });
    res.json(users[0]);
  } catch (err) {
    console.error('Get user by id error:', err.message);
    res.status(500).json({ error: 'Server error.' });
  }
});

// ADD user (admin only)
app.post('/api/users', authenticateToken, async (req, res) => {
  try {
    if (req.user.role_id !== 2) return res.status(403).json({ error: 'Forbidden' });
    const { name, email, password, role_id } = req.body;
    
    // Validate role_id is either 3 or 4
    if (role_id !== 3 && role_id !== 4) {
      return res.status(400).json({ error: 'Invalid role. Can only add users with role 3 or 4.' });
    }

    if (!name || !email || !password || !role_id) {
      return res.status(400).json({ error: 'Semua field wajib diisi.' });
    }

    const hashedPassword = await bcrypt.hash(password, 10);
    await db.query(
      'INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, ?)', 
      [name, email, hashedPassword, role_id]
    );
    res.json({ message: 'User berhasil ditambahkan.' });
  } catch (err) {
    console.error('Add user error:', err.message);
    res.status(500).json({ error: 'Server error adding user.' });
  }
});

// EDIT user (admin only)
app.put('/api/users/:id', authenticateToken, async (req, res) => {
  try {
    if (req.user.role_id !== 2) return res.status(403).json({ error: 'Forbidden' });
    const { name, email, password, role_id, status } = req.body;
    const { id } = req.params;

    // Validate role_id is either 3 or 4
    if (role_id !== 3 && role_id !== 4) {
      return res.status(400).json({ error: 'Invalid role. Can only update to role 3 or 4.' });
    }

    // Check if target user has role 3 or 4
    const [user] = await db.query('SELECT role_id FROM users WHERE id = ?', [id]);
    if (!user.length || ![3,4].includes(user[0].role_id)) {
      return res.status(403).json({ error: 'Can only modify users with role 3 or 4.' });
    }

    let sql, params;
    if (password) {
      const hashedPassword = await bcrypt.hash(password, 10);
      sql = 'UPDATE users SET name=?, email=?, password=?, role_id=?, status=? WHERE id=?';
      params = [name, email, hashedPassword, role_id, status, id];
    } else {
      sql = 'UPDATE users SET name=?, email=?, role_id=?, status=? WHERE id=?';
      params = [name, email, role_id, status, id];
    }
    await db.query(sql, params);
    res.json({ message: 'User berhasil diupdate.' });
  } catch (err) {
    console.error('Edit user error:', err.message);
    res.status(500).json({ error: 'Server error updating user.' });
  }
});

// DELETE user (admin only)
app.delete('/api/users/:id', authenticateToken, async (req, res) => {
  try {
    if (req.user.role_id !== 2) return res.status(403).json({ error: 'Forbidden' });
    const { id } = req.params;

    // Check if target user has role 3 or 4
    const [user] = await db.query('SELECT role_id FROM users WHERE id = ?', [id]);
    if (!user.length || ![3,4].includes(user[0].role_id)) {
      return res.status(403).json({ error: 'Can only delete users with role 3 or 4.' });
    }

    await db.query('DELETE FROM users WHERE id=?', [id]);
    res.json({ message: 'User berhasil dihapus.' });
  } catch (err) {
    console.error('Delete user error:', err.message);
    res.status(500).json({ error: 'Server error deleting user.' });
  }
});

// ==================== EVENT ENDPOINTS ====================

// GET public events (no auth required)
app.get('/api/public/events', async (req, res) => {
  try {
    const [events] = await db.query('SELECT * FROM events WHERE is_active = 1 ORDER BY date DESC');
    res.json({ events });
  } catch (err) {
    console.error('Get public events error:', err.message);
    res.status(500).json({ error: 'Server error fetching events.' });
  }
});

// GET all events
app.get('/api/events', authenticateToken, async (req, res) => {
  try {
    const [events] = await db.query('SELECT * FROM events ORDER BY date DESC');
    res.json({ events });
  } catch (err) {
    console.error('Get events error:', err.message);
    res.status(500).json({ error: 'Server error fetching events.' });
  }
});

// GET event by id
app.get('/api/events/:id', authenticateToken, async (req, res) => {
  try {
    const { id } = req.params;
    const [events] = await db.query('SELECT * FROM events WHERE id=?', [id]);
    if (!events.length) return res.status(404).json({ error: 'Event tidak ditemukan.' });
    res.json(events[0]);
  } catch (err) {
    console.error('Get event by id error:', err.message);
    res.status(500).json({ error: 'Server error.' });
  }
});

// ADD event
app.post('/api/events', authenticateToken, upload.single('poster'), async (req, res) => {
  try {
    const { name, description, date, time, location, speaker, price, max_participants, is_active, created_by } = req.body;
    const posterPath = req.file ? '/uploads/' + req.file.filename : null;
    await db.query(
      'INSERT INTO events (name, description, date, time, location, speaker, poster, price, max_participants, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
      [name, description, date, time, location, speaker, posterPath, price, max_participants, is_active, created_by]
    );
    res.json({ message: 'Event berhasil ditambahkan.' });
  } catch (err) {
    console.error('Add event error:', err.message);
    res.status(500).json({ error: 'Server error adding event.' });
  }
});

// EDIT event
app.put('/api/events/:id', authenticateToken, upload.single('poster'), async (req, res) => {
  try {
    const { name, description, date, time, location, speaker, price, max_participants, is_active } = req.body;
    const { id } = req.params;
    let sql, params;
    if (req.file) {
      const posterPath = '/uploads/' + req.file.filename;
      sql = 'UPDATE events SET name=?, description=?, date=?, time=?, location=?, speaker=?, poster=?, price=?, max_participants=?, is_active=? WHERE id=?';
      params = [name, description, date, time, location, speaker, posterPath, price, max_participants, is_active, id];
    } else {
      sql = 'UPDATE events SET name=?, description=?, date=?, time=?, location=?, speaker=?, price=?, max_participants=?, is_active=? WHERE id=?';
      params = [name, description, date, time, location, speaker, price, max_participants, is_active, id];
    }
    await db.query(sql, params);
    res.json({ message: 'Event berhasil diupdate.' });
  } catch (err) {
    console.error('Edit event error:', err.message);
    res.status(500).json({ error: 'Server error updating event.' });
  }
});

// Add new endpoint in app.js
app.post('/api/registrations', authenticateToken, async (req, res) => {
    try {
        const { event_id, users_id, total_tickets, participant_names } = req.body;
        
        // Check available tickets first
        const [event] = await db.query('SELECT max_participants FROM events WHERE id = ?', [event_id]);
        const [sold] = await db.query(
            'SELECT COALESCE(SUM(total_tickets), 0) as sold_tickets FROM event_registration WHERE event_id = ?', 
            [event_id]
        );
        
        const availableTickets = event[0].max_participants - sold[0].sold_tickets;
        
        if (total_tickets > availableTickets) {
            return res.status(400).json({ 
                error: `Only ${availableTickets} tickets remaining` 
            });
        }

        // Validate participant_names
        if (!Array.isArray(participant_names) || participant_names.length !== Number(total_tickets)) {
            return res.status(400).json({ error: 'Participant names must be provided for each ticket.' });
        }
        
        // Proceed with registration if tickets are available
        const [registration] = await db.query(
            'INSERT INTO event_registration (event_id, users_id, total_tickets, registered_at) VALUES (?, ?, ?, NOW())',
            [event_id, users_id, total_tickets]
        );
        const registrationId = registration.insertId;

        // Insert tickets, dapatkan id ticket, lalu update qr_code
        for (const name of participant_names) {
          // 1. Insert ticket tanpa qr_code dan qr_value dulu
          const [ticketResult] = await db.query(
              'INSERT INTO ticket (event_registration_id, qr_code, participant_name) VALUES (?, ?, ?)',
              [registrationId, null, name]
          );
          const ticketId = ticketResult.insertId;

          // 2. Buat kode unik untuk QR code
          const qrValue = `TICKET-${event_id}-${users_id}-${ticketId}`;

          // 3. Generate QR code (base64 image) dari qrValue
          const qr_code = await QRCode.toDataURL(qrValue);

          // 4. Update ticket dengan qr_code dan qr_value
          await db.query(
              'UPDATE ticket SET qr_code = ?, qr_value = ? WHERE id = ?',
              [qr_code, qrValue, ticketId]
          );
      }
        
        res.json({ id: registrationId });
    } catch (err) {
        console.error('Registration error:', err);
        res.status(500).json({ error: 'Failed to register for event' });
    }
});

app.get('/api/events/:id/available-tickets', authenticateToken, async (req, res) => {
  try {
    // Get total tickets for this event
    const [event] = await db.query('SELECT max_participants FROM events WHERE id = ?', [req.params.id]);
    
    if (!event.length) {
      return res.status(404).json({ error: 'Event not found' });
    }

    // Get sum of tickets already sold
    const [sold] = await db.query(
      'SELECT COALESCE(SUM(total_tickets), 0) as sold_tickets FROM event_registration WHERE event_id = ?',
      [req.params.id]
    );

    const availableTickets = event[0].max_participants - sold[0].sold_tickets;
    
    res.json({ available: availableTickets });
  } catch (err) {
    console.error('Error checking available tickets:', err);
    res.status(500).json({ error: 'Server error' });
  }
});

app.get('/api/my-events', authenticateToken, async (req, res) => {
  try {
    const userId = req.user.id;
    const [registrations] = await db.query(`
      SELECT r.*, e.name, e.description, e.poster, e.date, e.location, e.is_active, p.status AS payment_status
      FROM event_registration r
      JOIN events e ON r.event_id = e.id
      LEFT JOIN payments p ON r.payment_id = p.id
      WHERE r.users_id = ?
      ORDER BY r.registered_at DESC
    `, [userId]);

    // Ambil tiket untuk setiap registration
    for (const reg of registrations) {
      const [tickets] = await db.query(
        'SELECT id, participant_name, qr_code, scanned_at, certificate_path FROM ticket WHERE event_registration_id = ?',
        [reg.id]
      );
      reg.tickets = tickets;
    }

    res.json({ events: registrations });
  } catch (err) {
    res.status(500).json({ error: 'Server error' });
  }
});

const paymentStorage = multer.diskStorage({
  destination: function (req, file, cb) {
    cb(null, path.join(__dirname, 'uploads/payments'));
  },
  filename: function (req, file, cb) {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
    cb(null, uniqueSuffix + '-' + file.originalname);
  }
});
const paymentUpload = multer({ storage: paymentStorage });

// Endpoint upload bukti pembayaran
app.post('/api/payments', authenticateToken, paymentUpload.single('proof'), async (req, res) => {
  try {
    const { registration_id } = req.body;
    const uploaded_by = req.user.id;
    const proof_path = '/uploads/payments/' + req.file.filename;

    // Insert ke payments
    const [result] = await db.query(
      `INSERT INTO payments (registration_id, uploaded_by, proof_path, status, uploaded_at) VALUES (?, ?, ?, 'pending', NOW())`,
      [registration_id, uploaded_by, proof_path]
    );
    const paymentId = result.insertId;

    // Update event_registration.payment_id
    await db.query(
      `UPDATE event_registration SET payment_id = ? WHERE id = ?`,
      [paymentId, registration_id]
    );

    res.json({ success: true });
  } catch (err) {
    console.error('Payment upload error:', err);
    res.status(500).json({ error: 'Gagal upload pembayaran' });
  }
});

app.get('/api/registration/:id', authenticateToken, async (req, res) => {
  try {
    const [rows] = await db.query(`
      SELECT r.*, e.name, e.description, e.poster, e.date, e.location, e.price
      FROM event_registration r
      JOIN events e ON r.event_id = e.id
      WHERE r.id = ?
    `, [req.params.id]);
    if (!rows.length) return res.status(404).json({ error: 'Not found' });
    res.json({ registration: rows[0], event: rows[0] });
  } catch (err) {
    res.status(500).json({ error: 'Server error' });
  }
});

app.get('/api/finance/approvals', authenticateToken, async (req, res) => {
  try {
    const [rows] = await db.query(`
      SELECT r.users_id, u.name as user_name, r.registered_at, r.total_tickets, e.name, e.date, e.location, p.id as payment_id, p.proof_path, p.status as payment_status
      FROM event_registration r
      JOIN users u ON r.users_id = u.id
      JOIN events e ON r.event_id = e.id
      JOIN payments p ON r.payment_id = p.id
      WHERE p.status = 'pending'
      ORDER BY r.registered_at DESC
    `);
    res.json({ events: rows });
  } catch (err) {
    res.status(500).json({ error: 'Server error' });
  }
});

app.post('/api/finance/approve', authenticateToken, async (req, res) => {
  try {
    const { payment_id, status } = req.body;
    // status: 'verified' atau 'rejected'
    await db.query(
      `UPDATE payments SET status = ?, verified_by = ?, verified_at = NOW() WHERE id = ?`,
      [status, req.user.id, payment_id]
    );
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: 'Gagal update status pembayaran' });
  }
});

app.post('/api/scan-ticket', authenticateToken, async (req, res) => {
  const { qr_code } = req.body;
  try {
    // Cari tiket berdasarkan qr_value
    const [tickets] = await db.query(
      `SELECT t.*, e.name as event_name, e.date as event_date, e.location, e.time as event_time
       FROM ticket t
       JOIN event_registration r ON t.event_registration_id = r.id
       JOIN events e ON r.event_id = e.id
       WHERE t.qr_value = ?`, [qr_code]
    );
    if (!tickets.length) return res.json({ error: 'Tiket tidak ditemukan!' });

    const ticket = tickets[0];
    if (ticket.scanned_at) {
      return res.json({ error: 'Tiket sudah pernah di-scan pada ' + ticket.scanned_at });
    }

    // Update scanned_at dan scanned_by
    await db.query(
      'UPDATE ticket SET scanned_at = NOW(), scanned_by = ? WHERE id = ?',
      [req.user.id, ticket.id]
    );

    res.json({
      success: true,
      ticket: {
        participant_name: ticket.participant_name,
        event_name: ticket.event_name,
        event_date: ticket.event_date,
        event_time: ticket.event_time
      }
    });
  } catch (err) {
    res.status(500).json({ error: 'Server error' });
  }
});

app.post('/api/tickets/:id/certificate', authenticateToken, certificateUpload.single('certificate'), async (req, res) => {
  try {
    const ticketId = req.params.id;
    if (!req.file) return res.status(400).json({ error: 'No file uploaded' });

    const certPath = 'uploads/certificates/' + req.file.filename;
    await db.query('UPDATE ticket SET certificate_path = ? WHERE id = ?', [certPath, ticketId]);
    res.json({ success: true, path: certPath });
  } catch (err) {
    console.error('Upload certificate error:', err);
    res.status(500).json({ error: 'Gagal upload sertifikat' });
  }
});

app.get('/api/all-tickets', authenticateToken, async (req, res) => {
  try {
    // Hanya panitia (role_id 4) yang boleh akses
    // if (req.user.role_id !== 4) return res.status(403).json({ error: 'Forbidden' });

    const [tickets] = await db.query(`
      SELECT t.id, t.participant_name, t.scanned_at, t.certificate_path, e.name as event_name
      FROM ticket t
      JOIN event_registration r ON t.event_registration_id = r.id
      JOIN events e ON r.event_id = e.id
      ORDER BY t.id DESC
    `);
    res.json({ tickets });
  } catch (err) {
    res.status(500).json({ error: 'Server error' });
  }
});

// Protected route
app.get('/dashboard', authenticateToken, (req, res) => {
  res.json({ message: 'Welcome to the dashboard!', user: req.user });
});

app.listen(port, () => {
  console.log(`✅ Server running at http://localhost:${port}`);
});