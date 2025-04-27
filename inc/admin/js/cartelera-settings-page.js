const activateFilter = function( activateDeactivate ) {
  const allRows = document.querySelectorAll('.result-row');
  if (activateDeactivate) {
    allRows.forEach( row => row.classList.add('hidden') );
    const tickermasterRows = document.querySelectorAll('.yes-tickermaster');
    tickermasterRows.forEach( row => row.classList.remove('hidden') );
  } else {
    allRows.forEach( row => row.classList.remove('hidden') );
  }
}
const handleClickFilterButton = function(e) {


  const filterButton = document.getElementById('filter-by-yes-tickermaster');
  filterButton.classList.toggle('active');
  const filterActive = filterButton.classList.contains('active');
  activateFilter(filterActive);

  localStorage.setItem('cartelera-activefilterActive', filterActive);
}

// On dom loaded
document.addEventListener('DOMContentLoaded', () => {

  console.log('Cartelera Settings Page script loaded.');

  const filterButton = document.getElementById('filter-by-yes-tickermaster');
  const activatefilterButton = localStorage.getItem('cartelera-activefilterActive') === 'true';
  if ( activatefilterButton ) {
    filterButton.classList.add('active');
    activateFilter(true);
  }

  // Example: Add a click event to a button with ID 'save-settings'

  if (filterButton) {
    filterButton.addEventListener('click', handleClickFilterButton);
  }


  // Example: Initialize other settings or UI components
  // Add your custom logic here
});
