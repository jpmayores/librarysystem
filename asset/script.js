 function openBorrowModal(bookId, bookTitle) {
            document.getElementById('modalBookId').value = bookId;
            document.getElementById('modalBookTitle').textContent = 'Borrow: ' + bookTitle;
            document.getElementById('borrowModal').style.display = 'flex';
        }

        function closeBorrowModal() {
            document.getElementById('borrowModal').style.display = 'none';
        }

        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;

        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'dark') {
            body.classList.add('dark-mode');
            darkModeToggle.textContent = 'Light Mode';
        } else if (currentTheme === 'light') {
            body.classList.remove('dark-mode');
            darkModeToggle.textContent = 'Dark Mode';
        }

        darkModeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            let theme = 'light';
            if (body.classList.contains('dark-mode')) {
                theme = 'dark';
                darkModeToggle.textContent = 'Light Mode';
            } else {
                darkModeToggle.textContent = 'Dark Mode';
            }
            localStorage.setItem('theme', theme);
        });
        function openEditModal(id, title, author, category, status) {
    document.getElementById('edit_book_id').value = id;
    document.getElementById('edit_book_title').value = title;
    document.getElementById('edit_book_author').value = author;
    document.getElementById('edit_book_category').value = category;
    document.getElementById('edit_book_status').value = status;
    document.getElementById('editBookModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editBookModal').style.display = 'none';
}

//? FOR OVERDUE FINE FUNCTION
function openReturnModal(txnId, bookId) {
    console.log("Opening Modal - TXN:", txnId, "Book:", bookId); // Pwede mong icheck sa F12 Console
    
    document.getElementById('return_txn_id').value = txnId;
    document.getElementById('return_book_id').value = bookId; // Dito napupunta ang book_id
    document.getElementById('condition_status').value = 'Good';
    
    var feeBox = document.getElementById('damageFeeContainer');
    if (feeBox) {
        feeBox.style.display = 'none';
    }
    
    document.getElementById('returnBookModal').style.display = 'flex';
}
function closeReturnModal() {
    document.getElementById('returnBookModal').style.display = 'none';
}

function toggleDamageFee() {
    var statusSelect = document.getElementById('condition_status');
    var feeBox = document.getElementById('damageFeeContainer');
    
    if (statusSelect && feeBox) {
        if (statusSelect.value === 'Damaged') {
            feeBox.style.display = 'block'; // Ipakita nang direkta
        } else {
            feeBox.style.display = 'none';  // Itago kung Good
            // I-reset sa 0.00 ang input kung ibinalik sa Good
            var damageInput = feeBox.querySelector('input[name="damage_fee"]');
            if (damageInput) {
                damageInput.value = '0.00';
            }
        }
    }
}





function togglePassword(fieldId, element) {
            const inputField = document.getElementById(fieldId);
            const svgIcon = element.querySelector('svg');
            
            if (inputField.type === "password") {
                inputField.type = "text";
                // Eye slash (nakapikit / hide) SVG
                svgIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.07 10.07 0 014.213-5.362m3.9-1.348A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.027 10.027 0 01-4.132 5.411m0 0L21 21" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                `;
            } else {
                inputField.type = "password";
                // Normal eye (nakabukas / show) SVG
                svgIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                `;
            }
        }