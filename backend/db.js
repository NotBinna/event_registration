// db.js
const mysql = require('mysql2');

const pool = mysql.createPool({
  host: 'localhost',
  user: 'root',
  password: '',
  database: 'mydb_eventRegistration'
});

// Gunakan pool dengan .promise() agar bisa pakai async/await
module.exports = pool.promise();
