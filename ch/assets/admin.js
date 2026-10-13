'use strict';
document.addEventListener('submit', event => {
  const confirmation = event.submitter?.dataset.confirm || event.target.dataset.confirm;
  if (confirmation && !window.confirm(confirmation)) event.preventDefault();
});
