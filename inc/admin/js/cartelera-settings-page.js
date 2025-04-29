/**
 * Behaviour for the button `Filter by results only in tickermaster`
 * User localstorage to keep the selected option consistent across page loads.
 */

/**
 * show/hide the rows depending on the state of the button filter
 * @param bool activateDeactivate
 */
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

/**
 * handle when clicking the filter button.
 * @param {*} e
 */
const handleClickFilterButton = function(e) {


  const filterButton = document.getElementById('filter-by-yes-tickermaster');
  if (!filterButton) {
    console.error('didnt find #filter-by-yes-tickermaster', filterButton);
    return;
  }
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

  if (filterButton) {
    filterButton.addEventListener('click', handleClickFilterButton);
  }

});
