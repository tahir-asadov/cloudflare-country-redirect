document.getElementById('ccr-add-row').addEventListener('click', function () {
  const table = document.querySelector('#ccr-rules-table tbody');
  const row = document.createElement('tr');
  row.innerHTML = `
      <td><input type="text" placeholder="${wp.i18n.__('Country Code', 'redirect-by-country')}: es" name="country[]" value="" /></td>
      <td><input type="url" placeholder="https://example.com/es" name="url[]" value="" style="width: 100%;" /></td>
      <td><button type="button" class="button ccr-remove-row">${wp.i18n.__('Remove', 'redirect-by-country')}</button></td>
  `;
  table.appendChild(row);
});

document.addEventListener('click', function (e) {
  if (e.target && e.target.classList.contains('ccr-remove-row')) {
    e.target.closest('tr').remove();
  }
});
