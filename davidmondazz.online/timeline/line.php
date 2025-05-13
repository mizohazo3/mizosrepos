
<?php

include 'trackerDB.php';

?>

 <html>
  <head>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable([

         <?php

$selectCats = $con->query("SELECT * FROM categories");
$counter = 0;
while ($fetch = $selectCats->fetch()) {
    $array[] = $fetch['name'];
    $counter++;
}

echo "['Day', ";

foreach ($array as $key) {
    echo "{label: '$key', type: 'number'},";
}

echo "],";

?>


  <?php

$select = $con->query("SELECT * FROM details group by STR_TO_DATE(start_date, '%d %M, %Y')");
while ($row = $select->fetch()) {
    $st1 = str_replace(',', '', $row['start_date']);
    $Alt_date = date('Y-m-d', strtotime($st1));

    echo "['$Alt_date',";

    for ($i = 0; $i < $counter; $i++) {
        $catname = $array[$i];

        ${'select' . $i} = $con->query("SELECT *,SUM(total_time) as totTime FROM details where cat_name='$catname' and STR_TO_DATE(start_date, '%d %M, %Y')='$Alt_date'  ");
        ${'fetch' . $i} = ${'select' . $i}->fetch();
        echo ${'time_spent' . $i} = round((${'fetch' . $i}['totTime'] / 60) / 60, 2) . ',';
    }

    echo "],";

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
