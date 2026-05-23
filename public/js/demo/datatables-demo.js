// Call the dataTables jQuery plugin
$(document).ready(function() {
  $('#dataTable').DataTable({
        language: { url: "/js/lang/Spanish.json" },
        order: [[0, "desc"]],
        pageLength: 25,
        dom: 'Bfrtip',
        responsive: true,
    });

});
