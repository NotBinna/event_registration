require('dotenv').config();
const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const multer = require('multer');
const path = require('path');

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
    const match = await bcrypt.compare(password, user.password);
    if (!match) return res.status(401).json({ error: 'Invalid credentials' });

    // 🔐 Buat JWT Token
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
    const [roles] = await db.query('SELECT id, name FROM roles');
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
    const [users] = await db.query('SELECT id, name, email, role_id, status, created_at FROM users');
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
    if (!name || !email || !password || !role_id) return res.status(400).json({ error: 'Semua field wajib diisi.' });

    const hashedPassword = await bcrypt.hash(password, 10);
    await db.query('INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, ?)', [name, email, hashedPassword, role_id]);
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
    await db.query('DELETE FROM users WHERE id=?', [id]);
    res.json({ message: 'User berhasil dihapus.' });
  } catch (err) {
    console.error('Delete user error:', err.message);
    res.status(500).json({ error: 'Server error deleting user.' });
  }
});

// ==================== EVENT ENDPOINTS ====================

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

// DELETE event
app.delete('/api/events/:id', authenticateToken, async (req, res) => {
  try {
    const { id } = req.params;
    await db.query('DELETE FROM events WHERE id=?', [id]);
    res.json({ message: 'Event berhasil dihapus.' });
  } catch (err) {
    console.error('Delete event error:', err.message);
    res.status(500).json({ error: 'Server error deleting event.' });
  }
});

// Protected route
app.get('/dashboard', authenticateToken, (req, res) => {
  res.json({ message: 'Welcome to the dashboard!', user: req.user });
});

app.listen(port, () => {
  console.log(`✅ Server running at http://localhost:${port}`);
});