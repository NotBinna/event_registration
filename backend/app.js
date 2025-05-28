// index.js
const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
require('dotenv').config();

const db = require('./db');
const authenticateToken = require('./middleware/auth');

const app = express();
const port = 3000;

// Middleware
app.use(cors({
  origin: 'http://localhost:8000',
  credentials: true
}));
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// Default route
app.get('/', (req, res) => {
  res.send('API is running!');
});

// Protected route
app.get('/dashboard', authenticateToken, (req, res) => {
  res.json({
    message: 'Welcome to the dashboard!',
    user: req.user
  });
});

// Register
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
    db.query(sql, [name, email, hashedPassword, role_id, status], (err, result) => {
      if (err) return res.status(500).json({ error: err.message });

      res.status(200).json({ message: 'User registered successfully' });
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});


// Login
app.post('/api/login', async (req, res) => {
  const { email, password } = req.body;

  if (!email || !password) {
    return res.status(400).json({ error: 'Email and password are required.' });
  }

  try {
    const sql = 'SELECT * FROM users WHERE email = ?';
    db.query(sql, [email], async (err, results) => {
      if (err) return res.status(500).json({ error: err.message });
      if (results.length === 0) return res.status(401).json({ error: 'Invalid credentials' });

      const user = results[0];
      const match = await bcrypt.compare(password, user.password);
      if (!match) return res.status(401).json({ error: 'Invalid credentials' });

      // Optional: create session or JWT token
      res.status(200).json({ message: 'Login successful', user });
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Jalankan server
app.listen(port, () => {
  console.log(`Server running at http://localhost:${port}`);
});
