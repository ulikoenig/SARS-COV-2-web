<?php

declare(strict_types=1);
header("Content-type: text/html; charset=utf-8\r\n");
include_once("connect.php");
include_once("bundesland.php");

$dbInstance = ConnectDB::getInstance();
$TABLE =  $dbInstance::TABLE;
$link = $dbInstance->link;



$landkreisSQL = "";
if (isset($_GET['land'])) {
	$landkreisSQL = Bundesland::get($_GET['land']);
} else {
	$landkreisSQL = Bundesland::get(0);
}

class Landkreise
{
	public $name;
	public $genesen;
	public $infiziert;
	public $nochInfiziert;
	public $verstorben;
}


$sql = "SELECT SUBSTRING(Landkreis,4) AS Landkreis, SUM(Anzahlgenesen) AS Genesen, SUM(AnzahlFall) AS Infiziert, SUM(AnzahlFall)-SUM(Anzahlgenesen) AS NochInfiziert, SUM(AnzahlTodesfall) AS Tot FROM " . $TABLE . " WHERE " .
	$landkreisSQL . " GROUP BY Landkreis  ORDER BY Landkreis;";
if ($result = mysqli_query($link, $sql)) {
	/*echo "<!-- SELECT successfully, Returned rows are: " . mysqli_num_rows($result) . " -->";*/
} else {
	echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($link) . "\n -->";
}


$landkreise = array();

while ($row = mysqli_fetch_assoc($result)) {
	$landkreis = new Landkreise();
	$landkreis->name = $row["Landkreis"];
	$landkreis->genesen = $row["Genesen"];
	$landkreis->infiziert =  $row["Infiziert"];
	$landkreis->nochInfiziert =  $row["NochInfiziert"];
	$landkreis->verstorben =  $row["Tot"];
	$landkreise[] = $landkreis;
}
$result->free_result();

$labels = "labels: [";
foreach ($landkreise as $value) {
	$labels .= '\'';
	$labels .= $value->name;
	$labels .= '\',';
}
$labels = substr($labels, 0, -1);
$labels .= "],";


/* Grafik 2*/
class DatumSatz
{
	public $datum;
	public $genesen;
	public $infiziert;
	public $nochInfiziert;
	public $verstorben;
}


$sql = "SELECT SUM(AnzahlFall) AS Infiziert,SUM(AnzahlGenesen) AS Genesen,SUM(AnzahlFall)-SUM(AnzahlGenesen) AS NochInfiziert, SUM(AnzahlTodesfall) AS Tot, Refdatum, DATEDIFF( Refdatum, '2020-01-01') AS DayIndex FROM " . $TABLE . " 
WHERE " . $landkreisSQL . " GROUP BY Refdatum ORDER BY Refdatum";
if ($result = mysqli_query($link, $sql)) {
	/*	echo "<!-- SELECT successfully, Returned rows are: " . mysqli_num_rows($result) . " -->";*/
} else {
	echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($link) . "\n -->";
}

$datums = array();
$minDatumIndex = PHP_INT_MAX;
$maxDatumIndex = 0;

while ($row = mysqli_fetch_assoc($result)) {
	$landkreis = new DatumSatz();
	$landkreis->datum = $row["Refdatum"];
	$landkreis->genesen = $row["Genesen"];
	$landkreis->infiziert =  $row["Infiziert"];
	$landkreis->nochInfiziert =  $row["NochInfiziert"];
	$landkreis->verstorben =  $row["Tot"];
	$dayIndex = intval($row["DayIndex"]);

	/*set min/max */
	if ($dayIndex < $minDatumIndex) $minDatumIndex = $dayIndex;
	if ($dayIndex > $maxDatumIndex) $maxDatumIndex = $dayIndex;
	$datums[$dayIndex] = $landkreis;
}

$Datumlabels = "labels: [";
foreach ($datums as $value) {
	$Datumlabels .= '\'';
	$Datumlabels .= $value->datum;
	$Datumlabels .= '\',';
}
$Datumlabels = substr($Datumlabels, 0, -1);
$Datumlabels .= "],";

