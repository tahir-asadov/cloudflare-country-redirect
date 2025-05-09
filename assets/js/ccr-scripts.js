document.getElementById('ccr-add-row').addEventListener('click', function () {
  const table = document.querySelector('#ccr-rules-table tbody');
  const row = document.createElement('tr');
  row.innerHTML = `
      <td><input type="text" name="country[]" value="" /></td>
      <td><input type="url" name="url[]" value="" style="width: 100%;" /></td>
      <td><button type="button" class="button ccr-remove-row">Remove</button></td>
  `;
  table.appendChild(row);
});

document.addEventListener('click', function (e) {
  if (e.target && e.target.classList.contains('ccr-remove-row')) {
    e.target.closest('tr').remove();
  }
});
