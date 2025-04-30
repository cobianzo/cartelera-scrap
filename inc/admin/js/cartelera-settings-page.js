/**
 * Behaviour for the button `Filter by results only in tickermaster`
 * User localstorage to keep the selected option consistent across page loads.
 */

/**
 * show/hide the rows depending on the state of the button filter
 * @param bool activateDeactivate
 */
const activateFilter = function( activateDeactivate, selectorToHide, selectorToShowOnly ) {
  // hide all first
  const allRows = document.querySelectorAll(selectorToHide); // '.result-row'
  if (activateDeactivate) {
    allRows.forEach( row => row.classList.add('hidden') );
    // show our selector then
    const tickermasterRows = document.querySelectorAll(selectorToShowOnly); // '.yes-tickermaster'
    tickermasterRows.forEach( row => row.classList.remove('hidden') );
  } else {
    allRows.forEach( row => row.classList.remove('hidden') );
  }
}

const handleClickFilterButtonGeneric = function(event, buttonId, selectorHide, selectorShow) {
  const filterButton = document.getElementById(buttonId);
  if (!filterButton) {
    console.error('didnt find #'+buttonId, filterButton);
    return;
  }
  filterButton.classList.toggle('active');

  const filterActive = filterButton.classList.contains('active');

  activateFilter(filterActive, selectorHide, selectorShow);

  localStorage.setItem('cartelera-' + buttonId, filterActive);
}


// On dom loaded
document.addEventListener('DOMContentLoaded', () => {

  console.log('Cartelera Settings Page script loaded.');

  // Button 1
  const buttons = ['filter-by-yes-tickermaster', 'filter-by-fail-tickermaster', 'hide-full-url'];
  buttons.forEach( buttonId => {
    const filterButton = document.getElementById(buttonId);
    const activatefilterButton = localStorage.getItem('cartelera-' + buttonId) === 'true';

    let hideSelector = '.result-row';
    let showSelector = '.yes-tickermaster';
    if ( buttonId === 'filter-by-yes-tickermaster') {
      hideSelector = '.result-row';
      showSelector = '.yes-tickermaster';
    }
    if ( buttonId === 'filter-by-fail-tickermaster') {
      hideSelector = '.result-row';
      showSelector = '.yes-tickermaster.comparison-fail';
    }
    if ( buttonId === 'hide-full-url') {
      hideSelector = '.full-url';
      showSelector = '.not-existing';
    }

    if ( activatefilterButton ) {
      filterButton.classList.add('active');
      activateFilter( true, hideSelector, showSelector );
    }

    if (filterButton) {
      filterButton.addEventListener('click', (e) => {
        handleClickFilterButtonGeneric(e, buttonId, hideSelector, showSelector);
      });
    }

  });

});
