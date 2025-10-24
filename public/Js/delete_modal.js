// Open modal. `el` is the clicked Delete button element.
function openDeleteModal(el) {
    // Retrieve action from data-action attribute
    const action = el.dataset.action || el.getAttribute('data-action') || '';
    if (!action) {
      console.error('Delete action not provided on button (data-action).');
      return;
    }
  
    // Set form action and reset option
    const form = document.getElementById('deleteForm');
    form.action = action;
    document.getElementById('deleteOption').value = '';
  
    // Show modal
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
  
    // prevent page scroll while modal open
    document.body.style.overflow = 'hidden';
  }
  
  // Close modal
  function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
  
    // restore page scroll
    document.body.style.overflow = '';
  }
  
  // Submit modal choice (sets hidden input and submits the form)
  function submitDelete(option) {
    document.getElementById('deleteOption').value = option;
    document.getElementById('deleteForm').submit();
  }
  
  // Close modal on Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      const modal = document.getElementById('deleteModal');
      if (modal && modal.style.display === 'flex') closeDeleteModal();
    }
  });
  
  // Clicking outside modal-content closes it
  document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
  });