/**
 * Pro 100.000 Einwohner
 */
$proEinwohner = 100000;
$nachkomma = 1;
$nachkommaTod = 2;

$sql = "SELECT x.IdLandkreis, SUBSTRING(x.Landkreis,4) AS Landkreis, ROUND((Genesen/Einwohner) * 100000," . $nachkomma . ") AS Genesen,  ROUND((Infiziert/Einwohner) * 100000," . $nachkomma . ") AS Infiziert, ROUND((
NochInfiziert/Einwohner) * 100000," . $nachkomma . ") AS NochInfiziert,  ROUND((Tot/Einwohner) * 100000," . $nachkommaTod . ") AS Tot FROM Kreise,
(SELECT " . $TABLE . ".IdLandkreis, " . $TABLE . ".Landkreis, SUM(Anzahlgenesen ) AS Genesen, SUM(AnzahlFall) AS Infiziert, SUM(AnzahlFall)-SUM(Anzahlgenesen) AS NochInfiziert, SUM(AnzahlTodesfall) AS Tot FROM " . $TABLE . " WHERE  " .
	$landkreisSQL
	. " GROUP BY " . $TABLE . ".Landkreis, " . $TABLE . ".IdLandkreis) AS x WHERE Kreise.Krs = x.IdLandkreis ORDER BY Landkreis;";

if ($result = mysqli_query($link, $sql)) {
	/*echo "<!-- SELECT successfully, Returned rows are: " . mysqli_num_rows($result) . " -->";*/
} else {
	echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($link) . "\n -->";
}

$landkreiseProEinwohner = array();

while ($row = mysqli_fetch_assoc($result)) {
	$landkreis = new Landkreise();
	$landkreis->name = $row["Landkreis"];
	$landkreis->genesen = $row["Genesen"];
	$landkreis->infiziert =  $row["Infiziert"];
	$landkreis->nochInfiziert =  $row["NochInfiziert"];
	$landkreis->verstorben =  $row["Tot"];
	$landkreiseProEinwohner[] = $landkreis;
}
$result->free_result();

$landkreiseProEinwohnerLabels = "labels: [";
foreach ($landkreiseProEinwohner as $value) {
	$landkreiseProEinwohnerLabels .= '\'';
	$landkreiseProEinwohnerLabels .= $value->name;
	$landkreiseProEinwohnerLabels .= '\',';
}
$landkreiseProEinwohnerLabels = substr($landkreiseProEinwohnerLabels, 0, -1) . "],";


$nav = "<p><a href=\"?land=8\">Baden-Württemberg</a>&nbsp;&middot;&nbsp;
<a href=\"?land=9\">Bayern</a>&nbsp;&middot;&nbsp;
<a href=\"?land=11\">Berlin</a>&nbsp;&middot;&nbsp;
<a href=\"?land=12\">Brandenburg</a>&nbsp;&middot;&nbsp;
<a href=\"?land=4\">Bremen</a>&nbsp;&middot;&nbsp;<a href=\"?land=2\">Hamburg</a>&nbsp;&middot;&nbsp;<a href=\"?land=6\">Hessen</a>&nbsp;&middot;&nbsp;<a href=\"?land=13\">Mecklenburg-Vorpommern</a>&nbsp;&middot;&nbsp;<a href=\"?land=3\">Niedersachsen</a>&nbsp;&middot;&nbsp;<a href=\"?land=5\">Nordrhein-Westfalen</a>&nbsp;&middot;&nbsp;<a href=\"?land=7\">Rheinland-Pfalz</a>&nbsp;&middot;&nbsp;<a href=\"?land=10\">Saarland</a>&nbsp;&middot;&nbsp;<a href=\"?land=14\">Sachsen</a>&nbsp;&middot;&nbsp;<a href=\"?land=15\">Sachsen-Anhalt</a>&nbsp;&middot;&nbsp;<a href=\"?land=1\">Schleswig-Holstein</a>&nbsp;&middot;&nbsp;<a href=\"?land=16\">Thüringen</a></p><p><a href=\"#datum\">Datum</a>&nbsp;&middot;&nbsp;<a href=\"#kreise\">Kreise</a>&nbsp;&middot;&nbsp;<a href=\"#kreiseProEinwohner\">Kreise pro Einwohner</a></p>";

?>

<!doctype html>
<html>

<head>
	<title>SARS-CoV-2 infektionen in SH</title>
	<script src="lib/Chart.min.js"></script>
	<script src="lib/utils.js"></script>
	<style>
		canvas {
			-moz-user-select: none;
			-webkit-user-select: none;
			-ms-user-select: none;
		}
	</style>
</head>

<body>

	<div style="width: 100%">
		<?php echo $nav; ?>
		<canvas id="kreise"></canvas>
		<?php echo $nav; ?>
		<canvas id="kreiseProEinwohner"></canvas>
		<?php echo $nav; ?>
		<canvas id="datum"></canvas>
	</div>
	<script>
		var barChartData = {
			<?php echo $labels; ?>

			datasets: [{
				label: 'Verstorben',
				backgroundColor: window.chartColors.black,
				stack: 'Stack 0',
				data: [<?php
						foreach ($landkreise as $value) {
							echo $value->verstorben . ",";
						}
						?>]
			}, {
				label: 'Infiziert',
				backgroundColor: window.chartColors.red,
				stack: 'Stack 0',
				data: [<?php
						foreach ($landkreise as $value) {
							echo $value->nochInfiziert . ",";
						}
						?>]
			}, {
				label: 'Genesen',
				backgroundColor: window.chartColors.blue,
				stack: 'Stack 0',
				data: [
					<?php
					foreach ($landkreise as $value) {
						echo $value->genesen . ",";
					}
					?>
				]
			}]
		};


		var barChartData2 = {
			<?php echo $Datumlabels; ?>

			datasets: [{
				label: 'Verstorben',
				backgroundColor: window.chartColors.black,
				stack: 'Stack 0',
				data: [<?php
						foreach ($datums as $value) {
							echo $value->verstorben . ",";
						}
						?>]
			}, {
				label: 'Infiziert',
				backgroundColor: window.chartColors.red,
				stack: 'Stack 0',
				data: [<?php
						foreach ($datums as $value) {
							echo $value->nochInfiziert . ",";
						}
						?>]
			}, {
				label: 'Genesen',
				backgroundColor: window.chartColors.blue,
				stack: 'Stack 0',
				data: [
					<?php
					foreach ($datums as $value) {
						echo $value->genesen . ",";
					}
					?>
				]
			}]
		};


		var barChartData3 = {
			<?php echo $landkreiseProEinwohnerLabels; ?>

			datasets: [{
				label: 'Verstorben',
				backgroundColor: window.chartColors.black,
				stack: 'Stack 0',
				data: [<?php
						foreach ($landkreiseProEinwohner as $value) {
							echo $value->verstorben . ",";
						}
						?>]
			}, {
				label: 'Infiziert',
				backgroundColor: window.chartColors.red,
				stack: 'Stack 0',
				data: [<?php
						foreach ($landkreiseProEinwohner as $value) {
							echo $value->nochInfiziert . ",";
						}
						?>]
			}, {
				label: 'Genesen',
				backgroundColor: window.chartColors.blue,
				stack: 'Stack 0',
				data: [
					<?php
					foreach ($landkreiseProEinwohner as $value) {
						echo $value->genesen . ",";
					}
					?>
				]
			}]
		};



		window.onload = function() {
			/* Grafik 1*/

			var ctx = document.getElementById('kreise').getContext('2d');
			window.myBar = new Chart(ctx, {
				type: 'bar',
				data: barChartData,
				options: {
					title: {
						display: true,
						text: 'SARS-CoV-2 infektionen <?php echo Bundesland::getNameIn($_GET['land']); ?>'
					},
					tooltips: {
						mode: 'index',
						intersect: false,
						callbacks: {
							label: function(tooltipItem, data) {
								var tooltipValue = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
								var tooltipLabel = data.datasets[tooltipItem.datasetIndex].label;
								var tooltipResult = tooltipValue.toLocaleString() +": "+tooltipLabel;

								// Loop through all datasets to get the actual total of the index
								var total = 0;
								for (var i = 0; i < data.datasets.length; i++)
									total += data.datasets[i].data[tooltipItem.index];

								if (tooltipItem.datasetIndex != data.datasets.length - 1) {
									return tooltipResult;
								} else { // .. else, you display the dataset and the total, using an array
									return [tooltipResult, Math.round(total)+": Summe"];
								}
							}
						}
					},
					responsive: true,
					scales: {
						xAxes: [{
							stacked: true,
						}],
						yAxes: [{
							stacked: true,
							ticks: {
								beginAtZero: true
							}
						}]
					}
				}
			});
			/* Grafik 2*/

			var cty = document.getElementById('datum').getContext('2d');
			window.myBar = new Chart(cty, {
				type: 'bar',
				data: barChartData2,
				options: {
					title: {
						display: true,
						text: 'SARS-CoV-2 infektionen <?php echo Bundesland::getNameIn($_GET['land']); ?>'
					},
					tooltips: {
						mode: 'index',
						intersect: false,
						callbacks: {
							label: function(tooltipItem, data) {
								var tooltipValue = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
								var tooltipLabel = data.datasets[tooltipItem.datasetIndex].label;
								var tooltipResult = tooltipValue.toLocaleString() +": "+tooltipLabel;

								// Loop through all datasets to get the actual total of the index
								var total = 0;
								for (var i = 0; i < data.datasets.length; i++)
									total += data.datasets[i].data[tooltipItem.index];

								if (tooltipItem.datasetIndex != data.datasets.length - 1) {
									return tooltipResult;
								} else { // .. else, you display the dataset and the total, using an array
									return [tooltipResult, Math.round(total)+": Summe"];
								}
							}
						}
					},
					responsive: true,
					scales: {
						xAxes: [{
							stacked: true,
						}],
						yAxes: [{
							stacked: true
						}]
					}
				}
			});


			/* Grafik 3*/
			var cty = document.getElementById('kreiseProEinwohner').getContext('2d');
			window.myBar = new Chart(cty, {
				type: 'bar',
				data: barChartData3,
				options: {
					title: {
						display: true,
						text: 'SARS-CoV-2 infektionen <?php echo Bundesland::getNameIn($_GET['land']); ?> pro <?php echo number_format($proEinwohner,  0, ",", "."); ?> Einwohner'
					},
					tooltips: {
						mode: 'index',
						intersect: false,
						callbacks: {
							label: function(tooltipItem, data) {
								var tooltipValue = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
								var tooltipLabel = data.datasets[tooltipItem.datasetIndex].label;
								var tooltipResult = tooltipValue.toLocaleString() + ": " + tooltipLabel + " pro <?php echo number_format($proEinwohner,  0, ",", "."); ?> Einwohner";

								// Loop through all datasets to get the actual total of the index
								var total = 0;
								for (var i = 0; i < data.datasets.length; i++)
									total += data.datasets[i].data[tooltipItem.index];

								if (tooltipItem.datasetIndex != data.datasets.length - 1) {
									return tooltipResult;
								} else { // .. else, you display the dataset and the total, using an array
									return [tooltipResult, (Math.round(total * 100) / 100) + ": Summe pro <?php echo number_format($proEinwohner,  0, ",", "."); ?>: "];
								}
							}
						}
					},
					responsive: true,
					scales: {
						xAxes: [{
							stacked: true,
						}],
						yAxes: [{
							stacked: true,
							ticks: {
								beginAtZero: true,
								userCallback: function(value, index, values) {
									return value.toLocaleString('de-DE'); // this is all we need
								}
							}
						}]
					}
				}
			});

		};
	</script>
</body>

<?php
mysqli_close($link);
?>

</html>