jQuery(document).ready(function(){
    jQuery('#empTable').DataTable({
       'processing': true,
       'serverSide': true,
       'serverMethod': 'post',
       'ajax': {
           'url':'/wordpress/wp-admin/admin-ajax.php'
       },
       'columns': [
          { data: 'emp_name' },
          { data: 'email' },
          { data: 'gender' },
          { data: 'salary' },
          { data: 'city' },
       ]
    });
 });
