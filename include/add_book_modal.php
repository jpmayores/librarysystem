<div id="addBookModal" class="modal">
    <div class="modal-content">
        <h2>Magdagdag ng Libro</h2>
        <form action="actions/add_book.php" method="POST" enctype="multipart/form-data" onsubmit="return validateFileSize()">
            <input type="text" name="title" placeholder="Book Title" required>
            <input type="text" name="author" placeholder="Author" required>
            
            <!-- Category / Genre Dropdown -->
            <label style="display: block; margin: 8px 0 4px; font-size: 13px; color: #4a5568; font-weight: 600;">
                Select Category:
            </label>
            <select name="category" required style="width: 100%; padding: 10px; margin-bottom: 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                <option value="" disabled selected>-- Select Genre / Category --</option>
                <option value="Horror">Horror</option>
                <option value="Food">Food</option>
                <option value="Fantasy">Fantasy</option>
            </select>
            
            <!-- Status Dropdown -->
            <select name="status" style="width: 100%; padding: 10px; margin-bottom: 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                <option value="Available">Available</option>
                <option value="Borrowed">Borrowed</option>
            </select>

            <!-- File Upload Field -->
            <label style="display: block; margin: 8px 0 4px; font-size: 13px; color: #4a5568; font-weight: 600;">
                Book Cover Image File (Max 2MB):
            </label>
            <input type="file" id="cover_image" name="cover_image" accept="image/*">

            <!-- URL Alternative -->
            <label style="display: block; margin: 8px 0 4px; font-size: 13px; color: #4a5568; font-weight: 600;">
                o Cover Image URL:
            </label>
            <input type="url" name="cover_url" placeholder="https://example.com/cover.jpg">

            <div class="modal-buttons">
                <button type="button" class="btn-close" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save Book</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('addBookModal').style.display = 'block';
    }
    
    function closeAddModal() {
        document.getElementById('addBookModal').style.display = 'none';
    }

    function validateFileSize() {
        const fileInput = document.getElementById('cover_image');
        const maxFileSize = 2 * 1024 * 1024; // 2 MB Limit

        if (fileInput.files.length > 0) {
            const fileSize = fileInput.files[0].size;
            if (fileSize > maxFileSize) {
                alert("Masyadong malaki ang file! Ang maximum size na pinapayagan ay 2MB para maiwasan ang quota limit.");
                return false;
            }
        }
        return true;
    }
</script>