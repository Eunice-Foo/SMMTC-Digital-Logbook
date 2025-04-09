function directSign(entryId) {
    fetch('sv_sign_log.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `entry_id=${entryId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error signing log: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error signing log');
    });
}

function signLog(entryId) {
    // Create modal overlay for remarks
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <h3>Add Remarks</h3>
            <div class="form-group">
                <label for="remarks">Remarks:</label>
                <textarea 
                    id="remarks" 
                    placeholder="Enter your remarks here..."
                    maxlength="1000"
                ></textarea>
            </div>
            <div class="modal-buttons">
                <button onclick="submitSignature(${entryId})" class="btn btn-sign">Sign with Remarks</button>
                <button onclick="closeModal()" class="btn btn-cancel">Cancel</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeModal() {
    const modal = document.querySelector('.modal');
    if (modal) {
        modal.remove();
    }
}

function submitSignature(entryId) {
    const remarks = document.getElementById('remarks').value;
    
    fetch('sv_sign_log.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `entry_id=${entryId}&remarks=${encodeURIComponent(remarks)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error signing log: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error signing log');
    });
}