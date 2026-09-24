const express = require('express');
const mysql = require('mysql2');
const cors = require('cors');
const path = require('path');
require('dotenv').config();

const app = express();
app.use(cors());
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ limit: '50mb', extended: true }));app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

const db = mysql.createPool({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  port: process.env.DB_PORT || 3306,
  ssl: { rejectUnauthorized: false }, // REQUIRED para sa Aiven SSL
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// --- API ENDPOINTS ---

// 1. Dashboard Metrics Counter
app.get('/api/metrics', (req, res) => {
    const query = `
        SELECT 
            (SELECT COUNT(*) FROM books) as total_books,
            (SELECT COUNT(*) FROM books WHERE status = 'Available') as available_books,
            (SELECT COUNT(*) FROM books WHERE status = 'Borrowed') as borrowed_books,
            (SELECT IFNULL(SUM(fine), 0) FROM transactions WHERE status = 'Borrowed') as pending_fines
    `;
    db.query(query, (err, results) => {
        if (err) return res.status(500).json({ error: err.message });
        res.json(results[0]);
    });
});

// 2. Get All Books
// GET all books
app.get('/api/books', (req, res) => {
  const query = 'SELECT * FROM books';
  db.query(query, (err, results) => {
    if (err) {
      console.error('Error fetching books:', err);
      return res.status(500).json({ error: err.message });
    }
    res.json(results);
  });
});

// POST add a new book
app.post('/api/books', (req, res) => {
  const { title, author, category, image_url, genre } = req.body;
  
  // Gagamit ng category o genre depende sa kung alin ang isinend mula sa frontend
  const bookCategory = category || genre || 'General';
  const bookImage = image_url || '';

  const query = 'INSERT INTO books (title, author, category, image_url) VALUES (?, ?, ?, ?)';
  db.query(query, [title, author, bookCategory, bookImage], (err, result) => {
    if (err) {
      console.error('Error inserting book:', err);
      return res.status(500).json({ error: err.message });
    }
    res.status(201).json({ message: 'Book added successfully', id: result.insertId });
  });
});
// 4. Delete Book
app.delete('/api/books/:id', (req, res) => {
    const { id } = req.params;

    // 1. Burahin muna ang mga kaugnay na record sa transactions
    db.query('DELETE FROM transactions WHERE book_id = ?', [id], (err) => {
        if (err) {
            console.error('Error deleting transactions:', err);
            return res.status(500).json({ error: err.message });
        }

        // 2. Burahin ang libro
        db.query('DELETE FROM books WHERE id = ?', [id], (err, result) => {
            if (err) {
                console.error('Error deleting book:', err);
                return res.status(500).json({ error: err.message });
            }

            if (result.affectedRows === 0) {
                return res.status(404).json({ message: 'Book not found' });
            }

            res.json({ message: 'Book deleted successfully' });
        });
    });
});
// 5. Borrow Book
app.post('/api/borrow', (req, res) => {
    const { book_id, borrower_name, borrower_email, due_days } = req.body;

    // 1. Tignan kung umiiral na ang member batay sa email
    db.query('SELECT id FROM members WHERE email = ?', [borrower_email], (err, members) => {
        if (err) {
            console.error('Member Check Error:', err);
            return res.status(500).json({ error: err.message });
        }

        const proceedToBorrow = (memberId) => {
            const days = due_days || 7;
            const dueDate = new Date();
            dueDate.setDate(dueDate.getDate() + days);

            // 2. I-record ang transaction
            db.query(
                'INSERT INTO transactions (book_id, member_id, borrow_date, due_date) VALUES (?, ?, NOW(), ?)',
                [book_id, memberId, dueDate],
                (err) => {
                    if (err) {
                        console.error('Transaction Error:', err);
                        return res.status(500).json({ error: err.message });
                    }

                    // 3. I-update ang status ng libro sa "Borrowed"
                    db.query('UPDATE books SET status = "Borrowed" WHERE id = ?', [book_id], (err) => {
                        if (err) {
                            console.error('Update Status Error:', err);
                            return res.status(500).json({ error: err.message });
                        }

                        res.json({ message: 'Book borrowed successfully' });
                    });
                }
            );
        };

        // Kung may umiiral nang member, gamitin ang ID niya. Kung wala, i-insert muna.
        if (members && members.length > 0) {
            proceedToBorrow(members[0].id);
        } else {
            db.query(
                'INSERT INTO members (name, email) VALUES (?, ?)',
                [borrower_name, borrower_email],
                (err, result) => {
                    if (err) {
                        console.error('Member Insert Error:', err);
                        return res.status(500).json({ error: err.message });
                    }
                    proceedToBorrow(result.insertId);
                }
            );
        }
    });
});
// 6. Return Book (Mark Returned)
app.post('/api/return', (req, res) => {
  const { transaction_id, book_id } = req.body;

  // 1. I-update ang transaction status sa 'Returned'
  const updateTxn = 'UPDATE transactions SET status = "Returned", return_date = NOW() WHERE id = ?';
  
  db.query(updateTxn, [transaction_id], (err) => {
    if (err) return res.status(500).json({ error: err.message });

    // 2. I-update ang status ng libro pabalik sa 'Available'
  const updateBook = 'UPDATE books SET status = ? WHERE id = ?';
db.query(updateBook, ['Borrowed', book_id], (err) => {
  if (err) console.error("Update Status Error:", err);


      res.json({ message: 'Book returned successfully' });
    });
  });
});
// 7. Get All Transactions
app.get('/api/transactions', (req, res) => {
    const query = `
        SELECT t.id, t.id as txn_code, b.id as book_id, b.title as book_title, 
               IFNULL(m.name, 'Guest Member') as member_name, 
               t.borrow_date, t.due_date, t.return_date, t.fine, t.status
        FROM transactions t
        JOIN books b ON t.book_id = b.id
        LEFT JOIN members m ON t.member_id = m.id
        ORDER BY t.borrow_date DESC
    `;
    db.query(query, (err, results) => {
        if (err) return res.status(500).json({ error: err.message });
        res.json(results);
    });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`🚀 Server running sa http://localhost:${PORT}`);
});