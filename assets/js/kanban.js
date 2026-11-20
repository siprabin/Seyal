/* Kanban board: drag a card to another column to change its status. */
(function () {
  'use strict';
  var board = document.getElementById('kanban');
  if (!board || !window.Sortable) return;
  var url = board.getAttribute('data-status-url');

  function recount() {
    board.querySelectorAll('.kanban-col').forEach(function (col) {
      col.querySelector('.count').textContent = col.querySelectorAll('.kanban-card').length;
    });
  }

  board.querySelectorAll('.kanban-list').forEach(function (list) {
    new Sortable(list, {
      group: 'tasks',
      animation: 150,
      filter: '.locked',
      ghostClass: 'sortable-ghost',
      onMove: function (evt) { return !evt.dragged.classList.contains('locked'); },
      onAdd: function (evt) {
        var card = evt.item;
        var status = evt.to.getAttribute('data-status');
        card.className = card.className.replace(/border-status-\S+/, 'border-status-' + status);
        recount();
        window.appPost(url, { id: card.getAttribute('data-id'), status: status })
          .catch(function (err) {
            alert(err.message || window.APP.t.saveFailed);
            window.location.reload();
          });
      }
    });
  });
})();
