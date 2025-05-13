
<?php

include 'trackerDB.php';

$cat_name = $_GET['name'];

?>

 <html>
  <head>
    <script type="text/javascript" src="js/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable([

         <?php

echo "['Day',  {label: '$cat_name', type: 'number'} ],";

?>


  <?php

$select = $con->query("SELECT * FROM details group by STR_TO_DATE(start_date, '%d %M, %Y')");
while ($row = $select->fetch()) {
    $st1 = str_replace(',', '', $row['start_date']);
    $Alt_date = date('Y-m-d', strtotime($st1));

    $select2 = $con->query("SELECT *,SUM(total_time) as totTime FROM details where cat_name='$cat_name' and STR_TO_DATE(start_date, '%d %M, %Y')='$Alt_date'  ");
    $fetch = $select2->fetch();
    $time_spent = round(($fetch['totTime'] / 60) / 60, 2);

    echo "['$Alt_date', $time_spent,],";
}

?>

        ]);

        var options = {
          title: 'System Performance',
          curveType: 'function',
          legend: { position: 'bottom' }
        };

        var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));

        chart.draw(data, options);
      }
    </script>
  </head>
  <body>
    <div id="curve_chart" style="width: 1900px; height: 700px"></div>
  </body>
</html>
